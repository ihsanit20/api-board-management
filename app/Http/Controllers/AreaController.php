<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Retrieve all areas
        $areas = Area::all();
        return response()->json($areas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        // Create a new area
        $area = Area::create($validatedData);

        return response()->json($area, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Find the area by ID
        $area = Area::findOrFail($id);

        return response()->json($area);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'string|max:255'
        ]);

        // Find the area by ID and update it
        $area = Area::findOrFail($id);
        $area->update($validatedData);

        return response()->json($area);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return response()->json(null, 204);
    }
}
