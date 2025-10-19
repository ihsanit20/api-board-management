<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateStudentRegistrations extends Command
{
    protected $signature = 'students:update-registrations
                            {--chunk=200 : Number of applications per batch}
                            {--dry-run : Show counts only; do not write}
                            {--no-backup : Do not create backup before update}';

    protected $description = 'Overwrite students[*].registration = app_id*100 + (index+1) for all applications safely';

    public function handle()
    {
        $chunk    = (int) $this->option('chunk');
        $dryRun   = (bool) $this->option('dry-run');
        $noBackup = (bool) $this->option('no-backup');

        if ($dryRun) {
            $this->info('🚧 DRY RUN — no changes will be written.');
        } else {
            if (!$noBackup) {
                $this->createBackupTableIfMissing();
                $this->info('💾 Backup table ready: application_students_backup');
            }
        }

        $total = DB::table('applications')->whereNotNull('students')->count();
        $this->info("📊 Applications with students: {$total}");

        $bar = $this->output->createProgressBar($total);
        $appsProcessed = 0;
        $appsChanged   = 0;
        $invalidJson   = 0;

        // Process in ID order for stability
        DB::table('applications')
            ->select(['id', 'students'])
            ->whereNotNull('students')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use (&$appsProcessed, &$appsChanged, &$invalidJson, $bar, $dryRun, $noBackup) {
                foreach ($rows as $row) {
                    $appsProcessed++;

                    // Decode safely
                    $students = is_array($row->students)
                        ? $row->students
                        : json_decode($row->students ?? '[]', true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $invalidJson++;
                        $bar->advance();
                        continue;
                    }
                    if (empty($students)) {
                        $bar->advance();
                        continue;
                    }

                    // Overwrite registrations sequentially
                    foreach ($students as $i => &$s) {
                        if (!is_array($s)) $s = (array) $s;
                        $s['registration'] = ($row->id * 100) + ($i + 1); // id*100 + 1..N
                    }
                    unset($s);

                    $newJson = json_encode($students, JSON_UNESCAPED_UNICODE);

                    if ($dryRun) {
                        $appsChanged++;
                    } else {
                        DB::transaction(function () use ($row, $newJson, $noBackup) {
                            if (!$noBackup) {
                                // backup current JSON for this id (idempotent)
                                $current = DB::table('applications')->where('id', $row->id)->value('students');
                                DB::table('application_students_backup')->updateOrInsert(
                                    ['id' => $row->id],
                                    ['students' => $current, 'backed_up_at' => now()]
                                );
                            }
                            DB::table('applications')->where('id', $row->id)->update(['students' => $newJson]);
                        });
                        $appsChanged++;
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->line("\n");
        $this->info("✅ Done");
        $this->info("   Processed : {$appsProcessed}");
        $this->info("   Changed   : {$appsChanged}" . ($dryRun ? ' (dry-run)' : ''));
        $this->info("   Invalid JSON skipped : {$invalidJson}");
        if (!$dryRun && !$noBackup) {
            $this->info("   Backup table: application_students_backup (you can restore if needed).");
        }

        return self::SUCCESS;
    }

    private function createBackupTableIfMissing(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS application_students_backup (
                id BIGINT UNSIGNED PRIMARY KEY,
                students LONGTEXT NULL,
                backed_up_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
