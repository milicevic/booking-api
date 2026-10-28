<?php

return [
    // Auth
    'wrong_credentials' => 'Invalid credentials.',
    'account_suspended' => 'Account is suspended.',
    'logged_out' => 'Logged out.',
    'invalid_invite_token' => 'Invalid or expired invite token.',

    // Tenant / subscription
    'tenant_not_found' => 'Tenant not found.',
    'subscription_expired' => 'Subscription is expired or cancelled.',

    // Booking
    'slot_unavailable' => 'This slot is no longer available.',
    'service_required' => 'Please choose a service.',
    'service_unavailable_for_worker' => 'This service is not available for the selected worker.',
    'booking_not_pending' => 'Booking is not in pending status.',
    'booking_cancelled' => 'Booking cancelled.',
    'booking_confirmed' => 'Booking confirmed.',
    'booking_rejected' => 'Booking rejected.',
    'booking_not_accessible' => 'You do not have access to this booking.',
    'invalid_action' => 'Invalid action.',
    'invalid_link' => 'Link is invalid or expired.',

    // Services
    'service_assigned' => 'Service assigned to worker.',

    // Push notifications
    'push_subscribed' => 'Push notifications enabled.',
    'push_unsubscribed' => 'Push notifications disabled.',

    // Not found (404)
    'not_found' => [
        'booking' => "This booking doesn't exist or is no longer active.",
        'slot' => 'Time slot not found.',
        'service' => 'Service not found.',
        'user' => 'User not found.',
        'tenant' => 'Business not found.',
        'database_notification' => 'Notification not found.',
    ],

    // Notifications
    'notification_read' => 'Notification marked as read.',
    'notifications_all_read' => 'All notifications marked as read.',
];
