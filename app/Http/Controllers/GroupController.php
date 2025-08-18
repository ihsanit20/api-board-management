<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check if zamat_id is provided in the request
        $zamatId = $request->input('zamat_id');

        // Retrieve groups, filtering by zamat_id if provided
        $query = Group::with([
            'zamat:id,name',
            'areas:id,name,area_code',
        ]);

        if ($zamatId) {
            $query->where('zamat_id', $zamatId);
        }

        $groups = $query
            ->orderBy('zamat_id')
            ->orderBy('name')
            ->get();

        return response()->json($groups);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'zamat_id' => 'required|exists:zamats,id',
            'name' => 'required|string|max:255',
        ]);

        $group = Group::create($request->all());

        return response()->json($group, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $group = Group::findOrFail($id);
        return response()->json($group);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'zamat_id' => 'required|exists:zamats,id',
            'name' => 'required|string|max:255',
        ]);

        $group = Group::findOrFail($id);
        $group->update($request->all());

        return response()->json($group);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        $group->delete();

        return response()->json(null, 204);
    }
}
