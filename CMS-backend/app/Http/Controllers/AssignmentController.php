<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Assignment::with('teacher:id,name,email');

        if ($request->user()->role === 'teacher') {
            $query->where('teacher_id', $request->user()->id);
        }

        return response()->json(['data' => $query->latest()->get()]);
    }

    public function show(Assignment $assignment)
    {
        return response()->json(['data' => $assignment->load(['teacher:id,name,email'])]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'teacher_id' => ['sometimes', 'integer', 'exists:users,id'],
        ]);

        if ($request->user()->role === 'teacher') {
            $validated['teacher_id'] = $request->user()->id;
        } elseif (!isset($validated['teacher_id'])) {
            $validated['teacher_id'] = $request->user()->id;
        }

        $assignment = Assignment::create($validated);

        return response()->json(['message' => 'Assignment created', 'data' => $assignment], 201);
    }

    public function update(Request $request, Assignment $assignment)
    {
        if ($request->user()->role === 'teacher' && $assignment->teacher_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['sometimes', 'required', 'date'],
        ]);

        $assignment->update($validated);

        return response()->json(['message' => 'Assignment updated', 'data' => $assignment->fresh()]);
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        if ($request->user()->role === 'teacher' && $assignment->teacher_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($assignment->submissions()->exists()) {
            return response()->json(['message' => 'Assignment has submissions and cannot be deleted'], 422);
        }

        $assignment->delete();

        return response()->json(['message' => 'Assignment deleted']);
    }
}
