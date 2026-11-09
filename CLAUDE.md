<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

## Pre završetka zadatka
Pokreni (u Docker kontejneru, vidi "Local dev" ispod):
docker compose exec app vendor/bin/pint --dirty --format agent && docker compose exec app vendor/bin/phpstan analyse --memory-limit=1G && docker compose exec app php artisan test --compact
Zadatak nije gotov dok sve ne prođe.
</laravel-boost-guidelines>

# Project: booking-api

Multi-tenant, white-label booking SaaS API. Frontend is the separate repo `booking-fe`. `SCOPE.md` describes the domain model and phases, `api_doc.md` the routes. SCOPE.md is partly outdated (push, Google auth, email verification and the notification list already exist in code), so trust the code over it.

## Local dev
- `docker-compose.yml`: app (php-fpm 8.5) + nginx (http://localhost:8000) + Postgres 17 + Mailpit (UI on :8025).
- The host PHP is 8.4 and `composer.json` requires ^8.5, so **every** `php` / `artisan` / `composer` / `vendor/bin/*` command runs in the container: `docker compose exec app <command>`. Running them on the host fails the platform check.
- Quality gate (end of the Boost block): Pint, then PHPStan (level 6, paths `app/`, `database/factories/`, `routes/`, needs `--memory-limit=1G`), then the test suite.
- Local mail goes to Mailpit, never to real inboxes. Production (Laravel Cloud) uses `MAIL_MAILER=resend`. `php artisan mail:test you@example.com` prints the mailer in use and the Resend email ID.
- Scheduled: `subscriptions:expire-trials` (daily, `routes/console.php`). Other commands: `mail:test` (`SendTestMail`), `media:move-to-disk` (`MoveMediaToDisk`).
- Boost lists skills this API doesn't use (Livewire, Volt, Flux, Inertia, Folio, Pennant, Pest, Wayfinder, Blade): ignore them. Tests are PHPUnit and there is no server-rendered UI.

## Architecture
- Layers: Controller (`app/Http/Controllers/Api`) → Service (`app/Services/<Area>`) → Repository (`app/Repositories/Eloquent`). Services and repositories are used through interfaces in `Services/Contracts` and `Repositories/Contracts`.
- Every new contract MUST be bound in `DomainServiceProvider` (services) or `RepositoryServiceProvider` (repositories). `tests/Feature/Architecture/ContainerBindingsTest.php` fails otherwise.
- Validation: Form Requests in `app/Http/Requests/<Area>`. Responses: API Resources in `app/Http/Resources`, never raw models. Authorization: Policies in `app/Policies`.
- Mail and in-app notifications: classes in `app/Notifications`; texts live in `lang/{en,sr}/notifications.php`. They are sent synchronously (no `ShouldQueue`).
- Expected business errors: throw `App\Exceptions\DomainException::unprocessable()` / `conflict()` / `notFound()` from services instead of building responses there.
- Resources are not wrapped in `data` (`JsonResource::withoutWrapping()`). Public (no-auth) endpoints use `Public*Resource` classes, e.g. `PublicSlotResource` / `PublicWorkerResource`, so worker email, phone and account fields never reach customers. Don't reuse `UserResource` publicly.
- Keep controllers thin. Don't add a new layer or base folder; follow the sibling files.

## Multi-tenancy (don't break these)
- `ResolveTenant` middleware (`resolve.tenant`, mode `required` or `optional`) finds the tenant by request host (custom domain / subdomain) or the `X-Tenant-Subdomain` header, and stores it in `CurrentTenantInterface`.
- Every model with `tenant_id` uses the `BelongsToTenant` trait + `TenantScope` (auto-filter and auto-set `tenant_id`). Scope is bypassed for `superadmin` and when there is no current tenant. Don't hand-write `where('tenant_id', ...)` instead of the trait, and don't use `withoutGlobalScopes()` without a comment saying why.
- `superadmin` and `admin` have `tenant_id = null`. `PersonalAccessToken` is a custom model so Sanctum lookups ignore the tenant scope.
- `CheckSubscription` returns 403 for `expired` / `canceled` tenants. `CheckSuperadmin` guards `/api/superadmin/*`. `HandleTenantCors` handles per-tenant CORS.
- Every new endpoint or model that touches tenant data needs a test proving tenant A can't see tenant B's data.

## Time and dates
- Slot `date` / `start_time` / `end_time` are the salon's local wall-clock time. `APP_TIMEZONE` defaults to `Europe/Belgrade` (`config/app.php`) so `now()` / `today()` compare correctly. With UTC, slots that already passed locally get offered.
- Serialize date-only values as `Y-m-d` (`$slot->date->toDateString()`), never as a Carbon object: JSON turns it into a UTC instant and local midnight becomes the previous day.
- Tests that depend on the clock use `$this->travelTo(Carbon::parse('…', 'Europe/Belgrade'))`.

## Auth
- Sanctum bearer tokens. Login checks go through `User::loginDeniedReason()` (`suspended`, `email_not_verified`, `wrong_tenant`), shared by password and Google login.
- Password accounts must verify their email. Registration sends `VerifyEmailAddress` and doesn't issue a token. The signed link `GET /api/auth/email/verify/{id}/{hash}` (route `verification.verify`) redirects to `/admin/login?verified=1|invalid` on the tenant site, and `WelcomeClient` is sent after verification. Accepting a worker invite, Google login and superadmin-created accounts count as verified.
- Google login is stateless Socialite with one central callback (`/api/auth/google/callback`). The original host, intent and expiry travel in an encrypted `state`, and the result is redirected to `{host}/auth/callback#token=…` or `#error=<code>` (fragment only). Accounts are created only in the `register` intent, via `TenantRegistrationServiceInterface::createWithClient()`; never create a user without `tenant_id` from a public flow.

## Roles (`UserRole` enum)
`superadmin` (platform owner), `admin` (platform admin: list/suspend clients), `client` (business owner), `worker` (belongs to a client via `client_id`), plus `end_user` (no account, identified by the booking token).
- Workers can view services and bookings but can't create/edit/delete services. Slot editing only when `can_edit_slots`.
- Services are tenant-level and attached to workers through the `worker_services` pivot.
- A worker may edit only their own bio and photo (`UserPolicy::updateProfile`, `PATCH /workers/{id}/profile`, `POST|DELETE /workers/{id}/photo`). Name, email and permissions stay client-only (`PUT /workers/{id}`).

## Bookings
The full booking flow and its rules are in `docs/booking-flow.md`; update it whenever booking behaviour changes.
- Public endpoints (tenant required, no auth): available slots, create booking, get / cancel by token, confirm-by-link (signed URL).
- Creating a booking must stay inside a transaction with `lockForUpdate()` on the worker's slots (double-booking protection, see `EloquentSlotRepository`).
- `service_id` is required on every booking and must be an active service the slot's worker provides (pivot). There are no bookings without a service anymore (older rows may still have `service_id = null`).
- Bookable start times come from `SlotAvailabilityService`: consecutive slots merge into blocks, active bookings are subtracted, and starts are offered at slot starts and booking ends wherever the service duration fits. `GET /api/slots/times` requires `worker_id` and `service_id`; each item carries `worker_id`.
- `client_profiles.auto_confirm_bookings = true` → status starts `confirmed`, otherwise `pending`. Statuses: `BookingStatus` enum (`pending | confirmed | rejected | cancelled`). Customers can cancel `pending` or `confirmed` bookings (`BookingStatus::active()`).

## Media
- Logos and worker photos go through `MediaStorageInterface` onto `config('filesystems.media_disk')` (`MEDIA_DISK`): locally `public`, which stores relative `/storage/...` URLs proxied by the frontend; in production `s3` pointed at Cloudflare R2, which stores absolute URLs. Images are re-encoded to WebP with Intervention Image.
- Laravel Cloud's disk is wiped on deploy, so never write user uploads to the local disk directly.

## Localization
- `en` (default) and `sr`. Locale comes from `?locale=` or `Accept-Language` (`SetLocale` middleware).
- Every new user-facing string needs both languages: `lang/en/*.php` and `lang/sr/*.php` (plus `lang/sr.json` if it's a JSON string key). Emails use the recipient's locale (`MailLocaleTest`).

## Testing
- PHPUnit classes only (no Pest). Base `Tests\TestCase` creates an active tenant with subdomain `localhost` and binds it as `current_tenant`, so most feature tests run as that tenant. Use factories and their states.
- Tests mirror features under `tests/Feature/<Area>`. Priorities: tenant isolation, booking slot conflicts (second booking of the same slot is rejected), role/policy checks.
- True concurrent race-condition tests are out of scope; the `lockForUpdate` logic is covered by a sequential conflict test.

## Not done yet (verify in code before relying on it)
- Job that expires past unconfirmed bookings.
- Cancelling a booking without a token (lookup by name + email/phone).
- No payment gateway by design: the superadmin changes `subscription_status` manually.

## Don'ts
- Don't push to `main`, run destructive migrations, or add dependencies without asking.
- Don't over-engineer: smallest change that fits the existing patterns.
