<?php

namespace App\Console\Commands;

use App\Models\WorkerSchedule;
use App\Services\Contracts\WorkerScheduleServiceInterface;
use Illuminate\Console\Command;

class ExtendWorkerSchedules extends Command
{
    protected $signature = 'slots:extend-schedules';

    protected $description = 'Create slots from worker schedules so they always cover the next '.WorkerSchedule::WEEKS_AHEAD.' weeks';

    public function handle(WorkerScheduleServiceInterface $schedules): int
    {
        $result = $schedules->extendAll();

        $this->info("Extended {$result['schedules']} schedule(s), created {$result['created']} slot(s).");

        return self::SUCCESS;
    }
}
