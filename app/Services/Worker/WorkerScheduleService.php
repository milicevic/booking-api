<?php

namespace App\Services\Worker;

use App\Models\User;
use App\Models\WorkerSchedule;
use App\Repositories\Contracts\SlotRepositoryInterface;
use App\Repositories\Contracts\WorkerScheduleRepositoryInterface;
use App\Services\Contracts\SlotServiceInterface;
use App\Services\Contracts\WorkerScheduleServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WorkerScheduleService implements WorkerScheduleServiceInterface
{
    public function __construct(
        private readonly WorkerScheduleRepositoryInterface $schedules,
        private readonly SlotRepositoryInterface $slots,
        private readonly SlotServiceInterface $slotService,
    ) {}

    public function find(User $worker): ?WorkerSchedule
    {
        return $this->schedules->findForWorker($worker->id);
    }

    public function save(User $worker, array $data): array
    {
        return DB::transaction(function () use ($worker, $data): array {
            $schedule = $this->schedules->saveForWorker($worker->id, [
                'weekdays' => array_map('intval', $data['weekdays']),
                'start_time' => $data['start'],
                'end_time' => $data['end'],
                'slot_duration' => (int) $data['slot_duration'],
                'breaks' => $data['breaks'] ?? [],
                'generated_until' => null,
            ]);

            $removed = $this->slots->deleteFreeFutureFromSchedule($schedule->id);
            $result = $this->generate($schedule, today());

            return ['schedule' => $schedule, 'removed' => $removed, ...$result];
        });
    }

    public function delete(User $worker): void
    {
        $this->schedules->deleteForWorker($worker->id);
    }

    public function extendAll(): array
    {
        $until = WorkerSchedule::windowEnd();
        $schedules = $this->schedules->dueForExtension($until->toDateString());
        $created = 0;

        foreach ($schedules as $schedule) {
            $from = $schedule->generated_until
                ? $schedule->generated_until->copy()->addDay()->max(today())
                : today();

            $created += DB::transaction(fn (): int => $this->generate($schedule, $from)['created']);
        }

        return ['schedules' => $schedules->count(), 'created' => $created];
    }

    /**
     * Termini od $from do kraja prozora; postojeći termini radnika (i ručni) se ne dupliraju.
     *
     * @return array{created: int, skipped: int}
     */
    private function generate(WorkerSchedule $schedule, Carbon $from): array
    {
        $until = WorkerSchedule::windowEnd();
        $result = ['created' => 0, 'skipped' => 0];

        if ($from->lte($until)) {
            $result = $this->slotService->createMany([
                'worker_id' => $schedule->worker_id,
                'tenant_id' => $schedule->tenant_id,
                'worker_schedule_id' => $schedule->id,
                'date_from' => $from->toDateString(),
                'date_to' => $until->toDateString(),
                'weekdays' => $schedule->weekdays,
                'start' => substr($schedule->start_time, 0, 5),
                'end' => substr($schedule->end_time, 0, 5),
                'slot_duration' => $schedule->slot_duration,
                'breaks' => $schedule->breaks,
            ]);
        }

        $schedule->update(['generated_until' => $until->toDateString()]);

        return $result;
    }
}
