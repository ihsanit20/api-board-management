<?php

namespace App\Http\Controllers;

use App\Models\Institute;
use App\Models\InstituteInfo;
use App\Models\InstituteApplication;
use App\Models\Area; // ✅ added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InstituteApplicationController extends Controller
{
    /* ---------------------------------------------------------
     | পাবলিক সাবমিশন: NEW / UPDATE — দুটোই
     | POST /institute-applications
     ---------------------------------------------------------*/
    public function store(Request $request)
    {
        $data = $request->validate([
            'type'         => ['required', Rule::in(['NEW', 'UPDATE', 'DETAILS_ONLY'])],
            'institute_id' => ['nullable', 'integer', 'exists:institutes,id'],
            'name'         => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'area_id'      => ['nullable', 'integer', 'exists:areas,id'],
            'payload_json' => ['required', 'array'],
        ]);

        if (in_array($data['type'], ['UPDATE', 'DETAILS_ONLY']) && empty($data['institute_id'])) {
            return response()->json(['message' => 'institute_id is required for UPDATE/DETAILS_ONLY'], 422);
        }

        $app = InstituteApplication::create([
            'type'         => $data['type'],
            'institute_id' => $data['institute_id'] ?? null,
            'name'         => $data['name']   ?? null,
            'phone'        => $this->normalizePhone($data['phone'] ?? null),
            'area_id'      => $data['area_id'] ?? null,
            'payload_json' => $data['payload_json'],
            'status'       => InstituteApplication::STATUS_PENDING,
            'reviewed_by'  => null,
            'reviewed_at'  => null,
            'admin_notes'  => null,
        ]);

        return response()->json([
            'id'     => $app->id,
            'status' => $app->status,
        ], 201);
    }

    /* ---------------------------------------------------------
     | পাবলিক: ম্যাচ সাজেশন
     | GET /institute-applications/suggest-matches?q=&area_id=
     ---------------------------------------------------------*/
    public function suggestMatches(Request $request)
    {
        $q       = trim((string) $request->query('q', ''));
        $areaId  = $request->query('area_id');

        $normPhone = $this->normalizePhone($q);
        $query = Institute::query()
            ->when($areaId, fn($qr) => $qr->where('area_id', $areaId))
            ->when($q, function ($qr) use ($q, $normPhone) {
                $qr->where(function ($sub) use ($q, $normPhone) {
                    $sub->where('name', 'like', "%{$q}%");
                    if ($normPhone) {
                        $sub->orWhere('phone', $normPhone);
                        $sub->orWhere('phone', 'like', "%{$q}%");
                    }
                });
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone', 'area_id']);

        return response()->json($query);
    }

    /* ---------------------------------------------------------
     | পাবলিক: UPDATE কেসে ফর্ম প্রিফিল
     | GET /institute-applications/prefill?institute_id=
     ---------------------------------------------------------*/
    public function prefill(Request $request)
    {
        $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
        ]);

        $inst = Institute::findOrFail($request->query('institute_id'));
        $info = InstituteInfo::where('institute_id', $inst->id)->first();

        return response()->json([
            'institute'      => $inst->only(['id', 'name', 'phone', 'area_id']),
            'institute_info' => $info,
        ]);
    }

    /* ---------------------------------------------------------
     | পাবলিক: ট্র্যাক / স্ট্যাটাস দেখা
     | GET /institute-applications/{id}/track
     ---------------------------------------------------------*/
    public function track($id)
    {
        $app = InstituteApplication::findOrFail($id);
        return response()->json([
            'id'          => $app->id,
            'type'        => $app->type,
            'status'      => $app->status,
            'admin_notes' => $app->admin_notes,
            'reviewed_at' => $app->reviewed_at,
        ]);
    }

    /* ---------------------------------------------------------
     | অ্যাডমিন: কিউ লিস্ট
     | GET /admin/institute-applications
     ---------------------------------------------------------*/
    public function index(Request $request)
    {
        $data = $request->validate([
            'status'       => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'needs_info'])],
            'type'         => ['nullable', Rule::in(['NEW', 'UPDATE', 'DETAILS_ONLY'])],
            'q'            => ['nullable', 'string'],
            'area_id'      => ['nullable', 'integer', 'exists:areas,id'],
            'institute_id' => ['nullable', 'integer', 'exists:institutes,id'],
            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $apps = InstituteApplication::query()
            ->when($data['status'] ?? null,  fn($q, $v) => $q->where('status', $v))
            ->when($data['type'] ?? null,    fn($q, $v) => $q->where('type', $v))
            ->when($data['area_id'] ?? null, fn($q, $v) => $q->where('area_id', $v))
            ->when($data['institute_id'] ?? null, fn($q, $v) => $q->where('institute_id', $v))
            ->when($data['q'] ?? null, function ($q) use ($data) {
                $t = trim($data['q']);
                $q->where(function ($sub) use ($t) {
                    $sub->where('name', 'like', "%{$t}%")
                        ->orWhere('phone', 'like', "%{$t}%");
                });
            })
            ->when(($data['date_from'] ?? null) && ($data['date_to'] ?? null), function ($q) use ($data) {
                $q->whereBetween('created_at', [$data['date_from'], $data['date_to']]);
            })
            ->latest('id')
            ->paginate($data['per_page'] ?? 20);

        return response()->json($apps);
    }

    /* ---------------------------------------------------------
     | অ্যাডমিন: একক আবেদন ডিটেইলস
     | GET /admin/institute-applications/{id}
     ---------------------------------------------------------*/
    public function show($id)
    {
        $app = InstituteApplication::with(['institute'])->findOrFail($id);
        $info = $app->institute ? InstituteInfo::where('institute_id', $app->institute_id)->first() : null;

        return response()->json([
            'application'      => $app,
            'current_institute' => $app->institute,
            'current_info'     => $info,
        ]);
    }

    /* ---------------------------------------------------------
     | অ্যাডমিন: ডিফ কম্পিউট
     | GET /admin/institute-applications/{id}/diff
     ---------------------------------------------------------*/
    public function diff($id)
    {
        $app  = InstituteApplication::with('institute')->findOrFail($id);
        $info = $app->institute ? InstituteInfo::where('institute_id', $app->institute_id)->first() : null;

        $diff = $this->computeDiff($app, $app->institute, $info);

        return response()->json($diff);
    }

    /* ---------------------------------------------------------
     | অ্যাডমিন: APPROVE (মার্জ/ক্রিয়েট সহ)
     | POST /admin/institute-applications/{id}/approve
     ---------------------------------------------------------*/
    public function approve(Request $request, $id)
    {
        $app = InstituteApplication::findOrFail($id);

        $validated = $request->validate([
            'merge_plan'    => ['nullable', 'array'],
            'edited_values' => ['nullable', 'array'],
        ]);

        $mergePlan    = $validated['merge_plan']    ?? [];
        $editedValues = $validated['edited_values'] ?? [];

        $reviewerId = method_exists(Auth::class, 'id') ? Auth::id() : null;

        return DB::transaction(function () use ($app, $mergePlan, $editedValues, $reviewerId) {

            if ($app->type === 'NEW') {
                // ---------- NEW: create institute + info ----------
                $inst = new Institute();
                $inst->name      = $editedValues['institute']['name']   ?? $app->name;
                $inst->phone     = $this->normalizePhone($editedValues['institute']['phone'] ?? $app->phone);
                // ✅ area_id লাগবে কোড জেনারেটের আগে
                $inst->area_id   = $editedValues['institute']['area_id'] ?? $app->area_id;

                if (empty($inst->area_id)) {
                    return response()->json([
                        'message' => 'area_id is required to generate institute_code. Provide it in edited_values.institute.area_id or in application.'
                    ], 422);
                }

                $inst->is_active = true;
                $inst->is_center = $editedValues['institute']['is_center'] ?? false;

                // ✅ পুরাতন লজিক অনুযায়ী এরিয়া-ভিত্তিক ছোট কোড
                $inst->institute_code = $this->generateAreaWiseInstituteCode((int)$inst->area_id);
                $inst->save();

                // info create
                $infoData = $this->buildInfoPayload($app->payload_json, $editedValues['institute_info'] ?? []);
                $info = new InstituteInfo();
                $info->institute_id = $inst->id;
                $info->fill($infoData);
                $info->save();

                // app status
                $app->status      = InstituteApplication::STATUS_APPROVED;
                $app->reviewed_by = $reviewerId;
                $app->reviewed_at = now();
                $app->save();

                return response()->json([
                    'message'   => 'Application approved. New institute created.',
                    'institute' => $inst,
                    'info'      => $info,
                ]);
            }

            // ---------- UPDATE / DETAILS_ONLY ----------
            if (!$app->institute_id) {
                return response()->json(['message' => 'institute_id missing for UPDATE/DETAILS_ONLY'], 422);
            }

            $inst = Institute::findOrFail($app->institute_id);
            $info = InstituteInfo::firstOrNew(['institute_id' => $inst->id]);

            // 1) institutes আপডেট (merge plan থাকলে)
            $planInst = $mergePlan['institute'] ?? [];
            if (!empty($planInst)) {
                if (!empty($planInst['name']))    $inst->name    = $editedValues['institute']['name']  ?? $app->name  ?? $inst->name;
                if (!empty($planInst['phone']))   $inst->phone   = $this->normalizePhone($editedValues['institute']['phone'] ?? $app->phone ?? $inst->phone);
                if (!empty($planInst['area_id'])) $inst->area_id = $editedValues['institute']['area_id'] ?? $app->area_id ?? $inst->area_id;
                $inst->save();
            }

            // 2) institute_info create/update
            $payloadInfo = $this->buildInfoPayload($app->payload_json, $editedValues['institute_info'] ?? []);
            $planInfo    = $mergePlan['institute_info'] ?? null;

            if ($planInfo && is_array($planInfo)) {
                $apply = [];
                foreach ($planInfo as $key => $take) {
                    if ($take && array_key_exists($key, $payloadInfo)) {
                        $apply[$key] = $payloadInfo[$key];
                    }
                }
                $info->fill($apply);
            } else {
                $info->fill($payloadInfo);
            }

            $info->institute_id = $inst->id;
            $info->save();

            $app->status      = InstituteApplication::STATUS_APPROVED;
            $app->reviewed_by = $reviewerId;
            $app->reviewed_at = now();
            $app->save();

            return response()->json([
                'message'   => 'Application approved and merged.',
                'institute' => $inst,
                'info'      => $info,
            ]);
        });
    }

    /* ---------------------------------------------------------
     | অ্যাডমিন: REJECT
     | POST /admin/institute-applications/{id}/reject
     ---------------------------------------------------------*/
    public function reject(Request $request, $id)
    {
        $app = InstituteApplication::findOrFail($id);
        $data = $request->validate([
            'admin_notes' => ['required', 'string'],
        ]);

        $app->status      = InstituteApplication::STATUS_REJECTED;
        $app->admin_notes = $data['admin_notes'];
        $app->reviewed_by = method_exists(Auth::class, 'id') ? Auth::id() : null;
        $app->reviewed_at = now();
        $app->save();

        return response()->json(['message' => 'Application rejected.']);
    }

    /* ---------------------------------------------------------
     | অ্যাডমিন: NEEDS INFO
     | POST /admin/institute-applications/{id}/needs-info
     ---------------------------------------------------------*/
    public function needsInfo(Request $request, $id)
    {
        $app = InstituteApplication::findOrFail($id);
        $data = $request->validate([
            'admin_notes' => ['required', 'string'],
        ]);

        $app->status      = InstituteApplication::STATUS_NEEDS_INFO;
        $app->admin_notes = $data['admin_notes'];
        $app->reviewed_by = method_exists(Auth::class, 'id') ? Auth::id() : null;
        $app->reviewed_at = now();
        $app->save();

        return response()->json(['message' => 'Marked as needs info.']);
    }

    /* ---------------------------------------------------------
     | অ্যাডমিন: ভুল NEW → বিদ্যমান ইনস্টিটিউটে অ্যাটাচ
     | POST /admin/institute-applications/{id}/attach
     ---------------------------------------------------------*/
    public function attachToInstitute(Request $request, $id)
    {
        $app = InstituteApplication::findOrFail($id);
        $data = $request->validate([
            'target_institute_id' => ['required', 'integer', 'exists:institutes,id'],
        ]);

        $app->institute_id = $data['target_institute_id'];
        $app->type         = 'UPDATE';
        $app->save();

        return response()->json(['message' => 'Application attached to institute for UPDATE.', 'application' => $app]);
    }

    /* ---------------------------------------------------------
     | অ্যাডমিন: অ্যাপ্লিকেশন মেটা আপডেট
     | PATCH /admin/institute-applications/{id}
     ---------------------------------------------------------*/
    public function update(Request $request, $id)
    {
        $app = InstituteApplication::findOrFail($id);

        $data = $request->validate([
            'name'         => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'area_id'      => ['nullable', 'integer', 'exists:areas,id'],
            'admin_notes'  => ['nullable', 'string'],
            'payload_json' => ['nullable', 'array'],
            'type'         => ['nullable', Rule::in(['NEW', 'UPDATE', 'DETAILS_ONLY'])],
            'status'       => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'needs_info'])],
        ]);

        if (array_key_exists('name', $data))  $app->name  = $data['name'];
        if (array_key_exists('phone', $data)) $app->phone = $this->normalizePhone($data['phone'] ?? null);
        if (array_key_exists('area_id', $data)) $app->area_id = $data['area_id'];
        if (array_key_exists('admin_notes', $data)) $app->admin_notes = $data['admin_notes'];
        if (array_key_exists('payload_json', $data)) $app->payload_json = $data['payload_json'];
        if (array_key_exists('type', $data)) $app->type = $data['type'];
        if (array_key_exists('status', $data)) $app->status = $data['status'];

        $app->save();

        return response()->json(['message' => 'Application updated.', 'application' => $app]);
    }

    /* ---------------------------------------------------------
     | অ্যাডমিন: DESTROY
     | DELETE /admin/institute-applications/{id}
     ---------------------------------------------------------*/
    public function destroy($id)
    {
        $app = InstituteApplication::findOrFail($id);
        $app->delete();

        return response()->json(['message' => 'Application deleted.'], 204);
    }

    /* =========================================================
     | --------------------- Helpers ---------------------------
     =========================================================*/

    // ✅ পুরাতন InstituteController@store() এর মতোই area-wise ছোট কোড
    private function generateAreaWiseInstituteCode(int $areaId): string
    {
        $area = Area::findOrFail($areaId);
        $maxInstituteCode = Institute::where('area_id', $area->id)->max('institute_code');
        $newInstituteSerial = $maxInstituteCode ? (int) substr($maxInstituteCode, -3) + 1 : 1;
        return $area->area_code . str_pad((string) $newInstituteSerial, 3, '0', STR_PAD_LEFT);
    }

    // ফোন নরমালাইজ (খুব লাইট)
    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) === 13 && str_starts_with($digits, '880')) {
            $digits = substr($digits, 2); // "8801XXXXXXXXX" -> "01XXXXXXXXX"
        }
        return $digits ?: null;
    }

    // payload → institute_info fields (+ editedValues প্রয়োগ)
    private function buildInfoPayload(array $payload, array $edited = []): array
    {
        $merged = array_replace_recursive($payload, $edited);

        return [
            'address'                   => $merged['address']                  ?? null,
            'established_on'           => $merged['established_on']           ?? null,
            'founder_name'             => $merged['founder_name']             ?? null,
            'muhtamim'                 => $merged['muhtamim']                 ?? null,
            'upto_class'               => $merged['upto_class']               ?? null,
            'students'                 => $merged['students']                 ?? null,
            'teachers'                 => $merged['teachers']                 ?? null,
            'has_hostel'               => (bool)($merged['has_hostel']               ?? false),
            'land_info'                => $merged['land_info']                ?? null,
            'building_summary'         => $merged['building_summary']         ?? null,
            'has_library_for_students' => (bool)($merged['has_library_for_students'] ?? false),
            'has_kutubkhana'           => (bool)($merged['has_kutubkhana']    ?? false),
            'kutubkhana'               => $merged['kutubkhana']               ?? null,
        ];
    }

    // সিম্পল ডিফ
    private function computeDiff(InstituteApplication $app, ?Institute $inst, ?InstituteInfo $info): array
    {
        $payload = $app->payload_json ?? [];

        $instituteDiff = [];
        if ($inst) {
            if (array_key_exists('name', $payload)  && ($inst->name !== ($app->name ?? $payload['name']))) {
                $instituteDiff['name'] = ['current' => $inst->name, 'proposed' => ($app->name ?? $payload['name'])];
            }
            $pPhone = $this->normalizePhone($app->phone ?? ($payload['phone'] ?? null));
            if ($pPhone && $inst->phone !== $pPhone) {
                $instituteDiff['phone'] = ['current' => $inst->phone, 'proposed' => $pPhone];
            }
            if (array_key_exists('area_id', $app->getAttributes()) || array_key_exists('area_id', $payload)) {
                $propArea = $app->area_id ?? ($payload['area_id'] ?? null);
                if ((int) $inst->area_id !== (int) $propArea) {
                    $instituteDiff['area_id'] = ['current' => $inst->area_id, 'proposed' => $propArea];
                }
            }
        } else {
            $instituteDiff = [
                'name'    => ['current' => null, 'proposed' => $app->name ?? ($payload['name'] ?? null)],
                'phone'   => ['current' => null, 'proposed' => $this->normalizePhone($app->phone ?? ($payload['phone'] ?? null))],
                'area_id' => ['current' => null, 'proposed' => $app->area_id ?? ($payload['area_id'] ?? null)],
            ];
        }

        $infoFields = [
            'address',
            'established_on',
            'founder_name',
            'muhtamim',
            'upto_class',
            'students',
            'teachers',
            'has_hostel',
            'land_info',
            'building_summary',
            'has_library_for_students',
            'has_kutubkhana',
            'kutubkhana'
        ];

        $payloadInfo = $this->buildInfoPayload($payload);
        $infoDiff = [];
        foreach ($infoFields as $key) {
            $current = $info ? $info->{$key} : null;
            $prop    = $payloadInfo[$key] ?? null;
            if ($current != $prop) {
                $infoDiff[$key] = ['current' => $current, 'proposed' => $prop];
            }
        }

        return [
            'institute'      => $instituteDiff,
            'institute_info' => $infoDiff,
        ];
    }
}
