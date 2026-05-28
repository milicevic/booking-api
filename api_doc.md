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

**Response 200:** ažurirani worker objekat.

**Response 403:** ako worker ne pripada klijentu.

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

**Auth required.** Lista svih slotova klijenta (uključuje i zauzete).

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

### DELETE /slots/{id}

**Auth required.** Briše termin.

**Response 204:** (no content)

**Response 403:** ako slot ne pripada klijentu.

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

**Auth required.** Lista svih rezervacija klijenta.

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

### PATCH /bookings/{token}/confirm

**Auth required.** Klijent ručno potvrđuje rezervaciju.

**Response 200:** ažurirani booking objekat.

**Response 404:** ako token ne postoji.

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
