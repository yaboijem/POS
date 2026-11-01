# Verification record

Date: 2026-10-30

## Automated checks

- `php artisan test`: 95 passed, 343 assertions.
- `node --test tests/js/checkout-retry.test.mjs`: 1 passed.
- `npm run build`: passed.
- `vendor/bin/pint --test`: passed.
- `php artisan view:cache`: passed.
- `php artisan route:list --except-vendor`: application routes registered.
- Normal application migrations applied without resetting local sales.

## Browser checks

- Administrator and cashier login and role-specific navigation.
- Product selection, cash checkout, saved receipt and PDF response.
- Cashier GCash recording with reference; own-sales history excludes the administrator sale.
- Admin sales, users/create, settings, report and audit pages render.
- At 390px, mobile POS cart drawer works; users/settings/reports/sales have no document-wide horizontal overflow.
- Updated terminal assets load; reports show both manual test sales (PHP 206 total).

Manual checks created two local sales: one PHP 117 cash sale and one PHP 89 GCash test record. No payment provider was contacted.

## Independent review and fix pass

An independent QA reviewer inspected the implementation and reproduced seven issues. The fix pass addressed all seven:

1. Stale product forms restoring sold stock: product edits no longer accept stock mutations; intentional changes go through Inventory.
2. Concurrent administrator removals: update/status/delete take ordered shared user-row locks in a transaction and reread the target.
3. Changed checkout after a lost response: terminal freezes unresolved attempts and retries the original payload; server checks a stored canonical payload hash.
4. Quantity/snapshot precision mismatch: checkout accepts at most three decimals and exact whole quantities outside kilograms.
5. Fractional stock for whole-unit products: creation and locked inventory adjustments validate unit-dependent precision; fractional stock cannot be relabelled as whole units.
6. Concurrent duplicate checkout of last stock: attempts serialize on the cashier row and perform a current idempotency read before stock checks.
7. Historical recategorization: checkout snapshots category attribution; reports aggregate snapshots.

Regression tests were observed failing before these fixes and passing afterward. Locking tests verify real transaction/locking queries and duplicate final-stock retries; they are not a multi-process load test. Physical thermal-printer output and production deployment were not verified.

## Implementation rulings

- Continue in the existing feature branch rather than a separate worktree: the greenfield app is already the deliverable. Tradeoff: branch isolation rather than directory isolation.
- Do not repeat `migrate:fresh` against the app database after manual sales exist. Fresh-schema coverage comes from the earlier seed run and isolated test migrations.
- Staff password reset uses a dedicated section on the edit page instead of a list-row modal. Tradeoff: one navigation, with clearer validation and less modal state.
- Backfill old category attribution from currently known categories; historical recategorization before snapshot support cannot be reconstructed.

No minor review findings were deferred. Digital payments are recorded, not verified; no refund/void workflow or BIR-accreditation claim is made.
