<?php

namespace App\Http\Controllers;

use App\Models\Center;
use Illuminate\Http\Request;

class CenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Retrieve all centers with related models
        $query = Center::query()
            ->with([
                'institute',
                'zamat',
                'group',
            ]);

        if ($request->zamat_id) {
            $query->where('zamat_id', $request->zamat_id);
        }

        if ($request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->gender) {
            $query->where('gender', $request->gender);
        }

        $centers = $query->get();

        return response()->json($centers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'institute_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
            'group_id' => 'nullable|exists:groups,id',
            'gender' => 'nullable|in:male,female',
        ]);

        // Create a new center
        $center = Center::create($validatedData);

        return response()->json($center, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Find the center by ID
        $center = Center::with(['institute', 'zamat', 'group'])->findOrFail($id);

        return response()->json($center);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request
        $validatedData = $request->validate([
            'institute_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
            'group_id' => 'nullable|exists:groups,id',
            'gender' => 'nullable|in:male,female',
        ]);

        // Find the center by ID and update it
        $center = Center::findOrFail($id);
        $center->update($validatedData);

        return response()->json($center);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $center = Center::findOrFail($id);
        $center->delete();

        return response()->json(null, 204);
    }
}
