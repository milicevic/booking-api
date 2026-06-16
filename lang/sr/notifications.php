<?php

return [
    'booking_confirmed_customer' => [
        'subject' => 'Rezervacija potvrđena ✓',
        'greeting' => 'Zdravo, :name!',
        'line' => 'Vaša rezervacija je **potvrđena**. Vidimo se!',
        'worker' => '**Radnik:** :name',
        'date' => '**Datum:** :date',
        'time' => '**Vreme:** :start – :end',
        'action' => 'Upravljaj rezervacijom',
        'salutation' => 'Hvala što koristite naš servis!',
    ],

    'booking_pending_customer' => [
        'subject' => 'Zahtjev za rezervaciju primljen ⏳',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Vaš zahtjev za rezervaciju je uspješno primljen i čeka potvrdu.',
        'worker' => '**Radnik:** :name',
        'date' => '**Datum:** :date',
        'time' => '**Vreme:** :start – :end',
        'line2' => 'Bićete obaviješteni čim klijent potvrdi ili odbije vašu rezervaciju.',
        'action' => 'Pogledaj rezervaciju',
        'salutation' => 'Hvala što koristite naš servis!',
    ],

    'booking_pending_client' => [
        'subject' => 'Nova rezervacija čeka potvrdu — :name',
        'greeting' => 'Nova rezervacija!',
        'line' => 'Korisnik je zatražio rezervaciju. Potvrdite ili odbijte zahtjev.',
        'customer' => '**Korisnik:** :name',
        'contact' => '**Kontakt:** :contact',
        'worker' => '**Radnik:** :name',
        'date' => '**Datum:** :date',
        'time' => '**Vreme:** :start – :end',
        'note' => '**Napomena:** :note',
        'action_confirm' => 'Potvrdi rezervaciju',
        'action_reject' => 'Odbij rezervaciju',
        'salutation' => 'Booking App',
    ],

    'booking_confirmed_client' => [
        'subject' => 'Nova potvrđena rezervacija — :name',
        'greeting' => 'Nova rezervacija potvrđena!',
        'line' => 'Automatski je potvrđena nova rezervacija.',
        'customer' => '**Korisnik:** :name',
        'contact' => '**Kontakt:** :contact',
        'worker' => '**Radnik:** :name',
        'date' => '**Datum:** :date',
        'time' => '**Vreme:** :start – :end',
        'note' => '**Napomena:** :note',
        'action' => 'Otvori dashboard',
        'salutation' => 'Booking App',
    ],

    'booking_rejected_customer' => [
        'subject' => 'Rezervacija odbijena',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Nažalost, vaša rezervacija je odbijena.',
        'worker' => '**Radnik:** :name',
        'date' => '**Datum:** :date',
        'time' => '**Vreme:** :start – :end',
        'line2' => 'Slobodni termini su i dalje dostupni — možete probati rezervisati drugi.',
        'salutation' => 'Booking App',
    ],

    'welcome_client' => [
        'subject' => 'Dobrodošli u :app_name!',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Vaš nalog za **:app_name** je uspješno kreiran.',
        'line2' => 'Imate **7 dana besplatnog trial perioda** (do :date) da isprobate sve funkcionalnosti.',
        'action' => 'Prijavite se',
        'line3' => 'Na vašem dashboardu možete dodavati radnike, konfigurisati termine i pratiti rezervacije.',
        'line4' => 'Hvala što koristite našu platformu!',
    ],

    'booking_cancelled_customer' => [
        'subject' => 'Rezervacija otkazana',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Vaša rezervacija je otkazana.',
        'worker' => '**Radnik:** :name',
        'date' => '**Datum:** :date',
        'time' => '**Vreme:** :start – :end',
        'line2' => 'Slobodni termini su i dalje dostupni — možete rezervisati novi.',
        'salutation' => 'Booking App',
    ],

    'booking_pending_client_push' => [
        'title' => 'Nova rezervacija — :name',
        'body' => ':name je zatražio rezervaciju :date u :start kod :worker.',
    ],

    'booking_confirmed_client_push' => [
        'title' => 'Rezervacija potvrđena — :name',
        'body' => 'Nova rezervacija za :name :date u :start je potvrđena.',
    ],

    'worker_invite' => [
        'subject' => 'Poziv za pristup — :app_name',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Pozvani ste da se pridružite timu firme **:app_name** kao radnik.',
        'action' => 'Prihvati poziv',
        'line2' => 'Klikom na dugme postavite svoju lozinku i pristupite nalogu.',
        'line3' => 'Link važi 7 dana. Ako niste očekivali ovaj email, možete ga ignorisati.',
    ],
];
