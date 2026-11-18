<?php

return [
    'details' => [
        'customer' => 'Customer',
        'contact' => 'Contact',
        'worker' => 'Worker',
        'service' => 'Service',
        'date' => 'Date',
        'time' => 'Time',
        'note' => 'Note',
    ],

    'booking_confirmed_customer' => [
        'subject' => 'Booking confirmed ✓',
        'greeting' => 'Hello, :name!',
        'line' => 'Your booking has been **confirmed**. See you soon!',
        'action' => 'Manage booking',
        'salutation' => 'Thank you for using our service!',
    ],

    'booking_pending_customer' => [
        'subject' => 'Booking request received ⏳',
        'greeting' => 'Hello, :name!',
        'line1' => 'Your booking request has been received and is awaiting confirmation.',
        'line2' => 'You will be notified once the booking is confirmed or rejected.',
        'action' => 'View booking',
        'salutation' => 'Thank you for using our service!',
    ],

    'booking_pending_client' => [
        'subject' => 'New booking pending confirmation — :name',
        'greeting' => 'New booking!',
        'line' => 'A user has requested a booking. Please confirm or reject the request.',
        'action_confirm' => 'Confirm booking',
        'action_reject' => 'Reject booking',
        'subject_rescheduled' => 'Customer moved a booking — :name',
        'greeting_rescheduled' => 'A booking was moved!',
        'line_rescheduled' => 'The customer picked a new time. Please confirm or reject the booking.',
    ],

    'booking_confirmed_client' => [
        'subject' => 'New confirmed booking — :name',
        'greeting' => 'New booking confirmed!',
        'line' => 'A new booking has been automatically confirmed.',
        'action' => 'Open dashboard',
        'subject_rescheduled' => 'Customer moved a booking — :name',
        'greeting_rescheduled' => 'A booking was moved!',
        'line_rescheduled' => 'The customer picked a new time; the booking was confirmed automatically.',
    ],

    'booking_rejected_customer' => [
        'subject' => 'Booking rejected',
        'greeting' => 'Hello, :name!',
        'line1' => 'Unfortunately, your booking has been rejected.',
        'line2' => 'Available slots are still open — feel free to book another.',
    ],

    'reset_password' => [
        'subject' => 'Set a new password',
        'greeting' => 'Hello, :name!',
        'line1' => 'We received a request to set a new password for your account.',
        'action' => 'Set new password',
        'line2' => 'The link is valid for :minutes minutes. If you did not ask for this, you can ignore this email.',
    ],

    'verify_email' => [
        'subject' => 'Confirm your email for :app_name',
        'greeting' => 'Hello, :name!',
        'line1' => 'Click the button below to confirm your email address and activate your **:app_name** account.',
        'action' => 'Confirm email',
        'line2' => 'The link is valid for :hours hours. If you did not create an account, you can ignore this email.',
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
        'line2' => 'Available slots are still open — feel free to book another.',
    ],

    'booking_rescheduled_customer' => [
        'subject' => 'Your booking has been moved',
        'greeting' => 'Hello, :name!',
        'line1' => 'Your booking has been moved to a new time:',
        'action' => 'View booking',
        'line2' => 'If the new time does not work for you, you can cancel the booking using the link above.',
    ],

    'booking_rescheduled_customer_push' => [
        'title' => 'Your booking has been moved',
        'body' => 'New time with :worker: :date at :start.',
    ],

    'booking_cancelled_client' => [
        'subject' => 'A customer cancelled a booking — :name',
        'greeting' => 'Booking cancelled',
        'line' => 'The customer cancelled the booking. The time is free again for others.',
        'action' => 'Open dashboard',
    ],

    'booking_cancelled_client_push' => [
        'title' => 'Booking cancelled — :name',
        'body' => ':name cancelled the booking on :date at :start with :worker.',
    ],

    'booking_confirmed_customer_push' => [
        'title' => 'Booking confirmed ✓',
        'body' => 'Your booking with :worker on :date at :start has been confirmed.',
    ],

    'booking_rejected_customer_push' => [
        'title' => 'Booking rejected',
        'body' => 'Your booking with :worker on :date at :start has been rejected.',
    ],

    'booking_pending_client_push' => [
        'title' => 'New booking — :name',
        'body' => ':name requested a booking on :date at :start with :worker.',
        'title_rescheduled' => 'Booking moved — :name',
    ],

    'booking_confirmed_client_push' => [
        'title' => 'Booking confirmed — :name',
        'body' => 'A new booking for :name on :date at :start has been confirmed.',
        'title_rescheduled' => 'Booking moved — :name',
    ],

    'worker_invite' => [
        'subject' => 'Invitation to join — :app_name',
        'greeting' => 'Hello, :name!',
        'line1' => 'You have been invited to join **:app_name** as a worker.',
        'action' => 'Accept invitation',
        'line2' => 'Click the button to set your password and access your account.',
        'line3' => 'The link is valid for 7 days. If you did not expect this email, you may ignore it.',
    ],

    'test_push' => [
        'title' => 'Test notification',
        'body' => 'Notifications from :app_name work on this device.',
    ],
];
