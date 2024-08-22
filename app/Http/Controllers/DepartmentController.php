<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    // Get a list of departments (index)
    public function index()
    {
        return Department::all();
    }

    // Get a specific department (show)
    public function show($id)
    {
        return Department::findOrFail($id);
    }

    // Store a new department (store)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        return Department::create($request->all());
    }

    // Update an existing department (update)
    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department->update($request->all());

        return $department;
    }

    // Delete a department (destroy)
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->noContent();
    }
}
