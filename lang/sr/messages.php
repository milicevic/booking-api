<?php

return [
    // Auth
    'wrong_credentials' => 'Pogrešni podaci.',
    'account_suspended' => 'Nalog je suspendovan.',
    'logged_out' => 'Odjavljen.',
    'invalid_invite_token' => 'Nevažeći ili istekli token za poziv.',

    // Tenant / subscription
    'tenant_not_found' => 'Tenant nije pronađen.',
    'subscription_expired' => 'Pretplata je istekla ili otkazana.',

    // Booking
    'slot_unavailable' => 'Termin više nije dostupan.',
    'service_required' => 'Izaberite uslugu.',
    'service_unavailable_for_worker' => 'Izabrana usluga nije dostupna kod ovog radnika.',
    'booking_not_pending' => 'Rezervacija nije u statusu čekanja.',
    'booking_cancelled' => 'Rezervacija otkazana.',
    'booking_confirmed' => 'Rezervacija potvrđena.',
    'booking_rejected' => 'Rezervacija odbijena.',
    'booking_not_accessible' => 'Nemate pristup ovoj rezervaciji.',
    'invalid_action' => 'Neispravna akcija.',
    'invalid_link' => 'Link je nevažeći ili je istekao.',

    // Services
    'service_assigned' => 'Servis dodeljen radniku.',

    // Push notifikacije
    'push_subscribed' => 'Push notifikacije su uključene.',
    'push_unsubscribed' => 'Push notifikacije su isključene.',

    // Nepostojeći podaci (404)
    'not_found' => [
        'booking' => 'Rezervacija ne postoji ili više nije aktivna.',
        'slot' => 'Termin nije pronađen.',
        'service' => 'Usluga nije pronađena.',
        'user' => 'Korisnik nije pronađen.',
        'tenant' => 'Firma nije pronađena.',
        'database_notification' => 'Obaveštenje nije pronađeno.',
    ],

    // Notifikacije
    'notification_read' => 'Notifikacija označena kao pročitana.',
    'notifications_all_read' => 'Sve notifikacije označene kao pročitane.',
];
