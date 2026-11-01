# Admin dashboard redesign

## Scope

Operate mode. Adapt the official shadcn/ui dashboard example to the existing Laravel admin shell and dashboard. Preserve all reporting and management behavior. Shared admin styling is scoped to the app layout, not the separate POS or login layouts.

## Direction contract

THESIS: A compact shadcn-style workspace that puts actual sales first; the user's follow-up pins an orange palette to avoid a bland neutral result. Remove repeated action clutter.

OWN-WORLD: White content, warm white sidebar, zinc-200 borders, burnt-orange primary actions and selected period, orange-tinted metric cards, orange chart series, muted zinc text, 12px card corners, system sans typography, fine Lucide icons. Semantic green and amber remain for stock and change indicators.

STORY: Read today's four metrics, inspect the selected sales period, review best sellers, then inspect category and product detail. New sale stays available in the header.

FIRST VIEWPORT: A 240px collapsible sidebar, 56px header, compact greeting, four metric cards, a full-width sales chart with period tabs in its own header, and an understated inventory summary. Below: best sellers/category breakdown and a full-width product table.

FORM: User-pinned shadcn/ui dashboard reference; no random direction selection. Code-led. Signature interaction is the existing persisted collapsible sidebar, with clear active-route treatment.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance

## Checklist

- [x] Inspect official reference and existing app.
- [x] Protect navigation and dashboard behavior with tests.
- [x] Implement orange-accented admin shell and dashboard composition.
- [x] Build assets, run test suite, inspect desktop/mobile and interactions.
- [x] Review and document the implemented system.

## Verification and review

- Production build passes; 97 PHP tests with 350 assertions and the JavaScript checkout-retry test pass.
- BrowserOS inspected the reference and final desktop/mobile views. Period navigation, sidebar collapse, native modal opening, Escape dismissal, and trigger-focus restoration checked; no horizontal page overflow observed at 390px and 1440px.
- Mechanical design detector returned no findings before the user-requested palette adjustment.
- Reviewer verdict: ship for the two resolved mobile findings (native dialog focus handling and configured-logo preservation) and the requested orange palette. Not a full accessibility audit.
- DESIGN.md and .impeccable/design.json describe the finished system. Review captures are in .impeccable/review/.
