<?php

namespace App\Repositories\Contracts;

use App\Models\BlockedCustomer;
use Illuminate\Database\Eloquent\Collection;

interface BlockedCustomerRepositoryInterface
{
    /** Da li je telefon ili email blokiran u trenutnoj firmi (email bez obzira na velika slova). */
    public function isBlocked(?string $phone, ?string $email): bool;

    /**
     * @param  array{phone: ?string, email: ?string, name: ?string, blocked_by: ?int}  $attributes
     */
    public function create(array $attributes): BlockedCustomer;

    /**
     * @return Collection<int, BlockedCustomer>
     */
    public function all(): Collection;
}
