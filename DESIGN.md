---
name: Karinderya POS Admin
description: A compact shadcn-style sales workspace with a warm orange identity.
colors:
  primary: "#c2410c"
  primary-hover: "#9a3412"
  chart-orange: "#ea580c"
  chart-fill: "#f97316"
  orange-soft: "#fff7ed"
  orange-selected: "#ffedd5"
  orange-hover: "#fff0df"
  orange-border: "#fed7aa"
  warm-surface: "#fffaf5"
  surface: "#ffffff"
  surface-subtle: "#fafafa"
  surface-muted: "#f4f4f5"
  ink: "#18181b"
  muted: "#71717a"
  nav-ink: "#3f3f46"
  line: "#e4e4e7"
  success: "#15803d"
  danger: "#dc2626"
typography:
  headline:
    fontFamily: "'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "24px"
    fontWeight: 600
    letterSpacing: "-0.025em"
  title:
    fontFamily: "'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "15px"
    fontWeight: 600
  body:
    fontFamily: "'SF Pro Text', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "14px"
    lineHeight: 1.6
  label:
    fontSize: "12px"
    fontWeight: 500
  metric:
    fontFamily: "'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "30px"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "-0.03em"
rounded:
  nav: "6px"
  control: "7px"
  icon: "8px"
  card: "12px"
spacing:
  tight: "8px"
  grid: "16px"
  card-mobile: "20px"
  card: "24px"
  desktop-gutter: "32px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    rounded: "{rounded.control}"
    padding: "8px 16px"
    height: "40px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  metric-card:
    backgroundColor: "{colors.warm-surface}"
    rounded: "{rounded.card}"
    padding: "24px"
  navigation-active:
    backgroundColor: "{colors.orange-selected}"
    textColor: "{colors.primary-hover}"
    rounded: "{rounded.nav}"
    padding: "8px 10px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    padding: "10px 14px"
---

# Design System: Karinderya POS Admin

## Overview

**Creative North Star: "The Warm Operations Workspace"**

A compact, shadcn/ui-inspired admin workspace makes real sales easy to scan. White content and zinc structure keep reports quiet; burnt-orange actions, warm metric surfaces, and orange chart marks give the interface the character explicitly requested by the user.

This is an extraction of the finished admin implementation, not a React or shadcn dependency migration. The visual boundary is `.admin-workspace`; the separate POS checkout and authentication layouts retain their incumbent orange system. Keep the configured business identity and logo.

**Key Characteristics:**
- Compact hierarchy and tabular financial figures.
- Flat bordered cards with warm orange emphasis.
- Fine line icons, practical controls, and responsive navigation.

## Colors

### Primary
Burnt orange identifies primary actions, selected periods, and keyboard focus. The brighter chart orange draws the sales series; its translucent fill supports rather than obscures the line. Warm surface, soft orange, selected orange, and orange borders distinguish metrics and navigation without flooding the white reporting canvas.

### Neutral
Ink anchors headings and values; muted zinc supports labels. White is the content surface, subtle zinc separates table headings, and fine zinc borders organize sections.

Semantic green and red accompany change direction; amber remains an inventory warning, not a decorative accent. Preserve icons and words alongside status colors.

**The Scope Rule.** Apply this palette only inside the admin workspace; do not retheme checkout or authentication.

## Typography

System SF display and text stacks fall back to platform sans-serif; no downloaded font is required. Headlines are compact rather than promotional, section titles are restrained, and metric values supply the strongest numeric emphasis. Smaller labels support rather than compete with the values. Use the existing compact font stack and tabular numerals for quantities, currency, and aligned table columns.

## Layout

Desktop uses a sticky sidebar expanded to 240px or collapsed to 80px and a sticky 56px header. Main content is centered with a 1280px maximum width; horizontal gutters progress from 16px to 24px to 32px. Cards use 24px padding, reduced to 20px below 640px; grids use 16px gaps.

Metrics progress from one column to two at 640px and four at 1280px. Supporting reports become two columns at 1024px. Below 768px navigation becomes a native modal dialog: 288px wide, capped at 86% of the viewport, with backdrop dismissal and Escape handling. Preserve its native modal focus behavior and the skip-to-content link.

The sales chart occupies its own full-width card. Below 640px, render the dedicated 420×240 viewBox with at most four requested axis labels rather than shrinking the desktop chart; preserve its 170px minimum height. Tables scroll within their containers, not the page.

## Elevation & Depth

Report and metric cards are flat: no resting shadow. Borders and warm versus white surfaces establish hierarchy. Shadows belong to overlays such as the account popover and mobile navigation, not every dashboard panel. Sidebar width transitions use 200ms; inherited color transitions stay subtle. Honor the global reduced-motion override.

## Shapes

Cards have gently rounded corners; navigation is tighter and controls sit between them. Icons use compact rounded tiles, while account initials are circular. Use thin borders rather than ornamental frames. Metric cards have a 164px minimum desktop height and natural mobile height.

## Components

### Buttons and inputs
Primary buttons pair white text with burnt orange and deepen on hover. Standard buttons are medium-weight with a 40px minimum height; the existing small header variant is 32px. Secondary controls remain white with fine borders. Inputs are white, bordered, and shadowless, with an orange focus border and soft ring. Preserve disabled and error variants from the existing shared components.

Keyboard focus uses a visible burnt-orange 2px outline offset by 3px. Do not remove focus indication to mimic the reference.

### Navigation
Grouped links use fine icons, compact padding, subdued default text, warm hover fill, and a stronger selected fill. Active routes retain `aria-current`; collapsed links retain titles. The persisted sidebar toggle remains the signature shell interaction. Mobile uses the modal navigation instead of an offscreen desktop-only control.

### Metric cards
Warm white backgrounds and pale orange borders distinguish summary metrics. Labels precede large tabular values; orange icon tiles are 28px. Change indicators combine trend icons, semantic color, a compact border, and comparison text. Never invent positive performance values.

### Period control and chart
Daily, weekly, and monthly are real navigation links, grouped and labeled, with `aria-current` on the selected period. The selected link uses burnt orange with white text. The SVG series uses a 2px orange line, white-centered points, a faint orange area, dashed zinc gridlines, and muted axis labels. Preserve unique gradient IDs and point titles. Zero-sales periods show the existing explanatory empty state and sale action, not fabricated chart points.

### Tables and report cards
White bordered report containers hold understated titles and contextual subtitles. Table headings use sentence case, muted text, and a subtle background; body rows retain 14px vertical padding. Best sellers and category/product reports retain explicit empty states and real database-backed content.

## Do's and Don'ts

### Do:
- **Do** keep the orange admin palette scoped and preserve the configured business identity.
- **Do** preserve visible focus, active-route semantics, native mobile dialog behavior, and readable mobile charts.
- **Do** use real reporting values, PHP currency formatting, and tabular numeric alignment.

### Don't:
- **Don't** propagate admin overrides into POS checkout or authentication.
- **Don't** flatten the orange identity into an entirely neutral dashboard.
- **Don't** add decorative shipping rasters or replace informative empty states with sample sales.

Implementation sources: `resources/css/app.css`, admin layout, dashboard, sidebar navigation, stat-card, and line-chart Blade views. Direction: `PRODUCT.md` and `docs/dashboard-redesign.md`. Finish review verdict supplied for this documentation pass: **ship**, following the material fixes and orange palette. Review captures are `.impeccable/review/desktop.png`, `mobile.png`, and `mobile-chart.png`; they are review evidence, not shipping assets.
