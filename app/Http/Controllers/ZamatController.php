<?php

namespace App\Http\Controllers;

use App\Models\Zamat;
use Illuminate\Http\Request;

class ZamatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Retrieve all zamats
        $zamats = Zamat::with('department')->get();
        return response()->json($zamats);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'department_id' => 'required|exists:departments,id',
        ]);

        // Create a new zamat
        $zamat = Zamat::create($validatedData);

        return response()->json($zamat, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Find the zamat by ID
        $zamat = Zamat::findOrFail($id);

        return response()->json($zamat);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'is_active' => 'boolean',
            'department_id' => 'required|exists:departments,id',
        ]);

        // Find the zamat by ID and update it
        $zamat = Zamat::findOrFail($id);
        $zamat->update($validatedData);

        return response()->json($zamat);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $zamat = Zamat::findOrFail($id);
        $zamat->delete();

        return response()->json(null, 204);
    }
}
