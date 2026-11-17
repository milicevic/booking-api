# Booking API Documentation

Base URL: `/api`

---

## Authentication

Zaštićeni endpointi zahtijevaju Sanctum token u headeru:

```
Authorization: Bearer {token}
```

---

## Auth

### POST /auth/login

Login klijenta.

**Request body:**
```json
{
  "email": "klijent@example.com",
  "password": "tajna"
}
```

**Response 200:**
```json
{
  "token": "1|abc123...",
  "client": {
    "id": 1,
    "name": "Firma d.o.o.",
    "email": "klijent@example.com",
    "role": "client"
  }
}
```

**Response 401:**
```json
{ "message": "Pogrešni podaci" }
```

---

### POST /auth/logout

Odjava — briše trenutni token. **Auth required.**

**Response 200:**
```json
{ "message": "Odjavljen" }
```

---

### GET /auth/me

Vraća trenutno ulogovanog korisnika. **Auth required.**

**Response 200:**
```json
{
  "id": 1,
  "name": "Firma d.o.o.",
  "email": "klijent@example.com",
  "role": "client",
  "can_edit_slots": false
}
```

---

## Workers

Svi worker endpointi zahtijevaju autentikaciju. Klijent vidi samo svoje workere.

### GET /workers

Lista svih workera klijenta.

**Response 200:**
```json
[
  {
    "id": 2,
    "name": "Marko Radnik",
    "email": "marko@example.com",
    "role": "worker",
    "client_id": 1,
    "can_edit_slots": false,
    "worker_profile": {
      "id": 1,
      "user_id": 2,
      "phone": "+38161000000"
    }
  }
]
```

---

### POST /workers

Kreira novog workera.

**Request body:**
```json
{
  "name": "Marko Radnik",
  "email": "marko@example.com",
  "phone": "+38161000000"
}
```

| Polje   | Tip    | Obavezno | Napomena                       |
|---------|--------|----------|-------------------------------|
| name    | string | da       | max 255 znakova               |
| email   | string | ne       | mora biti jedinstven           |
| phone   | string | ne       |                               |

**Response 201:**
```json
{
  "id": 2,
  "name": "Marko Radnik",
  "email": "marko@example.com",
  "role": "worker",
  "client_id": 1,
  "worker_profile": {
    "id": 1,
    "user_id": 2,
    "phone": "+38161000000"
  }
}
```

---

### GET /workers/{id}

Detalji jednog workera. Klijent može vidjeti samo svoje workere (inače 403).

**Response 200:** isti format kao u listi.

**Response 403:** ako worker ne pripada klijentu.

---

### PUT /workers/{id}

Ažuriranje workera. Sva polja su opcionalna.

**Request body:**
```json
{
  "name": "Novo Ime",
  "email": "novi@example.com",
  "phone": "+38162000000",
  "can_edit_slots": true
}
```

Radnik dobija nalog samo preko pozivnice na email:
- Novi ili promenjen `email` radnika koji još nema nalog (nije postavio lozinku niti se prijavio Google-om) šalje novu pozivnicu, a stara prestaje da važi. `email: null` tada poništava pozivnicu.
- Radniku koji već ima nalog promena emaila ne šalje pozivnicu (prijavljuje se novim emailom), a brisanje emaila vraća 422.

**Response 200:** ažurirani worker objekat.

**Response 403:** ako worker ne pripada klijentu.

**Response 422:** brisanje emaila radniku koji već ima nalog.

---

### GET | PUT | DELETE /workers/{id}/schedule

**Auth required.** Stalni raspored radnika. Sme vlasnik, a radnik samo svoj i uz `can_edit_slots`.

PUT (isto telo kao `POST /slots/bulk`, bez radnika i datuma):
```json
{ "weekdays": [1, 2, 3, 4, 5], "start": "09:00", "end": "17:00", "slot_duration": 30, "breaks": [{ "start": "12:00", "end": "12:30" }] }
```

- Odmah pravi termine za narednih 4 nedelje (`weeks_ahead`), a komanda `slots:extend-schedules` (svaki dan u 01:00) ih dopunjuje dan po dan, samo za firme sa važećom pretplatom i radnike koji nisu suspendovani.
- Pri promeni rasporeda brišu se budući termini starog rasporeda koji nemaju rezervaciju i nisu blokirani, pa se prave novi. Ručni termini, blokirani i oni sa rezervacijom ostaju.
- Termin koji je vlasnik obrisao ne nastaje ponovo (komanda pravi samo dane posle `generated_until`).
- DELETE: raspored prestaje da pravi termine, postojeći termini ostaju.

**Response 200 (PUT):**
```json
{ "worker_id": 2, "weekdays": [1, 2, 3, 4, 5], "start": "09:00", "end": "17:00", "slot_duration": 30, "breaks": [...], "weeks_ahead": 4, "generated_until": "2027-01-03", "updated_at": "...", "generated": { "created": 280, "skipped": 0, "removed": 0 } }
```
GET vraća raspored ili `null`.

---

### DELETE /workers/{id}

Briše workera. Soft delete ne postoji — trajan bris.

**Response 204:** (no content)

**Response 403:** ako worker ne pripada klijentu.

---

## Slots

### GET /slots/available

**Javni endpoint.** Lista slobodnih termina.

**Query parametri:**

| Parametar | Tip    | Obavezno | Napomena                  |
|-----------|--------|----------|--------------------------|
| date      | date   | ne       | Format: `YYYY-MM-DD`     |
| worker_id | int    | ne       | Filtrira po workeru      |

**Primjer:** `GET /api/slots/available?date=2026-05-25&worker_id=2`

**Response 200:**
```json
[
  {
    "id": 10,
    "worker_id": 2,
    "date": "2026-05-25",
    "start_time": "09:00:00",
    "end_time": "09:30:00",
    "is_available": true,
    "worker": {
      "id": 2,
      "name": "Marko Radnik"
    }
  }
]
```

---

### GET /slots

**Auth required.** Termini koje korisnik sme da vidi (vlasnik: svi njegovi radnici, radnik: samo svoji), uključujući zauzete i blokirane.

| Query      | Napomena |
|------------|----------|
| from, to   | `Y-m-d`, opseg datuma (uključivo) |
| worker_id  | samo jedan radnik (ne može da proširi vidljivi skup) |
| per_page   | 1–500; ako je zadat, odgovor je straničen (`data`, `links`, `meta`), inače običan niz |

**Response 200:**
```json
[
  {
    "id": 10,
    "worker_id": 2,
    "date": "2026-05-25",
    "start_time": "09:00:00",
    "end_time": "09:30:00",
    "is_available": false,
    "is_blocked": false,
    "worker": { ... },
    "booking": { ... }
  }
]
```

---

### POST /slots

**Auth required.** Kreira novi termin za workera.

**Request body:**
```json
{
  "worker_id": 2,
  "date": "2026-05-25",
  "start_time": "09:00",
  "end_time": "09:30"
}
```

| Polje      | Tip    | Obavezno | Napomena                        |
|------------|--------|----------|---------------------------------|
| worker_id  | int    | da       | mora biti workerov klijenta     |
| date       | date   | da       | ne može biti u prošlosti        |
| start_time | string | da       | Format: `HH:MM`                 |
| end_time   | string | da       | mora biti nakon `start_time`    |

**Response 201:**
```json
{
  "id": 10,
  "worker_id": 2,
  "date": "2026-05-25",
  "start_time": "09:00:00",
  "end_time": "09:30:00",
  "is_available": true
}
```

**Response 403:** ako worker ne pripada klijentu.

---

### POST /slots/bulk

**Auth required.** Grupno pravljenje termina u jednoj transakciji (onboarding, generator).

```json
{
  "worker_id": 2,
  "date_from": "2026-12-07",
  "date_to": "2026-12-31",
  "weekdays": [1, 2, 3, 4, 5],
  "start": "09:00",
  "end": "17:00",
  "slot_duration": 30,
  "breaks": [{ "start": "12:00", "end": "12:30" }]
}
```

- `weekdays`: 1 = ponedeljak … 7 = nedelja. Termini idu redom od `start`; termin koji seče pauzu se ne pravi (isto kao `lib/slotGenerator.ts`).
- Termin koji se preklapa sa postojećim terminom radnika se preskače; današnji termini koji su već počeli se ne prave.
- Najviše 3000 termina po zahtevu (inače 422).

**Response 201:** `{ "created": 120, "skipped": 4 }`

**Response 403:** radnik ne pripada klijentu (radnik sme samo sebi, uz `can_edit_slots`).

---

### PATCH /slots/{id}

**Auth required.** Blokiranje termina: `{ "is_blocked": true }`. Blokiran termin ostaje u kalendaru, ali se ne nudi na `/slots/available` i `/slots/times` i ne može se rezervisati; postojeće rezervacije u njemu ostaju.

**Response 200:** termin. **403:** vlasnik drugog radnika, ili radnik bez `can_edit_slots` / tuđi termin.

---

### DELETE /slots/{id}

**Auth required.** Briše termin.

**Response 204:** (no content)

**Response 403:** ako slot ne pripada klijentu (radnik sme svoj termin uz `can_edit_slots`).

---

## Bookings

### POST /bookings

**Javni endpoint.** Krajnji korisnik kreira rezervaciju.

**Request body:**
```json
{
  "slot_id": 10,
  "customer_name": "Petar Petrović",
  "customer_email": "petar@example.com",
  "customer_phone": "+38163000000",
  "note": "Molim da dođem malo ranije."
}
```

| Polje          | Tip    | Obavezno | Napomena              |
|----------------|--------|----------|-----------------------|
| slot_id        | int    | da       | mora biti dostupan    |
| customer_name  | string | da       | max 255 znakova       |
| customer_email | string | ne       | ako postoji, šalje se email potvrde |
| customer_phone | string | ne       |                       |
| note           | string | ne       |                       |

**Response 201:**
```json
{
  "id": 5,
  "slot_id": 10,
  "customer_name": "Petar Petrović",
  "customer_email": "petar@example.com",
  "customer_phone": "+38163000000",
  "token": "Xk7a9b...",
  "status": "confirmed",
  "note": "Molim da dođem malo ranije.",
  "slot": {
    "id": 10,
    "date": "2026-05-25",
    "start_time": "09:00:00",
    "end_time": "09:30:00",
    "worker": { ... }
  }
}
```

**Response 409:** ako je termin već zauzet.

> Napomena: Nakon kreiranja, sistem automatski šalje email potvrde korisniku (ako ima email) i klijentu (firmi).

---

### GET /bookings/{token}

**Javni endpoint.** Korisnik vidi svoju rezervaciju putem tokena.

**Response 200:** isti format kao u POST /bookings response.

**Response 404:** ako token ne postoji.

---

### PATCH /bookings/{token}/customer-reschedule

**Javno (kupac, po tokenu).** `{ "slot_id": 12, "start_time": "14:00" }`.

- Samo vremena koja se nude i pri zakazivanju (`/slots/times`); radnik mora da radi uslugu.
- Najkasnije `reschedule_cutoff_hours` sati pre početka (podrazumevano 6, vlasnik menja u `PATCH /client/settings`). Rok i da li je pomeranje sada moguće vraćaju `GET /bookings/{token}` (`can_reschedule`, `reschedule_cutoff_hours`) i `/tenant/config`.
- U režimima `owner` i `worker` rezervacija ponovo čeka potvrdu i zahtev ide vlasniku ili radniku; u režimu `auto` ostaje potvrđena.

**Response 200:** rezervacija. **404:** token ne postoji ili rezervacija nije aktivna. **409:** vreme nije slobodno. **422:** prošao je rok ili radnik ne radi uslugu.

---

### PATCH /bookings/{token}/cancel

**Javni endpoint.** Korisnik otkazuje rezervaciju putem tokena.

Može se otkazati samo rezervacija sa statusom `confirmed`.

**Response 200:**
```json
{ "message": "Rezervacija otkazana" }
```

**Response 404:** ako token ne postoji ili status nije `confirmed`.

---

### GET /bookings

**Auth required.** Rezervacije koje korisnik sme da vidi (vlasnik: svi njegovi radnici, radnik: samo svoje), uvek sa `slot.worker` i `service`.

| Query      | Napomena |
|------------|----------|
| from, to   | `Y-m-d`, datum termina (uključivo) |
| worker_id  | samo jedan radnik (ne može da proširi vidljivi skup) |
| status     | `pending`, `confirmed`, `rejected`, `cancelled` |
| per_page   | 1–200; ako je zadat, odgovor je straničen (`data`, `links`, `meta`), inače običan niz |

**Response 200:**
```json
[
  {
    "id": 5,
    "slot_id": 10,
    "customer_name": "Petar Petrović",
    "customer_email": "petar@example.com",
    "customer_phone": "+38163000000",
    "token": "Xk7a9b...",
    "status": "confirmed",
    "note": null,
    "slot": {
      "id": 10,
      "date": "2026-05-25",
      "start_time": "09:00:00",
      "end_time": "09:30:00",
      "worker": { ... }
    }
  }
]
```

---

### PATCH /bookings/{token}/confirm i /reject

**Auth required.** Potvrda ili odbijanje rezervacije koja čeka potvrdu. Sme vlasnik, a u režimu potvrde `worker` i radnik kod koga je rezervacija.

**Response 200:** `{ "message": "..." }`. **403:** nema pravo. **404:** token ne postoji. **422:** rezervacija ne čeka potvrdu.

---

### PATCH /bookings/{token}/reschedule

**Auth required.** Pomera aktivnu rezervaciju: `{ "slot_id": 12, "start_time": "15:00" }`.

- Sme vlasnik (na termin bilo kog svog radnika koji radi tu uslugu), a u režimu `worker` i radnik kod koga je rezervacija (samo na svoj termin).
- Usluga mora da stane u slobodno vreme od `start_time` (njeno staro vreme se ne računa kao zauzeto); vreme ne mora biti jedno od ponuđenih početaka. Provera u transakciji sa zaključanim danima radnika.
- Status ostaje isti; kupac dobija push i mejl (`BookingRescheduledCustomer`).

**Response 200:** rezervacija. **403:** nema pravo. **409:** vreme nije slobodno ili je prošlo. **422:** rezervacija nije aktivna ili radnik ne radi uslugu.

---

## Status kodovi (pregled)

| Kod | Značenje                              |
|-----|---------------------------------------|
| 200 | OK                                    |
| 201 | Kreiran                               |
| 204 | Uspješno, nema sadržaja               |
| 401 | Nije autentikovan                     |
| 403 | Nema pristupa (tuđi resurs)           |
| 404 | Resurs nije pronađen                  |
| 409 | Konflikt (npr. termin već zauzet)     |
| 422 | Validacijska greška                   |

---

## Booking statusi

| Status      | Opis                                         |
|-------------|----------------------------------------------|
| `confirmed` | Rezervacija aktivna (default po kreiranju)   |
| `cancelled` | Otkazana od strane korisnika                 |
