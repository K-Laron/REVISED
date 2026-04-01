# Civic Ledger UI/UX Redesign

Date: April 1, 2026
Project: Catarman Animal Shelter
Audience: Kenneth, capstone reviewers, implementation follow-up
Status: Approved design direction, ready for implementation planning after review

## 1. Summary

The system will adopt a shared design language named `Civic Ledger`.

`Civic Ledger` is a trust-first visual system for both the internal shelter operations product and the public adopter portal. The internal application will feel civic, precise, calm, and operationally credible. The public portal will use the same structural language, but with warmer surfaces and more humane emphasis so it remains welcoming to adopters.

The first implementation pass will focus on five flagship surfaces:

1. Shared internal shell
2. Dashboard
3. Global search
4. Settings
5. Public adopter landing page

This pass establishes the visual system, component vocabulary, navigation posture, and interaction rules that later module pages can inherit.

## 2. Goals

### Primary Goals

- Improve perceived quality and credibility of the system for capstone presentation and real-world use.
- Make internal workflows feel calmer, clearer, and more intentional without changing the underlying business logic.
- Make the public portal feel officially trusted and emotionally approachable at the same time.
- Unify public and internal experiences under one design language instead of two disconnected interfaces.
- Improve accessibility, responsiveness, and navigation clarity.

### Secondary Goals

- Create stronger visual hierarchy for data-heavy screens.
- Reduce the “flat admin template” feel of the internal UI.
- Improve first-impression confidence on the public landing page.

### Non-Goals

- No API changes.
- No route changes.
- No feature removals.
- No workflow redesign that changes system behavior.
- No full system-wide restyling in the first pass.

## 3. Design Direction

### Chosen Direction

Base tone: `Civic-professional`
Portal accent: `Warm rescue`

### Rationale

This system serves a shelter context that must communicate trust, procedural clarity, and care. A purely warm or playful system would weaken internal operational credibility. A purely enterprise or data-centric system would make the public portal feel cold. `Civic Ledger` balances both:

- Internal UI: authority, structure, legibility, operational calm
- Public UI: warmth, reassurance, trust, humane clarity

### Emotional Targets

- Internal users should feel: oriented, in control, informed, and confident.
- Public users should feel: welcomed, guided, and reassured.
- Reviewers should feel: this looks intentional, defensible, and production-aware.

## 4. Visual System

### 4.1 Color Palette

Core internal palette:

- Ledger Navy: `#0F172A`
- Authority Blue: `#1E3A8A`
- Signal Blue: `#0369A1`
- Paper Slate: `#F8FAFC`
- Deep Text: `#020617`
- Muted Slate: `#475569`

Warm public accents:

- Shelter Sand: `#FFF7ED`
- Warm Copper: `#B45309`
- Soft Amber Surface: `#FFE7BF`

Semantic guidance:

- Success states should remain green-based and clear.
- Warning states should lean amber, not yellow-washed beige.
- Error states should stay high contrast and explicit.
- Focus rings should use a visible blue family, not subtle gray.

### 4.2 Typography

Approved stack:

- Headings and navigation: `Lexend`
- Body text and forms: `Source Sans 3`
- System metadata, IDs, badges, timestamps, and operational labels: `JetBrains Mono`

Typography rules:

- JetBrains Mono is an accent/system font, not the primary body font.
- Internal screens should use JetBrains Mono for metrics, codes, audit-style data, and small operational labels.
- Portal copy should remain primarily proportional to preserve warmth and readability.
- Heading sizes should be larger and more deliberate than the current UI, with fewer small generic section headers.

### 4.3 Motion

Motion should be restrained and directional:

- subtle staggered entrances on load
- clearer hover elevation for interactive cards and buttons
- no bouncy or playful animations
- reduced-motion support must be preserved
- transitions should emphasize confidence and clarity, not decoration

### 4.4 Shape and Surface Language

- Cards should feel more substantial through sharper hierarchy, better spacing, stronger borders, and more intentional shadow.
- Internal surfaces should use layered “paper on civic desk” depth rather than thin glassmorphism.
- Public surfaces should use warmer gradients and softer contrast while preserving clarity.
- Border radii should be consistent and slightly more generous than the current UI.

## 5. UX Principles

### 5.1 Navigation

- The internal sidebar becomes a clearer command rail with stronger section grouping.
- Active navigation states must be more obvious and visually stable.
- Topbar search must read as a primary command surface, not a small accessory field.
- Sticky or fixed UI must never obscure content.
- Existing breadcrumb usage remains, but hierarchy should be visually cleaner.

### 5.2 Accessibility

Required baseline:

- strong visible focus states
- predictable heading hierarchy
- keyboard navigation preserved
- skip links preserved
- error and status messages announced accessibly where applicable
- color never used as the only status signal
- touch targets at least 44x44 where relevant
- light mode contrast must remain strong

### 5.3 Responsiveness

The redesign must explicitly support:

- 375px mobile
- 768px tablet
- 1024px laptop
- 1440px desktop

Mobile should feel designed, not merely collapsed.

## 6. Flagship Pass Scope

## 6.1 Shared Internal Shell

Files affected will likely include:

- `views/layouts/app.php`
- `views/partials/sidebar.php`
- `views/partials/header.php`
- `public/assets/css/variables.css`
- `public/assets/css/base.css`
- `public/assets/css/components.css`
- `public/assets/css/layout.css`
- `public/assets/css/responsive.css`

Required changes:

- replace current Fira-based global identity with the approved type stack
- improve sidebar hierarchy, grouping, and active state clarity
- calm the topbar and make global search more prominent
- improve overall spacing rhythm
- create a stronger design token system for the flagship pass
- make badges, buttons, cards, and inputs visually consistent across flagship pages

## 6.2 Dashboard

Current issue:
The dashboard feels functional but generic, with repetitive chart cards and limited hierarchy.

Redesign intent:

- convert dashboard into a briefing-style layout
- create a stronger hero/status area at top
- make quick actions feel more intentional
- improve chart framing and spacing
- better distinguish metrics, activity, and action zones

UX result:
Users should understand the shelter’s current operational state faster and with less scanning effort.

## 6.3 Global Search

Current issue:
Search is capable but visually dense and reads like a form-heavy admin utility.

Redesign intent:

- turn the query field into the dominant interaction
- make module chips clearer and more compact
- visually separate “core search” from “advanced filters”
- improve result section scanning
- use JetBrains Mono selectively for IDs, invoice numbers, SKUs, and system identifiers

UX result:
The page should feel like a command center rather than a generic filter form.

## 6.4 Settings

Current issue:
Settings is informative but long and visually uniform. Important operational zones compete equally for attention.

Redesign intent:

- reorganize the page into distinct operational zones:
  - health
  - configuration
  - backups
  - maintenance
  - readiness
  - notes
- emphasize status and control surfaces more clearly
- make backup and maintenance actions feel deliberate and high-trust
- make read-only versus editable states more obvious

UX result:
The page should feel like an operations console rather than a stacked sequence of cards and forms.

## 6.5 Public Adopter Landing Page

Current issue:
The landing page is already calmer than the internal product but still feels somewhat disconnected from the system’s trust language.

Redesign intent:

- preserve the current content logic and adoption guidance
- increase trust cues and shelter credibility in the hero
- simplify the CTA hierarchy
- make the featured animals area feel more curated and less template-like
- visually connect the portal to the internal system using the same navy foundation and warmer accent surfaces

UX result:
The page should feel official, transparent, and reassuring, while still human and adoption-focused.

## 7. Interaction Rules

- Any clickable card must visibly behave like a clickable card.
- Hover states must not create layout shift.
- Focus states must be more visible than hover states.
- Internal system buttons should feel stronger and more civic; portal CTAs should feel warmer but still disciplined.
- Notifications, alerts, and operational status need clear contrast and hierarchy.

## 8. Technical Constraints

- Use the existing view/template architecture.
- Preserve current route and controller behavior.
- Preserve the existing shared CSS structure unless a narrow refactor is needed for clarity.
- Do not add a new frontend dependency unless Kenneth explicitly approves it.
- Continue using the current no-build setup.
- Prefer the smallest correct implementation that still delivers a visible flagship-level redesign.

## 9. Rollout Recommendation

Implementation should happen in this order:

1. Global design tokens and typography
2. Shared internal shell
3. Dashboard
4. Search
5. Settings
6. Public layout and landing page
7. Accessibility and responsive cleanup
8. Verification pass

This ordering minimizes rework because the pages can inherit the shell and token changes instead of fighting them.

## 10. Verification Requirements

The implementation plan must include:

- responsive checks for mobile and desktop
- keyboard navigation checks
- focus visibility checks
- reduced-motion checks
- confirmation that sticky elements do not obscure content
- validation of readable contrast in light mode
- existing PHPUnit suite run after UI changes

If practical during implementation, browser-based screenshots or walkthrough checks should be added for the redesigned flagship pages.

## 11. Risks

- If the redesign tries to touch all module pages in the first pass, consistency will drop and risk will rise.
- If JetBrains Mono is overused, readability and warmth will degrade.
- If the public portal becomes too civic without enough warm accenting, adoption UX will feel sterile.
- If the internal UI becomes too warm, operational credibility will weaken.

## 12. Final Recommendation

Proceed with a flagship-pass implementation of `Civic Ledger` across:

- shared app shell
- dashboard
- search
- settings
- public adopter landing

This gives the system the highest visible quality improvement with controlled implementation risk and creates a reusable visual standard for the rest of the application.
