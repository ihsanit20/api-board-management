<?php

namespace App\Http\Controllers;

use App\Models\Institute;
use App\Models\LetterDistributionCenter;
use Illuminate\Http\Request;

class LetterDistributionCenterController extends Controller
{

    public function index()
    {
        $centers = LetterDistributionCenter::with([
            'area:id,name',
            'institute:id,name,institute_code'
        ])->get();

        return response()->json($centers);
    }

    public function searchByInstituteCode(Request $request)
    {
        $request->validate([
            'institute_code' => 'required|string',
        ]);

        $institute = Institute::where('institute_code', $request->institute_code)->first();

        if (!$institute) {
            return response()->json(['message' => 'Institute not found'], 404);
        }

        $center = LetterDistributionCenter::with(['area:id,name', 'institute:id,name,institute_code,phone'])
            ->where('institute_id', $institute->id)
            ->first();

        if (!$center) {
            return response()->json(['message' => 'Center not found'], 404);
        }
        $instituteIds = json_decode($center->institute_ids, true);

        $institutes = Institute::whereIn('id', $instituteIds)
            ->select('id', 'institute_code', 'name', 'phone')
            ->get();

        $responseData = [
            'center' => $center,
            'institutes' => $institutes,
        ];

        return response()->json($responseData);
    }

    public function store(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'institute_id' => 'required|exists:institutes,id',
            'name' => 'required|unique:letter_distribution_centers,name',
            'institute_ids' => 'nullable|array',
            'institute_ids.*' => 'exists:institutes,id',
            'person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);
    
        $center = LetterDistributionCenter::create([
            'area_id' => $request->area_id,
            'institute_id' => $request->institute_id,
            'name' => $request->name,
            'institute_ids' => json_encode($request->institute_ids),
            'person' => $request->person,
            'phone' => $request->phone,
        ]);
    
        return response()->json($center, 201);
    }
    
    

    public function show($id)
    {
        $center = LetterDistributionCenter::findOrFail($id);
        return response()->json($center);
    }


    public function update(Request $request, $id)
    {
        $center = LetterDistributionCenter::findOrFail($id);
    
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'institute_id' => 'required|exists:institutes,id',
            'name' => 'required|unique:letter_distribution_centers,name,' . $id,
            'institute_ids' => 'nullable|array',
            'institute_ids.*' => 'exists:institutes,id',
            'person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);
    
        $center->update([
            'area_id' => $request->area_id,
            'institute_id' => $request->institute_id,
            'name' => $request->name,
            'institute_ids' => json_encode($request->institute_ids),
            'person' => $request->person,
            'phone' => $request->phone,
        ]);
    
        return response()->json($center);
    }
    


    public function destroy($id)
    {
        $center = LetterDistributionCenter::findOrFail($id);
        $center->delete();

        return response()->json(['message' => 'Center deleted successfully']);
    }
}
