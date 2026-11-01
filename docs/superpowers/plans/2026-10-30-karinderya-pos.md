# Karinderya POS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a complete Laravel/Blade/Tailwind POS for a Filipino karinderya: fast cashier terminal, admin dashboard, products, categories, inventory, sales, users, settings, receipts.

**Architecture:** Server-rendered Blade pages under two role-aware shells. A same-origin JSON endpoint feeds the POS product grid while Alpine keeps the cart; checkout posts ids/quantities only and a service recalculates everything inside a locked DB transaction. Sales snapshot item data. Admin-only mutations sit behind middleware + policies + form requests; sensitive changes write audit records.

**Tech Stack:** Laravel 12.70, PHP 8.4, MySQL/MariaDB 10.4, Blade, Tailwind CSS 4 (Vite), Alpine.js 3, Lucide icons (inline server-rendered), DomPDF.

**Spec:** `docs/superpowers/specs/2026-10-30-karinderya-pos-design.md`

## Global Constraints

- Money: `decimal(12,2)` DB columns; compute in integer cents; format as `₱1,234.00`.
- Quantities: `decimal(12,3)`; kilograms may be fractional, all other units whole numbers.
- Sale item snapshots are immutable. Never join live products for receipt prices.
- Every checkout mutation is one transaction; lock product rows; idempotency key unique.
- Admin-only routes: `auth` + `active` + `admin`; cashier receipt access via policy.
- Timezone `Asia/Manila`; currency PHP; no unlicensed SF font files; no demo stats.
- No React/Vue/Inertia/separate API. Vanilla JS + Alpine only.

## Review Focus

- Double-click checkout must not create two sales or double-decrement stock.
- Manipulated client prices/totals must be ignored; server price is authoritative.
- Concurrent checkouts must not oversell tracked stock.
- Inactive users must lose access immediately, not only at next login.
- Last active administrator must never be deactivated/demoted/removed.
- Decimal kilogram quantities must validate, calculate, snapshot, and display correctly.

---

### Task 1: Project foundation, database, design tokens

**Files:** `.env`, `.env.example`, `config/app.php`, `phpunit.xml`, `resources/css/app.css`, `resources/js/app.js`, `package.json`, `vite.config.js`, `app/Support/helpers.php`, `app/Support/Icons.php`, `composer.json`.

**Interfaces:**
- Produces: `setting(string $key, mixed $default = null): mixed`, `peso(string|int|float|null $amount): string`, `quantity(decimal|string|float $value): string`, `App\Support\Icons::svg(string $name): string`.
- Produces Tailwind tokens: `orange`, `orange-dark`, `orange-soft`, `ink`, `muted`, `line`, `success`, `warning`, `danger`; utility classes `.card`, `.btn`, `.input`, `.label`, `.badge`, `.table-*`.

- [x] Configure MySQL (`karinderya_pos`, test `karinderya_pos_test`), app name, `Asia/Manila`, session hardening.
- [x] Install Alpine + lucide-static + DomPDF; generate icon map from lucide-static for required icons.
- [x] Write design tokens/components in `app.css`; font stacks for Display/Text/Compact with fallbacks.
- [x] Add helper functions and autoload.
- [x] Build assets; `php artisan about` shows mysql and Asia/Manila.

### Task 2: Schema, models, factories, seeders

**Files:** `database/migrations/*`, `app/Models/*`, `database/factories/*`, `database/seeders/*`.

**Interfaces:**
- Models with `$fillable`, decimal casts, relationships:
  `User` hasMany sales; `Category` hasMany products; `Product` belongsTo category, hasMany saleItems/inventoryMovements/priceHistories; `Sale` belongsTo cashier, hasMany items, hasOne payment; `SaleItem` belongsTo sale/product; `Payment` belongsTo sale; `InventoryMovement` belongsTo product/user/sale; `ProductPriceHistory` belongsTo product/changer; `AuditLog` belongsTo user.
- `Product::isLowStock(): bool`, `Product::requiresWholeQuantity(): bool`; `Sale::scopeCompleted()`; `User::isAdmin(): bool`.
- Seeders: admin + cashier, 10 categories, starter products, business defaults. No sales.

- [x] Modify users migration (username, role, is_active, last_login_at, softDeletes).
- [x] Create 9 domain tables with FKs, indexes, uniques (reference, transaction_number, idempotency_key, category slug).
- [x] Models/casts/factories; `DatabaseSeeder` idempotent-ish for fresh installs.
- [x] `php artisan migrate:fresh --seed` succeeds; relationships verified.

### Task 3: Authentication, roles, shells

**Files:** `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Middleware/{EnsureUserIsAdmin,EnsureUserIsActive}.php`, `bootstrap/app.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `resources/views/layouts/{app,pos,auth}.blade.php`, `resources/views/auth/login.blade.php`, partials sidebar/topbar/flash/confirm/toast.

**Interfaces:**
- Routes: `GET/POST /login`, `POST /logout`, `GET /` role redirect.
- Middleware aliases `admin`, `active`; gates `apply-discount` (admin) and `view-sale` (policy).
- Login accepts `login` as email OR username, throttle 5/min, rejects inactive, sets last_login_at, regenerates session.
- `GET /dashboard` admin only; `GET /pos` both roles.

- [x] Login flow + show/hide password, loading state, inline errors.
- [x] Active-user middleware logs out deactivated sessions.
- [x] Admin shell with collapsible sidebar, role-filtered nav, topbar, toasts, confirm modal.
- [x] Tests: guest redirect, email login, username login, inactive denied, role redirects, cashier blocked from dashboard, admin allowed.

### Task 4: Dashboard

**Files:** `app/Http/Controllers/DashboardController.php`, `app/Services/SalesReportService.php`, `resources/views/dashboard.blade.php`, chart components.

**Interfaces:**
- `SalesReportService` methods: `summary(CarbonInterface $from, CarbonInterface $to): array` (gross, net, transactions, average, tax), `dailySeries($from,$to): array`, `categoryTotals($from,$to): Collection`, `topProducts($from,$to,int $limit): Collection`, `paymentMix($from,$to): Collection`.
- Period query `daily|weekly|monthly`; yesterday comparisons; KPI product/low-stock counts.

- [x] KPI cards with real queries and delta indicators.
- [x] Server-rendered SVG line/bar charts, period filter, empty states.
- [x] Test: seeded sales produce correct totals; empty DB yields zeroes/empty states.

### Task 5: Categories

**Files:** `CategoryController`, `StoreCategoryRequest`, `UpdateCategoryRequest`, `views/categories/index.blade.php`, policy/route bindings.

**Interfaces:** resource routes minus show; `POST categories/{category}/move/{direction}`; `PATCH categories/{category}/toggle`; deletion refused (`422`/redirect error) while products exist (including archived).

- [x] Inline create/edit panel, color picker, reorder up/down, activate/deactivate, delete guard.
- [x] POS categories derive from active DB rows sorted by sort_order.
- [x] Tests: create/update/reorder, delete blocked with products, delete allowed empty, cashier forbidden.

### Task 6: Products, images, price history

**Files:** `ProductController`, `StoreProductRequest`, `UpdateProductRequest`, `UpdateProductPriceRequest`, `ProductService`, `views/products/{index,create,edit,_form}.blade.php`, `ProductPriceHistory` writes.

**Interfaces:**
- Resource routes + `PATCH products/{product}/price`, `DELETE products/{product}` (archive), `PATCH products/{product}/availability`.
- `ProductService::updatePrice(Product $product, User $actor, string $newPrice, ?string $note): void` writes unchanged check, history, audit.
- SKU auto-generated when blank; unique validation ignores self; image path public disk; cost optional.

- [x] Listing search/sort/filter/pagination, thumbnails, stock badge, inline price modal with confirmation.
- [x] Create/edit form with units, tracking toggle, thresholds, availability.
- [x] Tests: validation, price history/audit, negative blocked, cashier forbidden, old sale snapshot unchanged.

### Task 7: Inventory

**Files:** `InventoryController`, `AdjustStockRequest`, `InventoryService`, `views/inventory/{index,movements}.blade.php`.

**Interfaces:** `GET inventory`, `GET inventory/movements`, `POST inventory/{product}/adjust`; service `adjust(Product, User, string $type, string $quantity, ?string $reason): InventoryMovement` transaction + lock + before/after + audit; types `stock_in|stock_out|adjustment`.

- [x] Stock table, low-stock filter/indicators, adjust modal, movement history with filters.
- [x] Tests: stock in/out/set adjustment, negative stock rejected, movement recorded, cashier forbidden.

### Task 8: POS terminal UI + product feed

**Files:** `PosController`, `resources/views/pos/index.blade.php`, `resources/js/app.js` (pos terminal component), `resources/css/app.css` POS styles.

**Interfaces:** `GET pos` renders shell/categories/settings/payment methods; `GET pos/products?search=&category=&page=` JSON `{data:[{id,name,sku,category,price,formatted_price,unit,image,track_inventory,stock_remaining,is_available}], next_page, total}`; client cart `{product_id,name,price_cents,qty,unit,stock}`; checkout POST.

- [x] Search debounce, category chips, responsive product grid, stock badges, unavailable disabled.
- [x] Persistent cart panel/drawer, qty +/- (whole vs kg step), remove, clear confirm, totals.
- [x] `/` focuses search, Esc closes modals, Enter adds focused product; toasts; cart count.
- [x] Mobile cart drawer; desktop persistent panel.

### Task 9: Checkout service + endpoint

**Files:** `CheckoutRequest`, `CheckoutService`, `PosController@checkout`, tests.

**Interfaces:** `CheckoutService::checkout(User $cashier, array $payload): Sale`; payload keys `items[{product_id,quantity}]`, `payment_method`, `amount_tendered`, `reference_number?`, `discount_type?`, `discount_value?`, `notes?`, `idempotency_key`.
- Validates active/available products, quantities, stock with `lockForUpdate`, admin discount, tendered, settings payment method/tax.
- Returns existing sale for repeated idempotency key (same cashier), unique reference/transaction number.
- JSON success `{sale:{reference,transaction_number,total,...}, receipt_html, receipt_url}`; validation errors 422; failures keep cart.

- [x] Checkout modal: method cards, tendered, change, reference, server error display, loading.
- [x] Tests: totals ignore client payload values, stock decrement/movement, insufficient cash/stock, cashier discount denied, idempotent duplicate, failed checkout leaves stock intact.

### Task 10: Receipts

**Files:** `ReceiptController`, `SalePolicy`, `views/receipts/{show,pdf}.blade.php`, `resources/views/partials/receipt-body.blade.php`, print CSS.

**Interfaces:** `GET receipts/{sale:reference}` (policy), `GET receipts/{sale:reference}/pdf`, `?width=58|80`. Receipt data from saved sale/items/payment. PDF DomPDF 80mm.

- [x] Receipt layout with business identity, items, totals, payment, footer, thank-you.
- [x] Print hides chrome; 58/80mm switch; PDF download; success modal injects server-rendered body.
- [x] Tests: owner/admin access, other cashier 403, PDF response, totals stable after price change.

### Task 11: Sales, transaction detail, CSV

**Files:** `SaleController`, `views/sales/{index,show}.blade.php`, export route, `SaleExport`.

**Interfaces:** `GET sales` (admin all, cashier own) with search/date/cashier/method/sort; `GET sales/{sale:reference}`; `GET sales/export` admin CSV with filters.

- [x] Listing cards/table, filters, pagination, item summary, status badge.
- [x] Detail page with items/payment/audit, receipt link.
- [x] Tests: cashier sees only own, admin sees all, export auth.

### Task 12: User management

**Files:** `UserController`, requests, `views/users/{index,create,edit,_form}.blade.php`, `UserService`/controller guards.

**Interfaces:** resource routes except show; `PATCH users/{user}/status`; `PATCH users/{user}/password`; last-active-admin guard on delete/deactivate/demote; audit every change.

- [x] Search/filter list, role badges, active toggles, last login, reset password modal.
- [x] Prevent self-deactivation if last admin, prevent removing last active admin, confirmation dialogs.
- [x] Tests: create/edit, role change audit, last-admin protection, password reset, cashier forbidden.

### Task 13: Settings, audit log, reports

**Files:** `SettingController`, `AuditLogController`, `ReportController`, `views/settings/index.blade.php`, `views/audit-logs/index.blade.php`, `views/reports/index.blade.php`, CSV export.

**Interfaces:** `GET/PUT settings`; `GET audit-logs` admin; `GET reports?from&to`; `GET reports/export`. Settings keys listed in design; logo upload; payment method toggles at least one required; tax toggle/rate/inclusive; timezone; low-stock default.

- [x] Tabbed/sectioned settings, audit viewer with filters, report KPIs + charts + by-day/category/cashier/method/product tables, CSV.
- [x] Tests: settings validation/persistence, cashier forbidden, reports totals, audit entries.

### Task 14: Polish, verification, docs

**Files:** `README.md`, error views, pagination view, all views reviewed.

- [x] Consistent empty/loading/error states, focus rings, responsive checks.
- [x] `php artisan test`, `npm run build`, `php artisan route:list`, migrate fresh seed on MySQL.
- [x] Browser walkthrough: login both roles, POS checkout cash + digital, receipt/print/PDF, product price, inventory, user, settings, reports.
- [x] README: setup, XAMPP MySQL start, credentials, security notes, BIR disclaimer.
