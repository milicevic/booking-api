<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlockedCustomerResource;
use App\Http\Resources\MessageResource;
use App\Models\BlockedCustomer;
use App\Repositories\Contracts\BlockedCustomerRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Kupci koje je vlasnik blokirao (blokira se iz rezervacije: PATCH /bookings/{token}/block-customer).
 */
class BlockedCustomerController extends Controller
{
    public function __construct(private readonly BlockedCustomerRepositoryInterface $blocked) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', BlockedCustomer::class);

        return BlockedCustomerResource::collection($this->blocked->all());
    }

    /** Odblokiranje: kupac ponovo može da zakazuje (otkazane rezervacije se ne vraćaju) */
    public function destroy(BlockedCustomer $blockedCustomer): MessageResource
    {
        Gate::authorize('delete', $blockedCustomer);

        $blockedCustomer->delete();

        return MessageResource::make(__('messages.customer_unblocked'));
    }
}
