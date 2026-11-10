<?php

namespace App\Notifications\Messages;

use App\Enums\BookingStatus;
use App\Enums\TenantTheme;
use App\Models\Booking;
use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Mejl u dizajnu aplikacije: zaglavlje sa logom i imenom firme, dugme u boji firme,
 * zaobljenja po temi firme i detalji rezervacije kao kartica (isto kao stranica /manage).
 *
 * Podaci idu u viewData i koristi ih resources/views/vendor/notifications/email.blade.php.
 */
final class BrandedMailMessage extends MailMessage
{
    private const DEFAULT_COLOR = '#4f46e5';

    /**
     * Zaobljenja (px) po temi, prate --radius iz booking-fe/src/style.css.
     *
     * @var array<string, array{card: int, box: int, button: int, page: string}>
     */
    private const THEMES = [
        'minimal' => ['card' => 16, 'box' => 16, 'button' => 10, 'page' => '#f9fafb'],
        'modern' => ['card' => 28, 'box' => 20, 'button' => 14, 'page' => '#eef2ff'],
        'classic' => ['card' => 4, 'box' => 4, 'button' => 4, 'page' => '#f5f5f5'],
    ];

    public static function for(?Tenant $tenant): static
    {
        $message = new self;
        $message->viewData['brand'] = self::brand($tenant);

        return $message;
    }

    /**
     * Status rezervacije kao bedž iznad naslova.
     */
    public function status(BookingStatus $status): static
    {
        $tones = [
            'confirmed' => ['#dcfce7', '#15803d'],
            'cancelled' => ['#fee2e2', '#b91c1c'],
            'rejected' => ['#fee2e2', '#b91c1c'],
            'pending' => ['#fef9c3', '#a16207'],
        ];
        [$background, $color] = $tones[$status->value];

        $this->viewData['status'] = [
            'label' => __("manage.status.{$status->value}"),
            'background' => $background,
            'color' => $color,
        ];

        return $this;
    }

    /**
     * Kartica sa podacima rezervacije; prazne vrednosti se preskaču.
     *
     * @param  array<string, ?string>  $rows  natpis => vrednost
     */
    public function details(array $rows): static
    {
        $this->viewData['details'] = array_filter($rows, fn (?string $value) => $value !== null && $value !== '');

        return $this;
    }

    /**
     * Standardni redovi rezervacije (radnik, usluga, datum, vreme), opciono i podaci o kupcu.
     */
    public function bookingDetails(Booking $booking, bool $withCustomer = false): static
    {
        $slot = $booking->slot;
        $rows = [];

        if ($withCustomer) {
            $rows[__('notifications.details.customer')] = $booking->customer_name;
            $rows[__('notifications.details.contact')] = $booking->customer_phone ?? $booking->customer_email ?? '—';
        }

        $rows[__('notifications.details.worker')] = $slot->worker->name;
        $rows[__('notifications.details.service')] = $booking->service?->labelWithPrice();
        $rows[__('notifications.details.date')] = $slot->date->format(__('manage.date_format'));
        $rows[__('notifications.details.time')] = substr($booking->start_time, 0, 5).' – '.substr($booking->end_time, 0, 5);

        if ($withCustomer) {
            $rows[__('notifications.details.note')] = $booking->note;
        }

        return $this->details($rows);
    }

    /**
     * Sporedna akcija (npr. „Odbij“) kao destructive dugme ispod glavnog.
     */
    public function secondaryAction(string $text, string $url): static
    {
        $this->viewData['secondaryAction'] = ['text' => $text, 'url' => $url];

        return $this;
    }

    /**
     * @return array{name: string, logo: ?string, url: string, color: string, foreground: string, theme: array{card: int, box: int, button: int, page: string}}
     */
    public static function brand(?Tenant $tenant): array
    {
        $color = $tenant?->primary_color;
        if (! is_string($color) || ! preg_match('/^#[0-9a-f]{6}$/i', $color)) {
            $color = self::DEFAULT_COLOR;
        }

        $theme = $tenant?->theme instanceof TenantTheme ? $tenant->theme->value : 'minimal';

        return [
            'name' => $tenant ? ($tenant->app_name ?: $tenant->name) : (string) config('app.name'),
            'logo' => self::absoluteUrl($tenant?->logo_url),
            'url' => $tenant ? $tenant->siteUrl() : (string) config('app.frontend_url'),
            'color' => $color,
            'foreground' => self::readableForeground($color),
            'theme' => self::THEMES[$theme],
        ];
    }

    /**
     * Lokalni disk čuva relativne /storage/... putanje; mejl klijentu treba apsolutni URL.
     */
    private static function absoluteUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return str_starts_with($url, '/') ? rtrim((string) config('app.url'), '/').$url : $url;
    }

    /**
     * Ista logika kao readableForeground() u booking-fe/src/lib/color.ts (WCAG kontrast).
     */
    private static function readableForeground(string $hex): string
    {
        $channels = array_map(function (int $i) use ($hex) {
            $c = hexdec(substr($hex, $i, 2)) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, [1, 3, 5]);
        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];

        return 1.05 / ($luminance + 0.05) >= ($luminance + 0.05) / 0.05 ? '#ffffff' : '#111827';
    }
}
