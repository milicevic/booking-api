<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ __('manage.review.title') }}</title>
    @php
        // Isti brend kao mejl iz kog je link otvoren (boja, logo, naziv firme)
        $brand = \App\Notifications\Messages\BrandedMailMessage::brand($booking->tenant);
        $tones = [
            'pending' => ['#fef3c7', '#92400e'],
            'confirmed' => ['#dcfce7', '#166534'],
            'cancelled' => ['#f3f4f6', '#4b5563'],
            'rejected' => ['#f3f4f6', '#4b5563'],
        ];
        [$statusBg, $statusColor] = $tones[$booking->status->value] ?? $tones['cancelled'];
    @endphp
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 24px; padding: 32px 16px;
               background: #f9fafb; color: #111827; line-height: 1.5;
               font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .brand { display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 600; color: #111827; text-decoration: none; }
        .brand img, .brand-mark { width: 40px; height: 40px; border-radius: 10px; object-fit: contain; }
        .brand-mark { display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 600;
                      background: {{ $brand['color'] }}; color: {{ $brand['foreground'] }}; }
        .card { width: 100%; max-width: 480px; background: #fff; border: 1px solid #f3f4f6; border-radius: 16px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, .05); overflow: hidden; }
        .card-body { padding: 28px; }
        h1 { font-size: 24px; line-height: 1.25; font-weight: 600; margin: 0 0 6px; }
        .muted { color: #4b5563; font-size: 15px; margin: 0 0 20px; }
        .label { font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #4b5563; margin: 4px 0 8px; }
        .row { display: flex; justify-content: space-between; gap: 16px; padding: 6px 0; font-size: 14px; }
        .row span:first-child { color: #4b5563; }
        .row span:last-child { font-weight: 500; text-align: right; }
        .note { margin-top: 12px; padding: 12px 14px; border-radius: 12px; border: 1px solid #f3f4f6; background: #f9fafb; font-size: 14px; color: #374151; }
        .alert { margin-bottom: 20px; padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .status { display: inline-block; margin-bottom: 16px; padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 600;
                  background: {{ $statusBg }}; color: {{ $statusColor }}; }
        .actions { display: flex; gap: 8px; padding: 0 28px 28px; }
        .actions form { flex: 1; }
        button { width: 100%; min-height: 44px; padding: 10px 16px; border-radius: 8px; font: inherit; font-size: 15px; font-weight: 500; cursor: pointer; border: 1px solid transparent; }
        button:hover { filter: brightness(.92); }
        button:focus-visible { outline: 2px solid {{ $brand['color'] }}; outline-offset: 2px; }
        .btn-confirm { background: {{ $brand['color'] }}; color: {{ $brand['foreground'] }}; }
        .btn-reject { background: #fff; color: #374151; border-color: #d1d5db; }
        .suggested { box-shadow: 0 0 0 3px #c7d2fe; }
        .footer { font-size: 13px; color: #4b5563; }
    </style>
</head>
<body>
    <a class="brand" href="{{ $brand['url'] }}">
        @if ($brand['logo'])
            <img src="{{ $brand['logo'] }}" alt="">
        @else
            <span class="brand-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($brand['name'], 0, 1)) }}</span>
        @endif
        {{ $brand['name'] }}
    </a>
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

            <p class="label">{{ __('mail.details_title') }}</p>
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
    <p class="footer">{{ __('mail.footer') }}</p>
</body>
</html>
