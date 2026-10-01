---
name: tailwindcss-development
description: "Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: styling or restyling any Blade view or component in this application, building responsive card grids and feed layouts, page structure (nav, setup pages, the tier list board), styling cards, tables, buttons, badges, forms and inputs, fixing spacing or typography, and any visual or UI change. Covers this project's semantic @utility classes, its Blade component conventions, and Tailwind v4 specifics. Skip for backend PHP logic, database queries, build tool configuration, and vanilla CSS unrelated to the theme files."
license: MIT
metadata:
  author: laravel
  customized-for: songrank.dev
---

# Tailwind CSS Development

Tailwind v4, CSS-first. Entry point is `resources/css/app.css`, which imports `tailwindcss`, the typography plugin, the Spatie comments stylesheet, and then the project's own component files from `resources/css/components/`.

## Use the Semantic Utilities, Not Raw Colors

`resources/css/components/colors.css` defines the palette as `@utility` classes. **Reach for these before writing `bg-purple-400` by hand** — they carry the hover, text and disabled states with them:

| Family | Classes |
| --- | --- |
| Text | `text-primary`, `text-secondary`, `text-helper`, `text-danger`, `text-primary-icon` |
| Background | `bg-primary`, `bg-primary-soft`, `bg-primary-muted`, `bg-primary-accent`, `bg-secondary`, `bg-helper`, `bg-danger` |
| Border | `border-primary`, `border-primary-soft`, `border-secondary`, `border-helper`, `border-danger` |
| Button | `btn-primary`, `btn-secondary`, `btn-helper`, `btn-danger`, `btn-animated` |
| Misc | `medal-gold`, `medal-silver`, `medal-bronze`, the `notification-bell-*` set |

The meanings are fixed: **primary is purple, secondary is green, helper is blue, danger is red.** `RankingType::color()` and `TierlistType::color()` return those same names (`purple`, `green`, `blue`), so a type's color in Blade and its color in the admin panel stay in step.

`btn-*` already includes `rounded-md shadow-md`, spacing, `cursor-pointer` and `disabled:` states — do not re-add them. `btn-animated` is the gradient call-to-action and carries its own keyframes from `animations.css`.

Add a new `@utility` to `colors.css` only when the pattern repeats across views. A one-off stays inline.

## This Application Does Not Do Dark Mode

Two of 149 Blade files contain a `dark:` variant, and both are vendor-published or message partials. **Do not add `dark:` variants.** The design is light-only: white cards (`bg-white shadow-md rounded-xl`) on a light page, with `text-zinc-800` for body copy. If dark mode is ever wanted it is a project-wide decision, not something to introduce one component at a time.

## Blade Components Are the Reuse Unit

`resources/views/components/`, grouped by domain (`rankings/`, `tierlists/`, `songs/`, `welcome/`, `card/`, `about/`, `leaderboards/`, `emails/`). The base card is the pattern to copy:

```blade
{{-- components/card/index.blade.php --}}
<div {{ $attributes->class(['bg-white shadow-md rounded-xl overflow-hidden']) }}>
    {{ $slot }}
</div>
```

```blade
{{-- a consumer --}}
@props(['tierlist'])

<x-card class="cursor-pointer hover:shadow-lg transition-all duration-300 p-4">
    ...
</x-card>
```

- `$attributes->class([...])` on the wrapper so consumers can add their own classes — never hardcode a class list a caller might need to extend
- `@props([...])` at the top, `@php` for view-local derivation right after
- `x-card.header` / `x-card.footer` exist for the full card; `card-placeholder` variants exist for loading states
- Offer to extract a repeated pattern into a component rather than copying markup between views

## Layout and Spacing

- `gap` utilities for space between siblings, not margins
- Responsive card grids: `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6`
- `card-scroller` (80vh) and `card-scroller-half` (40vh) from `layout.css` are the scroll containers for long track and entry lists — the setup pages stack two lists, which is what the half variant is for
- `k-line` / `k-line-light` are the project's underline treatment for headings
- Font Awesome class names are used for icons, and `RankingType::icon()` / `TierlistType::icon()` return them (`fa-music`, `fa-compact-disc`, …)

## Tailwind v4 Specifics

- Configuration is CSS-first with `@theme` in `app.css` (the project sets `--font-sans`). **There is no `tailwind.config.js`** and `corePlugins` does not exist in v4
- `@import "tailwindcss"`, never the v3 `@tailwind base/components/utilities` directives
- New utilities are declared with `@utility name { @apply ... }`
- `@source` directives in `app.css` pull vendor Blade views (support bubble) into the content scan — add one if a new package's views need scanning
- `app.css` keeps a `@layer base` block setting `border-color` to `--color-gray-200`, because v4 changed the default to `currentColor`. Leave it unless you are ready to add explicit border colors everywhere
- Replaced utilities: `bg-opacity-*` → `bg-black/*`, `flex-shrink-*` → `shrink-*`, `flex-grow-*` → `grow-*`, `overflow-ellipsis` → `text-ellipsis`, `bg-gradient-to-r` → `bg-linear-to-r`

## Verification

Vite must be running for a change to appear: `npm run dev`, or `npm run build` for a production bundle. If the user reports not seeing a change, ask which one they ran.

## Pitfalls

- Writing `bg-purple-400` where `bg-primary` exists, so a palette change misses it
- Adding `dark:` variants to a light-only application
- Re-declaring padding, rounding or disabled styles on top of a `btn-*` class
- Hardcoding classes on a component wrapper instead of `$attributes->class([...])`
- Looking for `tailwind.config.js`
- Using v3 utility names (`bg-gradient-to-r`, `flex-shrink-0`)
