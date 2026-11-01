# Karinderya POS — Design

## Intent

A production-quality point-of-sale system for a small Filipino karinderya. Two interfaces:

- **Admin dashboard** — sales visibility, products, categories, inventory, staff, reports, settings.
- **POS terminal** — a fast, touch-friendly checkout screen for cashiers.

The people using it may not be technical. Every screen must be legible, forgiving, and fast.
Money must always be calculated on the server. A completed sale is a permanent record.

## Non-goals

- Public registration or customer accounts.
- Online ordering, delivery, or a customer-facing storefront.
- Real GCash/Maya payment verification or provider integration.
- BIR-compliant invoicing. The receipt is an internal sales receipt only.
- Refunds/voids. Completed sales are immutable in this version.

## Stack and constraints

- Laravel 12, Blade, Tailwind CSS 4, Alpine.js, MySQL (MariaDB 10.4 via XAMPP), Lucide icons.
- No SPA framework, no separate backend/API server. POS filtering uses same-origin Laravel JSON endpoints.
- Server-side session auth, CSRF, policies/gates, middleware, form requests.
- Decimal(12,2) money columns; sale items snapshot name, sku, unit, price, quantity, line total.
- PHP 8.4. Node used only to build assets.

## Visual language

Orange `#F97316`, dark `#EA580C`, tint `#FFF7ED`; page `#F8FAFC`, cards white, ink `#1E293B`,
muted `#64748B`, border `#E2E8F0`, success `#16A34A`, warning `#F59E0B`, danger `#DC2626`.

SF Pro Display for headings and hero numbers, SF Pro Text for UI copy, SF Compact for dense
numbers/receipts, with `-apple-system, BlinkMacSystemFont, "Segoe UI", ...` fallbacks. Do not
ship unlicensed Apple font files.

8–12px radii, subtle borders, restrained shadows, 44px+ touch targets in POS, visible focus rings.

## Roles

- **Admin** — everything, including prices, stock, staff, settings, all sales, exports.
- **Cashier** — POS, checkout, own receipts, own transaction history. No admin routes.
- Enforced by `admin` middleware plus policies; hidden nav is not the control.
- Inactive accounts cannot sign in and are logged out on their next request.

## Core flows

### Login
Email or username + password, show/hide password, remember me, throttle 5/min per login+IP.
Inactive accounts rejected with a clear message. Redirect admin → dashboard, cashier → POS.

### Checkout
1. Cashier builds cart client-side (product id + quantity only).
2. Checkout sends items, payment method, tendered amount, reference, optional admin discount, notes,
   and an idempotency key.
3. Server locks products, re-reads prices, validates availability and stock, computes line totals,
   discount, tax, total and change in integer cents. Client prices are never accepted.
4. One DB transaction creates sale, items, payment, inventory movements, updates stock, writes audit.
5. Unique sale reference (ULID) and human transaction number. Duplicate submit returns the same sale.
6. Receipt is rendered from the saved record.

### Discounts
Admin-only. Fixed peso or percentage, never below zero or above subtotal. Cashiers cannot submit one.

### Inventory
Only products with `track_inventory` are checked/decremented. Quantities decimal(12,3) to support
kilograms; non-kg units must use whole numbers. Adjustments lock the product row, write before/after,
movement type/reason, and audit.

### Pricing
Admins edit price directly from the product list behind a confirmation. Every change writes
`product_price_histories` and an audit entry. Sale items keep their snapshot forever.

### Receipts
Standalone screen with print CSS for 58mm/80mm, PDF download (DomPDF), and links to the transaction.
No customer personal data.

## Data model

`users` (+ username, role, is_active, last_login_at, soft deletes), `categories`, `products`,
`sales`, `sale_items`, `payments`, `inventory_movements`, `product_price_histories`,
`business_settings`, `audit_logs`.

Sales: ULID `reference`, unique `transaction_number`, cashier, status, subtotal, discount fields,
tax, total, tendered, change, payment method/reference, idempotency key, sold_at.
Payments: one per sale, method, amount, reference, recorded status. Digital payments are
"recorded", never claimed as verified.

Settings are key/value rows: business identity, logo, address, contact, currency PHP, timezone,
receipt footer, low-stock threshold, enabled payment methods, tax enabled/rate/inclusive.

## Key screens

- Login card.
- Admin shell: collapsible sidebar, topbar, page content; active orange nav.
- Dashboard: KPI cards with yesterday deltas, revenue line chart, category mix, top products;
  daily/weekly/monthly ranges; honest empty states.
- POS: product search/categories/grid left, persistent cart right, mobile cart drawer, checkout modal,
  success receipt modal, keyboard `/`, Esc, Enter. Cart survives filtering/search.
- Products: image/placeholder, name, SKU, category, price, cost (admin), stock, unit, status, updated,
  inline price edit, filters/sort/pagination, archive.
- Categories: create/edit color, reorder, deactivate/archive, delete blocked while products reference.
- Inventory: stock table, low-stock, adjust modal, movement history.
- Sales: filters/search/date/cashier/method, details, receipt reprint, CSV export (admin).
- Users: create/edit, role, active toggle, password reset, last login, last-admin protection.
- Reports: range, totals, by-day, category, cashier, payment mix, top products, CSV.
- Settings: identity/logo/contact/receipt, low stock, payments, tax, timezone; audit log access.
- Receipts: print/PDF/new order/transaction link.

## Reliability and security

CSRF, escaped output, server recalculation, guarded models, form requests, DB transactions,
row locks, unique idempotency key, upload validation, throttled login, audit logs for price,
inventory, role/account, settings and sales events. No demo numbers in production views.

## Seed data

Admin and cashier accounts for first login, the ten default categories, a starter karinderya
catalogue of products, and business defaults. No seeded sales, so dashboard empty states are real.

## Open decisions left to the builder

Exact icon set, chart rendering (server-side SVG), pagination sizing, minor copy, internal class
names. All within the constraints above.
