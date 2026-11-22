<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Content;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CmsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_login_user_and_logout_work(): void
    {
        $register = $this->postJson('/api/register', [
            'name' => 'Student One',
            'email' => 'student@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ]);

        $register->assertCreated()
            ->assertJsonPath('user.role', 'student')
            ->assertJsonStructure(['token', 'user']);

        $login = $this->postJson('/api/login', [
            'email' => 'student@example.com',
            'password' => 'StrongPass1!',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'user']);

        Sanctum::actingAs(User::where('email', 'student@example.com')->firstOrFail());

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('role', 'student');

        $this->postJson('/api/logout')
            ->assertOk();
    }

    public function test_password_reset_link_and_reset_work(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'role' => 'student',
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertOk();

        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'NewStrong1!',
            'password_confirmation' => 'NewStrong1!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewStrong1!', $user->fresh()->password));
    }

    public function test_admin_user_management_and_role_authorization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->postJson('/api/users', [
            'name' => 'Teacher One',
            'email' => 'teacher@example.com',
            'password' => 'StrongPass1!',
            'role' => 'teacher',
        ])->assertCreated();

        Sanctum::actingAs($student);

        $this->getJson('/api/users')->assertForbidden();
    }

    public function test_teacher_assignment_creation_and_student_submission_and_grading(): void
    {
        Storage::fake('private');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        Sanctum::actingAs($teacher);
        $assignmentResponse = $this->postJson('/api/assignments', [
            'title' => 'Laravel Security',
            'description' => 'Complete the exercise',
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $assignmentResponse->assertCreated();
        $assignmentId = $assignmentResponse->json('data.id');

        Sanctum::actingAs($student);
        $submissionResponse = $this->postJson('/api/submissions', [
            'assignment_id' => $assignmentId,
            'file' => UploadedFile::fake()->create('submission.pdf', 200, 'application/pdf'),
        ]);

        $submissionResponse->assertCreated();
        $submissionId = $submissionResponse->json('data.id');

        $this->assertTrue(Storage::disk('private')->exists(
            Submission::findOrFail($submissionId)->file_path
        ));

        Sanctum::actingAs($teacher);
        $this->putJson("/api/submissions/$submissionId", [
            'grade' => 'A',
            'feedback' => 'Excellent work.',
        ])->assertOk()
            ->assertJsonPath('data.grade', 'A');

        $this->get("/api/submissions/$submissionId/download")
            ->assertOk();

        Sanctum::actingAs($student);
        $this->getJson('/api/submissions')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/submissions/$submissionId")
            ->assertOk();
    }

    public function test_content_visibility_and_teacher_ownership(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);

        $published = Content::create([
            'title' => 'Published Content',
            'body' => 'Visible',
            'category' => 'General',
            'author_id' => $admin->id,
            'status' => 'published',
        ]);

        $draft = Content::create([
            'title' => 'Draft Content',
            'body' => 'Hidden',
            'category' => 'General',
            'author_id' => $teacher->id,
            'status' => 'draft',
        ]);

        Sanctum::actingAs($student);
        $this->getJson('/api/contents')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/contents/$draft->id")
            ->assertForbidden();

        Sanctum::actingAs($teacher);
        $this->getJson("/api/contents/$draft->id")
            ->assertOk();

        $this->getJson("/api/contents/$published->id")
            ->assertOk();
    }

    public function test_dashboard_returns_role_specific_stats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonStructure(['stats' => [
                'users',
                'teachers',
                'students',
                'assignments',
                'submissions',
                'contents',
                'published_contents',
            ]]);
    }
}
