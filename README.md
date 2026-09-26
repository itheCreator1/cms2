# cms2 — ΕΑΡΕΣ WordPress

New WordPress site for ΕΑΡΕΣ (Ένωση Αποφοίτων Ριζαρείου Εκκλησιαστικής Σχολής). It runs under Docker Compose and is rebuilt from code: WordPress core, plugins and roles are never changed through the browser.

## Quick start (local dev)

```sh
cp .env.example .env        # then change the passwords
docker compose up -d        # WordPress, MariaDB, Mailpit
scripts/setup.sh            # install + configure (idempotent, safe to re-run)
scripts/seed-users.sh       # optional: one fake test user per role
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
| Editor | ΔΣ, Ριζαρείτης editorial team | Publish and edit all content, moderate comments |
| Author | Trusted alumni | Publish their own posts |
| Contributor | Other alumni | Write drafts and upload images; an Editor publishes them |
| **Ανενεργός** (`eares_inactive`) | Former board members etc. (case by case) | Nothing. Login is refused and existing sessions are ended. Their posts and name stay on the site. |

Self-registration is off. Only Administrators and User Managers create accounts.

The roles are defined in `wp-content/mu-plugins/eares-roles.php`. After you change a capability there, bump `EARES_ROLES_VERSION`; the roles are rebuilt on the next request.

## Two-factor authentication

2FA uses the [Two-Factor](https://wordpress.org/plugins/two-factor/) plugin. It is **optional for everyone**, and each user enables it from their own profile. Three methods are offered:

- an authenticator app (Google Authenticator)
- backup (recovery) codes
- a code sent by email

If someone is locked out, an Administrator or User Manager opens **Users**, hovers over the person, and clicks **Επαναφορά 2FA**. The reset is recorded in Simple History, and the user gets an email about it.

The Greek step-by-step guide for members is in [`docs/2fa-odigos.md`](docs/2fa-odigos.md). It is meant to be exported to PDF and published as a help page.

## Users screen

Two extra sortable columns:

- **2FA**: on or off.
- **Τελευταία σύνδεση** (last login): accounts with no login for 12+ months get a red flag.

The **Χωρίς σύνδεση 12+ μήνες** view lists only the flagged accounts. Nothing happens to them automatically; an admin decides what to do.

The activity log (Simple History) is visible to Administrators and User Managers only.

## What lives where

| Path | Purpose |
|---|---|
| `docker-compose.yml` | The services. The DB is on an internal-only network, and ports are bound to 127.0.0.1. `wp-config` hardening is set here: `DISALLOW_FILE_EDIT`, and `DISALLOW_FILE_MODS` except under wp-cli. |
| `scripts/setup.sh` | Runs wp-cli to install core, the Greek language pack, the plugins and the settings. |
| `scripts/seed-users.sh` | Creates fake test users. Refuses to run in production. |
| `wp-content/mu-plugins/eares-roles.php` | Custom roles and anti-escalation filters (`editable_roles`, `map_meta_cap`, hiding protected users). |
| `wp-content/mu-plugins/eares-users-admin.php` | Last login, the 2FA and Last-login columns, the Reset 2FA action, the Inactive login block, the allowed 2FA methods and who sees the activity log. |
| `wp-content/mu-plugins/eares-profile.php` | Private phone field (not exposed through REST; included in the personal data export) and a simplified profile screen. |
| `wp-content/mu-plugins/eares-hardening.php` | Keeps registration off. Disables XML-RPC and application passwords, which would bypass 2FA. Blocks anonymous access to REST `/users`, `?author=N` and the users sitemap. |
| `wp-content/mu-plugins/eares-mail.php` | Sends mail over SMTP using the `SMTP_*` settings in `.env` (Mailpit locally). |

## Open items before hosting

- [ ] Check that the Two-Factor plugin's Greek translation is complete. If it isn't, ship a Greek `.po`/`.mo` file.
- [ ] Choose a production SMTP provider and set `SMTP_*` in `.env`. The email 2FA fallback depends on reliable delivery.
- [ ] Set `WP_ENVIRONMENT_TYPE=production` and `blog_public=1`, and put the site behind HTTPS.
