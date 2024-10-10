<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    private bool $includeStudents;

    public function __construct($resource, $includeStudents = false)
    {
        parent::__construct($resource);
        $this->includeStudents = $includeStudents;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            "id" => (int) ($this->id),
            "application_date" => (String) ($this->application_date ?? $this->created_at),
            "institute_code_name" => (String) ($this->institute->institute_code . ' - ' . $this->institute->name),
            "center_name" => (String) ($this->center->name ?? ''),
            "area_name" => (String) ($this->area->name ?? ''),
            "zamat_name" => (String) ($this->zamat->name ?? ''),
            "student_count" => (int) (count($this->students ?? [])),
            "total_amount" => (int) ($this->total_amount ?? 0),
            "payment_method" => (String) ($this->payment_method ?? ''),
            "payment_status" => (String) ($this->payment_status ?? ''),
            "submitted_by_name" => (String) ($this->submittedBy->name ?? ''),
            "approved_by_name" => (String) ($this->approvedBy->name ?? ''),
        ];

        // Include students only if the flag is true
        if ($this->includeStudents) {
            $data['students'] = $this->students ?? [];
        }

        return $data;
    }
}
