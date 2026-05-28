<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaša rezervacija</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            background-color: #f0fdf4;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #374151;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(21, 128, 61, 0.08);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            padding: 28px 32px;
            text-align: center;
        }

        .card-header .icon {
            font-size: 40px;
            margin-bottom: 8px;
            display: block;
        }

        .card-header h1 {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .card-header p {
            color: #dcfce7;
            font-size: 14px;
            margin: 0;
        }

        .card-body {
            padding: 28px 32px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .status-pending   { background: #fef9c3; color: #854d0e; }
        .status-confirmed { background: #dcfce7; color: #15803d; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-rejected  { background: #fee2e2; color: #991b1b; }

        .detail-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0fdf4;
        }

        .detail-row:last-of-type {
            border-bottom: none;
        }

        .detail-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .detail-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .detail-value {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .note-box {
            background: #f0fdf4;
            border-left: 3px solid #16a34a;
            border-radius: 0 6px 6px 0;
            padding: 12px 16px;
            margin-top: 20px;
        }

        .note-box .note-label {
            font-size: 11px;
            font-weight: 600;
            color: #16a34a;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }

        .note-box p {
            font-size: 14px;
            color: #374151;
            margin: 0;
            line-height: 1.5;
        }

        .alert {
            border-radius: 8px;
            padding: 14px 16px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

        .card-footer {
            padding: 0 32px 28px;
        }

        .btn-cancel {
            display: block;
            width: 100%;
            padding: 14px;
            background: #ffffff;
            color: #dc2626;
            border: 1.5px solid #fca5a5;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: background 0.15s, border-color 0.15s;
        }

        .btn-cancel:hover {
            background: #fff1f2;
            border-color: #dc2626;
        }

        .cancelled-notice {
            text-align: center;
            padding: 14px;
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            color: #6b7280;
        }

        .powered-by {
            text-align: center;
            padding: 20px 0 4px;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div>
        <div class="card">
            <div class="card-header">
                <span class="icon">📅</span>
                <h1>Vaša rezervacija</h1>
                <p>{{ $booking->customer_name }}</p>
            </div>

            <div class="card-body">

                @if(session('success'))
                    <div class="alert alert-success">
                        <span>✓</span> {{ session('success') }}
                    </div>
                @endif

                @php
                    $statusLabels = [
                        'pending'   => ['label' => 'Čeka potvrdu',  'class' => 'status-pending'],
                        'confirmed' => ['label' => 'Potvrđena',     'class' => 'status-confirmed'],
                        'cancelled' => ['label' => 'Otkazana',      'class' => 'status-cancelled'],
                        'rejected'  => ['label' => 'Odbijena',      'class' => 'status-rejected'],
                    ];
                    $statusInfo = $statusLabels[$booking->status] ?? ['label' => $booking->status, 'class' => ''];
                @endphp

                <span class="status-badge {{ $statusInfo['class'] }}">
                    {{ $statusInfo['label'] }}
                </span>

                <div class="detail-row">
                    <span class="detail-icon">👤</span>
                    <div>
                        <div class="detail-label">Radnik</div>
                        <div class="detail-value">{{ $booking->slot->worker->name }}</div>
                    </div>
                </div>

                <div class="detail-row">
                    <span class="detail-icon">📆</span>
                    <div>
                        <div class="detail-label">Datum</div>
                        <div class="detail-value">{{ $booking->slot->date->format('d.m.Y') }}</div>
                    </div>
                </div>

                <div class="detail-row">
                    <span class="detail-icon">🕐</span>
                    <div>
                        <div class="detail-label">Vreme</div>
                        <div class="detail-value">{{ $booking->slot->start_time }} – {{ $booking->slot->end_time }}</div>
                    </div>
                </div>

                @if($booking->note)
                    <div class="note-box">
                        <div class="note-label">Napomena</div>
                        <p>{{ $booking->note }}</p>
                    </div>
                @endif

            </div>

            <div class="card-footer">
                @if(in_array($booking->status, ['confirmed', 'pending']))
                    <form method="POST" action="{{ route('booking.cancel', $booking->token) }}"
                          onsubmit="return confirm('Da li ste sigurni da želite otkazati rezervaciju?')">
                        @csrf
                        <button type="submit" class="btn-cancel">
                            Otkaži rezervaciju
                        </button>
                    </form>
                @else
                    <div class="cancelled-notice">
                        Ova rezervacija je {{ $statusInfo['label'] | lower }}.
                    </div>
                @endif
            </div>
        </div>

        <p class="powered-by">Booking App</p>
    </div>
</body>
</html>
