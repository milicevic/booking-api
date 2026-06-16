<?php

return [
    'booking_confirmed_customer' => [
        'subject' => 'Booking confirmed ✓',
        'greeting' => 'Hello, :name!',
        'line' => 'Your booking has been **confirmed**. See you soon!',
        'worker' => '**Worker:** :name',
        'date' => '**Date:** :date',
        'time' => '**Time:** :start – :end',
        'action' => 'Manage booking',
        'salutation' => 'Thank you for using our service!',
    ],

    'booking_pending_customer' => [
        'subject' => 'Booking request received ⏳',
        'greeting' => 'Hello, :name!',
        'line1' => 'Your booking request has been received and is awaiting confirmation.',
        'worker' => '**Worker:** :name',
        'date' => '**Date:** :date',
        'time' => '**Time:** :start – :end',
        'line2' => 'You will be notified once the booking is confirmed or rejected.',
        'action' => 'View booking',
        'salutation' => 'Thank you for using our service!',
    ],

    'booking_pending_client' => [
        'subject' => 'New booking pending confirmation — :name',
        'greeting' => 'New booking!',
        'line' => 'A user has requested a booking. Please confirm or reject the request.',
        'customer' => '**Customer:** :name',
        'contact' => '**Contact:** :contact',
        'worker' => '**Worker:** :name',
        'date' => '**Date:** :date',
        'time' => '**Time:** :start – :end',
        'note' => '**Note:** :note',
        'action_confirm' => 'Confirm booking',
        'action_reject' => 'Reject booking',
        'salutation' => 'Booking App',
    ],

    'booking_confirmed_client' => [
        'subject' => 'New confirmed booking — :name',
        'greeting' => 'New booking confirmed!',
        'line' => 'A new booking has been automatically confirmed.',
        'customer' => '**Customer:** :name',
        'contact' => '**Contact:** :contact',
        'worker' => '**Worker:** :name',
        'date' => '**Date:** :date',
        'time' => '**Time:** :start – :end',
        'note' => '**Note:** :note',
        'action' => 'Open dashboard',
        'salutation' => 'Booking App',
    ],

    'booking_rejected_customer' => [
        'subject' => 'Booking rejected',
        'greeting' => 'Hello, :name!',
        'line1' => 'Unfortunately, your booking has been rejected.',
        'worker' => '**Worker:** :name',
        'date' => '**Date:** :date',
        'time' => '**Time:** :start – :end',
        'line2' => 'Available slots are still open — feel free to book another.',
        'salutation' => 'Booking App',
    ],

    'welcome_client' => [
        'subject' => 'Welcome to :app_name!',
        'greeting' => 'Hello, :name!',
        'line1' => 'Your account for **:app_name** has been successfully created.',
        'line2' => 'You have a **7-day free trial** (until :date) to explore all features.',
        'action' => 'Sign in',
        'line3' => 'From your dashboard you can add workers, configure slots, and track bookings.',
        'line4' => 'Thank you for using our platform!',
    ],

    'booking_cancelled_customer' => [
        'subject' => 'Booking cancelled',
        'greeting' => 'Hello, :name!',
        'line1' => 'Your booking has been cancelled.',
        'worker' => '**Worker:** :name',
        'date' => '**Date:** :date',
        'time' => '**Time:** :start – :end',
        'line2' => 'Available slots are still open — feel free to book another.',
        'salutation' => 'Booking App',
    ],

    'booking_pending_client_push' => [
        'title' => 'New booking — :name',
        'body' => ':name requested a booking on :date at :start with :worker.',
    ],

    'booking_confirmed_client_push' => [
        'title' => 'Booking confirmed — :name',
        'body' => 'A new booking for :name on :date at :start has been confirmed.',
    ],

    'worker_invite' => [
        'subject' => 'Invitation to join — :app_name',
        'greeting' => 'Hello, :name!',
        'line1' => 'You have been invited to join **:app_name** as a worker.',
        'action' => 'Accept invitation',
        'line2' => 'Click the button to set your password and access your account.',
        'line3' => 'The link is valid for 7 days. If you did not expect this email, you may ignore it.',
    ],
];
