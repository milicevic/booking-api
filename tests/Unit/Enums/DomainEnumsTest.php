<?php

namespace Tests\Unit\Enums;

use App\Enums\BookingLinkAction;
use App\Enums\BookingStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class DomainEnumsTest extends TestCase
{
    public function test_values_lists_all_backing_values(): void
    {
        $this->assertSame(['pending', 'confirmed', 'rejected', 'cancelled'], BookingStatus::values());
        $this->assertSame(['confirm', 'reject'], BookingLinkAction::values());
    }

    public function test_only_pending_and_confirmed_bookings_are_active(): void
    {
        $this->assertTrue(BookingStatus::Pending->isActive());
        $this->assertTrue(BookingStatus::Confirmed->isActive());
        $this->assertFalse(BookingStatus::Rejected->isActive());
        $this->assertFalse(BookingStatus::Cancelled->isActive());
    }

    public function test_platform_users_are_superadmin_and_admin(): void
    {
        $this->assertTrue(UserRole::Superadmin->isPlatformUser());
        $this->assertTrue(UserRole::Admin->isPlatformUser());
        $this->assertFalse(UserRole::Client->isPlatformUser());
        $this->assertFalse(UserRole::Worker->isPlatformUser());
    }

    public function test_only_client_and_admin_manage_services(): void
    {
        $this->assertTrue(UserRole::Client->managesServices());
        $this->assertTrue(UserRole::Admin->managesServices());
        $this->assertFalse(UserRole::Worker->managesServices());
        $this->assertFalse(UserRole::Superadmin->managesServices());
    }

    public function test_expired_and_canceled_subscriptions_block_access(): void
    {
        $this->assertTrue(SubscriptionStatus::Expired->blocksAccess());
        $this->assertTrue(SubscriptionStatus::Canceled->blocksAccess());
        $this->assertFalse(SubscriptionStatus::Trialing->blocksAccess());
        $this->assertFalse(SubscriptionStatus::Active->blocksAccess());
    }
}
