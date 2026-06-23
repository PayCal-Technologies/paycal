# PayCal PHP File Categories

Total PHP files (excluding vendor): **1,209**

## Categories by count

| Count | Category | Description |
|------:|----------|-------------|
| 235 | `tests` | PHPUnit, integration, contract, exploit tests |
| 215 | `transparency-pages` | Public transparency docs (multi-locale) |
| 158 | `domain-domain` | Domain layer — many emit HTML (Earnings, DataGrid, Layout, Render) |
| 108 | `css-php-served` | CSS bundles served via PHP (no UI markup) |
| 53 | `templates` | Reusable HTML partials (calendar, contact, email, nav) |
| 49 | `scripts-cli` | Migrations, crypto, maintenance scripts |
| 44 | `js-php-served` | JS modules served via PHP — often inject UI strings/ARIA |
| 43 | `public-marketing-pages` | About, contact, FAQ, blog, media, premium, security |
| 37 | `repo-other` | Root-level misc PHP |
| 34 | `business-pages` | Business workspace pages + partials |
| 32 | `help-pages` | Help center (multi-locale) |
| 31 | `extensions` | Extension hooks/manifests (some emit HTML) |
| 26 | `domain-controllers` | HTTP controllers |
| 25 | `domain-observability` | Lens, Argus, logging |
| 22 | `domain-infrastructure` | Persistence, queues, transactions |
| 20 | `tools-cli` | Dev/seed tools |
| 19 | `admin-pages` | Admin dashboard surfaces |
| 18 | `fonts-fpdf` | FPDF font definitions |
| 16 | `html-other` | Miscellaneous HTML pages (api, ws, cli, etc.) |
| 9 | `user-feature-pages` | Settings, reports, forecast |
| 6 | `auth-pages` | Auth, verify, unverified |
| 3 | `core-app-pages` | Calendar (`html/index.php`) |
| 3 | `core-shell` | `header.php`, `footer.php`, `config.php` |
| 3 | `domain-other` | Other src files |

## Five ARIA analysis groups

Manifest with full file lists: `docs/a11y/php-file-groups.json`

| Group | Files | Scope |
|-------|------:|-------|
| **G1** core-shell-auth-templates | 63 | Shell, auth, calendar entry, templates |
| **G2** user-features-sites | 11 | Settings, reports, sites, pay periods |
| **G3** business-workspace | 34 | Business subpages, partials, dialogs |
| **G4** public-docs-admin | 324 | Transparency, help, marketing, admin |
| **G5** domain-renderers-js-ui | 309 | Domain HTML emitters, JS-served UI, extensions |

**Excluded from primary ARIA sweep (467 files):** tests, css-php-served, scripts-cli, tools-cli, fonts-fpdf, repo-other — unless they emit HTML.
