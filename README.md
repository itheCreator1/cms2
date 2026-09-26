# cms2 — EARES WordPress

[![CI](https://github.com/itheCreator1/cms2/actions/workflows/ci.yml/badge.svg)](https://github.com/itheCreator1/cms2/actions/workflows/ci.yml)

**The new home on the web for EARES, the Rizarios Ecclesiastical School Alumni Association** (ΕΑΡΕΣ, Ένωση Αποφοίτων Ριζαρείου Εκκλησιαστικής Σχολής).

The school has been sending graduates out into the world since 1844. The association keeps them in touch with news, events, elections and its own newspaper, *The Rizareitis* (Ο Ριζαρείτης). This repository is the site that does that job: a WordPress install you can rebuild from scratch with one script, locked down so the browser can edit content but never code, and dressed in a theme that looks like it belongs to a 180-year-old institution without looking 180 years old.

> **The one rule of this project:** everything that isn't content lives in git. WordPress core, plugins, roles, the theme, the settings: all of it is code, all of it is reviewed, all of it can be rebuilt. The browser is for writing posts, not for installing things.

---

## At a glance

| | |
|---|---|
| **Stack** | WordPress 7.1 on PHP 8.3 + Apache, MariaDB 11.4, wp-cli and Mailpit, all under Docker Compose with pinned image versions |
| **People** | Six roles, including a custom **User Manager** who runs the membership desk without ever touching an Administrator |
| **Security** | Optional two-factor login, login throttling, no XML-RPC, no application passwords, no leaking of login names, no file edits from the browser |
| **Public site** | A custom block theme with self-hosted Greek fonts, the association's own photos, and a front page built like a bulletin board |
| **Operations** | Update and backup scripts with a documented restore; setup that you can run as often as you like |
| **Quality** | CI runs coding standards, shellcheck and a full end-to-end smoke test on a fresh stack for every push |

---

## Quick start

You need Docker with Compose. That's it.

```sh
cp .env.example .env        # then change the passwords
docker compose up -d        # WordPress, MariaDB, Mailpit
scripts/setup.sh            # install and configure everything (safe to re-run)
scripts/seed-users.sh       # optional: one fake test user per role
scripts/seed-content.sh     # optional: fake sample posts, to see the theme in action
scripts/smoke-test.sh       # optional: prove it all works
```

A few minutes later:

| URL | What you'll find |
|---|---|
| http://localhost:8080 | The public site |
| http://localhost:8080/wp-admin | The dashboard (admin credentials from `.env`) |
| http://localhost:8025 | Mailpit, which catches every email the site sends: invites, password resets, 2FA codes. Nothing escapes to a real inbox. |
| http://localhost:8081 | Adminer, if you want to look at the database: `docker compose --profile tools up -d adminer` |

Want a clean slate? `docker compose down -v`, then run the quick start again. `setup.sh` checks before it changes anything, so running it twice changes nothing the second time.

---

## The public site

The old eares.gr did its job for years: a portrait of the founder, the school, the church, burgundy everywhere, a login box in the sidebar. The new site keeps its character and loses the clutter.

**The look.** The colours come straight from the photos of the school church: crimson from the brick arches, limestone from the walls, cypress green from the trees in the courtyard and the old site's widgets. Headings are set in GFS Didot, a typeface from the Greek Font Society, with Noto Serif for reading and Commissioner for menus and labels. All three cover full Greek, polytonic included, so a feast-day text in the old accents renders as beautifully as the news. The fonts are served by the site itself: visitors' browsers never call Google, a CDN or the WordPress emoji service.

**The front page reads like a parish bulletin board, only tidier:**

1. The church dome at sunset, with the association's name set in the dark sky beside it.
2. Pinned announcements, such as a call to a general assembly, directly under the photo where nobody can miss them.
3. The latest news on the left. On the right, the front page of the newest issue of *The Rizareitis* and the upcoming events.
4. The welcome text from the old site, next to a photo of the church porch. It says how old the school is, and the number now updates itself every year instead of being stuck at "178".
5. A green band inviting members to renew their subscription.

**The menu** keeps the old sections under clearer names: Home, The Association (Η Ένωση), News (Νέα), The Rizareitis (Ο Ριζαρείτης), The School (Η Σχολή), Subscriptions (Συνδρομές), Contact (Επικοινωνία). The old sidebar login box became a quiet "Members' login" (Είσοδος μελών) link in the header.

**For editors,** everyday tasks need no training beyond "write a post":

| To... | Do this |
|---|---|
| Put an announcement at the top of the front page | Tick *Stick to the top of the blog* on the post; untick it when it's over |
| Publish a new issue of the newspaper | A post in the *The Rizareitis* category (Ο Ριζαρείτης), with the front page as its featured image and the PDF in a File block |
| Announce an event | A post in *Events* (Εκδηλώσεις), with the date and time in the text |

[`docs/site-map.md`](docs/site-map.md) maps every section of the old site onto the new one.

**Under the hood,** the theme lives in [`wp-content/themes/eares/`](wp-content/themes/eares/):

- Colours, fonts and spacing are in `theme.json`.
- Templates and the header and footer are in `templates/` and `parts/`.
- Front-page sections and ready-made blocks for editors are in `patterns/`: a board-members grid, a cross ornament divider, the membership band.
- What `theme.json` can't express (ornaments, the photo overlay, the news list) is in `assets/theme.css`.

**Photos.** The original photos sit in `assets/pictures/` at 10–12 MB each. `scripts/optimize-photo.sh` turns them into WebP copies of 150–290 KB for the theme, so a visitor on a phone doesn't download a poster to read the news.

`setup.sh` builds the site's skeleton:
- the categories;
- the menu pages, with clearly marked "to be completed" placeholders (Προς συμπλήρωση) instead of invented facts;
- the static front page;
- header photos for The Association and The School pages.

It only ever fills gaps, so it never overwrites what editors have written.

---

## Who can do what

WordPress's standard roles, plus two of our own:

| Role | Who | Can |
|---|---|---|
| Administrator | The site admin, plus one backup | Everything |
| **User Manager** (Διαχειριστής Χρηστών, `eares_user_manager`) | The office or secretary | Everything an Editor can, plus create, edit and delete users, change their roles, and reset their 2FA. **Cannot** see, edit or assign Administrators or other User Managers. |
| Editor | Board members (ΔΣ) and the newspaper's editorial team (Ριζαρείτης) | Publish and edit all content, moderate comments |
| Author | Trusted alumni | Publish their own posts |
| Contributor | Other alumni | Write drafts and attach photos; an Editor publishes them |
| **Inactive** (Ανενεργός, `eares_inactive`) | Former board members and the like, case by case | Nothing. Login is refused and open sessions end at once, but their posts and name stay on the site. |

The User Manager is the interesting one. They run the membership desk, but the Administrators are invisible to them: those accounts don't appear in their user list or its counts, the API doesn't return them, and the promote option isn't in their role menu. A forged request is refused too, because the checks happen on the server, not just in the menus.

Two more rules close the obvious loopholes:
- **Self-registration is off.** Only Administrators and User Managers create accounts.
- **Only Administrators may post raw HTML.** Otherwise an Editor's `<script>` could run in an Administrator's browser and walk around every protection above.

The roles are defined in code, in `wp-content/mu-plugins/eares-roles.php`. Change a capability, bump `EARES_ROLES_VERSION`, and the roles are rebuilt on the next request.

---

## Keeping accounts safe

### Two-factor login

Two-factor authentication uses the [Two-Factor](https://wordpress.org/plugins/two-factor/) plugin. It's **optional for everyone**, and each member turns it on from their own profile, choosing from:

- an authenticator app, such as Google Authenticator;
- backup (recovery) codes;
- a code sent by email.

Members have a friendly step-by-step guide in Greek, [`docs/2fa-odigos.md`](docs/2fa-odigos.md), ready to export to PDF or publish as a help page.

**Lost your phone?** An Administrator or User Manager opens **Users**, hovers over the person and clicks **Reset 2FA** (Επαναφορά 2FA). The reset goes into the activity log, and the member gets an email about it, so a reset they didn't ask for never goes unnoticed.

> **Before resetting, confirm who is asking.** Hang up and call back the phone number on their profile, never a number given during the call. A reset leaves the account protected by its password alone.

There's also a guard against the classic takeover: "change the email, reset the 2FA, reset the password". If anyone other than the member changes an account's email address, User Managers can't reset its 2FA for the next 7 days; only an Administrator can.

### Logins

- **Guessing gets expensive.** Five failed logins for one username from one IP, or twenty from one IP in total, lock that IP out for 15 minutes, even with the right password. Lockouts show up in the activity log.
- **Login names stay private.** Author pages use a `member-…` address (`/author/member-5a5385c0b6/`) instead of the login name. Anonymous visitors can't list users through the API, the `?author=N` trick or the sitemap.
- **The side doors are closed.** XML-RPC is off, and so are application passwords, which would otherwise let someone log in without 2FA.

Behind a reverse proxy, set `TRUST_PROXY=1` in `.env`. Otherwise every visitor seems to come from the proxy's IP, and one attacker could lock everybody out.

### The browser can look, but it can't touch

`wp-config` forbids editing and installing code from the dashboard (`DISALLOW_FILE_EDIT`, `DISALLOW_FILE_MODS`). Only wp-cli, run by the scripts, can change WordPress core, plugins or the theme. A stolen admin password can do damage to content, which backups fix, but it can't plant code on the server.

The database sits on a Docker network with no internet access, and every port listens on `127.0.0.1` only.

---

## The Users screen

Two extra columns you can sort by:

- **2FA**: on or off, at a glance.
- **Last login** (Τελευταία σύνδεση): accounts silent for 12 months or more get a red flag.

The **No login for 12+ months** view (Χωρίς σύνδεση 12+ μήνες) lists just the flagged accounts. Nothing happens to them automatically; a person decides.

The activity log (Simple History) is visible to Administrators and User Managers only, and User Managers don't see events by or about Administrators and other User Managers.

---

## Running it

### Updating

Neither the dashboard nor a newer Docker image updates WordPress core: core lives in the `wp_data` volume. There's exactly one way in, and it comes with a safety net. At least once a month, and whenever a security release comes out:

```sh
scripts/backup.sh
scripts/update.sh
```

A new `wordpress` image tag in `docker-compose.yml` updates PHP and Apache only.

### Backup and restore

`scripts/backup.sh` writes `backups/db-<time>.sql.gz` and `backups/uploads-<time>.tar.gz`. Copy them off the server; a backup that lives next to the thing it backs up is a comforting story, not a backup. Core and plugins aren't included, because `setup.sh` reinstalls them.

To restore into a running stack:

```sh
set -a; source .env; set +a
gunzip -c backups/db-<time>.sql.gz \
  | docker compose exec -T -e MYSQL_PWD="$DB_ROOT_PASSWORD" db mariadb -uroot "$DB_NAME"
docker compose exec -T -u www-data wordpress tar -C /var/www/html/wp-content -xz \
  < backups/uploads-<time>.tar.gz
```

### Email

The site sends mail over SMTP using the `SMTP_*` settings in `.env`, with STARTTLS whenever the server offers it. Locally that's Mailpit; in production it's whichever provider you choose.

---

## Quality checks

```sh
composer install && vendor/bin/phpcs   # WordPress coding standards
shellcheck scripts/*.sh                # shell scripts
scripts/smoke-test.sh                  # needs the stack + seed-users.sh + seed-content.sh
```

The smoke test drives a real running site and checks:
- the roles and the anti-escalation rules;
- the hardening: XML-RPC, the users API, author URLs;
- the theme and its pages;
- the login lockout.

[CI](.github/workflows/ci.yml) runs all of it on every push, on a stack built from nothing, and finishes by taking a backup.

---

## Project map

| Path | What it does |
|---|---|
| `docker-compose.yml` | The services, with pinned images. The database is on an internal network, and ports bind to 127.0.0.1. The `wp-config` hardening is set here: file edits off, code installs only from wp-cli, `FORCE_SSL_ADMIN`, and HTTPS detection behind a proxy. |
| `wp-content/mu-plugins/eares-roles.php` | The custom roles and the rules that stop anyone climbing above their role |
| `wp-content/mu-plugins/eares-users-admin.php` | Last login, the 2FA and last-login columns, the Reset 2FA action and its 7-day guard, the Inactive login block, the allowed 2FA methods, and who sees what in the activity log |
| `wp-content/mu-plugins/eares-login-limit.php` | Login throttling |
| `wp-content/mu-plugins/eares-hardening.php` | Registration off; XML-RPC and application passwords off; no user listing for anonymous visitors; `member-…` author addresses |
| `wp-content/mu-plugins/eares-profile.php` | A private phone field (not in the API; included in personal-data export and erasure) and a simpler profile screen |
| `wp-content/mu-plugins/eares-mail.php` | SMTP from the `.env` settings |
| `wp-content/themes/eares/` | The public site's block theme |
| `scripts/setup.sh` | Installs and configures everything: core, the Greek language pack, plugins, settings, the theme and the site structure. Refuses the `change-me` passwords outside `local`. |
| `scripts/setup-content.php` | Categories, menu pages, the static front page and page header photos (run by `setup.sh`) |
| `scripts/update.sh` | Updates core, plugins and translations |
| `scripts/backup.sh` | Backs up the database and uploads into `backups/` |
| `scripts/optimize-photo.sh` | Resizes a photo to WebP for the theme |
| `scripts/seed-users.sh`, `scripts/seed-content.sh` | Fake test users and sample posts; both refuse to run in production |
| `scripts/smoke-test.sh` | End-to-end checks against a running stack |
| `docs/site-map.md` | The old site mapped onto the new one, and how editors handle announcements, issues and events |
| `docs/2fa-odigos.md` | The members' 2FA guide, in Greek |
| `.github/workflows/ci.yml` | CI: PHP syntax, PHPCS, shellcheck, then the full stack with the smoke test and a backup |

---

## Language

Greek is the site's language. Anyone can switch their own admin screens to English from their profile. The EARES additions follow that choice for the role names; their other labels (columns, the phone field, messages) are in Greek only.

---

## Before going live

- [ ] Check that the Two-Factor plugin's Greek translation is complete. If it isn't, ship a Greek `.po`/`.mo` file.
- [ ] Choose a production SMTP provider and set `SMTP_*` in `.env`. The email 2FA option depends on reliable delivery.
- [ ] Set `WP_ENVIRONMENT_TYPE=production` and `blog_public=1`, and change every `change-me` password (`setup.sh` refuses them outside `local` anyway).
- [ ] Put the site behind HTTPS and set `FORCE_SSL_ADMIN=1`. If a reverse proxy terminates TLS, also set `TRUST_PROXY=1` and make sure WordPress can only be reached through the proxy.
- [ ] Schedule `scripts/backup.sh` (for example, a nightly cron job) and copy the files off the server.
- [ ] Put `scripts/update.sh` on a monthly calendar.
- [ ] Replace the "to be completed" placeholders (Προς συμπλήρωση) on the pages: the history, the board members, the subscription amount and IBAN, the contact details.
