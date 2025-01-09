<?php

namespace App\Http\Controllers;

use App\Models\ParaGroup;
use Illuminate\Http\Request;

class ParaGroupController extends Controller
{

    public function index(Request $request)
    {
        $zamatId = $request->query('zamat_id');

        if ($zamatId) {
            $paraGroups = ParaGroup::where('zamat_id', $zamatId)->get();
        } else {
            $paraGroups = ParaGroup::all();
        }

        return response()->json($paraGroups);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'zamat_id' => 'required|exists:zamats,id',
            'name' => 'required|string|max:255',
        ]);

        $paraGroup = ParaGroup::create($validatedData);

        return response()->json([
            'message' => 'Para Group created successfully.',
            'para_group' => $paraGroup,
        ], 201);
    }

    public function show($id)
    {
        $paraGroup = ParaGroup::find($id);

        if (!$paraGroup) {
            return response()->json(['error' => 'Para Group not found'], 404);
        }

        return response()->json($paraGroup);
    }

    public function update(Request $request, $id)
    {
        $paraGroup = ParaGroup::find($id);

        if (!$paraGroup) {
            return response()->json(['error' => 'Para Group not found'], 404);
        }

        $validatedData = $request->validate([
            'zamat_id' => 'sometimes|required|exists:zamats,id',
            'name' => 'sometimes|required|string|max:255',
        ]);

        $paraGroup->update($validatedData);

        return response()->json([
            'message' => 'Para Group updated successfully.',
            'para_group' => $paraGroup,
        ]);
    }

    public function destroy($id)
    {
        $paraGroup = ParaGroup::find($id);

        if (!$paraGroup) {
            return response()->json(['error' => 'Para Group not found'], 404);
        }

        $paraGroup->delete();

        return response()->json(['message' => 'Para Group deleted successfully.']);
    }
}
