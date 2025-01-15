<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoticeController extends Controller
{
    public function index()
    {
        return Notice::latest()->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'publish_date' => 'required|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('notices', 'public');
            $data['file_path'] = $filePath;
        }

        $notice = Notice::create($data);

        return response()->json($notice, 201);
    }


    public function show($id)
    {
        return Notice::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'publish_date' => 'sometimes|required|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('notices', 'public');
            if ($notice->file_path) {
                Storage::disk('public')->delete($notice->file_path);
            }

            $data['file_path'] = $filePath;
        }

        $notice->update($data);

        return response()->json($notice, 200);
    }


    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();

        return response()->json(null, 204);
    }

    public function downloadFile($id)
    {
        $notice = Notice::findOrFail($id);

        if ($notice->file_path) {
            return response()->download(storage_path('app/public/' . $notice->file_path));
        }

        return response()->json(['message' => 'File not found'], 404);
    }
}
