<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Content;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return response()->json([
                'role' => 'admin',
                'stats' => [
                    'users' => User::count(),
                    'teachers' => User::where('role', 'teacher')->count(),
                    'students' => User::where('role', 'student')->count(),
                    'assignments' => Assignment::count(),
                    'submissions' => Submission::count(),
                    'contents' => Content::count(),
                    'published_contents' => Content::where('status', 'published')->count(),
                ],
            ]);
        }

        if ($user->role === 'teacher') {
            $assignments = Assignment::where('teacher_id', $user->id);

            return response()->json([
                'role' => 'teacher',
                'stats' => [
                    'assignments' => (clone $assignments)->count(),
                    'submissions' => Submission::whereHas('assignment', function ($query) use ($user) {
                        $query->where('teacher_id', $user->id);
                    })->count(),
                    'graded_submissions' => Submission::whereNotNull('grade')
                        ->whereHas('assignment', function ($query) use ($user) {
                            $query->where('teacher_id', $user->id);
                        })->count(),
                    'contents' => Content::where('author_id', $user->id)->count(),
                ],
            ]);
        }

        return response()->json([
            'role' => 'student',
            'stats' => [
                'available_assignments' => Assignment::count(),
                'submitted_assignments' => Submission::where('student_id', $user->id)
                    ->distinct('assignment_id')
                    ->count('assignment_id'),
                'grades' => Submission::where('student_id', $user->id)
                    ->whereNotNull('grade')
                    ->count(),
                'published_contents' => Content::where('status', 'published')->count(),
            ],
        ]);
    }
}
