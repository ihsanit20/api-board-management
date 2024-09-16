<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Institute;
use Illuminate\Http\Request;

class InstituteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Institute::with('area');
    
        if ($request->has('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }
    
        if ($request->has('is_center')) {
            $query->where('is_center', 1);
        }
    
        $perPage = $request->input('per_page', 15); // Default per page is 15
    
        // If 'all' is requested, return the full list
        return $perPage === 'all' 
            ? response()->json([
                'data' => $query->get(),
                'total' => $query->count(),
                'per_page' => $query->count(),
                'current_page' => 1,
                'last_page' => 1,
            ])
            : response()->json($query->paginate($perPage));
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'is_active' => 'boolean',
            'is_center' => 'boolean',
        ]);

        // Find the area based on area_id
        $area = Area::findOrFail($request->area_id);

        // Calculate the new institute serial and institute code
        $maxInstituteCode = Institute::where('area_id', $area->id)->max('institute_code');
        $newInstituteSerial = $maxInstituteCode ? (int)substr($maxInstituteCode, -3) + 1 : 1;
        $newInstituteCode = $area->area_code . str_pad($newInstituteSerial, 3, '0', STR_PAD_LEFT);

        // Create a new institute using validated data
        $institute = Institute::create([
            'name' => $validatedData['name'],
            'phone' => $validatedData['phone'],
            'area_id' => $validatedData['area_id'],
            'institute_code' => $newInstituteCode,
            'is_active' => $validatedData['is_active'] ?? 0,
            'is_center' => $validatedData['is_center'] ?? 0,
        ]);

        return response()->json($institute, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Find the institute by ID
        $institute = Institute::findOrFail($id);

        return response()->json($institute);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'phone' => 'string|max:255',
            'area_id' => 'exists:areas,id',
            'is_active' => 'boolean',
            'is_center' => 'boolean',
        ]);

        // Find the institute by ID and update it
        $institute = Institute::findOrFail($id);
        $institute->update($validatedData);

        return response()->json($institute);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Find the institute by ID and delete it
        $institute = Institute::findOrFail($id);
        $institute->delete();

        return response()->json(null, 204);
    }
}
