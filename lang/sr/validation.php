<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Polje :attribute mora biti prihvaćeno.',
    'accepted_if' => 'Polje :attribute mora biti prihvaćeno kada je :other :value.',
    'active_url' => 'Polje :attribute mora biti važeći URL.',
    'after' => 'Polje :attribute mora biti datum posle :date.',
    'after_or_equal' => 'Polje :attribute mora biti datum posle ili jednak :date.',
    'alpha' => 'Polje :attribute može sadržati samo slova.',
    'alpha_dash' => 'Polje :attribute može sadržati samo slova, brojeve, crtice i donje crte.',
    'alpha_num' => 'Polje :attribute može sadržati samo slova i brojeve.',
    'any_of' => 'Polje :attribute nije ispravno.',
    'array' => 'Polje :attribute mora biti niz.',
    'array_keys' => 'Polje :attribute može sadržati samo sledeće ključeve: :values.',
    'ascii' => 'Polje :attribute može sadržati samo jednobajtne alfanumeričke znakove i simbole.',
    'base64' => 'Polje :attribute mora biti ispravan Base64 zapis.',
    'before' => 'Polje :attribute mora biti datum pre :date.',
    'before_or_equal' => 'Polje :attribute mora biti datum pre ili jednak :date.',
    'between' => [
        'array' => 'Polje :attribute mora imati između :min i :max stavki.',
        'file' => 'Fajl :attribute mora biti između :min i :max KB.',
        'numeric' => 'Polje :attribute mora biti između :min i :max.',
        'string' => 'Polje :attribute mora imati između :min i :max znakova.',
    ],
    'boolean' => 'Polje :attribute mora biti tačno ili netačno.',
    'can' => 'Polje :attribute sadrži nedozvoljenu vrednost.',
    'confirmed' => 'Potvrda polja :attribute se ne poklapa.',
    'contains' => 'Polju :attribute nedostaje obavezna vrednost.',
    'current_password' => 'Lozinka nije ispravna.',
    'date' => 'Polje :attribute mora biti ispravan datum.',
    'date_equals' => 'Polje :attribute mora biti datum jednak :date.',
    'date_format' => 'Polje :attribute mora biti u formatu :format.',
    'decimal' => 'Polje :attribute mora imati :decimal decimalnih mesta.',
    'declined' => 'Polje :attribute mora biti odbijeno.',
    'declined_if' => 'Polje :attribute mora biti odbijeno kada je :other :value.',
    'different' => 'Polja :attribute i :other moraju biti različita.',
    'digits' => 'Polje :attribute mora imati :digits cifara.',
    'digits_between' => 'Polje :attribute mora imati između :min i :max cifara.',
    'dimensions' => 'Slika :attribute nema odgovarajuće dimenzije.',
    'distinct' => 'Polje :attribute sadrži duplu vrednost.',
    'doesnt_contain' => 'Polje :attribute ne sme sadržati nijednu od sledećih vrednosti: :values.',
    'doesnt_end_with' => 'Polje :attribute ne sme da se završava nečim od sledećeg: :values.',
    'doesnt_start_with' => 'Polje :attribute ne sme da počinje nečim od sledećeg: :values.',
    'email' => 'Polje :attribute mora biti ispravna email adresa.',
    'encoding' => 'Polje :attribute mora biti kodirano kao :encoding.',
    'ends_with' => 'Polje :attribute mora da se završava nečim od sledećeg: :values.',
    'enum' => 'Izabrana vrednost za :attribute nije ispravna.',
    'exists' => 'Izabrana vrednost za :attribute nije ispravna.',
    'extensions' => 'Fajl :attribute mora imati jednu od sledećih ekstenzija: :values.',
    'file' => 'Polje :attribute mora biti fajl.',
    'filled' => 'Polje :attribute mora imati vrednost.',
    'gt' => [
        'array' => 'Polje :attribute mora imati više od :value stavki.',
        'file' => 'Fajl :attribute mora biti veći od :value KB.',
        'numeric' => 'Polje :attribute mora biti veće od :value.',
        'string' => 'Polje :attribute mora imati više od :value znakova.',
    ],
    'gte' => [
        'array' => 'Polje :attribute mora imati :value ili više stavki.',
        'file' => 'Fajl :attribute mora biti veći od ili jednak :value KB.',
        'numeric' => 'Polje :attribute mora biti veće od ili jednako :value.',
        'string' => 'Polje :attribute mora imati :value ili više znakova.',
    ],
    'hex_color' => 'Polje :attribute mora biti ispravna heksadecimalna boja.',
    'image' => 'Polje :attribute mora biti slika.',
    'in' => 'Izabrana vrednost za :attribute nije ispravna.',
    'in_array' => 'Polje :attribute mora postojati u :other.',
    'in_array_keys' => 'Polje :attribute mora sadržati bar jedan od sledećih ključeva: :values.',
    'integer' => 'Polje :attribute mora biti ceo broj.',
    'ip' => 'Polje :attribute mora biti ispravna IP adresa.',
    'ipv4' => 'Polje :attribute mora biti ispravna IPv4 adresa.',
    'ipv6' => 'Polje :attribute mora biti ispravna IPv6 adresa.',
    'json' => 'Polje :attribute mora biti ispravan JSON.',
    'list' => 'Polje :attribute mora biti lista.',
    'lowercase' => 'Polje :attribute mora biti napisano malim slovima.',
    'lt' => [
        'array' => 'Polje :attribute mora imati manje od :value stavki.',
        'file' => 'Fajl :attribute mora biti manji od :value KB.',
        'numeric' => 'Polje :attribute mora biti manje od :value.',
        'string' => 'Polje :attribute mora imati manje od :value znakova.',
    ],
    'lte' => [
        'array' => 'Polje :attribute ne sme imati više od :value stavki.',
        'file' => 'Fajl :attribute mora biti manji od ili jednak :value KB.',
        'numeric' => 'Polje :attribute mora biti manje od ili jednako :value.',
        'string' => 'Polje :attribute može imati najviše :value znakova.',
    ],
    'mac_address' => 'Polje :attribute mora biti ispravna MAC adresa.',
    'max' => [
        'array' => 'Polje :attribute ne sme imati više od :max stavki.',
        'file' => 'Fajl :attribute ne sme biti veći od :max KB.',
        'numeric' => 'Polje :attribute ne sme biti veće od :max.',
        'string' => 'Polje :attribute može imati najviše :max znakova.',
    ],
    'max_digits' => 'Polje :attribute ne sme imati više od :max cifara.',
    'mimes' => 'Fajl :attribute mora biti jednog od sledećih tipova: :values.',
    'mimetypes' => 'Fajl :attribute mora biti jednog od sledećih tipova: :values.',
    'min' => [
        'array' => 'Polje :attribute mora imati najmanje :min stavki.',
        'file' => 'Fajl :attribute mora imati najmanje :min KB.',
        'numeric' => 'Polje :attribute mora biti najmanje :min.',
        'string' => 'Polje :attribute mora imati najmanje :min znakova.',
    ],
    'min_digits' => 'Polje :attribute mora imati najmanje :min cifara.',
    'missing' => 'Polje :attribute ne sme biti prisutno.',
    'missing_if' => 'Polje :attribute ne sme biti prisutno kada je :other :value.',
    'missing_unless' => 'Polje :attribute ne sme biti prisutno osim ako je :other :value.',
    'missing_with' => 'Polje :attribute ne sme biti prisutno kada je prisutno :values.',
    'missing_with_all' => 'Polje :attribute ne sme biti prisutno kada su prisutna polja :values.',
    'multiple_of' => 'Polje :attribute mora biti deljivo sa :value.',
    'not_in' => 'Izabrana vrednost za :attribute nije ispravna.',
    'not_regex' => 'Format polja :attribute nije ispravan.',
    'numeric' => 'Polje :attribute mora biti broj.',
    'password' => [
        'letters' => 'Polje :attribute mora sadržati bar jedno slovo.',
        'mixed' => 'Polje :attribute mora sadržati bar jedno veliko i jedno malo slovo.',
        'numbers' => 'Polje :attribute mora sadržati bar jedan broj.',
        'symbols' => 'Polje :attribute mora sadržati bar jedan simbol.',
        'uncompromised' => 'Uneta vrednost za :attribute se pojavila u curenju podataka. Izaberite drugu vrednost.',
    ],
    'present' => 'Polje :attribute mora biti prisutno.',
    'present_if' => 'Polje :attribute mora biti prisutno kada je :other :value.',
    'present_unless' => 'Polje :attribute mora biti prisutno osim ako je :other :value.',
    'present_with' => 'Polje :attribute mora biti prisutno kada je prisutno :values.',
    'present_with_all' => 'Polje :attribute mora biti prisutno kada su prisutna polja :values.',
    'prohibited' => 'Polje :attribute nije dozvoljeno.',
    'prohibited_if' => 'Polje :attribute nije dozvoljeno kada je :other :value.',
    'prohibited_if_accepted' => 'Polje :attribute nije dozvoljeno kada je :other prihvaćeno.',
    'prohibited_if_declined' => 'Polje :attribute nije dozvoljeno kada je :other odbijeno.',
    'prohibited_unless' => 'Polje :attribute nije dozvoljeno osim ako je :other u :values.',
    'prohibits' => 'Polje :attribute ne dozvoljava da :other bude prisutno.',
    'regex' => 'Format polja :attribute nije ispravan.',
    'required' => 'Polje :attribute je obavezno.',
    'required_array_keys' => 'Polje :attribute mora sadržati stavke za: :values.',
    'required_if' => 'Polje :attribute je obavezno kada je :other :value.',
    'required_if_accepted' => 'Polje :attribute je obavezno kada je :other prihvaćeno.',
    'required_if_declined' => 'Polje :attribute je obavezno kada je :other odbijeno.',
    'required_unless' => 'Polje :attribute je obavezno osim ako je :other u :values.',
    'required_with' => 'Polje :attribute je obavezno kada je prisutno :values.',
    'required_with_all' => 'Polje :attribute je obavezno kada su prisutna polja :values.',
    'required_without' => 'Polje :attribute je obavezno kada :values nije prisutno.',
    'required_without_all' => 'Polje :attribute je obavezno kada nijedno od polja :values nije prisutno.',
    'same' => 'Polja :attribute i :other se moraju poklapati.',
    'size' => [
        'array' => 'Polje :attribute mora sadržati :size stavki.',
        'file' => 'Fajl :attribute mora imati :size KB.',
        'numeric' => 'Polje :attribute mora biti :size.',
        'string' => 'Polje :attribute mora imati :size znakova.',
    ],
    'starts_with' => 'Polje :attribute mora da počinje nečim od sledećeg: :values.',
    'string' => 'Polje :attribute mora biti tekst.',
    'timezone' => 'Polje :attribute mora biti ispravna vremenska zona.',
    'unique' => 'Vrednost za :attribute je već zauzeta.',
    'uploaded' => 'Otpremanje fajla :attribute nije uspelo.',
    'uppercase' => 'Polje :attribute mora biti napisano velikim slovima.',
    'url' => 'Polje :attribute mora biti ispravan URL.',
    'ulid' => 'Polje :attribute mora biti ispravan ULID.',
    'uuid' => 'Polje :attribute mora biti ispravan UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'logo' => [
            'mimes' => 'Logo mora biti PNG, JPG ili WebP slika.',
            'dimensions' => 'Logo mora imati najmanje 128×128 px.',
            'max' => 'Logo može imati najviše 2 MB.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our messages more expressive.
    |
    */

    'attributes' => [
        'app_name' => 'naziv firme',
        'auto_confirm_bookings' => 'automatska potvrda rezervacija',
        'can_edit_slots' => 'izmena termina',
        'custom_domain' => 'sopstveni domen',
        'customer_email' => 'email',
        'customer_name' => 'ime i prezime',
        'customer_phone' => 'telefon',
        'date' => 'datum',
        'deploy_status' => 'status objave',
        'duration_minutes' => 'trajanje',
        'email' => 'email',
        'end_time' => 'vreme završetka',
        'endpoint' => 'endpoint',
        'invite_token' => 'token poziva',
        'is_active' => 'aktivno',
        'keys.auth' => 'ključ za autentifikaciju',
        'logo' => 'logo',
        'logo_url' => 'URL loga',
        'name' => 'ime',
        'note' => 'napomena',
        'password' => 'lozinka',
        'phone' => 'telefon',
        'price' => 'cena',
        'primary_color' => 'primarna boja',
        'secondary_color' => 'sekundarna boja',
        'service_id' => 'usluga',
        'slot_id' => 'termin',
        'start_time' => 'vreme početka',
        'subdomain' => 'poddomen',
        'subscription_ends_at' => 'kraj pretplate',
        'subscription_status' => 'status pretplate',
        'theme' => 'tema',
        'trial_ends_at' => 'kraj probnog perioda',
        'worker_id' => 'radnik',
    ],

];
