# cms2 — ΕΑΡΕΣ WordPress

New WordPress site for ΕΑΡΕΣ (Ένωση Αποφοίτων Ριζαρείου Εκκλησιαστικής Σχολής). It runs under Docker Compose and is rebuilt from code: WordPress core, plugins and roles are never changed through the browser.

## Quick start (local dev)

```sh
cp .env.example .env        # then change the passwords
docker compose up -d        # WordPress, MariaDB, Mailpit
scripts/setup.sh            # install + configure (idempotent, safe to re-run)
scripts/seed-users.sh       # optional: one fake test user per role
scripts/smoke-test.sh       # optional: check roles, hardening and the login limit
```

| URL | What |
|---|---|
| http://localhost:8080/wp-admin | WordPress (admin credentials from `.env`) |
| http://localhost:8025 | Mailpit. Every email the site sends ends up here: invites, password resets, 2FA codes. |
| http://localhost:8081 | Adminer, optional: `docker compose --profile tools up -d adminer` |

To start over from nothing, run `docker compose down -v` and then repeat the quick start.

## Roles

| Role | Who | Can |
|---|---|---|
| Administrator | Site admin + 1 backup | Everything |
| **Διαχειριστής Χρηστών** (`eares_user_manager`) | Office / secretary | Everything an Editor can do. Can also create, edit, delete and change the role of users, and reset their 2FA. **Cannot** see, edit or assign Administrators or other User Managers. |
| Editor | ΔΣ, Ριζαρείτης editorial team | Publish and edit all content, moderate comments. Their HTML is filtered: no `<script>`, iframes or inline event handlers. |
| Author | Trusted alumni | Publish their own posts |
| Contributor | Other alumni | Write drafts and upload images; an Editor publishes them |
| **Ανενεργός** (`eares_inactive`) | Former board members etc. (case by case) | Nothing. Login is refused and existing sessions are ended. Their posts and name stay on the site. |

Self-registration is off. Only Administrators and User Managers create accounts.

Only Administrators may post unfiltered HTML. If an Editor or User Manager could, a `<script>` in a post would run in an Administrator's browser and get around every protection above.

The roles are defined in `wp-content/mu-plugins/eares-roles.php`. After you change a capability there, bump `EARES_ROLES_VERSION`; the roles are rebuilt on the next request.

## Two-factor authentication

2FA uses the [Two-Factor](https://wordpress.org/plugins/two-factor/) plugin. It is **optional for everyone**, and each user enables it from their own profile. Three methods are offered:

- an authenticator app (Google Authenticator)
- backup (recovery) codes
- a code sent by email

If someone is locked out, an Administrator or User Manager opens **Users**, hovers over the person, and clicks **Επαναφορά 2FA**. The reset is recorded in Simple History, and the user gets an email about it.

**Before resetting, confirm who is asking.** Hang up and call back the phone number on their profile, never a number given during the call. A reset leaves the account protected by its password alone.

After someone other than the user changes an account's email address, a User Manager cannot reset its 2FA for 7 days; only an Administrator can. This stops "change the email, reset 2FA, reset the password" from taking over an account.

The Greek step-by-step guide for members is in [`docs/2fa-odigos.md`](docs/2fa-odigos.md). It is meant to be exported to PDF and published as a help page.

## Logins

Five failed logins for one username from one IP, or twenty from one IP, lock that IP out for 15 minutes, even with the right password. Lockouts appear in Simple History.

Behind a reverse proxy, set `TRUST_PROXY=1` in `.env`. Otherwise every visitor appears to come from the proxy's IP, and one attacker locks everybody out.

Author pages use a `member-…` slug instead of the login name (`/author/member-5a5385c0b6/`), so post bylines do not reveal logins.

## Users screen

Two extra sortable columns:

- **2FA**: on or off.
- **Τελευταία σύνδεση** (last login): accounts with no login for 12+ months get a red flag.

The **Χωρίς σύνδεση 12+ μήνες** view lists only the flagged accounts. Nothing happens to them automatically; an admin decides what to do.

The activity log (Simple History) is visible to Administrators and User Managers only. User Managers do not see events by or about Administrators and other User Managers.

## What lives where

| Path | Purpose |
|---|---|
| `docker-compose.yml` | The services, with pinned image versions. The DB is on an internal-only network, and ports are bound to 127.0.0.1. `wp-config` hardening is set here: `DISALLOW_FILE_EDIT`, `DISALLOW_FILE_MODS` except under wp-cli, `FORCE_SSL_ADMIN` and proxy HTTPS detection. |
| `scripts/setup.sh` | Runs wp-cli to install core, the Greek language pack, the plugins and the settings, then `update.sh`. Refuses the `change-me` passwords outside `local`. |
| `scripts/update.sh` | Updates core, plugins and translations. |
| `scripts/backup.sh` | Backs up the database and uploads into `backups/`. |
| `scripts/seed-users.sh` | Creates fake test users (`seed-users.php`). Refuses to run in production. |
| `scripts/smoke-test.sh` | End-to-end checks against a running stack (`smoke-test.php` + curl). CI runs it. |
| `wp-content/mu-plugins/eares-roles.php` | Custom roles and anti-escalation filters (`editable_roles`, `map_meta_cap`, hiding protected users). |
| `wp-content/mu-plugins/eares-users-admin.php` | Last login, the 2FA and Last-login columns, the Reset 2FA action and its 7-day email-change guard, the Inactive login block, the allowed 2FA methods and what the activity log shows to whom. |
| `wp-content/mu-plugins/eares-login-limit.php` | Locks out repeated failed logins. |
| `wp-content/mu-plugins/eares-profile.php` | Private phone field (not exposed through REST; included in personal data export and erasure) and a simplified profile screen. |
| `wp-content/mu-plugins/eares-hardening.php` | Keeps registration off. Disables XML-RPC and application passwords, which would bypass 2FA. Blocks anonymous access to REST `/users`, `?author=N` and the users sitemap. Gives author pages `member-…` slugs. |
| `wp-content/mu-plugins/eares-mail.php` | Sends mail over SMTP using the `SMTP_*` settings in `.env` (Mailpit locally). Uses STARTTLS whenever the server offers it. |
| `.github/workflows/ci.yml` | CI: PHP syntax, PHPCS, shellcheck, then the full stack with the smoke test and a backup. |

## Updating

The browser can't update anything, and pulling a newer `wordpress` image doesn't change WordPress core either: core lives in the `wp_data` volume. So updates happen only this way, **at least once a month** and whenever a security release comes out:

```sh
scripts/backup.sh
scripts/update.sh
```

A new `wordpress` image tag in `docker-compose.yml` updates PHP and Apache only.

## Backup & restore

`scripts/backup.sh` writes `backups/db-<time>.sql.gz` and `backups/uploads-<time>.tar.gz`. Copy them off the server. Core and plugins are not included; `setup.sh` reinstalls them.

To restore into a running stack:

```sh
set -a; source .env; set +a
gunzip -c backups/db-<time>.sql.gz \
  | docker compose exec -T -e MYSQL_PWD="$DB_ROOT_PASSWORD" db mariadb -uroot "$DB_NAME"
docker compose exec -T -u www-data wordpress tar -C /var/www/html/wp-content -xz \
  < backups/uploads-<time>.tar.gz
```

## Checks

```sh
composer install && vendor/bin/phpcs   # WordPress coding standards
shellcheck scripts/*.sh
scripts/smoke-test.sh                  # needs the stack + seed-users.sh
```

CI (`.github/workflows/ci.yml`) runs all three on every push.

## Language

Greek is the site language. Users can switch their admin screens to English from their profile. The ΕΑΡΕΣ additions follow that choice only for the role names; their other labels (columns, phone field, messages) are Greek only.

## Open items before hosting

- [ ] Check that the Two-Factor plugin's Greek translation is complete. If it isn't, ship a Greek `.po`/`.mo` file.
- [ ] Choose a production SMTP provider and set `SMTP_*` in `.env`. The email 2FA fallback depends on reliable delivery.
- [ ] Set `WP_ENVIRONMENT_TYPE=production` and `blog_public=1`, and change every `change-me` password (`setup.sh` refuses them outside `local`).
- [ ] Put the site behind HTTPS and set `FORCE_SSL_ADMIN=1`. If a reverse proxy terminates TLS, also set `TRUST_PROXY=1` and make sure WordPress can only be reached through the proxy.
- [ ] Schedule `scripts/backup.sh` (e.g. nightly cron) and copy the files off the server.
- [ ] Put `scripts/update.sh` on a monthly calendar.
