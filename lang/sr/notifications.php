<?php

return [
    'details' => [
        'customer' => 'Korisnik',
        'contact' => 'Kontakt',
        'worker' => 'Radnik',
        'service' => 'Usluga',
        'date' => 'Datum',
        'time' => 'Vreme',
        'note' => 'Napomena',
    ],

    'booking_confirmed_customer' => [
        'subject' => 'Rezervacija potvrđena ✓',
        'greeting' => 'Zdravo, :name!',
        'line' => 'Vaša rezervacija je **potvrđena**. Vidimo se!',
        'action' => 'Upravljaj rezervacijom',
        'salutation' => 'Hvala što koristite naš servis!',
    ],

    'booking_pending_customer' => [
        'subject' => 'Zahtev za rezervaciju je primljen ⏳',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Vaš zahtev za rezervaciju je primljen i čeka potvrdu.',
        'line2' => 'Obavestićemo vas čim rezervacija bude potvrđena ili odbijena.',
        'action' => 'Pogledaj rezervaciju',
        'salutation' => 'Hvala što koristite naš servis!',
    ],

    'booking_pending_client' => [
        'subject' => 'Nova rezervacija čeka potvrdu — :name',
        'greeting' => 'Nova rezervacija!',
        'line' => 'Korisnik je zatražio rezervaciju. Potvrdite ili odbijte zahtev.',
        'action_confirm' => 'Potvrdi rezervaciju',
        'action_reject' => 'Odbij rezervaciju',
    ],

    'booking_confirmed_client' => [
        'subject' => 'Nova potvrđena rezervacija — :name',
        'greeting' => 'Nova rezervacija potvrđena!',
        'line' => 'Automatski je potvrđena nova rezervacija.',
        'action' => 'Otvori kontrolnu tablu',
    ],

    'booking_rejected_customer' => [
        'subject' => 'Rezervacija odbijena',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Nažalost, vaša rezervacija je odbijena.',
        'line2' => 'Slobodni termini su i dalje dostupni — možete da rezervišete drugi.',
    ],

    'reset_password' => [
        'subject' => 'Postavite novu lozinku',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Dobili smo zahtev da postavite novu lozinku za svoj nalog.',
        'action' => 'Postavi novu lozinku',
        'line2' => 'Link važi :minutes minuta. Ako niste vi tražili promenu, slobodno ignorišite ovaj mejl.',
    ],

    'verify_email' => [
        'subject' => 'Potvrdite email adresu za :app_name',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Kliknite na dugme ispod da potvrdite email adresu i aktivirate nalog za **:app_name**.',
        'action' => 'Potvrdi email',
        'line2' => 'Link važi :hours sati. Ako niste vi napravili nalog, slobodno ignorišite ovaj mejl.',
    ],

    'welcome_client' => [
        'subject' => 'Dobrodošli u :app_name!',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Vaš nalog za **:app_name** je uspešno kreiran.',
        'line2' => 'Imate **7 dana besplatnog probnog perioda** (do :date) da isprobate sve mogućnosti.',
        'action' => 'Prijavite se',
        'line3' => 'Na kontrolnoj tabli možete da dodajete radnike, usluge i termine i da pratite rezervacije.',
        'line4' => 'Hvala što koristite našu platformu!',
    ],

    'booking_cancelled_customer' => [
        'subject' => 'Rezervacija otkazana',
        'greeting' => 'Zdravo, :name!',
        'line1' => 'Vaša rezervacija je otkazana.',
        'line2' => 'Slobodni termini su i dalje dostupni — možete da rezervišete novi.',
    ],

    'booking_confirmed_customer_push' => [
        'title' => 'Rezervacija potvrđena ✓',
        'body' => 'Vaša rezervacija kod :worker dana :date u :start je potvrđena.',
    ],

    'booking_rejected_customer_push' => [
        'title' => 'Rezervacija odbijena',
        'body' => 'Vaša rezervacija kod :worker dana :date u :start je odbijena.',
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
