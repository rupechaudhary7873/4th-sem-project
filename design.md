---
name: Fiscal Precision
colors:
  surface: '#faf8ff'
  surface-dim: '#d9d9e5'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f3fe'
  surface-container: '#ededf9'
  surface-container-high: '#e7e7f3'
  surface-container-highest: '#e1e2ed'
  on-surface: '#191b23'
  on-surface-variant: '#434655'
  inverse-surface: '#2e3039'
  inverse-on-surface: '#f0f0fb'
  outline: '#737686'
  outline-variant: '#c3c6d7'
  surface-tint: '#0053db'
  primary: '#004ac6'
  on-primary: '#ffffff'
  primary-container: '#2563eb'
  on-primary-container: '#eeefff'
  inverse-primary: '#b4c5ff'
  secondary: '#006c4a'
  on-secondary: '#ffffff'
  secondary-container: '#82f5c1'
  on-secondary-container: '#00714e'
  tertiary: '#943700'
  on-tertiary: '#ffffff'
  tertiary-container: '#bc4800'
  on-tertiary-container: '#ffede6'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#85f8c4'
  secondary-fixed-dim: '#68dba9'
  on-secondary-fixed: '#002114'
  on-secondary-fixed-variant: '#005137'
  tertiary-fixed: '#ffdbcd'
  tertiary-fixed-dim: '#ffb596'
  on-tertiary-fixed: '#360f00'
  on-tertiary-fixed-variant: '#7d2d00'
  background: '#faf8ff'
  on-background: '#191b23'
  surface-variant: '#e1e2ed'
  income-green: '#059669'
  expense-red: '#DC2626'
  warning-amber: '#D97706'
  surface-white: '#FFFFFF'
  bg-gray: '#F4F6F9'
  border-subtle: '#E2E8F0'
typography:
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  stat-lg:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-bold:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  table-header:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '600'
    lineHeight: 18px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  container-max: 1280px
  gutter: 1.5rem
  margin-mobile: 1rem
  stack-sm: 0.5rem
  stack-md: 1rem
  stack-lg: 2rem
---

## Brand & Style
The design system is engineered for **Budget Manager**, a personal finance platform that prioritizes clarity, trust, and actionable insights. The brand personality is professional yet accessible—acting as a reliable financial advisor rather than a cold institutional tool.

The visual style follows a **Corporate / Modern** aesthetic with a strong emphasis on **Minimalism**. It utilizes high-contrast typography and a structured grid to manage dense financial data without overwhelming the user. 

**Key Principles:**
- **Clarity over Ornament:** Every element must serve a functional purpose in financial tracking.
- **Trust through Consistency:** Standardized patterns for data entry and status reporting to ensure the user feels in control.
- **Immediate Feedback:** Use of color-coded accents (Income/Expense) to provide instant cognitive mapping of financial health.

## Colors
This design system utilizes a "Professional Blue" primary color to establish authority and reliability. The palette is strictly categorized to prevent visual confusion:

- **Primary (#2563EB):** Reserved for primary actions (buttons), navigation highlights, and active states.
- **Income Green (#059669):** Used exclusively for positive cash flow, savings growth, and "On Track" status badges.
- **Expense Red (#DC2626):** Used for outflows, "Exceeded" budget warnings, and destructive actions (delete).
- **Neutral/Background (#F4F6F9):** Provides a soft, low-contrast canvas that reduces eye strain during long sessions of data review.
- **Surface (#FFFFFF):** All content containers (cards, modals) use pure white to pop against the gray background.

## Typography
The system relies entirely on **Inter** for its exceptional legibility in data-heavy environments. 

**Usage Guidelines:**
- **Headlines:** Use `headline-lg` for page titles and `headline-md` for card titles.
- **Financial Figures:** Use `stat-lg` for Rupee amounts in summary cards to ensure high prominence.
- **Tables:** Header rows use `label-bold` with a muted gray color to distinguish from the data rows.
- **Currency:** Rupee symbols should match the weight of the associated value but can be sized 10% smaller to keep focus on the number.

## Layout & Spacing
The design system employs a **12-column fluid grid** for desktop, transitioning to a single-column stack for mobile devices.

- **Grid Logic:** Use a 24px (1.5rem) gutter between cards. 
- **Dashboards:** Stat cards typically span 3 columns each (4-up) on desktop, 6 columns on tablet, and 12 columns on mobile.
- **Sidebar/Nav:** The top navigation is fixed with a height of 64px.
- **Zebra Tables:** Use a 4px vertical padding for rows to maintain a "compact yet airy" feel. Alternate row background colors using `#F8FAFC`.

## Elevation & Depth
Hierarchy is established using **Tonal Layers** combined with **Ambient Shadows**.

- **Level 0 (Background):** `#F4F6F9` — The lowest layer.
- **Level 1 (Cards):** `#FFFFFF` surface with a subtle shadow (`0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1)`).
- **Level 2 (Modals/Dropdowns):** Elevated cards with a more pronounced shadow to indicate focus and interaction.
- **Outlines:** Use `1px solid #E2E8F0` for all cards and input fields to maintain sharp definition between white surfaces and the light gray background.

## Shapes
The shape language is consistently **Rounded** (8px / 0.5rem) to soften the professional aesthetic and make the application feel modern and user-friendly.

- **Buttons & Inputs:** 0.5rem border radius.
- **Cards:** 0.75rem (rounded-lg) to provide a clear container for data.
- **Badges:** Use a "Pill" shape (9999px) for status indicators (e.g., "On Track", "Exceeded") to distinguish them from interactive buttons.
- **Progress Bars:** Fully rounded (pill) tracks and indicators.

## Components

### Buttons
- **Primary:** Solid Professional Blue with white text.
- **Secondary/Action:** Ghost buttons with Professional Blue borders for "Edit" actions.
- **Destructive:** Solid Expense Red for "Delete" or "Revoke" actions.

### Tables (Financial Focus)
- **Zebra Striping:** Even rows use `#F8FAFC`.
- **Alignments:** Text should be left-aligned, but **Currency/Amount columns must be right-aligned** for easier comparison of values.
- **Status Badges:** Small pill-shaped containers with 10% opacity backgrounds of the status color (e.g., Light Green background with Dark Green text).

### Form Inputs
- **Style:** White background, 1px border (`#E2E8F0`), 8px roundedness.
- **Focus State:** 2px solid Professional Blue border with a soft blue outer glow.

### Progress Bars
- **Container:** Light gray background.
- **Fill:** Dynamic coloring based on value (Green < 80%, Amber 80-99%, Red >= 100%).

### Stat Cards
- Feature an emoji icon in a soft-colored circular background (20% opacity of the associated brand color).
- Large Rs figures using the `stat-lg` typography token.