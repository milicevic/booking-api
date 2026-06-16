# Booking SaaS — Scope Dokument

## Pregled sistema

Multi-tenant SaaS booking aplikacija sa odvojenim frontend i backend servisom.
Svaki tenant (klijent) dobija izolovanu instancu aplikacije na sopstvenoj subdomeni ili custom domeni.

---

## Arhitektura

- **Backend**: Laravel 13 API (`booking-api`)
- **Frontend**: Vue 3 (`booking-fe`)
- **Multi-tenancy**: Subdomena ili custom domena per tenant; middleware `resolve.tenant`
- **Auth**: Laravel Sanctum (token-based)
- **Mail**: Transakcioni mailovi (registracija, invite, booking notifikacije)
- **Lokalizacija**: `en` (default) i `sr`; jezik se bira putem `?locale=` ili `Accept-Language` headera

---

## Tipovi korisnika

| Tip | Opis |
|-----|------|
| `superadmin` | Vlasnik platforme, upravlja svim tenantima |
| `admin` | Platform-level admin, može da pregleda/suspenduje klijente |
| `client` | Vlasnik biznisa, upravlja radnicima, servisima i slotovima |
| `worker` | Radnik klijenta, pregleda termine i svoje servise |
| `end_user` | Krajnji korisnik, rezerviše termine (bez naloga) |

---

## Domenski model (implementirano)

```
tenants
  - id, name, subdomain (unique), custom_domain (nullable, unique)
  - app_name, logo_url, primary_color, secondary_color, theme
  - subscription_status (enum: trialing | active | expired | canceled)
  - trial_ends_at, subscription_ends_at
  - deploy_status (enum: pending | pending_deploy | deployed)
  - timestamps

users
  - id, tenant_id (FK, nullable za superadmin/admin)
  - name, email, password (nullable za workere bez lozinke)
  - role (enum: superadmin | admin | client | worker)
  - client_id (FK users, za workere — kojoj firmi pripadaju)
  - can_edit_slots (boolean), is_suspended (boolean)
  - invite_token (nullable, unique — za worker invite flow)
  - timestamps

worker_profiles
  - id, user_id (FK), tenant_id (FK)
  - phone
  - timestamps

client_profiles
  - id, user_id (FK), tenant_id (FK)
  - business_name
  - auto_confirm_bookings (boolean, default false)
  - timestamps

services
  - id, tenant_id (FK)
  - name, duration_minutes, price (nullable decimal)
  - is_active (boolean, default true)
  - timestamps

worker_services (pivot)
  - worker_id (FK users), service_id (FK services)
  - PRIMARY KEY (worker_id, service_id)

slots
  - id, tenant_id (FK), worker_id (FK users)
  - date, start_time, end_time
  - is_available (boolean)
  - timestamps

bookings
  - id, tenant_id (FK), slot_id (FK), service_id (FK services, nullable)
  - customer_name, customer_email (nullable), customer_phone (nullable)
  - token (unique, 64 chars)
  - status (enum: pending | confirmed | rejected | cancelled)
  - note (nullable)
  - timestamps
```

---

## Moduli — Implementirano

### Faza 1 — Core Backend ✅

- Tenant model + migracije
- `BelongsToTenant` trait — automatski filtrira po `tenant_id`, auto-setuje pri kreiranju
- `TenantScope` global scope — bypass za `superadmin`, bypass bez tenanta ako nema `current_tenant`
- `ResolveTenant` middleware — detektuje tenant po `custom_domain`, subdomeni, ili `X-Tenant-Subdomain` headeru
- `CheckSubscription` middleware — blokira `expired`/`canceled` tenante (403)
- `CheckSuperadmin` middleware — dozvoljava samo superadmin rolu
- `PersonalAccessToken` model — Sanctum token lookup bez global scope (za platform korisnike bez tenanta)
- Superadmin rute (`/api/superadmin/tenants/*`) — CRUD tenanta, upravljanje subscription i deploy statusom, theming
- Admin rute (`/api/admin/clients/*`) — pregled i suspenzija klijenata
- Scheduled job `php artisan subscriptions:expire-trials` — menja `trialing → expired` dnevno

### Faza 2 — Onboarding Flow ✅

- `POST /api/register` — kreira tenant + client nalog u transakciji, šalje welcome mail, vraća token
- Auto-generisanje jedinstvene subdomene iz naziva firme
- Worker invite flow:
  - `WorkerController::store` kreira workera sa `invite_token` (UUID)
  - Šalje `WorkerInvite` mail sa linkom
  - `POST /api/auth/accept-invite` — worker postavlja lozinku, vraća token
- Deploy zahtev: `POST /api/client/deploy-request` — menja `deploy_status` na `pending_deploy`, klijent može da doda subdomenu/custom domenu
- Client settings: `PATCH /api/client/settings` — update theming podataka

### Faza 3 — Servisi ✅

- Servisi su na nivou biznisa (tenant), ne per worker
- Klijent kreira/uređuje/briše servise za svog tenanta
- Klijent dodeljuje servise radnicima (`POST /api/services/{service}/workers/{worker}`)
- Klijent uklanja servis od radnika (`DELETE /api/services/{service}/workers/{worker}`)
- Worker ne može da kreira, menja ni briše servise — samo pregledava
- `GET /api/services?worker_id=X` — servisi koje određeni radnik pruža

### Faza 4 — Booking Flow ✅

**Javni API (bez auth):**
- `GET /api/slots/available?date=&worker_id=&service_id=` — slobodni termini; `service_id` filtrira termine po radnicima koji pružaju dati servis
- `POST /api/bookings` — kreiranje rezervacije
  - Opcioni `service_id` — validira se da radnik zaista pruža tu uslugu (via pivot)
  - Lock-for-update zaštita od race condition
  - Auto-confirm ako `client_profiles.auto_confirm_bookings = true`
- `GET /api/bookings/{token}` — detalji rezervacije
- `PATCH /api/bookings/{token}/cancel` — otkazivanje (samo `confirmed` rezervacije)
- `GET /api/bookings/{token}/confirm-by-link?action=confirm|reject&signature=...` — potvrda/odbijanje putem signed linka iz emaila

**Zaštićeni API (auth:sanctum):**
- `GET /api/bookings` — lista rezervacija (klijent vidi sve svoje, worker svoje, admin po `client_id`)
- `PATCH /api/bookings/{token}/confirm` — ručna potvrda (klijent)
- `PATCH /api/bookings/{token}/reject` — odbijanje (klijent)

### Faza 5 — Notifikacije (Mail) ✅

| Događaj | Ko dobija | Tip |
|---------|-----------|-----|
| Registracija tenanta | Klijent | Mail (`WelcomeClient`) |
| Worker invite | Worker | Mail (`WorkerInvite`) |
| Rezervacija kreirana (auto-confirm) | Korisnik + Klijent | Mail |
| Rezervacija čeka odobrenje | Korisnik + Klijent | Mail (sa signed linkovima za potvrdu/odbijanje) |
| Rezervacija potvrđena | Korisnik | Mail |
| Rezervacija odbijena | Korisnik | Mail |

### Faza 6 — Lokalizacija ✅

- Default jezik: `en`
- Podržani jezici: `en`, `sr`
- `lang/en/messages.php` i `lang/sr/messages.php` — API response poruke
- `lang/en/notifications.php` i `lang/sr/notifications.php` — sadržaj email notifikacija
- `SetLocale` middleware — čita `?locale=` query param ili `Accept-Language` header

### Faza 7 — Theming ✅

- `GET /api/tenant/config` — vraća theming podatke (app_name, boje, tema, subdomena, subscription_status)
- Superadmin menja theming per tenant
- Klijent menja vlastiti theming

---

## API Rute (pregled)

```
# Platform (bez tenanta)
POST   /api/register
POST   /api/auth/login

# Admin
GET    /api/admin/clients
PATCH  /api/admin/clients/{client}/suspend

# Superadmin
GET    /api/superadmin/tenants
GET    /api/superadmin/tenants/{tenant}
PATCH  /api/superadmin/tenants/{tenant}/subscription
PATCH  /api/superadmin/tenants/{tenant}/deploy-status
PATCH  /api/superadmin/tenants/{tenant}/theme

# Tenant config (opcioni tenant)
GET    /api/tenant/config

# Javne tenant rute (tenant obavezan)
GET    /api/slots/available
POST   /api/bookings
GET    /api/bookings/{token}
PATCH  /api/bookings/{token}/cancel
GET    /api/bookings/{token}/confirm-by-link
POST   /api/auth/accept-invite

# Zaštićene rute (auth, opcioni tenant)
POST   /api/auth/logout
GET    /api/auth/me
GET|POST|PUT|PATCH|DELETE  /api/workers
GET|POST|PUT|PATCH|DELETE  /api/slots
GET    /api/bookings
PATCH  /api/bookings/{token}/confirm
PATCH  /api/bookings/{token}/reject
PATCH  /api/client/settings
POST   /api/client/deploy-request
GET    /api/services
POST   /api/services
PATCH  /api/services/{service}
DELETE /api/services/{service}
POST   /api/services/{service}/workers/{worker}
DELETE /api/services/{service}/workers/{worker}
```

---

## Faze — Preostalo

### Push notifikacije (nije implementirano)
- Web Push integracija (Firebase FCM ili Vapid)
- Notifikacije pri novoj rezervaciji, potvrdi, odbijanju, deploy statusu

### Automatsko istekivanje rezervacija (nije implementirano)
- Scheduled job koji menja status u `expired` za prošle nepotvrđene rezervacije

### Otkazivanje po korisničkom kalendaru (nije implementirano)
- Korisnik pronalazi rezervaciju po imenu + email/telefon
- Mogućnost otkazivanja bez tokena

---

## Napomene

- Superadmin i Admin nemaju `tenant_id` (null) — slobodni su od tenanta
- Worker može da pregleda servise ali ne može da ih kreira/menja
- Krajnji korisnik nema nalog, identifikuje se tokenom iz emaila
- Trial period: 7 dana od registracije
- Naplata: ručna, admin menja subscription status manuelno
- Svi modeli sa `tenant_id` koriste `BelongsToTenant` trait i `TenantScope`
