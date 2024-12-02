<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Examiner;

class ExaminerController extends Controller
{
    public function index()
    {
        $examiners = Examiner::with([
            'institute:id,name,institute_code',
            'center:id,name',   
            'exam:id,name',     
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
            'education.*.exam' => 'nullable|string|max:255',
            'education.*.passing_year' => 'nullable|integer|min:1300|max:' . date('Y'),
            'education.*.result' => 'nullable|string|max:50',
            'education.*.institute' => 'nullable|string|max:255',
            'education.*.board' => 'nullable|string|max:255',
            'experience' => 'nullable|array', 
            'experience.duration' => 'nullable|string|max:255',
            'experience.books' => 'nullable|string|max:255',
            'ex_experience' => 'nullable|string|max:255',
            'student_count' => 'nullable|string|max:255',
            'institute_id' => 'required|exists:institutes,id',
            'type' => 'required|in:examiner,guard',
            'designation' => 'nullable|string|max:255',
            'exam_id' => 'required|exists:exams,id',
            'center_id' => 'nullable|exists:institutes,id',
            'status' => 'required|in:active,pending,rejected',
        ]);

        $validatedData['education'] = !empty($validatedData['education']) 
            ? json_encode($validatedData['education']) 
            : json_encode([]);

        $examiner = Examiner::create($validatedData);

        $examinerCode = str_pad($examiner->exam_id, 2, '0', STR_PAD_LEFT) . 
                        str_pad($examiner->id, 3, '0', STR_PAD_LEFT);

        $examiner->update(['examiner_code' => $examinerCode]);

        return response()->json([
            'message' => 'Examiner created successfully',
            'examiner' => $examiner
        ], 201);
    }

    public function publicStore(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'nid' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'education' => 'nullable|array', 
            'education.*.exam' => 'nullable|string|max:255',
            'education.*.passing_year' => 'nullable|integer|min:1300|max:' . date('Y'),
            'education.*.result' => 'nullable|string|max:50',
            'education.*.institute' => 'nullable|string|max:255',
            'education.*.board' => 'nullable|string|max:255',
            'experience' => 'nullable|array', 
            'experience.duration' => 'nullable|string|max:255',
            'experience.books' => 'nullable|string|max:255',
            'ex_experience' => 'nullable|string|max:255',
            'student_count' => 'nullable|string|max:255',
            'institute_id' => 'required|exists:institutes,id',
            'type' => 'required|in:examiner,guard',
            'designation' => 'nullable|string|max:255',
            'exam_id' => 'required|exists:exams,id',
            'center_id' => 'nullable|exists:institutes,id',
            'status' => 'required|in:active,pending,rejected',
        ]);

        $validatedData['education'] = !empty($validatedData['education']) 
            ? json_encode($validatedData['education']) 
            : json_encode([]);

        $examiner = Examiner::create($validatedData);

        $examinerCode = str_pad($examiner->exam_id, 2, '0', STR_PAD_LEFT) . 
                        str_pad($examiner->id, 3, '0', STR_PAD_LEFT);

        $examiner->update(['examiner_code' => $examinerCode]);

        if (!empty($examiner->phone)) {
            $message = "আপনার আবেদন সফল হয়েছে।\nনাম: {$examiner->name},\nফোন: {$examiner->phone},\nকোড: {$examiner->examiner_code}\n-তানযীম পরীক্ষা নিয়ন্ত্রণ বিভাগ";

            $this->sendSmsWithStore($message, $examiner->phone, "Examiner");
        }

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

    public function search(Request $request)
    {
        $validatedData = $request->validate([
            'examiner_code' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
        ]);

        $examiner = Examiner::with([
            'institute:id,name,institute_code',
            'center:id,name',   
            'exam:id,name',     
        ])
            ->where('examiner_code', $validatedData['examiner_code'])
            ->where('phone', $validatedData['phone'])
            ->first();

        if (!$examiner) {
            return response()->json(['message' => 'Examiner not found'], 404);
        }

        return response()->json($examiner);
    }


    public function update(Request $request, string $id)
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
            'experience' => 'nullable|array', 
            'experience.duration' => 'nullable|string|max:255',
            'experience.books' => 'nullable|string|max:255',
            'ex_experience' => 'nullable|string|max:255',
            'institute_id' => 'required|exists:institutes,id',
            'student_count' => 'nullable|string|max:255',
            'type' => 'required|in:examiner,guard',
            'designation' => 'nullable|string|max:255',
            'exam_id' => 'required|exists:exams,id',
            'center_id' => 'nullable|exists:institutes,id',
            'status' => 'required|in:active,pending,rejected',
        ]);

        // Null check and convert to JSON
        $validatedData['education'] = !empty($validatedData['education']) 
            ? json_encode($validatedData['education']) 
            : json_encode([]);

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