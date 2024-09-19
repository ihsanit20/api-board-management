<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Examiner;

class ExaminerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all examiners
        $examiners = Examiner::all();
        return response()->json($examiners);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'institute' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:15',
        ]);

        // Create a new examiner
        $examiner = Examiner::create($validatedData);

        return response()->json([
            'message' => 'Examiner created successfully',
            'examiner' => $examiner
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Find examiner by ID
        $examiner = Examiner::findOrFail($id);
        return response()->json($examiner);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'institute' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:15',
        ]);

        // Find and update examiner
        $examiner = Examiner::findOrFail($id);
        $examiner->update($validatedData);

        return response()->json([
            'message' => 'Examiner updated successfully',
            'examiner' => $examiner
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find and delete examiner
        $examiner = Examiner::findOrFail($id);
        $examiner->delete();

        return response()->json([
            'message' => 'Examiner deleted successfully'
        ]);
    }
}
