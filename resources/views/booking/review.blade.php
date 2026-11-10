<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ __('manage.review.title') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px;
               background: #f9fafb; color: #374151; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .card { width: 100%; max-width: 480px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
        .card-body { padding: 28px 32px; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #111827; }
        .muted { color: #6b7280; font-size: 14px; margin: 0 0 20px; }
        .row { display: flex; justify-content: space-between; gap: 16px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .row span:first-child { color: #6b7280; }
        .row span:last-child { font-weight: 600; color: #111827; text-align: right; }
        .note { margin-top: 12px; padding: 12px; border-radius: 8px; background: #f9fafb; font-size: 14px; }
        .alert { margin-bottom: 20px; padding: 12px 14px; border-radius: 8px; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #15803d; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .status { display: inline-block; margin-bottom: 16px; padding: 4px 12px; border-radius: 99px; font-size: 13px; font-weight: 600; background: #f3f4f6; }
        .actions { display: flex; gap: 12px; padding: 0 32px 28px; }
        .actions form { flex: 1; }
        button { width: 100%; padding: 12px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; border: 1px solid transparent; }
        .btn-confirm { background: #16a34a; color: #fff; }
        .btn-reject { background: #fff; color: #b91c1c; border-color: #fecaca; }
        .suggested { outline: 3px solid #93c5fd; outline-offset: 2px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-body">
            <h1>{{ __('manage.review.title') }}</h1>
            <p class="muted">{{ __('manage.review.subtitle') }}</p>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <span class="status">{{ __("manage.status.{$booking->status->value}") }}</span>

            <div class="row"><span>{{ __('manage.review.customer') }}</span><span>{{ $booking->customer_name }}</span></div>
            <div class="row"><span>{{ __('manage.review.contact') }}</span><span>{{ $booking->customer_phone ?? $booking->customer_email ?? '—' }}</span></div>
            <div class="row"><span>{{ __('manage.worker') }}</span><span>{{ $booking->slot->worker->name }}</span></div>
            @if ($booking->service)
                <div class="row"><span>{{ __('manage.service') }}</span><span>{{ $booking->service->labelWithPrice() }}</span></div>
            @endif
            <div class="row"><span>{{ __('manage.date') }}</span><span>{{ $booking->slot->date->format(__('manage.date_format')) }}</span></div>
            <div class="row"><span>{{ __('manage.time') }}</span><span>{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</span></div>

            @if($booking->note)
                <div class="note">{{ $booking->note }}</div>
            @endif
        </div>

        @if($booking->status === \App\Enums\BookingStatus::Pending)
            <div class="actions">
                <form method="POST" action="{{ $applyUrl }}">
                    @csrf
                    <input type="hidden" name="action" value="confirm">
                    <button type="submit" class="btn-confirm {{ $suggested === \App\Enums\BookingLinkAction::Confirm ? 'suggested' : '' }}">
                        {{ __('manage.review.confirm') }}
                    </button>
                </form>
                <form method="POST" action="{{ $applyUrl }}">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn-reject {{ $suggested === \App\Enums\BookingLinkAction::Reject ? 'suggested' : '' }}">
                        {{ __('manage.review.reject') }}
                    </button>
                </form>
            </div>
        @endif
    </div>
</body>
</html>
