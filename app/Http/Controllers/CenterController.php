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
        $request->validate([
            'institute_ids' => 'array|required',
            'zamat_id' => 'required|exists:zamats,id',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        $institute_ids = array_unique($request->institute_ids); // Unique institute IDs

        // Fetch previously stored institute IDs for the given zamat and group
        $previous_institute_ids = Center::query()
            ->where([
                'zamat_id' => $request->zamat_id,
                'group_id' => $request->group_id,
            ])
            ->pluck('institute_id')
            ->toArray();

        // Filter out institute IDs that already exist
        $new_institute_ids = array_diff($institute_ids, $previous_institute_ids);

        // Prepare data for bulk insert
        $centers_data = [];
        foreach ($new_institute_ids as $institute_id) {
            $centers_data[] = [
                'zamat_id' => $request->zamat_id,
                'group_id' => $request->group_id,
                'institute_id' => $institute_id,
                'created_at' => now(), // Make sure to add timestamps if your model uses them
                'updated_at' => now(),
            ];
        }

        // Bulk insert new centers
        if (!empty($centers_data)) {
            Center::insert($centers_data); // Bulk insert
            return response()->json(['message' => 'Centers created successfully.'], 201);
        }

        return response()->json(['message' => 'No new centers created.'], 200);
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
