# Karinderya POS

Laravel 12 with Blade, Tailwind CSS 4, Alpine.js, Lucide icons, MySQL/MariaDB, and DomPDF. Node is used only to build frontend assets.

## Local setup

Requires PHP 8.2+ (PDO MySQL, mbstring, XML, GD, fileinfo), Composer 2, Node.js 20+, npm, and MySQL/MariaDB.

```powershell
composer install
npm ci
Copy-Item .env.example .env
php artisan key:generate
```

Start MySQL through the XAMPP control panel, or locally:

```powershell
Start-Process -FilePath 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList '--standalone','--console' -WindowStyle Hidden
```

Create `karinderya_pos` and configure `.env`:

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=karinderya_pos
DB_USERNAME=root
DB_PASSWORD=
```

Use a dedicated database account with a strong password outside local development.

```powershell
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open **http://127.0.0.1:8000**. For asset development run `npm run dev` separately.

### Local evaluation accounts

| Role | Username | Email | Password |
| --- | --- | --- | --- |
| Administrator | admin | admin@karinderya.test | password |
| Cashier | cashier | cashier@karinderya.test | password |

**Change both passwords before deployment.** Public registration is disabled. Administrators manage accounts and reset passwords from Users.

The seed creates starter categories/products and settings, but no demo sales. Manual checkout testing creates real local sales records.

## Features

- Throttled login, inactive-account blocking, administrator/cashier authorization
- Database-backed dashboard, trends and sales breakdowns
- Products/images, price history, category ordering, availability and archiving
- Stock-in/out/adjustments with reasons and movement history
- Searchable responsive POS, cart controls and administrator discounts
- Server-calculated checkout, stock locks, snapshots and idempotency protection
- Saved 58mm/80mm receipts, printing and PDF downloads
- Sales filters/detail, cashier-own history and administrator CSV exports
- Staff management, last-administrator protection, settings and audit viewer
- Date-range reports with daily/weekly/monthly trends and CSV export

Currency is PHP; timezone is Asia/Manila. Fractional quantities are supported for kilograms; other units use whole quantities. Completed sales cannot be edited, voided or refunded in this version.

## Verification

Create a separate empty database named `karinderya_pos_test`; `phpunit.xml` selects it automatically. Tests refresh it. Never configure tests against useful records.

```powershell
php artisan test
node --test tests/js/checkout-retry.test.mjs
npm run build
php artisan route:list
vendor/bin/pint --test
```

Do not run `migrate:fresh` against useful data: it deletes all tables and records. For ordinary updates use `php artisan migrate`.

## Deployment and operations

- Serve only `public/` over HTTPS. Artisan's server is for local development.
- Set `APP_ENV=production`, `APP_DEBUG=false`, the correct URL and secure database/session settings. Protect `.env` and `APP_KEY`.
- Install dependencies, build assets, migrate, create the storage link and allow the application account to write `storage/` and `bootstrap/cache/`.
- Replace seeded credentials/contact details. Back up the database and uploaded files, and test restoration.
- Run `php artisan optimize` after configuring production; clear cached configuration before changing environment settings.
- Audit records are read-only through the UI, not tamper-proof against database administrators. Restrict direct database access.

### Payment, tax and printer limitations

Digital payments are **recorded, not verified or processed**. Staff must independently confirm receipt of payment.

Optional tax is disabled by default; configure it only with appropriate accounting advice. Receipts are internal sales records. **This application is not represented as BIR-accredited or a compliant official-invoice system.**

Thermal printer margins vary. Select 58mm or 80mm, disable browser headers/footers and test your printer before live use.

Stock changes after product creation must go through Inventory, not product editing. If checkout reports an uncertain outcome, retry the unchanged order before starting another sale; do not refresh the page. The server rejects an idempotency key reused for a different order.

Category attribution is snapshotted for new sales. On upgrading an older database, existing items are backfilled from their currently known categories; previously changed category history cannot be reconstructed.
