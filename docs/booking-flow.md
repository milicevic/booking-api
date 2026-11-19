# Tok zakazivanja

Kako kupac zakazuje termin na sajtu firme i koja pravila važe na backendu i frontendu.
Kad se promeni ponašanje zakazivanja, ažurirati i ovaj dokument.

## Pojmovi

| Pojam | Tabela | Značenje |
|---|---|---|
| Termin | `slots` | Radno vreme **jednog** radnika: datum, početak i kraj (npr. Marko, 10.11, 09:00–12:00). |
| Usluga | `services` | Šta firma nudi: naziv, trajanje (`duration_minutes`), cena, `is_active`. |
| Radnik–usluga | `worker_services` | Koje usluge koji radnik radi. |
| Rezervacija | `bookings` | Usluga kod radnika u tačno određeno vreme, za kupca bez naloga (identifikuje se tokenom). |

Jedan termin uvek pripada tačno jednom radniku (`slots.worker_id`). Ako dva radnika rade u isto vreme,
to su **dva termina** sa različitim `slot_id`. Zato `slot_id` jednoznačno određuje radnika.

Termini istog radnika se ne smeju preklapati. Backend odbija novi termin koji se preklapa sa postojećim
terminom tog radnika istog dana (422); dodirivanje (09:00–10:00 i 10:00–11:00) je dozvoljeno.
Generator termina u adminu (`booking-fe/src/lib/slotGenerator.ts`) takve termine unapred preskače, a
grupno pravljenje (`POST /api/slots/bulk`) ih preskače i vraća koliko ih je preskočilo.

Termini mogu nastajati i iz **rasporeda radnika** (`worker_schedules`, `PUT /api/workers/{id}/schedule`):
dani, radno vreme, dužina termina i pauze. Termini se odmah prave za 4 nedelje unapred, a komanda
`slots:extend-schedules` ih svakog dana dopunjuje. Promena rasporeda zamenjuje buduće slobodne termine
starog rasporeda (bez rezervacije, neblokirane); ručni termini ostaju. Detalji su u `api_doc.md`.

Termin može biti **blokiran** (`slots.is_blocked`, `PATCH /api/slots/{id}`): ostaje u kalendaru, ali se ne
nudi kupcima i ne ulazi u računanje slobodnih vremena. Rezervacije koje su već u njemu ostaju.

## Šta rezervacija uvek ima

- **Termin** (`slot_id`): određuje radnika.
- **Uslugu** (`service_id`): obavezna. Mora biti aktivna i dodeljena radniku iz termina, inače 422.
- **Vreme početka** (`start_time`, `HH:MM`): mora biti jedno od slobodnih vremena za tu uslugu, inače 409. Kraj računa backend: početak + trajanje usluge.
- **Kupac:** ime (obavezno) i kontakt po pravilu firme (`client_profiles.customer_contact`, enum `CustomerContact`). Pravilo bira vlasnik na Pregledu u adminu:
  - `phone` (podrazumevano): telefon obavezan, email opcion;
  - `both`: i email i telefon;
  - `email_or_phone`: bar jedno od toga;
  - `email`: email obavezan, telefon opcion.

  Backend proverava pravilo pri svakoj rezervaciji (422 sa porukom šta nedostaje). Strana za zakazivanje ga dobija u `/api/tenant/config` (`customer_contact`), označava obavezna polja i proverava ih pre slanja. Telefon se uvek šalje u međunarodnom obliku (`+381641234567`, inače 422): na strani za zakazivanje kupac bira pozivni broj iz liste (podrazumevano Srbija +381) i unosi broj. Napomena je uvek opciona.

Rezervacije bez usluge više ne nastaju. Stari redovi u bazi mogu imati `service_id = null` i normalno se prikazuju.

## Računanje slobodnih vremena

`app/Services/Slot/SlotAvailabilityService.php`, za jednog radnika i trajanje usluge:

1. Neblokirani termini radnika za isti dan se spajaju u blokove (08:00–08:30 + 08:30–09:00 = 08:00–09:00), pa duža usluga može da pređe preko više termina.
2. Od blokova se oduzimaju aktivne rezervacije (`pending` i `confirmed`). Otkazane i odbijene ne zauzimaju vreme.
3. Kao početak se nude: početak svakog termina i kraj svake aktivne rezervacije. Vreme se nudi samo ako od tog trenutka ima dovoljno slobodnog vremena za celu uslugu. Primer: posle usluge od 40 min u 08:00 nudi se 08:40 za uslugu od 20 min.
4. Za današnji dan se ne nude vremena koja su već prošla.

Sva vremena su **lokalno vreme salona**. `APP_TIMEZONE` je podrazumevano `Europe/Belgrade` (`config/app.php`), da se `now()` / `today()` slažu sa terminima. Datum termina se šalje kao `Y-m-d`; kao Carbon objekat bi se pretvorio u UTC i ponoć bi postala prethodni dan.

Posle svake promene (nova, otkazana ili odbijena rezervacija) `refreshAvailability()` osvežava `slots.is_available`. Termin je „slobodan“ dok u njemu ima bar malo nezauzetog vremena.

## API

Sve rute su javne (bez prijave), a firma se prepoznaje po adresi (`resolve.tenant`).

### `GET /api/slots/available`
Slobodni termini (danas od sada, i budući) sa radnikom (`PublicSlotResource` / `PublicWorkerResource`):
ime, slika, opis i aktivne usluge radnika. Email, telefon i podaci naloga se **ne** šalju.
Frontend iz ovoga gradi cenovnik i tim.

### `GET /api/slots/times`
| Parametar | |
|---|---|
| `service_id` | obavezan |
| `worker_id` | opcion — **samo filter prikaza** |
| `date` | opcion (`Y-m-d`) |

- Sa `worker_id`: vremena tog radnika za uslugu. Ako radnik ne radi uslugu → 422.
- Bez `worker_id`: vremena **svih** radnika koji rade uslugu. Isto vreme se javlja jednom za svakog slobodnog radnika. Odgovor je poređan po datumu, vremenu, pa radniku. Neaktivna usluga → 422.

Svaki red ima oblik `{ slot_id, worker_id, date, start_time, end_time }`.

### `POST /api/bookings`
Polja: `slot_id`, `service_id`, `start_time`, `customer_name`, `customer_email` | `customer_phone`, `note`.
- **422:** nedostaje usluga („Izaberite uslugu.“), radnik iz termina je ne radi ili kontakt kupca ne ispunjava pravilo firme.
- **409:** vreme je u međuvremenu zauzeto ili više ne postoji.
- **429:** previše zahteva sa iste IP adrese (najviše 10 rezervacija u minutu).
- Kad je uključena zaštita od botova (`TURNSTILE_SECRET_KEY` na backendu, `VITE_TURNSTILE_SITE_KEY` na frontendu), zahtev mora imati ispravan `captcha_token` (Cloudflare Turnstile), inače 422.
- **201:** rezervacija sa `token`-om.

Rezervacija se pravi u transakciji koja zaključava sve termine radnika tog dana (`lockWorkerDay`, `lockForUpdate`), pa dve istovremene rezervacije ne mogu da zauzmu isto vreme.

### `GET /api/bookings/{token}` i `PATCH /api/bookings/{token}/cancel`
Pregled i otkazivanje po tokenu. Otkazati se može rezervacija koja čeka potvrdu (`pending`) ili je potvrđena (`confirmed`).

## Tok na sajtu firme

`booking-fe/src/views/booking/BookingView.vue`, pomoćne funkcije u `booking-fe/src/lib/bookingTimes.ts`.

1. **Levo su uvek prikazani cenovnik** (usluge: cena, trajanje, ko ih radi) i **tim** (radnici sa slikom ili generičkom slikom, i opisom).
   - Radnici bez ijedne usluge se ne nude.
   - Firma bez usluga vidi poruku da online zakazivanje još nije dostupno.
2. **Automatski izbor:** ako postoji samo jedna usluga, ona je izabrana; ako postoji samo jedan radnik, i on je izabran. Takav jedini izbor se ne može poništiti klikom.
3. **Usluga je obavezna; radnik je opcioni filter.** Ponovni klik poništava izbor.
   - Usluge koje izabrani radnik ne radi, i radnici koji ne rade izabranu uslugu, su onemogućeni.
   - Dok usluga nije izabrana, umesto kalendara stoji poruka šta treba izabrati.
4. **Kalendar** označava dane sa bar jednim slobodnim vremenom (`GET /api/slots/times`, sa `worker_id` samo ako je radnik izabran).
5. **Vremena za izabrani dan:** svako vreme se prikazuje jednom, a ako radnik nije izabran, i sa brojem slobodnih radnika („2 radnika slobodno“).
6. **Izbor radnika za vreme:**
   - ako je radnik već izabran, ili je u to vreme slobodan samo jedan, termin je odmah poznat;
   - inače kupac bira jednog od radnika slobodnih u to vreme.
   
   Svaka opcija nosi `slot_id` tog radnika.
7. **Forma i slanje:** `slot_id` izabranog radnika, `service_id`, `start_time` i podaci kupca.
   - Na 409 prikazuje se poruka, a vremena se ponovo učitavaju.
   - Na uspeh, kupac ide na stranu potvrde.

## Posle slanja

- **Režim potvrde** (`client_profiles.confirmation_mode`, enum `ConfirmationMode`, bira vlasnik u podešavanjima):
  - `owner` (podrazumevano): rezervacija je `pending`, zahtev za potvrdu dobija vlasnik;
  - `worker`: rezervacija je `pending`, zahtev dobija radnik kod koga je zakazana (radnik bez emaila nema nalog, pa tada vlasnik); potvrditi, odbiti i pomeriti sme taj radnik i vlasnik;
  - `auto`: rezervacija je odmah `confirmed`.

  Staro polje `auto_confirm_bookings` ostaje radi kompatibilnosti i uvek odgovara `confirmation_mode = auto` (sinhronizuje ga `ClientProfile`).
- **Potvrda i odbijanje:** vlasnik (ili radnik, u režimu `worker`) potvrđuje ili odbija rezervaciju koja čeka potvrdu, u adminu (`PATCH /api/bookings/{token}/confirm|reject`) ili iz mejla. Link iz mejla (potpisan, važi 7 dana) samo otvara stranicu `/bookings/{token}/review` sa podacima i dugmićima; akcija se izvršava tek klikom (POST), da je programi koji skeniraju linkove u mejlovima ne bi izvršili sami. Stari linkovi (`/api/bookings/{token}/confirm-by-link`) samo vode na tu stranicu. Odbijena rezervacija oslobađa vreme.
- **Pomeranje:** `PATCH /api/bookings/{token}/reschedule` sa `slot_id` i `start_time`. Za razliku od zakazivanja, vreme ne mora biti jedan od ponuđenih početaka: dovoljno je da usluga stane u slobodno vreme od tog trenutka (`SlotAvailabilityService::timeAt`), pri čemu staro vreme iste rezervacije ne smeta. Status se ne menja, kupac dobija obaveštenje. 
- **Pomeranje od strane kupca:** `PATCH /api/bookings/{token}/customer-reschedule`, sa stranice `/manage/{token}`. Kupac bira samo među ponuđenim vremenima (kao pri zakazivanju), najkasnije `client_profiles.reschedule_cutoff_hours` sati pre početka (podrazumevano 6, podešava vlasnik). U režimima `owner` i `worker` rezervacija ponovo čeka potvrdu i zahtev ide onome ko potvrđuje (mejl „Kupac je pomerio rezervaciju“); u režimu `auto` ostaje potvrđena.
- **Obaveštenja** (`BookingNotifier`, klase u `app/Notifications`):
  - kupac dobija mejl kad zakaže (čeka potvrdu) i kad otkaže; kad vlasnik potvrdi ili odbije, dobija push i mejl (mejl samo ako je ostavio email);
  - vlasnik dobija mejl, obaveštenje u adminu i push za novu rezervaciju (na čekanju ili odmah potvrđenu); u režimu `worker` zahtev za potvrdu umesto njega dobija radnik;
  - kupac dobija push i mejl kad mu se rezervacija pomeri;
  - vlasnik dobija mejl, obaveštenje u adminu i push kad kupac otkaže rezervaciju (`BookingCancelledClient`);
  - jezik mejla prati jezik kupca, odnosno firme.
- **Kalendar kupca** (`BookingCalendar`, ako vlasnik nije isključio `client_profiles.calendar_invites`):
  - mejl potvrde (i pomeranja) ima prilog `termin.ics` sa podsetnikom 60 min pre početka i dugmad za Google i Apple/Outlook kalendar;
  - stranica rezervacije nudi „Dodaj u kalendar“: na iPhone-u `.ics` (`GET /api/bookings/{token}/calendar.ics`), na Android-u i webu Google Calendar;
  - mejl otkazivanja i odbijanja ima `.ics` koji otkazuje isti događaj (isti UID, `METHOD:CANCEL`), pa ga Apple i Outlook uklanjaju; iz Google kalendara (dodatog linkom) događaj se briše ručno;
  - kupac dobija mejl o otkazivanju i kad sam otkaže na sajtu (ako je ostavio email).
- **Zaštita od zauzimanja termina** (podešavanja vlasnika u `client_profiles`, 0 = bez ograničenja):
  - kupac (isti telefon ili email, email bez obzira na velika slova) sme najviše `max_active_bookings_per_customer` (podrazumevano 3) budućih aktivnih rezervacija i `max_daily_bookings_per_customer` (5) novih u 24 sata, inače 422;
  - sa iste IP adrese najviše 10 rezervacija u minuti i 30 na sat za istu firmu (limiter `bookings`);
  - vlasnik može da blokira kupca iz rezervacije (`POST /api/bookings/{token}/block-customer`): telefon i email idu u `blocked_customers`, sve buduće aktivne rezervacije tog kupca se otkazuju (kupac dobija mejl), a nove se odbijaju porukom „Online rezervacija trenutno nije moguća“; lista i odblokiranje: `GET|DELETE /api/blocked-customers`;
  - rezervacija koja čeka potvrdu duže od `pending_expiry_hours` (12) ili čiji je termin počeo se sama odbija i oslobađa termin (`bookings:expire-pending`, svakih 15 min); kupac dobija mejl kao pri odbijanju.
- **„Vaše rezervacije“:** token se pamti u `localStorage` pregledača (`booking-fe/src/lib/myBookings.ts`) i prikazuje se na početnoj strani sajta firme. Iz pregledača se briše tek kad je rezervacija prošla, otkazana, odbijena ili je server ne prepoznaje (404). Na grešku mreže ostaje. Na drugom uređaju kupac koristi link iz mejla.
- **Upravljanje:** kupac rezervaciju vidi i otkazuje na `/manage/{token}` (frontend) ili preko linka iz mejla.
