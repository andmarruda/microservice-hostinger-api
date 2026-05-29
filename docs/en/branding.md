# Branding — Hostinger VPS Engineer Console

This document is the single source of truth for all visual and verbal identity decisions in this project. Follow it when building new pages, components, emails, or any other user-facing surface.

---

## 1. Identity

| Property | Value |
|----------|-------|
| **Product name** | Hostinger VPS |
| **Descriptor** | engineer console |
| **Company** | Novos Horizontes |
| **Tagline** | Engineer-grade infrastructure management |
| **Audience** | Software engineers and infrastructure operators |

### Full lockup
```
Hostinger VPS
engineer console          ← always lowercase, monospace, muted
```

### Abbreviated references
- In headings and navigation: **Hostinger VPS**
- In prose: "the console", "the platform"
- Never: "Laravel app", "the system", "the tool"

---

## 2. Color Palette

### Brand (primary action, highlights, CTAs)

| Token | Hex | Usage |
|-------|-----|-------|
| `--color-brand-50` | `#f5f3ff` | Tinted backgrounds on light surfaces |
| `--color-brand-100` | `#ede9fe` | Hover state backgrounds (light mode) |
| `--color-brand-200` | `#ddd6fe` | Borders on light surfaces |
| `--color-brand-400` | `#a78bfa` | Icons and accents on dark surfaces |
| `--color-brand-500` | `#8b5cf6` | Interactive highlights |
| `--color-brand-600` | `#7c3aed` | **Primary action color** — buttons, links |
| `--color-brand-700` | `#6d28d9` | Hover state for primary actions |
| `--color-brand-800` | `#5b21b6` | Borders on dark surfaces |
| `--color-brand-900` | `#4c1d95` | Dark backgrounds with brand tint |
| `--color-brand-950` | `#2e1065` | Deepest brand background (badges, pills) |

> `brand-600` is the canonical primary. Use it for every CTA, active state indicator, and brand accent.

### Neutral (surfaces, text, borders)

The UI is built on Tailwind's `slate` scale for dark surfaces and `gray` for light surfaces.

| Role | Light surface | Dark surface |
|------|--------------|-------------|
| Page background | `white` / `gray-50` | `slate-950` |
| Card / panel | `white` | `slate-900` |
| Border | `gray-200` | `slate-800` / `brand-800` |
| Body text | `slate-900` | `white` |
| Secondary text | `slate-500` | `slate-400` |
| Placeholder / muted | `gray-400` | `slate-600` |

### Semantic

| Meaning | Background | Text | Border | Tailwind classes |
|---------|-----------|------|--------|-----------------|
| Success | `green-50` | `green-800` | `green-200` | `bg-green-600` for buttons |
| Warning | `yellow-50` | `yellow-800` | `yellow-200` | — |
| Destructive | `red-50` | `red-600` / `red-800` | `red-200` | `bg-red-600` for buttons |
| Informational | `blue-50` | `blue-800` | `blue-200` | — |

---

## 3. Typography

### Fonts

| Role | Family | CSS token | Load source |
|------|--------|-----------|-------------|
| UI sans | Instrument Sans 400/500/600 | `--font-sans` | Bunny Fonts CDN |
| Code / terminal | ui-monospace → Cascadia Code → Source Code Pro → Menlo | `--font-mono` | System stack |

### Scale (Tailwind defaults, applied consistently)

| Element | Size | Weight | Class example |
|---------|------|--------|---------------|
| Page heading (h1) | 36–48 px | 600 | `text-4xl font-semibold tracking-tight` |
| Section heading (h2) | 24 px | 600 | `text-2xl font-semibold tracking-tight` |
| Card title | 16 px | 500 | `text-base font-medium` |
| Body | 14 px | 400 | `text-sm` |
| Caption / badge | 12 px | 400–500 | `text-xs` |
| Terminal label | 12 px | 400 | `font-mono text-xs` |

### Monospace usage rules
Use `font-mono` for:
- Server hostnames and IP addresses
- VPS IDs and resource identifiers
- Terminal output and command strings
- Technical descriptors rendered inline with prose (e.g., `engineer console` badge)

---

## 4. Logomark

The logomark is a simplified server-rack SVG. It should appear alongside the product name in every authenticated and unauthenticated shell.

### Construction
```
36×36 px container, rounded-lg (rx=8)
├── Outer rect: brand-950 background, 15% opacity brand-600 fill
├── Top server unit: stroke brand-400, 1.5px, rx=2
├── Bottom server unit: stroke brand-400, 1.5px, rx=2
├── Status LEDs: brand-600 fill, cx=12.5
└── Drive bars: brand-400 fill at 50% opacity
```

### On dark backgrounds (e.g., branding panel, emails on dark)
- Container fill: `brand-600` at 15% opacity
- Strokes: `brand-400` (`#a78bfa`)
- LEDs: `brand-600` (`#7c3aed`)

### On light backgrounds (e.g., mobile logo, emails on light)
- Container fill: `brand-100`
- Strokes: `brand-600` (`#7c3aed`)
- LEDs: `brand-700` (`#6d28d9`)

### Clear space
Maintain at least `8px` of clear space on all sides. Never place the logomark directly against a border.

### What not to do
- Do not recolor the logomark with gray or black
- Do not stretch or skew the SVG
- Do not use the logomark without the product name at sizes below 24×24 px

---

## 5. UI Components

All components live under `resources/js/components/ui/`. Below is the canonical variant table for each.

### Button

| Variant | Background | Text | Use for |
|---------|-----------|------|---------|
| `default` | `gray-900` | white | General actions, form submits |
| `destructive` | `red-600` | white | Delete, revoke, terminate |
| `outline` | white / `gray-50` | `gray-700` | Secondary actions |
| `ghost` | transparent / `gray-100` on hover | `gray-700` | Icon buttons, table row actions |
| `link` | — | `gray-900` underline | Inline text links |
| `success` | `green-600` | white | Confirm, approve |

For **primary brand actions** (login submit, main CTA per page), use the brand color directly via `className`:
```
className="bg-brand-600 text-white hover:bg-brand-700 ..."
```
This is intentional — the `Button` component's `default` variant uses `gray-900` for general UI consistency, while brand-colored buttons are reserved for the most prominent action on a screen.

### Badge

| Variant | Use for |
|---------|---------|
| `default` | Neutral status, labels |
| `success` | Running, active, approved |
| `warning` | Pending, expiring soon, review needed |
| `destructive` | Stopped, failed, revoked, overdue |
| `info` | Informational state, queued |
| `outline` | Read-only tags, non-semantic labels |

### Alert

| Variant | Use for |
|---------|---------|
| `default` | General informational notices |
| `success` | Action completed successfully |
| `warning` | Non-blocking issue that needs attention |
| `destructive` | Error, failed operation, access denied |

### Input / Label
- Always pair every `<Input>` with a `<Label>` pointing to the same `id`
- Error state: add `border-red-400 focus-visible:ring-red-400` to the input and render a `<p className="text-xs text-red-600">` immediately below it

---

## 6. Layout Patterns

### Login / unauthenticated shell
Two-column full-screen layout:
- **Left (60%, `lg+`):** `bg-slate-950` with `dot-grid` utility + radial brand gradient. Contains logo, tagline, feature list, company footer.
- **Right (40%):** `bg-white`. Contains logo (mobile only), form, and CTA.

Responsive: the left panel is `hidden` on screens below `lg`. On mobile the right panel is full-screen with the logo at the top.

### Authenticated shell (`AppLayout`)
- Sidebar: `bg-gray-900` with `text-white` nav links; active item: `bg-gray-800 text-white font-medium`
- Content area: `bg-gray-50` or `bg-white`
- Topbar: `bg-white border-b border-gray-200`

### Background texture
The `dot-grid` CSS utility produces a 24×24 px repeating SVG dot pattern (1 px dots at 6% white opacity). Use it exclusively on dark surfaces (`slate-900` and darker) to add depth without distraction.

### Elevation (shadow)
| Level | Class | Use for |
|-------|-------|---------|
| 0 | — | Flat tables, sidebar items |
| 1 | `shadow-sm` | Cards, panels, modals |
| 2 | `shadow-md` | Dropdowns, floating elements |

---

## 7. Motion

- Use `transition-colors` (150 ms) on all interactive elements for hover/focus color changes
- Use `animate-spin` exclusively for loading spinners (e.g., button in processing state)
- Avoid layout animations or scroll-triggered effects — this is a dense data UI, not a marketing page

---

## 8. Voice & Tone

### Principles
1. **Terse and direct.** Engineers read dashboards, not copy. Every label, heading, and message should be as short as possible while remaining unambiguous.
2. **Active voice.** "Sign in" not "Login". "Terminate server" not "Server termination".
3. **Honest about state.** Never say "Done!" when an operation is queued. Use `queued`, `running`, `succeeded`, `failed`.

### Vocabulary

| Prefer | Avoid |
|--------|-------|
| Sign in | Login (noun form as verb) |
| Engineer | User, end user |
| VPS | Server (unless domain context requires it) |
| Terminate | Delete (for VPS lifecycle) |
| Grant access | Add user |
| Revoke access | Remove user |
| Audit log | Activity log, history |

### Error messages
- One sentence, sentence case, no trailing period in inline field errors
- For generic auth errors: "These credentials do not match." (period included — complete sentence)
- Never expose internal exceptions or stack traces in the UI

### Monospace identifiers
Render all hostnames, IP addresses, UUIDs, and VPS IDs in `font-mono`. Example:
```
192.168.1.1    → <code className="font-mono">192.168.1.1</code>
vps-abc-123    → <code className="font-mono">vps-abc-123</code>
```

---

## 9. Do / Don't

### Color
| Do | Don't |
|----|-------|
| Use `brand-600` for the single primary CTA per screen | Use brand color on every button |
| Use semantic colors (green/red/yellow) for status | Use brand purple to indicate status |
| Use `slate-*` for dark surfaces | Mix `slate` and `zinc` or `gray` on the same dark surface |

### Typography
| Do | Don't |
|----|-------|
| Use `font-mono` for technical identifiers | Use `font-mono` for body copy |
| Use `tracking-tight` on headings ≥ 24 px | Add extra letter spacing to small text |
| Keep headings sentence case | Use ALL CAPS headings |

### Components
| Do | Don't |
|----|-------|
| Use `variant="destructive"` on the button that confirms deletion | Use `destructive` on a button that navigates to a deletion confirmation |
| Show a spinner inside the button during async actions | Disable the form and show a separate loading overlay |
| Use `Badge variant="success"` for running VPS | Use raw colored `<span>` tags |
