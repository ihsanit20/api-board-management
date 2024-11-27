<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Examiner;

class ExaminerController extends Controller
{
    public function index()
    {
        $examiners = Examiner::with([
            'institute:id,name',
            'center:id,name',   
            'exam:id,name'     
        ])->get();

        return response()->json($examiners);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'nid' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'education' => 'nullable|array', 
            'education.*.exam' => 'required|string|max:255',
            'education.*.passing_year' => 'required|integer|min:1900|max:' . date('Y'),
            'education.*.result' => 'required|string|max:50',
            'education.*.institute' => 'required|string|max:255',
            'education.*.board' => 'required|string|max:255',
            'institute_id' => 'required|exists:institutes,id',
            'type' => 'required|in:examiner,guard',
            'designation' => 'nullable|string|max:255',
            'exam_id' => 'required|exists:exams,id',
            'center_id' => 'nullable|exists:centers,id',
            'status' => 'required|in:active,pending,rejected',
        ]);

        $validatedData['education'] = json_encode($validatedData['education']); 

        $examiner = Examiner::create($validatedData);

        return response()->json([
            'message' => 'Examiner created successfully',
            'examiner' => $examiner
        ], 201);
    }


    public function show(string $id)
    {
        $examiner = Examiner::with(['institute', 'center', 'exam'])->findOrFail($id);
        return response()->json($examiner);
    }

    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'nid' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'education' => 'nullable|json',
            'institute_id' => 'required|exists:institutes,id',
            'type' => 'required|in:examiner,guard',
            'designation' => 'nullable|string|max:255',
            'exam_id' => 'required|exists:exams,id',
            'center_id' => 'nullable|exists:centers,id',
            'status' => 'required|in:active,pending,rejected',
        ]);

        $examiner = Examiner::findOrFail($id);
        $examiner->update($validatedData);

        return response()->json([
            'message' => 'Examiner updated successfully',
            'examiner' => $examiner
        ]);
    }

    public function destroy(string $id)
    {
        $examiner = Examiner::findOrFail($id);
        $examiner->delete();

        return response()->json([
            'message' => 'Examiner deleted successfully'
        ]);
    }
}