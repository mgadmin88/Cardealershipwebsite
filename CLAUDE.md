# Car dealership site — working rules

- The owner wants **every update pushed to this repo (branch `main`)**. Never finish a change without committing and pushing it.
- Repo layout mirrors the live host's `public_html`: `app/`, `public/`, `storage/`, root `.htaccess`. Keep it that way.
- Never commit `config.php`, databases, sessions, logs, `public/uploads/*` (see `.gitignore`).
- Every release: add a Persian entry to `CHANGELOG.md` (newest first, Jalali date) and give the owner a zip of only the changed files, laid out to extract into `public_html`.
- Schema changes go through `Schema::VERSION` + `Schema::upgrade()` (runs automatically on live sites); never require a reinstall.
- The live host runs PHP 8.3 + MySQL 8, without `ext-fileinfo`. Don't depend on optional extensions without a fallback; test on MySQL as well as SQLite.
- UI is Persian RTL. When replying to the owner, write in Persian and start lines with Persian words (Latin/code at line start renders left-aligned for them).

## Licensing (vendor-side)
- Ed25519 signed license keys. Site (`app/lib/License.php`) holds only the PUBLIC key. Manager app: `tools/license-manager/` (vendor only). Manager replies are signed and stored as-is in `storage/license.state`.
- `tools/` and `license-keys/` are excluded from customer zips (update + full install). `storage/license.key` is the per-install license, gitignored.
- Keys bind plan (1m/1y/custom), domain, and expiry. Expired/invalid ⇒ whole site shows the license screen; only admin login + `/admin/license` stay open. Enforcement toggled by `config('license.enabled')` (default true).
- See LICENSING.md (also vendor-only; keep out of customer zips).
