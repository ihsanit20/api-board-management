<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSmsLogsToSmsRecordsWithGrouping extends Migration
{
    public function up()
    {
        $groupedLogs = DB::table('sms_logs')
            ->select([
                'message',
                DB::raw('GROUP_CONCAT(phone_number) as phone_numbers'),
                'sms_parts',
                DB::raw('SUM(sms_parts) as sms_count'),
                DB::raw('SUM(cost) as total_cost'),
                'status',
                DB::raw('MAX(created_at) as created_at'),
                DB::raw('MAX(updated_at) as updated_at'),
            ])
            ->groupBy('message', 'status', 'sms_parts')
            ->get();

        foreach ($groupedLogs as $log) {
            DB::table('sms_records')->insert([
                'message'       => $log->message,
                'sms_parts'     => $log->sms_parts ?? 1,
                'sms_count'     => $log->sms_count ?? 1,
                'numbers'       => json_encode(explode(',', $log->phone_numbers)),
                'cost'          => $log->total_cost ?? 0.00,
                'event'         => 'SMS Panel',
                'status'        => $log->status,
                'institute_id'  => null,
                'created_at'    => $log->created_at,
                'updated_at'    => $log->updated_at,
            ]);
        }
    }

    public function down()
    {
        DB::table('sms_records')->truncate();
    }
}