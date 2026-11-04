# Total Drive Care (TDC) - Design System & UI Architecture

This document defines the visual design language, color tokens, typography hierarchy, and UI component standards for **Total Drive Care (TDC) Workshop Management System**.

---

## 1. Design Ethos & Philosophy

The TDC system is an internal operations hub for automotive engineering, diagnostics, inventory logistics, and double-entry bookkeeping. The interface reflects:

* **Modern Minimalism**: Uncluttered surfaces, generous negative space, intentional hierarchy, and zero visual noise.
* **True Black & High Contrast**: Dark mode is grounded in pure `#000000` (pitch black) and deep carbon neutrals (`#09090b` / `#0f0f11`). The blue/slate hue has been replaced with true neutral monochromes.
* **Typographic Precision**: A pairing of **Plus Jakarta Sans** (clean, geometric humanism) with **JetBrains Mono** (technical tabular clarity for license plates, VINs, odometers, and financial currencies).
* **Workplace Ergonomics**: Fast visual scanning on workshop tablets and desktop workstations, high contrast ratios (WCAG AAA compliant), and zero distraction from unnecessary drop-shadows or gradients.

---

## 2. Color System Architecture

### 2.1 Dark Mode Palette (True Black & Carbon Neutral)

All blue-slate tones have been remapped to a neutral monochromatic scale:

| Token / Class | Hex Code | Visual Role |
| :--- | :--- | :--- |
| `bg-slate-950` / Root Canvas | `#000000` | True Pitch Black canvas background |
| `bg-slate-900` / Card Surface | `#0f0f11` | Primary cards, content tiles, table headers |
| Sidebar Surface | `#09090b` | Fixed side navigation bar background |
| `bg-slate-850` / Elevated Hover | `#161618` | Hover states, interactive list items, drawers |
| `border-slate-800` / Hairline | `#202023` | Clean structural dividing borders |
| `border-slate-750` / Focus | `#27272a` | Form input borders and focused dividers |
| `app-input` Background | `#050506` | Deep recessed inputs and select controls |
| Text Primary (`text-slate-100`) | `#f4f4f5` | Headings, primary labels, values |
| Text Secondary (`text-slate-350`) | `#a1a1aa` | Explanations, subtitles, table body cells |
| Text Muted (`text-slate-500`) | `#71717a` | Metadata labels, category overlines, timestamps |

### 2.2 Light Mode Palette (Crisp Minimal Light)

| Token / Class | Hex Code | Visual Role |
| :--- | :--- | :--- |
| Canvas Background | `#fafafa` | Ultra-clean soft off-white canvas |
| `app-card` / Sidebar | `#ffffff` | Pure white elevated surfaces |
| `border-slate-200` | `#e4e4e7` | Hairline dividers and table borders |
| `border-slate-300` | `#d4d4d8` | Input outlines and form boundaries |
| Text Primary | `#09090b` | Dark charcoal for maximum legibility |
| Text Secondary | `#52525b` | Neutral zinc body copy |
| Text Muted | `#71717a` | Captions and tertiary labels |

### 2.3 Brand Accent Engine

Accents are injected via CSS custom properties (`--primary-color` and `--primary-hover`), switchable per user preference or corporate theme:

```css
:root {
    --primary-color: #2563eb;       /* TDC Electric Blue (Default) */
    --primary-hover: #1d4ed8;
}
```

* **Electric Blue** (`#2563eb`): Standard primary actions, active tabs, focused elements.
* **Emerald Green** (`#059669` / `#10b981`): Completed job cards, paid invoices, healthy stock indicators.
* **Amber / Warning** (`#d97706` / `#f59e0b`): Draft status, low inventory alerts, pending salary advances.
* **Crimson Red** (`#dc2626` / `#ef4444`): Blocked repairs, urgent vehicle issues, permanent deletion.
* **Indigo** (`#4f46e5`): Testing phase, technical diagnostics, quality assurance.

---

## 3. Typography Architecture

The typographic system utilizes a **dual-font architecture** loaded directly via Google Fonts:

```html
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
```

### 3.1 Font Roles

* **Primary Sans (`Plus Jakarta Sans`)**: Applied globally to UI components, buttons, navigation menus, titles, and body prose. Its geometric curves and high x-height deliver immediate readability.
* **Technical Monospace (`JetBrains Mono`)**: Applied to all technical and numeric data:
  * License plate numbers (e.g., `WP CAB-1234`)
  * Chassis numbers & VINs (e.g., `NZE161-2094812`)
  * Job Card & Invoice numbers (e.g., `JC-0042`, `INV-2026-001`)
  * Financial figures, currency sums, odometer readings, and hourly rates
  * Leverages tabular figures (`tabular-nums`) to align digits vertically in grids.

### 3.2 Hierarchy & Type Variations

| Level / Usage | Tailwind Classes | Tracking / Case | Weight | Sample Application |
| :--- | :--- | :--- | :--- | :--- |
| **KPI Metric** | `text-2xl sm:text-3xl font-mono font-extrabold` | `tracking-tight` | 800 | Financial revenue totals, ticket sums |
| **Page Title** | `text-lg sm:text-xl font-bold` | `tracking-tight` | 700 | Main workspace title |
| **Card Header** | `text-base font-bold` | Normal | 700 | Job Card title, Client profile header |
| **Section Overline**| `text-[10px] sm:text-xs font-bold uppercase`| `tracking-wider` | 700 | "DIAGNOSTICS & REMARKS", "SERVICES" |
| **Body Standard** | `text-sm font-medium` | Normal | 500 | Explanations, dialogue entries, descriptions |
| **Body Subtle** | `text-xs text-slate-500` | Normal | 400 | Subtitles, helper text, user roles |
| **Technical Data**| `text-xs font-mono font-semibold` | `tracking-tight` | 600 | Plate number, phone, email, parts SKU |

---

## 4. UI Elements & Component Standards

### 4.1 Cards & Containers (`.app-card`)

```css
.app-card {
    background-color: #ffffff;
    border: 1px solid #e4e4e7;
    border-radius: 1rem; /* 16px */
}
.dark .app-card {
    background-color: #0d0d0f;
    border: 1px solid #202023;
}
```

* Use rounded 16px corners (`rounded-2xl`).
* Do not apply heavy blurry drop-shadows. The crisp 1px border provides high-definition structural separation.

### 4.2 Form Controls & Inputs (`.app-input`)

```css
.app-input {
    background-color: #ffffff;
    border: 1px solid #d4d4d8;
    color: #09090b;
}
.dark .app-input {
    background-color: #050506;
    border: 1px solid #27272a;
    color: #f4f4f5;
}
```

* Always give inputs clear labels and explicit placeholders.
* On focus, outline cleanly with `focus:border-primary` without obscuring neighboring labels.

### 4.3 Side-Sliding Drawers (Slide-overs)

To maintain context without disrupting the operator's mental model:

* **Slide-over instead of center modals**: Forms, item registrations, and edits slide in from the screen's right edge.
* **Drawer Positioning**: `pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10`.
* **Overlay Backdrop**: `bg-slate-950/75` or `bg-black/80 transition-opacity`.
* **Prohibition of Backdrop Blur**: Never use `backdrop-blur-*` filters on drawers or overlays. Backdrop blur causes GPU font blurring and clipping anomalies on certain hardware setups.
* **DOM Placement**: Place drawer HTML blocks outside data tables (at the root of `@section('content')`) to prevent stacking context or layout disruption.

### 4.4 DataTables & Responsive Grids

* **Scroll Wrapping**: Any table with dense data columns must be enclosed in an `<div class="overflow-x-auto">` container.
* **Generous Spacing**: Table cells use standard `py-4 px-6` (1rem vertical, 1.5rem horizontal) padding to keep data legible.
* **Alternating Rows & Hover**: Dark mode uses subtle `hover:bg-slate-100/30 dark:hover:bg-slate-900/30` transitions.

---

## 5. Printable Document Standards (Invoices, Payslips & Reports)

When generating printed output or saving to PDF via the browser:

1. **Suppression of Flash & Status Alerts (`print:hidden`)**:
   * Flash status banners (such as *"Invoice generated successfully."* or *"Salary slip generated successfully."*) are strictly tagged with `print:hidden` and `[role="alert"]` suppression rules.
   * Toasts, navigation headers, sidebars, theme toggles, and action buttons are automatically hidden.
2. **Paper-Optimized Clean Palette**:
   * Backgrounds are reset to pure white (`#ffffff`).
   * All text is forced to crisp black (`#000000`).
   * Border boxes, shadow wrappers, and gray background fills are stripped.
   * Table headers feature a clean, solid 1.5px black line underneath (`thead th { border-bottom: 1.5px solid #000000 !important; }`), creating an executive, professional document aesthetic.
3. **Page Break Discipline**:
   * Crucial blocks (such as invoice signatures and job log items) use `break-inside: avoid;` (`page-break-inside-avoid`) to prevent awkward page splits.

