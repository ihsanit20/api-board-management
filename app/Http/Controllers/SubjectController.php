<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with('zamat')->get(); // Related Zamat data
        return response()->json($subjects);
    }

    public function store(Request $request)
    {
        $request->validate([
            'zamat_id' => 'required|exists:zamats,id',
            'name' => 'required|string',
        ]);

        $subject = Subject::create([
            'zamat_id' => $request->zamat_id,
            'name' => $request->name,
        ]);

        return response()->json(['message' => 'Subject created successfully', 'subject' => $subject], 201);
    }

    public function show($id)
    {
        $subject = Subject::with('zamat')->findOrFail($id);
        return response()->json($subject);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $request->validate([
            'zamat_id' => 'exists:zamats,id',
            'name' => 'string',
        ]);

        $subject->update($request->only(['zamat_id', 'name']));

        return response()->json(['message' => 'Subject updated successfully', 'subject' => $subject]);
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return response()->json(['message' => 'Subject deleted successfully']);
    }
}

