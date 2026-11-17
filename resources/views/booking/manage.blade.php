<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ __('manage.title') }}</title>
    @php
        // Isti izgled kao mejlovi i aplikacija: brend firme, bela kartica, statusi u bojama iz admina
        $brand = \App\Notifications\Messages\BrandedMailMessage::brand($booking->tenant ?? $booking->slot->worker->tenant);
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
                box-shadow: 0 1px 2px rgba(0, 0, 0, .05); padding: 28px; display: flex; flex-direction: column; gap: 16px; }
        h1 { font-size: 24px; line-height: 1.25; font-weight: 600; margin: 0; }
        .muted { color: #4b5563; font-size: 15px; margin: 0; }
        .status { align-self: flex-start; padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 600;
                  background: {{ $statusBg }}; color: {{ $statusColor }}; }
        .label { font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #4b5563; margin: 0; }
        .rows { display: flex; flex-direction: column; gap: 10px; font-size: 14px; }
        .row { display: flex; justify-content: space-between; gap: 16px; }
        .row span:first-child { color: #4b5563; }
        .row span:last-child { font-weight: 500; text-align: right; }
        .note { padding: 12px 14px; border-radius: 12px; border: 1px solid #f3f4f6; background: #f9fafb; font-size: 14px; color: #374151; }
        .alert { padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .notice { font-size: 14px; color: #4b5563; border-top: 1px solid #f3f4f6; padding-top: 16px; }
        button { width: 100%; min-height: 44px; padding: 10px 16px; border-radius: 8px; font: inherit; font-size: 15px; font-weight: 500; cursor: pointer;
                 background: #fff; color: #991b1b; border: 1px solid #fca5a5; }
        button:hover { background: #fef2f2; }
        button:focus-visible { outline: 2px solid {{ $brand['color'] }}; outline-offset: 2px; }
        .footer { font-size: 13px; color: #4b5563; margin: 0; }
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

    <main class="card">
        <div>
            <h1>{{ __('manage.title') }}</h1>
            <p class="muted">{{ $booking->customer_name }}</p>
        </div>

        @if(session('success'))
            <div class="alert" role="status">{{ session('success') }}</div>
        @endif

        <span class="status">{{ __("manage.status.{$booking->status->value}") }}</span>

        <p class="label">{{ __('mail.details_title') }}</p>
        <div class="rows">
            <div class="row"><span>{{ __('manage.worker') }}</span><span>{{ $booking->slot->worker->name }}</span></div>
            @if ($booking->service)
                <div class="row"><span>{{ __('manage.service') }}</span><span>{{ $booking->service->labelWithPrice() }}</span></div>
            @endif
            <div class="row"><span>{{ __('manage.date') }}</span><span>{{ $booking->slot->date->format(__('manage.date_format')) }}</span></div>
            <div class="row"><span>{{ __('manage.time') }}</span><span>{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</span></div>
        </div>

        @if($booking->note)
            <div class="note"><strong>{{ __('manage.note') }}:</strong> {{ $booking->note }}</div>
        @endif

        @if($booking->status->isActive())
            <form method="POST" action="{{ route('booking.cancel', $booking->token) }}"
                  onsubmit="return confirm(@js(__('manage.cancel_confirm')))">
                @csrf
                <button type="submit">{{ __('manage.cancel_button') }}</button>
            </form>
        @else
            <p class="notice">{{ __("manage.status_notice.{$booking->status->value}") }}</p>
        @endif
    </main>

    <p class="footer">{{ __('mail.footer') }}</p>
</body>
</html>
