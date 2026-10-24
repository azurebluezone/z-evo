# PHP2Shell (CVE-2026-109618 / CVE-2026-109619) — local exposure assessment

Date: 2026-10-24
Assessor: jarin (Principal Debug & Root Cause Engineer)
Scope: this workstation (`/home/xubuntu/Desktop/z-evo` + system PHP packages)
Verdict: **patched on this machine — no PHP core patch left to apply here.**
The user's claim is correct, with one caveat about production hosts (§6).

## 1. What was disclosed

| Item | Value |
|---|---|
| Names | PHP2Shell — two related critical RCEs |
| CVE-2026-109618 | RCE via OOM corruption (out-of-memory at the wrong moment leaves partially initialised internal data that is later treated as valid) |
| CVE-2026-109619 | RCE via stale global state (fatal error leaves internal state pointing at a previous request's freed memory → use-after-free) |
| CVSS | 9.2 (the leaked "10.0" was wrong) |
| Attack | unauthenticated RCE; on Apache + mod_php it triggers **before any application code runs**; also confirmed via PHP-FPM application paths |
| Fixed upstream | 8.5.12, 8.4.27, 8.3.36, 8.2.35 (released 2026-10-21 14:00 UTC) |
| PHP 8.1 | EOL upstream — no 8.1.x from php.net; only distro backports |

Sources: PHP Foundation post 2026-10-21 (`thephp.foundation/blog/2026/10/21/...`),
CNBC 2026-10-22/23, php.net archive 2026-10-21.

## 2. Local evidence (this machine)

### 2.1 PHP 8.2 — the SAPI that actually serves requests

```
php -v
  PHP 8.2.35 (cli) (built: Oct 21 2026 14:29:22) (NTS)

php-fpm8.2 -v
  PHP 8.2.35 (fpm-fcgi) (built: Oct 21 2026 14:29:22)

dpkg -l php8.2-cli
  8.2.35-1+ubuntu22.04.1+deb.sury.org+1

zcat /usr/share/doc/php8.2-common/changelog.Debian.gz
  php8.2 (8.2.35-1) unstable; urgency=medium
    * New upstream version 8.2.35
     + (CVE-2026-109618, CVE-2026-109619): Avoid state corruption via bailout.
```

→ 8.2.35 **is** the fixed upstream release, and the package changelog names both
CVEs. The build timestamp (14:29 UTC) is 29 minutes after the coordinated
release (14:00 UTC).

### 2.2 PHP 8.1 — installed, running, but unused by the web server

```
php8.1 -v
  PHP 8.1.2-1ubuntu2.28 (cli) (built: Oct 19 2026 18:54:45)

zcat /usr/share/doc/php8.1-common/changelog.Debian.gz | head
  php8.1 (8.1.2-1ubuntu2.28) jammy-security; urgency=medium
    * SECURITY UPDATE: Remote RCE via state corruption issue
      - debian/patches/CVE-2026-109618_109619.patch: Avoid state corruption via
        bailout in Zend/zend_API.h, Zend/zend_alloc.c, ...
      - CVE-2026-109618
      - CVE-2026-109619
```

→ Ubuntu shipped the backport **before** public disclosure (Oct 19 build), so
8.1.2-1ubuntu2.28 is patched even though the upstream version number predates
the fixed releases.

### 2.3 Which SAPI is exposed

```
nginx -T | grep fastcgi_pass
  fastcgi_pass unix:/run/php/php8.2-fpm.sock;   (the only vhost: /etc/nginx/sites-available/arafat)

ss -lx | grep php
  /run/php/php8.2-fpm.sock   LISTEN
  /run/php/php8.1-fpm.sock   LISTEN

systemctl list-units --state=running
  nginx.service, php8.1-fpm.service, php8.2-fpm.service
```

→ nginx only ever talks to **8.2** (patched). `php8.1-fpm` is running but has
**zero** vhosts and zero established socket connections — pure attack surface
with no consumer.

### 2.4 No sign of exploitation

```
grep -iE 'signal|segfault|oom|killed' /var/log/php8.2-fpm.log /var/log/php8.1-fpm.log   → (none)
grep -E 'uploads/.*\.php' /var/log/nginx/access.log                                      → (none)
dmesg | grep -iE 'segfault|php'                                                          → (none)
```

Both RCEs end in memory corruption; a segfaulting or signal-11 FPM child is the
cheapest local indicator. Nothing of the sort is present.

## 3. Answer to "প্যাচগুলো এপ্লাই করা লাগবে না"

Correct for **this machine**: 8.2.35 already contains the upstream fix and
8.1.2-1ubuntu2.28 already contains the Ubuntu backport. Nothing to apply.

Two caveats:

1. **Do not blanket-upgrade php8.1.** `apt` currently offers
   `8.1.34-9+ubuntu22.04.1+deb.sury.org+1` from the ondrej PPA. 8.1.34 predates
   the disclosure, and that package's changelog is not reachable
   (`apt-get changelog php8.1-cli` → "Changelog unavailable"), so we cannot
   prove it carries the backport. Upgrading could **remove** the patch we
   already have. Keep 8.1.2-1ubuntu2.28 until the PPA version is verified.
2. **Other hosts are unverified.** This assessment covers this workstation only.

## 4. Residual risks found while checking (not PHP2Shell)

| # | Finding | Evidence | Severity | Action |
|---|---|---|---|---|
| 1 | phpMyAdmin served on `:80` with MySQL **root** login | `/etc/nginx/sites-available/arafat` → `location ^~ /phpmyadmin { root /var/www; ... }` | Critical | Bind to localhost only, or delete the location; never expose root over plain HTTP |
| 2 | Generic `location ~ \.php$` would execute a `.php` file dropped in `public/uploads/` | same vhost, root = `.../arafat/public` | High | `deploy/nginx-z-evo.conf` adds `location ^~ /uploads/ { ... location ~ \.php$ { return 404; } }` |
| 3 | Product/variant upload accepted `svg` | `app/Http/Controllers/Admin/ProductController.php:236,242` (pre-fix) | Medium (stored XSS) | Fixed: `svg` removed from both rules |
| 4 | `php8.1-fpm` running with no consumer | §2.3 | Medium (surface) | `sudo systemctl disable --now php8.1-fpm` (revert: `enable --now`) — left for the operator because it is a system-level change |
| 5 | `APP_DEBUG=true` in `.env` | `.env` | High in production | Set `APP_DEBUG=false` on the live host |
| 6 | PHP session hardening off | `session.cookie_httponly=Off`, `cookie_secure=Off`, `use_strict_mode=Off` (8.2 CLI ini) | Medium | Set in the FPM pool `php_admin_value`; Laravel already sets `http_only=true`, `same_site=lax` |
| 7 | `expose_php=On` | `php -i` | Low | `expose_php=Off` in the FPM pool |
| 8 | Composer 2.2.6 (2022) has no `composer audit` | `composer --version`; `composer audit` → "Command audit is not defined" | Medium | Upgrade Composer, then run `composer audit` |
| 9 | Laravel 10.50.3 | `php artisan --version` | Medium | Laravel 10 is out of security support — plan the 11/12 upgrade |

## 5. Hardening shipped in this repo

- `deploy/nginx-z-evo.conf` — validated with `nginx -t` (syntax OK): uploads are
  static-only, dotfiles denied, `X-Content-Type-Options`/`X-Frame-Options`/
  `Referrer-Policy`/`Permissions-Policy` set, php8.2 socket only, asset caching.
- `ProductController` no longer accepts `svg` for product or variant images
  (an uploaded SVG served from the same origin is stored XSS).

## 6. Production checklist (run on the real host, paste the output back)

```bash
# 1. which PHP and is it the fixed build?
php -v; php-fpm8.2 -v 2>/dev/null; dpkg -l | grep -E '^ii\s+php[0-9.]+-(cli|fpm)'

# 2. every SAPI the web server uses
nginx -T 2>/dev/null | grep fastcgi_pass
apache2ctl -M 2>/dev/null | grep php

# 3. what the packages say about the two CVEs
zcat /usr/share/doc/php*-common/changelog.Debian.gz | grep -iE 'CVE-2026-109618|CVE-2026-109619'

# 4. exploitation indicators
grep -iE 'signal (6|11)|segfault|oom' /var/log/php*-fpm.log
dmesg | grep -i segfault
grep -E 'uploads/.*\.php' /var/log/nginx/access.log

# 5. uploads must never execute
curl -s -o /dev/null -w '%{http_code}\n' https://<host>/uploads/probe.php   # expect 404
```

Any host still on 8.2.34 / 8.3.35 / 8.4.26 / 8.5.11 or older must be patched to
8.2.35 / 8.3.36 / 8.4.27 / 8.5.12 immediately. PHP 8.1 hosts need the distro
backport or an upgrade to a supported branch — 8.1 has no upstream fix.
