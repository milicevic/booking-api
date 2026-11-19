<?php

namespace App\Repositories\Eloquent;

use App\Models\BlockedCustomer;
use App\Repositories\Contracts\BlockedCustomerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentBlockedCustomerRepository implements BlockedCustomerRepositoryInterface
{
    public function isBlocked(?string $phone, ?string $email): bool
    {
        if (! $phone && ! $email) {
            return false;
        }

        return BlockedCustomer::query()
            ->where(fn ($query) => $query
                ->when($phone, fn ($q) => $q->orWhere('phone', $phone))
                ->when($email, fn ($q) => $q->orWhereRaw('lower(email) = ?', [mb_strtolower((string) $email)])))
            ->exists();
    }

    public function create(array $attributes): BlockedCustomer
    {
        return BlockedCustomer::create($attributes);
    }

    public function all(): Collection
    {
        return BlockedCustomer::latest()->get();
    }
}
