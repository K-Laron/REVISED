# Page Layout Specifications

> **AI IMPLEMENTATION INSTRUCTION:** These wireframes define the exact layout structure for every page. Build each page using these layouts. Components use the design system classes from Section 7 of the PRD.

## Global Layout Shell

```
┌──────────────────────────────────────────────────────────┐
│  TOPBAR (64px height, sticky)                            │
│  ┌────┐  App Name        🔍 Search    🔔 3   🌙  👤    │
│  │Logo│                   (global)    notif  theme user  │
│  └────┘                                      toggle      │
├────────┬─────────────────────────────────────────────────┤
│SIDEBAR │  MAIN CONTENT AREA                              │
│(240px) │  ┌─────────────────────────────────────────┐    │
│        │  │ Page Title                    [Action]   │    │
│ 📊 Dash│  │ Breadcrumb: Home > Module > Page        │    │
│ 🐾 Anim│  ├─────────────────────────────────────────┤    │
│ 🏥 Med │  │                                         │    │
│ 🏠 Kenn│  │  PAGE CONTENT                           │    │
│ 💝 Adop│  │  (defined per page below)               │    │
│ 💰 Bill│  │                                         │    │
│ 📦 Inv │  │                                         │    │
│ 👥 User│  │                                         │    │
│ 📊 Rept│  └─────────────────────────────────────────┘    │
│        │                                                  │
│ ─────  │                                                  │
│ ⚙ Sett│                                                  │
│ 🚪 Out │                                                  │
├────────┴─────────────────────────────────────────────────┤
│  FOOTER (optional, visible on scroll to bottom)          │
│  © 2025 Catarman Dog Pound    v1.0.0                     │
└──────────────────────────────────────────────────────────┘
```

**Mobile (<768px):** Sidebar collapses into hamburger menu. Topbar stays fixed. Content goes full-width.

---

## 1. Dashboard Page (`/dashboard`)

```
┌─────────────────────────────────────────────────────────┐
│  Dashboard                                    [Filter ▾]│
│  Welcome back, {firstName}                              │
├─────────┬─────────┬─────────┬──────────────────────────┤
│         │         │         │                            │
│  CARD 1 │  CARD 2 │  CARD 3 │  CARD 4                   │
│  Total  │  Under  │Adoption │  Kennel                   │
│ Animals │  Care   │Pipeline │ Occupancy                 │
│   127   │   12    │   23    │  78%                      │
│  +5 ▲   │  -2 ▼   │  +8 ▲   │  ─                       │
│         │         │         │                            │
├─────────┴─────────┼─────────┴──────────────────────────┤
│                    │                                     │
│  CHART 1           │  CHART 2                            │
│  Intake Trend      │  Species Distribution               │
│  (Line chart,      │  (Donut chart)                      │
│   last 12 months)  │                                     │
│                    │                                     │
├────────────────────┼─────────────────────────────────────┤
│                    │                                     │
│  CHART 3           │  RECENT ACTIVITY FEED               │
│  Adoption Rate     │  ┌───────────────────────────────┐  │
│  (Bar chart,       │  │ 🟢 Rex adopted by J. Cruz     │  │
│   by month)        │  │ 🔵 New intake: Buddy (Stray)  │  │
│                    │  │ 🟡 Vaccine due: Lucky          │  │
│                    │  │ 🔴 Low stock: Amoxicillin     │  │
│                    │  │ 🟢 Invoice #INV-2025-0042 paid│  │
│                    │  └───────────────────────────────┘  │
├────────────────────┴─────────────────────────────────────┤
│  QUICK ACTIONS                                           │
│  [+ New Intake]  [+ Medical Record]  [View Kennels]      │
└──────────────────────────────────────────────────────────┘
```

**Stat Cards:** 4 cards in a row (desktop), 2×2 grid (tablet), stacked (mobile).
**Charts:** 2 columns (desktop), stacked (mobile). Use Chart.js.
**Activity Feed:** Scrollable list, max 10 items, "View All" link at bottom.

---

## 2. Animal List Page (`/animals`)

```
┌──────────────────────────────────────────────────────────┐
│  Animals                                  [+ New Intake] │
│  Home > Animals                                          │
├──────────────────────────────────────────────────────────┤
│  FILTER BAR                                              │
│  [Search... 🔍] [Species ▾] [Status ▾] [Gender ▾]       │
│  [Intake Date ▾]  [Size ▾]  [Reset Filters]             │
├──────────────────────────────────────────────────────────┤
│  TABLE                                                   │
│  ┌──┬─────────┬────────┬──────┬────────┬───────┬──────┐ │
│  │  │ Animal  │Species │Gender│ Status │Intake │Action│ │
│  │  │ ID/Name │ /Breed │      │        │ Date  │      │ │
│  ├──┼─────────┼────────┼──────┼────────┼───────┼──────┤ │
│  │☐ │ 📷 Rex  │Dog     │Male  │🟢 Avail│Jan 15 │ ⋮    │ │
│  │  │ A-25-01 │Aspin   │      │        │ 2025  │      │ │
│  ├──┼─────────┼────────┼──────┼────────┼───────┼──────┤ │
│  │☐ │ 📷 Luna │Cat     │Femal │🟡 Care │Feb 03 │ ⋮    │ │
│  │  │ A-25-02 │Persian │      │        │ 2025  │      │ │
│  └──┴─────────┴────────┴──────┴────────┴───────┴──────┘ │
│                                                          │
│  Showing 1-20 of 127       [← 1 2 3 4 5 ... 7 →]       │
└──────────────────────────────────────────────────────────┘
```

**Action menu (⋮):** View, Edit, Medical Records, Print QR, Change Status, Delete.
**Mobile:** Table becomes card list. Each animal = one card with photo, name, status badge.

---

## 3. Animal Detail Page (`/animals/{id}`)

```
┌──────────────────────────────────────────────────────────┐
│  Home > Animals > Rex (A-2025-0001)    [Edit] [Print QR] │
├──────────────────┬───────────────────────────────────────┤
│                  │                                       │
│  PHOTO GALLERY   │  DETAILS CARD                        │
│  ┌────────────┐  │  Name: Rex                           │
│  │            │  │  Species: Dog                        │
│  │   Photo    │  │  Breed: Aspin                        │
│  │            │  │  Gender: Male                        │
│  └────────────┘  │  Age: 2 years 3 months               │
│  [◀ 1/4 ▶]      │  Weight: 12.5 kg                     │
│                  │  Color: Brown/White                   │
│  ┌────┐          │  Temperament: Friendly               │
│  │ QR │          │  Status: 🟢 Available                │
│  │Code│          │  Kennel: K-A03                       │
│  └────┘          │  Intake: Jan 15, 2025 (Stray)        │
│                  │  Temperament: Friendly                │
├──────────────────┴───────────────────────────────────────┤
│  TABS: [Timeline] [Medical] [Kennel History] [Documents] │
├──────────────────────────────────────────────────────────┤
│  TIMELINE VIEW (default tab)                             │
│  ┌─ Jan 15 ──────────────────────────────────────────┐   │
│  │ 📥 Intake: Stray found at Brgy. Centro            │   │
│  └───────────────────────────────────────────────────┘   │
│  ┌─ Jan 16 ──────────────────────────────────────────┐   │
│  │ 🏠 Assigned to Kennel K-A03                        │   │
│  └───────────────────────────────────────────────────┘   │
│  ┌─ Jan 18 ──────────────────────────────────────────┐   │
│  │ 💉 Vaccination: Anti-rabies by Dr. Santos          │   │
│  └───────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────┘
```

---

## 4. Animal Intake Form (`/animals/create`)

```
┌──────────────────────────────────────────────────────────┐
│  New Animal Intake                          [Cancel]     │
│  Home > Animals > New Intake                             │
├──────────────────────────────────────────────────────────┤
│  STEP INDICATOR (if multi-step):                         │
│  ● Basic Info  ○ Photo Upload  ○ Kennel Assignment       │
├──────────────────────────────────────────────────────────┤
│  FORM — Single page, sections separated by dividers      │
│                                                          │
│  ── Animal Information ──────────────────────────────    │
│  [Name          ]  [Species    ▾]  [Breed    ▾]         │
│  [Gender      ▾ ]  [Age Years   ]  [Age Months ]        │
│  [Size        ▾ ]  [Weight (kg) ]  [Color/Markings]     │
│  [Distinguishing Features                         ]     │
│  [Condition at Intake ▾]  [Temperament   ▾]            │
│                                                          │
│  ── Intake Details ──────────────────────────────────    │
│  [Intake Type ▾ ]  [Intake Date 📅]                     │
│  [Condition at Intake ▾]                                │
│  [Location Found (if stray)                       ]     │
│  [Surrender Reason (if surrender)                 ]     │
│                                                          │
│  ── Brought By ──────────────────────────────────────    │
│  [Name             ]  [Contact         ]                │
│  [Address                              ]                │
│                                                          │
│  ── Photo Upload ────────────────────────────────────    │
│  ┌──────────────────────────────────────┐               │
│  │  📷 Drag & drop photos here          │               │
│  │  or click to browse (max 5, 5MB each)│               │
│  └──────────────────────────────────────┘               │
│                                                          │
│  ── Kennel Assignment ───────────────────────────────    │
│  [Select Kennel ▾] (shows only available kennels)       │
│                                                          │
│  [Cancel]                          [Save & Generate QR] │
└──────────────────────────────────────────────────────────┘
```

---

## 5. Kennel Management Page (`/kennels`)

```
┌──────────────────────────────────────────────────────────┐
│  Kennel Management                         [+ Add Kennel]│
│  Home > Kennels                                          │
├──────────────────────────────────────────────────────────┤
│  STATS BAR                                               │
│  Total: 48  │  🟢 Available: 15  │  🔴 Occupied: 28     │
│  🟡 Maintenance: 3  │  🟣 Quarantine: 2                 │
├──────────────────────────────────────────────────────────┤
│  VIEW TOGGLE: [Grid View ▣] [List View ☰]              │
│  FILTERS: [Zone ▾] [Status ▾] [Species ▾] [Size ▾]     │
├──────────────────────────────────────────────────────────┤
│  GRID VIEW (Visual floor plan style)                     │
│                                                          │
│  Zone A — Building A                                     │
│  ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐    │
│  │K-A01  │ │K-A02  │ │K-A03  │ │K-A04  │ │K-A05  │    │
│  │🟢     │ │🔴     │ │🔴     │ │🟡     │ │🟢     │    │
│  │Empty  │ │Rex    │ │Luna   │ │Maint. │ │Empty  │    │
│  │Small  │ │Medium │ │Small  │ │Large  │ │Medium │    │
│  └───────┘ └───────┘ └───────┘ └───────┘ └───────┘    │
│                                                          │
│  Zone B — Building B                                     │
│  ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐               │
│  │K-B01  │ │K-B02  │ │K-B03  │ │K-B04  │               │
│  │🔴     │ │🔴     │ │🟢     │ │🟣     │               │
│  │Buddy  │ │Max    │ │Empty  │ │Quaran.│               │
│  │Large  │ │Large  │ │XLarge │ │Medium │               │
│  └───────┘ └───────┘ └───────┘ └───────┘               │
│                                                          │
│  Click any kennel to open detail panel (slide-in)       │
└──────────────────────────────────────────────────────────┘
```

**Kennel Click → Slide-in Panel:** Shows kennel details, current animal (if occupied), assignment history, maintenance log, and action buttons (Assign, Release, Set Maintenance).

---

## 6. Medical Record Form (`/medical/create/{animalId}`)

```
┌──────────────────────────────────────────────────────────┐
│  New Medical Record — Rex (A-2025-0001)     [Cancel]     │
│  Home > Medical > New Record                             │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Procedure Type:                                         │
│  ┌──────────┐ ┌──────────┐ ┌────────────┐ ┌──────────┐ │
│  │💉        │ │🔪        │ │🩺          │ │💊        │ │
│  │Vaccination│ │ Surgery  │ │Examination │ │Treatment │ │
│  │(selected)│ │          │ │            │ │          │ │
│  └──────────┘ └──────────┘ └────────────┘ └──────────┘ │
│  ┌──────────┐ ┌──────────┐                              │
│  │🐛        │ │⚠️        │                              │
│  │Deworming │ │Euthanasia│                              │
│  └──────────┘ └──────────┘                              │
│                                                          │
│  ── Common Fields ───────────────────────────────────    │
│  [Date 📅         ]  [Veterinarian ▾  ]                 │
│  [General Notes                       ]                 │
│                                                          │
│  ── DYNAMIC FORM (changes based on type selected) ───   │
│  (Example: Vaccination form shown)                       │
│  [Vaccine Name ▾    ]  [Vaccine Brand    ]              │
│  [Batch/Lot Number  ]  [Dosage (ml)     ]              │
│  [Route ▾           ]  [Injection Site ▾ ]              │
│  [Dose Number       ]  [Next Due Date 📅]              │
│  [Adverse Reactions                     ]               │
│                                                          │
│  [Cancel]                              [Save Record]    │
└──────────────────────────────────────────────────────────┘
```

**Key behavior:** Selecting a procedure type card re-renders only the dynamic form section below. Common fields (date, vet, notes) stay constant.

---

## 7. Adoption Pipeline Page (`/adoptions`)

```
┌──────────────────────────────────────────────────────────┐
│  Adoption Pipeline                    [Pipeline Stats 📊]│
│  Home > Adoptions                                        │
├──────────────────────────────────────────────────────────┤
│  KANBAN BOARD (horizontally scrollable on mobile)        │
│                                                          │
│  Pending (5)    Interview (3)  Seminar (2)   Payment (1)│
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐│
│  │APP-25-001│  │APP-25-003│  │APP-25-006│  │APP-25-009││
│  │J. Cruz   │  │M. Santos │  │R. Lopez  │  │A. Reyes  ││
│  │for: Rex  │  │for: Luna │  │for: Buddy│  │for: Max  ││
│  │ 2 days   │  │ Jan 20   │  │ Jan 25   │  │₱2,500    ││
│  │[Review →]│  │[Details] │  │[Details] │  │[Pay →]   ││
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘│
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │APP-25-002│  │APP-25-004│  │APP-25-007│   Complete(12)│
│  │K. Laron  │  │D. Garcia │  │T. Mendoza│  ┌──────────┐│
│  │General   │  │for: Coco │  │for: Daisy│  │APP-25-010││
│  │ 5 days   │  │ Jan 22   │  │ Jan 28   │  │L. Tan    ││
│  │[Review →]│  │[Details] │  │[Details] │  │✅ Done    ││
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘│
│                                                          │
└──────────────────────────────────────────────────────────┘
```

**Card click → opens full application detail** with tabs: Application Info, Interview, Seminar, Payment, Timeline.

---

## 8. Billing Dashboard (`/billing`)

```
┌──────────────────────────────────────────────────────────┐
│  Billing & Invoicing                  [+ Create Invoice] │
│  Home > Billing                                          │
├─────────────┬─────────────┬─────────────┬───────────────┤
│  Total Rev  │  Outstanding│  Paid Today │ Overdue       │
│  ₱125,450   │  ₱23,100    │  ₱5,200     │ ₱8,400       │
│  this month │  8 invoices │  3 payments │ 4 invoices    │
├─────────────┴─────────────┴─────────────┴───────────────┤
│  TABS: [Invoices] [Payments] [Fee Schedule]             │
├─────────────────────────────────────────────────────────┤
│  Invoices Tab (default):                                │
│  [Search... 🔍] [Status ▾] [Date Range 📅-📅]         │
│  ┌──────────┬──────────┬────────┬────────┬─────┬──────┐│
│  │Invoice # │Payor     │Amount  │Balance │Stat │Action││
│  ├──────────┼──────────┼────────┼────────┼─────┼──────┤│
│  │INV-25-042│J. Cruz   │₱2,500  │₱0     │🟢Paid│ ⋮   ││
│  │INV-25-041│M. Santos │₱3,200  │₱3,200 │🔴Due │ ⋮   ││
│  │INV-25-040│Walk-in   │₱500    │₱500   │🟡Part│ ⋮   ││
│  └──────────┴──────────┴────────┴────────┴─────┴──────┘│
│  Showing 1-20 of 42          [← 1 2 3 →]              │
└─────────────────────────────────────────────────────────┘
```

---

## 9. Inventory Page (`/inventory`)

```
┌──────────────────────────────────────────────────────────┐
│  Inventory Management                     [+ Add Item]   │
│  Home > Inventory                                        │
├──────────────────────────────────────────────────────────┤
│  ALERTS BAR (dismissible, only shows when applicable)    │
│  ⚠️ 3 items below reorder level  │  ⏰ 2 items expiring │
│  [View Low Stock]                  [View Expiring]       │
├──────────────────────────────────────────────────────────┤
│  TABS: [All Items] [Medical] [Food] [Cleaning] [Office]  │
│  [Search... 🔍]  [Sort: Name ▾]                         │
├──────────────────────────────────────────────────────────┤
│  ┌──────┬────────────┬──────┬───────┬────────┬────────┐ │
│  │ SKU  │ Item Name  │ Qty  │ Unit  │ Expiry │ Action │ │
│  ├──────┼────────────┼──────┼───────┼────────┼────────┤ │
│  │MED001│ Amoxicillin│🔴 5  │bottle │Mar 2025│[+][-]⋮│ │
│  │MED002│ Anti-rabies│ 32   │vial   │Dec 2025│[+][-]⋮│ │
│  │FOD001│ Dog Food   │ 120  │kg     │ —      │[+][-]⋮│ │
│  └──────┴────────────┴──────┴───────┴────────┴────────┘ │
│  Showing 1-20 of 85          [← 1 2 3 4 5 →]           │
└──────────────────────────────────────────────────────────┘
```

**[+] button** → Quick stock-in modal. **[-] button** → Quick stock-out modal.
**🔴 Quantity** → Red when at or below reorder level.

---

## 10. User Management Page (`/users`)

```
┌──────────────────────────────────────────────────────────┐
│  User Management                          [+ Create User]│
│  Home > Users                                            │
├──────────────────────────────────────────────────────────┤
│  [Search... 🔍]  [Role ▾]  [Status ▾]                  │
│  TABS: [Active Users] [Deleted Users]                    │
├──────────────────────────────────────────────────────────┤
│  ┌──────┬──────────────┬──────────────┬───────┬────────┐│
│  │Avatar│ Name / Email │ Role         │Status │ Action ││
│  ├──────┼──────────────┼──────────────┼───────┼────────┤│
│  │ 👤   │ Juan Cruz    │🔵 Vet        │🟢 Act │ ⋮     ││
│  │      │ juan@mail.com│              │       │        ││
│  ├──────┼──────────────┼──────────────┼───────┼────────┤│
│  │ 👤   │ Maria Santos │🟣 Staff      │🟢 Act │ ⋮     ││
│  │      │ maria@mail   │              │       │        ││
│  └──────┴──────────────┴──────────────┴───────┴────────┘│
│  Showing 1-10 of 15           [← 1 2 →]                ││
└──────────────────────────────────────────────────────────┘
```

---

## 11. Reports Page (`/reports`)

```
┌──────────────────────────────────────────────────────────┐
│  Reports Center                                          │
│  Home > Reports                                          │
├──────────────────────────────────────────────────────────┤
│  REPORT TYPE CARDS (clickable)                           │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐           │
│  │📥          │ │🏥          │ │💝          │           │
│  │Intake      │ │Medical     │ │Adoption    │           │
│  │Report      │ │Report      │ │Report      │           │
│  └────────────┘ └────────────┘ └────────────┘           │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐           │
│  │💰          │ │📦          │ │📋          │           │
│  │Billing     │ │Inventory   │ │Census      │           │
│  │Report      │ │Report      │ │Report      │           │
│  └────────────┘ └────────────┘ └────────────┘           │
│  ┌────────────┐ ┌────────────┐                           │
│  │🐾          │ │🔍          │                           │
│  │Animal      │ │Audit       │                           │
│  │Dossier     │ │Trail       │                           │
│  └────────────┘ └────────────┘                           │
├──────────────────────────────────────────────────────────┤
│  REPORT BUILDER (shown after selecting a type)           │
│  Date Range: [From 📅] [To 📅]  Group By: [Month ▾]     │
│  Filters: [Species ▾] [Status ▾] [...depends on type]   │
│  [Generate Preview]  [Export CSV]  [Export PDF]           │
├──────────────────────────────────────────────────────────┤
│  PREVIEW AREA (chart + table)                            │
│  ┌──────────────────────────────────────────────────┐    │
│  │  Chart visualization of report data               │    │
│  └──────────────────────────────────────────────────┘    │
│  ┌──────────────────────────────────────────────────┐    │
│  │  Data table with sortable columns                 │    │
│  └──────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

---

## 12. Adopter Landing Page (`/adopt`) — Public

```
┌──────────────────────────────────────────────────────────┐
│  NAVBAR (transparent, becomes solid on scroll)           │
│  🐾 Catarman Shelter     About  Animals  Apply   🌙 Login│
├──────────────────────────────────────────────────────────┤
│                                                          │
│  HERO SECTION (full viewport height)                     │
│  ┌──────────────────────────────────────────────────┐    │
│  │                                                    │    │
│  │    Give Them a                                     │    │
│  │    Second Chance                                   │    │
│  │                                                    │    │
│  │    Every animal deserves a loving home.             │    │
│  │    Start your adoption journey today.              │    │
│  │                                                    │    │
│  │    [Browse Animals]  [How It Works]                │    │
│  │                                                    │    │
│  └──────────────────────────────────────────────────┘    │
│  (Background: subtle gradient or blurred animal photo)  │
│                                                          │
├──────────────────────────────────────────────────────────┤
│  HOW IT WORKS — 4 steps horizontally                     │
│  ┌─────┐    ┌─────┐    ┌─────┐    ┌─────┐              │
│  │  1  │───▶│  2  │───▶│  3  │───▶│  4  │              │
│  │Apply│    │Inter│    │Semi-│    │Bring│              │
│  │     │    │view │    │nar  │    │Home │              │
│  └─────┘    └─────┘    └─────┘    └─────┘              │
│                                                          │
├──────────────────────────────────────────────────────────┤
│  FEATURED ANIMALS (3–4 cards)                            │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐              │
│  │  📷       │ │  📷       │ │  📷       │              │
│  │  Rex      │ │  Luna     │ │  Buddy    │              │
│  │  Dog, Male│ │  Cat, Fem │ │  Dog, Male│              │
│  │  2 years  │ │  1 year   │ │  3 years  │              │
│  │ [Meet Rex]│ │[Meet Luna]│ │[Meet Buddy│              │
│  └───────────┘ └───────────┘ └───────────┘              │
│                    [View All Animals →]                   │
│                                                          │
├──────────────────────────────────────────────────────────┤
│  FOOTER                                                  │
│  Contact: (055) 123-4567  │  Open: Mon-Fri 8AM-5PM     │
│  Address: Catarman, N. Samar  │  © 2025                 │
└──────────────────────────────────────────────────────────┘
```
