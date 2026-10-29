# Dupay Admin — DeWallet dark UI rebuild

This rebuilds the **look and feel** of the admin panel to match the DeWallet
dashboard (dark theme, mint `#00E5A0` accent, Space Grotesk / Inter / JetBrains
Mono type). **No feature was removed.** All feature logic lives in the individual
module blades and controllers, which are left untouched — only the shared layout
layer + dashboard + login were replaced. Because the theme restyles the shared
component classes (`.widget`, `.card`, `.table`, `.btn-*`, `.modal-*`,
DataTables, select2, breadcrumbs, badges, switches, forms), every existing page
(users, transactions, cards, foreign accounts, VAT, exchange rates, etc.)
inherits the new look automatically.

## What's in this package

All paths are relative to `resources/` on the server.

| File | Status | Purpose |
|------|--------|---------|
| `views/admin/layouts/theme.blade.php` | **NEW** | The whole dark theme (global override of Cork/Bootstrap components). Change one variable `--accent` to recolor the panel. |
| `views/admin/layouts/head.blade.php` | replaced | Loads fonts + core plugin CSS, then the theme last. |
| `views/admin/layouts/app.blade.php` | replaced | Clean topbar + content layout (removed the old "nuclear scroll fix"). |
| `views/admin/layouts/sidebar.blade.php` | replaced | DeWallet grouped nav. **Every link and every `@can` permission check is preserved.** |
| `views/admin/layouts/bottom.blade.php` | replaced | Same scripts as before (DataTables etc. still work), minus the DOM-nuking scroll hack. |
| `views/admin/dashboard/index.blade.php` | replaced | Dashboard reskinned to dark. All `@can` stat blocks + visitor chart preserved. |
| `views/admin/auth/login.blade.php` | replaced | Dark DeWallet login (same form action + fields). |

## Install

1. **Back up first** (from the project root):
   ```bash
   cp -r resources/views/admin resources/views/admin_backup_$(date +%F)
   ```
2. Copy the `views/` folder from this package over `resources/views/`
   (it only overwrites the 7 files above).
3. Clear Laravel's view cache:
   ```bash
   php artisan view:clear
   ```
4. Hard-refresh the browser (Ctrl/Cmd+Shift+R) to drop cached CSS.

## About the bug (scrolling)

The old `app.blade.php` and `bottom.blade.php` carried a large "nuclear scroll
fix" that destroyed PerfectScrollbar by force. That's a symptom of the Cork
template's fixed-sidebar + PerfectScrollbar conflict. The rebuild uses a plain
fixed sidebar + natively-scrolling content and disables PerfectScrollbar cleanly
in one small block, so the page scrolls normally without the hacks.

## Recolor / tweak

Open `theme.blade.php` and edit the `:root` block. The main knob is:
```css
--accent:#00E5A0;   /* mint — change to rebrand the whole panel */
```

## Notes / please verify after deploy

- `bottom.blade.php` keeps `vendors.min.js`, the Bootstrap bundle, `mousetrap`,
  and `custom.js`. The list/form pages load `general.js` and the DataTables CSS
  themselves, so tables keep working. If any DataTable stops rendering, confirm
  DataTables' JS is bundled in `vendors.min.js`; if not, add its script tag to
  `bottom.blade.php`.
- `head.blade.php` no longer pulls the external `designreset.com` Cork
  stylesheet (it was the light theme and an external dependency). The theme file
  replaces everything it provided for the components in use.
