<?php

return [
    // Auth
    'wrong_credentials' => 'Invalid credentials.',
    'account_suspended' => 'Account is suspended.',
    'email_not_verified' => 'Please confirm your email address. We sent you a link when you registered.',
    'verification_sent' => 'If the account exists and is not confirmed yet, we sent a new confirmation link.',
    'password_reset_link_sent' => 'If an account with that email exists, we sent a link to set a new password.',
    'logged_out' => 'Logged out.',
    'invalid_invite_token' => 'Invalid or expired invite token.',

    // Tenant / subscription
    'tenant_not_found' => 'Tenant not found.',
    'subscription_expired' => 'Subscription is expired or cancelled.',

    // Booking
    'slot_unavailable' => 'This slot is no longer available.',
    'slot_overlaps' => 'This worker already has a slot that overlaps this time.',
    'service_required' => 'Please choose a service.',
    'captcha_failed' => 'Please confirm that you are not a robot and try again.',
    'contact_email_or_phone_required' => 'Enter your email or phone number.',
    'contact_both_required' => 'Enter both your email and phone number.',
    'contact_phone_required' => 'Enter your phone number.',
    'contact_email_required' => 'Enter your email.',
    'service_unavailable_for_worker' => 'This service is not available for the selected worker.',
    'service_unavailable' => 'This service is not available.',
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
