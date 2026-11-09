<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Slot;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Briše duplirane termine (termini istog radnika istog dana koji se preklapaju), nastale pre nego što
 * je backend počeo da ih odbija. Termin koji ima ikakvu rezervaciju se nikad ne briše.
 */
class RemoveOverlappingSlots extends Command
{
    protected $signature = 'slots:remove-overlaps {--force : Stvarno obriši (bez ove opcije samo prikazuje šta bi bilo obrisano)}';

    protected $description = 'Remove overlapping slots of the same worker on the same day (keeps slots with bookings)';

    public function handle(): int
    {
        $bookedSlotIds = Booking::withoutGlobalScopes()->distinct()->pluck('slot_id')->flip();
        $toDelete = collect();

        Slot::withoutGlobalScopes()
            ->orderBy('worker_id')
            ->orderBy('date')
            ->get()
            ->groupBy(fn (Slot $slot): string => $slot->worker_id.'|'.$slot->date->toDateString())
            ->each(function (Collection $daySlots) use ($bookedSlotIds, $toDelete): void {
                // Prvo termini sa rezervacijom, pa najstariji: oni ostaju, a kasniji duplikati se brišu
                $ordered = $daySlots->sortBy(fn (Slot $slot): array => [$bookedSlotIds->has($slot->id) ? 0 : 1, $slot->id]);
                $kept = [];

                foreach ($ordered as $slot) {
                    $overlapsKept = collect($kept)->contains(
                        fn (Slot $other): bool => $slot->start_time < $other->end_time && $slot->end_time > $other->start_time
                    );

                    if ($overlapsKept && ! $bookedSlotIds->has($slot->id)) {
                        $toDelete->push($slot->id);
                    } else {
                        $kept[] = $slot;
                    }
                }
            });

        if ($toDelete->isEmpty()) {
            $this->info('No overlapping slots found.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->warn("{$toDelete->count()} overlapping slot(s) would be deleted. Run with --force to delete them.");

            return self::SUCCESS;
        }

        DB::transaction(fn () => $toDelete->chunk(500)->each(
            fn (Collection $ids) => Slot::withoutGlobalScopes()->whereIn('id', $ids)->delete()
        ));

        $this->info("Deleted {$toDelete->count()} overlapping slot(s).");

        return self::SUCCESS;
    }
}
