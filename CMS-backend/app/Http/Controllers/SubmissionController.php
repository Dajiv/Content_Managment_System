<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Submission::with([
            'student:id,name,email',
            'assignment:id,title,teacher_id,due_date',
        ]);

        if ($request->user()->role === 'student') {
            $query->where('student_id', $request->user()->id);
        } elseif ($request->user()->role === 'teacher') {
            $query->whereHas('assignment', function ($assignment) use ($request) {
                $assignment->where('teacher_id', $request->user()->id);
            });
        }

        return response()->json(['data' => $query->latest('submitted_at')->get()]);
    }

    public function show(Request $request, Submission $submission)
    {
        if (!$this->canReview($request, $submission)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $submission->load([
                'student:id,name,email',
                'assignment:id,title,teacher_id,due_date',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assignment_id' => ['required', 'integer', 'exists:assignments,id'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,txt,zip', 'max:10240'],
        ]);

        $assignment = Assignment::findOrFail($validated['assignment_id']);

        if ($assignment->due_date->isPast()) {
            return response()->json(['message' => 'The assignment due date has passed'], 422);
        }

        $path = $request->file('file')->store('submissions', 'private');

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $request->user()->id,
            'file_path' => $path,
            'submitted_at' => now(),
        ]);

        return response()->json(['message' => 'Submission uploaded', 'data' => $submission], 201);
    }

    public function update(Request $request, Submission $submission)
    {
        if (!$this->canReview($request, $submission)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'grade' => ['nullable', 'string', 'max:10'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update($validated);

        return response()->json([
            'message' => 'Submission graded',
            'data' => $submission->fresh(['student:id,name,email', 'assignment']),
        ]);
    }

    public function download(Request $request, Submission $submission)
    {
        if (!$this->canReview($request, $submission)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!Storage::disk('private')->exists($submission->file_path)) {
            return response()->json(['message' => 'Submission file not found'], 404);
        }

        return Storage::disk('private')->download($submission->file_path);
    }

    private function canReview(Request $request, Submission $submission): bool
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'student') {
            return $submission->student_id === $user->id;
        }

        return $user->role === 'teacher'
            && $submission->assignment
            && $submission->assignment->teacher_id === $user->id;
    }
}
