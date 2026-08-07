# WP PostgreSQL Database — Brand & Asset System

Everything in `.wordpress-org/` is generated. `icon.svg` and
`resources/brand/banner.html` are the only files edited by hand — never touch
the PNGs directly, they are overwritten on every render.

## Asset inventory

| File | Dimensions | Purpose |
|---|---|---|
| `icon.svg` | vector | Canonical icon; the directory consumes this directly when present |
| `icon-256x256.png` · `icon-128x128.png` | 256 · 128 | Directory hero and grid |
| `icon-512x512.png` | 512 | Channels outside the directory |
| `banner-1544x500.png` · `banner-772x250.png` | — | Desktop and mobile directory banners |
| `banner-1024x512.png` | 1024×512 | Square-ish crop for other channels |
| `screenshot-*.png` | 1200×900 | Listing screenshots, once there are any |

## Palette

**It lives in `tests/assets/brand.ts`, and only there.** `icon.svg` repeats the
values because SVG cannot import, and its header says so.

| Token | Hex |
|---|---|
| `ink` | `#1e1b4b` |
| `inkMid` | `#312e81` |
| `inkLift` | `#3730a3` |
| `royal` | `#4f46e5` |
| `royalLight` | `#6366f1` |
| `sky` | `#818cf8` |
| `accent` | `#a5b4fc` |
| `glyphMid` | `#eef2ff` |
| `glyphBase` | `#c7d2fe` |

Tailwind indigo. Indigo, which is as near PostgreSQL's own blue as an unaffiliated plugin should get.

## The mark

The database cylinder. Every diagram of a datastore since the 1970s has used it, and this plugin replaces exactly that box.

## Regenerating

```bash
yarn install        # once
yarn shots:banners  # icon + banner PNGs; no site needed
```

Screenshots need a running site and a `shots` project; this plugin has the
banner half of the pipeline only, until there are screens worth capturing.
