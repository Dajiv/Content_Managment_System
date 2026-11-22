<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $query = Content::with('author:id,name,email');

        if ($request->user()->role === 'student') {
            $query->where('status', 'published');
        } elseif ($request->user()->role === 'teacher') {
            $query->where(function ($contents) use ($request) {
                $contents->where('status', 'published')
                    ->orWhere('author_id', $request->user()->id);
            });
        }

        return response()->json(['data' => $query->latest()->get()]);
    }

    public function show(Request $request, Content $content)
    {
        if ($request->user()->role === 'student' && $content->status !== 'published') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($request->user()->role === 'teacher'
            && $content->status !== 'published'
            && $content->author_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $content->load('author:id,name,email')]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:draft,published'],
        ]);

        $validated['author_id'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'draft';

        $content = Content::create($validated);

        return response()->json(['message' => 'Content created', 'data' => $content], 201);
    }

    public function update(Request $request, Content $content)
    {
        if ($request->user()->role === 'teacher' && $content->author_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', 'in:draft,published'],
        ]);

        $content->update($validated);

        return response()->json(['message' => 'Content updated', 'data' => $content->fresh()]);
    }

    public function destroy(Request $request, Content $content)
    {
        if ($request->user()->role === 'teacher' && $content->author_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $content->delete();

        return response()->json(['message' => 'Content deleted']);
    }
}
