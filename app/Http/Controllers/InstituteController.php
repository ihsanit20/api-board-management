<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Institute;
use Illuminate\Http\Request;

class InstituteController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Institute::query()
            ->with('area')
            ->oldest('institute_code');
    
        if ($request->has('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }
    
        if ($request->has('is_center')) {
            $query->where('is_center', $request->input('is_center'));
        }
    
        if ($request->has('is_active')) {
            $query->where('is_active', $request->input('is_active'));
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

    public function getInstitutesByArea(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
        ]);

        $institutes = Institute::where('area_id', $request->input('area_id'))
            ->select('id','name', 'institute_code', 'phone')
            ->oldest('institute_code')
            ->get();

        return response()->json($institutes);
    }


    public function instituteCounts()
    {
        $totalInstitutesCount = Institute::count();
        $activeInstitutesCount = Institute::where('is_active', true)->count();
        $centerInstitutesCount = Institute::where('is_center', true)->count();

        return response()->json([
            'totalInstitutes' => $totalInstitutesCount,
            'activeInstitutes' => $activeInstitutesCount,
            'centerInstitutes' => $centerInstitutesCount,
        ]);
    }

    public function institutesWithApplications()
    {
        $institutesWithApplications = Institute::whereHas('applications')->get();
        return response()->json($institutesWithApplications);
    }

    public function institutesWithoutApplications()
    {
        $institutesWithoutApplications = Institute::whereDoesntHave('applications')->get();
        return response()->json($institutesWithoutApplications);
    }

    public function institutesApplicationStatusCounts()
    {
        $institutesWithApplicationsCount = Institute::whereHas('applications')->count();
        $institutesWithoutApplicationsCount = Institute::whereDoesntHave('applications')->count();
    
        return response()->json([
            'withApplications' => $institutesWithApplicationsCount,
            'withoutApplications' => $institutesWithoutApplicationsCount,
        ]);
    }    
   
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'is_active' => 'boolean',
            'is_center' => 'boolean',
        ]);

        $area = Area::findOrFail($request->area_id);

        $maxInstituteCode = Institute::where('area_id', $area->id)->max('institute_code');

        $newInstituteSerial = $maxInstituteCode ? (int)substr($maxInstituteCode, -3) + 1 : 1;

        $newInstituteCode = $area->area_code . str_pad($newInstituteSerial, 3, '0', STR_PAD_LEFT);

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


    public function show(string $id)
    {
        $institute = Institute::findOrFail($id);

        return response()->json($institute);
    }

    public function instituteByCode(string $institute_code)
    {
        $institute = Institute::query()
            ->with([
                'area:id,name,area_code',
            ])
            ->where('institute_code', $institute_code)
            ->firstOrFail();

        return response()->json($institute);
    }

    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'phone' => 'max:255',
            'area_id' => 'exists:areas,id',
            'is_active' => 'boolean',
            'is_center' => 'boolean',
        ]);

        $institute = Institute::findOrFail($id);
        $institute->update($validatedData);

        return response()->json($institute);
    }

    public function destroy($id)
    {
        $institute = Institute::findOrFail($id);
        $institute->delete();

        return response()->json(null, 204);
    }
}
