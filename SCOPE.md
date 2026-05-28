# Booking SaaS — Scope Dokument

## Pregled sistema

Multi-tenant SaaS booking aplikacija sa odvojenim frontend i backend servisom.
Svaki tenant (klijent) dobija izolovanu instancu aplikacije na sopstvenoj subdomeni ili custom domeni.

---

## Arhitektura

- **Backend**: Laravel API (booking-api)
- **Frontend**: Vue 3 (booking-fe)
- **Multi-tenancy**: Subdomena ili custom domena per tenant
- **Auth**: Laravel Sanctum (token-based)
- **Notifikacije**: Push notifikacije (Web Push / Firebase FCM)
- **Mail**: Transakcioni mailovi (registracija, invite, deploy potvrda)

---

## Tipovi korisnika

| Tip | Opis |
|-----|------|
| `superadmin` | Vlasnik platforme, upravlja svim tenantima |
| `client` | Vlasnik biznisa, upravlja radnicima i slotovima |
| `worker` | Radnik klijenta, pregled termina |
| `end_user` | Krajnji korisnik, rezerviše termine (bez naloga) |

---

## Moduli

### 1. Tenant Management (Superadmin)

- Pregled svih tenanta sa subscription statusom
- Ručna promena subscription statusa: `trialing → active → expired → canceled`
- Prihvatanje zahteva za deploy (subdomena ili custom domena)
- Push notifikacija pri novom deploy zahtevu
- Dashboard sa deploy opcijom per tenant
- Podešavanje teme per tenant:
  - Logo upload
  - Primary i secondary boja
  - Naziv aplikacije
  - Subdomena / custom domena

### 2. Tenant Onboarding Flow

1. Potencijalni klijent popunjava **registracionu formu**:
   - Ime firme
   - Email
   - Password
   - Željene boje
   - Izbor teme (predefinisane opcije)
2. Sistem automatski:
   - Kreira tenant zapis u bazi
   - Kreira client nalog
   - Postavlja `subscription_status = trialing`, `trial_ends_at = now() + 7 dana`
   - Šalje **welcome mail** sa:
     - Login linkom
     - Uputstvom za korišćenje
     - Napomenom da je trial period (7 dana)
3. Klijent se loguje na svoj dashboard

### 3. Client Dashboard

- Upravljanje radnicima:
  - Kreiranje naloga za workere (šalje im invite mail sa linkom)
  - Pregled svih workera
- Konfiguracija slotova per worker:
  - Radni dani
  - Radno vreme
  - Pauze
  - Dužina slota (npr. 30min, 60min)
  - Vrste usluga koje worker nudi
  - Automatsko ili ručno odobravanje rezervacija
- **Deploy dugme**:
  - Klijent bira željenu subdomenu ili unosi custom domenu
  - Šalje zahtev adminu
  - Tenant status prelazi u `pending_deploy`
  - Superadmin dobija push notifikaciju
- Pregled svih rezervacija
- Odobravanje / odbijanje rezervacija (ako je ručni mod)
- Push notifikacije pri novoj rezervaciji (ručni mod)

### 4. Worker Onboarding Flow

1. Klijent kreira nalog za workera iz svog dashboarda
2. Worker dobija **invite mail** sa registracionim linkom
3. Worker popunjava svoje podatke i postavlja password
4. Worker se loguje na Worker Dashboard

### 5. Worker Dashboard

- Pregled kalendara sa terminima
- Detalji svake rezervacije (ime, kontakt, usluga, vreme)
- Prikaz slobodnih i zauzetih slotova

### 6. Booking Flow (Krajnji korisnik)

Korisnik dolazi na link aplikacije (npr. sa Instagrama):

1. Vidi **kalendar optimizovan za mobilni**
2. Iz padajućeg menija bira **radnika** (ako postoji više)
3. Bira **slobodan termin**
4. Iz padajućeg menija bira **uslugu**
5. Unosi **ime + email ili telefon**
6. Klikne **Rezerviši**

**Scenario A — Automatsko odobravanje:**
- Rezervacija se odmah beleži
- Korisnik dobija push notifikaciju: "Termin potvrđen"
- Rezervacija vidljiva u korisničkom kalendaru

**Scenario B — Ručno odobravanje:**
- Korisnik dobija push notifikaciju: "Zahtev poslat, čeka se odobrenje"
- Klijent dobija push notifikaciju: "Nova rezervacija čeka odobrenje"
- Klijent u dashboardu odobrava ili odbija
- Korisnik dobija push notifikaciju: "Termin potvrđen" ili "Termin odbijen"

### 7. Otkazivanje rezervacije (Krajnji korisnik)

- Korisnik u svom kalendaru pronalazi rezervaciju (po imenu + email/telefon)
- Klikne "Otkaži"
- Sistem šalje zahtev sa kombinacijom ime + kontakt
- Rezervacija se otkazuje
- Korisnik dobija potvrdu otkazivanja

### 8. Automatsko otkazivanje

- Scheduled job koji prolazi kroz rezervacije
- Ako je vreme termina prošlo i rezervacija nije završena → status `expired`

### 9. Subscription Management

| Status | Opis |
|--------|------|
| `trialing` | Prvih 7 dana, pun pristup |
| `active` | Plaćen, pun pristup |
| `expired` | Trial/subscription istekao, blokiran pristup |
| `canceled` | Ručno ugašen od strane admina |
| `pending_deploy` | Čeka deploy od admina |

- Uplata je **ručna** (klijent kontaktira admina, admin ručno menja status)
- Middleware blokira API pozive za `expired` i `canceled` tenante (vraća 403)
- Scheduled job menja `trialing → expired` kada istekne `trial_ends_at`

---

## Theming (White-label)

Per tenant se čuva:
- `app_name` — naziv aplikacije
- `logo_url` — logo
- `primary_color` — primarna boja (hex)
- `secondary_color` — sekundarna boja (hex)
- `theme` — izbor teme (npr. `minimal`, `modern`, `classic`)

FE pri učitavanju poziva `GET /api/tenant/config` i dinamički primenjuje CSS varijable.
Superadmin može da menja theming iz svog dashboarda per tenant.
Klijent može da menja theming iz svog dashboarda.

---

## Domenski model (tabele)

```
tenants
  - id, name, subdomain (unique), custom_domain (nullable)
  - app_name, logo_url, primary_color, secondary_color, theme
  - subscription_status (enum), trial_ends_at, subscription_ends_at
  - deploy_status (enum: pending, pending_deploy, deployed)
  - timestamps

users
  - id, tenant_id (FK), name, email, password
  - role (enum: superadmin, client, worker)
  - timestamps

worker_profiles
  - id, user_id (FK), tenant_id (FK)
  - bio, avatar_url
  - timestamps

slots
  - id, tenant_id (FK), worker_id (FK)
  - service_name, duration_minutes
  - working_days (JSON), start_time, end_time
  - break_start, break_end
  - auto_confirm (boolean)
  - timestamps

bookings
  - id, tenant_id (FK), slot_id (FK), worker_id (FK)
  - customer_name, customer_email, customer_phone
  - service_name, starts_at, ends_at
  - status (enum: pending, confirmed, rejected, canceled, expired)
  - timestamps

client_profiles
  - id, user_id (FK), tenant_id (FK)
  - business_name, auto_confirm_bookings
  - timestamps
```

---

## Notifikacije

| Događaj | Ko dobija | Tip |
|---------|-----------|-----|
| Rezervacija kreirana (auto) | Korisnik | Push |
| Rezervacija čeka odobrenje | Korisnik + Klijent | Push |
| Rezervacija odobrena/odbijena | Korisnik | Push |
| Rezervacija otkazana | Korisnik | Push |
| Novi deploy zahtev | Superadmin | Push |
| Deploy završen | Klijent | Push (+ Mail) |
| Welcome (registracija) | Klijent | Mail |
| Worker invite | Worker | Mail |
| Trial ističe (3 dana pre) | Klijent | Mail |

---

## Faze implementacije

### Faza 1 — Core Backend
- [ ] Tenant model + migracije
- [ ] Tenant middleware (resolve po subdomeni/domeni)
- [ ] Subscription middleware (blokada expired/canceled)
- [ ] User model refaktor (dodati tenant_id, role superadmin)
- [ ] Sve postojeće tabele dobijaju tenant_id + global scope
- [ ] Superadmin rute za upravljanje tenantima
- [ ] Subscription status management (ručni + scheduled job)

### Faza 2 — Onboarding Flow
- [ ] Registraciona forma (tenant kreiranje)
- [ ] Automatsko kreiranje subdomene
- [ ] Welcome mail
- [ ] Worker invite flow
- [ ] Deploy zahtev sistem

### Faza 3 — Booking Flow
- [ ] Javni API za prikaz slotova (bez auth)
- [ ] Kreiranje rezervacije (auto + ručni mod)
- [ ] Otkazivanje rezervacije
- [ ] Automatsko istekivanje termina (scheduled job)

### Faza 4 — Push Notifikacije
- [ ] Web Push integracija (Firebase FCM ili Vapid)
- [ ] Notifikacije za sve događaje iz tabele iznad

### Faza 5 — Theming + Custom Domena
- [ ] Tenant config API endpoint
- [ ] FE dinamički theming (CSS varijable)
- [ ] Custom domena podrška (Nginx/Caddy config)
- [ ] SSL automatizacija

---

## Napomene

- Sve rute osim javnog booking flow-a zahtevaju auth
- Superadmin nema tenant_id (null) i vidi sve
- Worker ne može da menja slotove, samo da ih pregleda
- Krajnji korisnik nema nalog, identifikuje se po imenu + kontaktu
- Trial period: 7 dana od registracije
- Naplata: ručna, admin menja status manuelno
