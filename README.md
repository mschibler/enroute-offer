# Enroute Offers – WordPress Plugin

## Overview

Manages **Offers**, **Stations** and **Resources** with multilingual UI support (DE, FR, IT) and front-end listings powered by Tailwind CSS + Alpine JS.

---

## Installation

1. Upload the `enroute_offers` folder to `/wp-content/plugins/`.
2. Activate the plugin in **Plugins → Installed Plugins**.
3. The three custom post types and all taxonomies are immediately available.

---

## Tailwind CSS setup (important)

The plugin registers a placeholder stylesheet at `public/css/tailwind.css`.  
You have two options:

**Option A – CDN (quick start)**  
Add to your theme's `functions.php` or `header.php`:
```html
<script src="https://cdn.tailwindcss.com"></script>
```
Then comment out the `enroute-tailwind` enqueue in `enroute_offers.php`.

**Option B – Compiled (recommended for production)**  
Install Tailwind and scan the plugin's template directory:
```bash
npm install -D tailwindcss
npx tailwindcss -i ./src/input.css \
  -o ./public/css/tailwind.css \
  --content "./templates/**/*.php" \
  --minify
```

---

## Shortcodes

| Shortcode | Description |
|---|---|
| `[enroute_offers_listing]` | Renders the offers grid with filter modal |
| `[enroute_resources_listing]` | Renders the resources grid with filter modal |

Place these shortcodes on any page.

---

## Post Types & Fields

### Offer
- **Title** (WP native)
- **Featured Image** (WP native thumbnail)
- **Description** – textarea
- **Price** – text
- **Station** – dropdown (selects a Station post)
- **Language** – DE / FR / IT / EN
- **Weekday** – Mon–Sun
- **Time of Day** – Morning / Afternoon / Evening
- **Additional Date Information** – textarea
- **Fixed Date** – date picker (optional)
- **Resources** – multi-checkbox (selects Resource posts)
- **External Links** – repeatable URL fields
- **Taxonomies**: Subject, Target Group, Offer Type, Tags

### Station
- **Name** (WP title)
- **Portrait / Description** – textarea
- **Address** – street & number
- **PLZ** – postal code
- **Place** – city
- **Taxonomy**: Tags

### Resource
- **Title** (WP native)
- **File** – media library picker (PDF, Word, Excel, PowerPoint)
- **Language** – DE / FR / IT / EN
- **Taxonomies**: Subject, Target Group, Resource Type, Tags

---

## Taxonomies

| Taxonomy | Slug | Applied to |
|---|---|---|
| Subject | `offer_subject` | Offer, Resource |
| Target Group | `offer_target_group` | Offer, Resource |
| Offer Type | `offer_type` | Offer |
| Resource Type | `resource_type` | Resource |
| Tags | `offer_tag` | Offer, Station, Resource |

---

## Translations

Translation files live in `languages/`:

| File | Locale |
|---|---|
| `enroute_offers-de_DE.po` | German |
| `enroute_offers-fr_FR.po` | French |
| `enroute_offers-it_IT.po` | Italian |

Compile `.po` → `.mo` with [Poedit](https://poedit.net/) or:
```bash
msgfmt languages/enroute_offers-de_DE.po -o languages/enroute_offers-de_DE.mo
msgfmt languages/enroute_offers-fr_FR.po -o languages/enroute_offers-fr_FR.mo
msgfmt languages/enroute_offers-it_IT.po -o languages/enroute_offers-it_IT.mo
```

---

## Classic Editor

The plugin forces the **Classic Editor** experience for all three post types by setting `'show_in_rest' => false` and not including `editor` in `supports`. Install the [Classic Editor plugin](https://wordpress.org/plugins/classic-editor/) for the full classic editing experience.

---

## File Structure

```
enroute_offers/
├── enroute_offers.php          ← Main plugin file
├── README.md
├── admin/
│   └── admin.css               ← Meta box styles
├── includes/
│   ├── post-types.php          ← CPT registration
│   ├── taxonomies.php          ← Taxonomy registration
│   ├── meta-boxes.php          ← Meta box callbacks (render)
│   ├── meta-save.php           ← Meta save handlers
│   ├── shortcodes.php          ← Shortcode registration
│   └── helpers.php             ← Utility functions
├── languages/
│   ├── enroute_offers-de_DE.po
│   ├── enroute_offers-fr_FR.po
│   └── enroute_offers-it_IT.po
├── public/
│   ├── css/
│   │   └── tailwind.css        ← Compiled Tailwind (or placeholder)
│   └── js/
│       └── listings.js         ← Alpine JS components
└── templates/
    ├── offers-listing.php      ← Offer grid + filter modal
    └── resources-listing.php   ← Resource grid + filter modal
```
