# Deploying the kpp version (runbook)

These steps are for kovarex at a keyboard: they need his NearlyFreeSpeech.NET (NFS) account and the
database's passwords. **Nothing here has been run, and no Claude thread runs these steps or
connects to NFS.** The live site, gorating.com, and its production database are not touched until
the switch at the end, which kovarex decides.

## What has to exist first (in kpp, not here)

NFS runs FreeBSD. What kpp needed to build this program for it is in (kpp's master, 2026-11-04):
exceptions on FreeBSD (stage 3 of kpp's docs/design.md, "Targets and hosts"), the standard library's POSIX
layer (stage 4), the FreeBSD 14.3 sysroot (stage 6; kpp's tools\sysroot\freebsd.ps1 makes it), and
httpclient's FreeBSD client (OpenSSL from FreeBSD's base system, linked statically). `kpp --release
--target freebsd` builds a static FreeBSD 14.3 executable. It has not run on FreeBSD yet: the
first run is CI's FreeBSD VM with a MariaDB of the made-up data (README.md, "Not yet").

## 1. A test site (kovarex, NFS panel)

- A new site, e.g. `gorating-test`, server type **Apache 2.4 Generic** (static files and CGI). Its
  address is `gorating-test.nfshost.com` unless an alias such as `test.gorating.com` is added.
- A database for it in the MariaDB process: a new database `gorating_test` and a user with rights
  on it only. Load a copy of production into it (`mariadb-dump --single-transaction --routines
  --triggers --hex-blob`, as for the local copy), never the production database itself.
- The kpp version's changes to the database, once, on that copy: each file of `migrations/` in
  order (`mariadb gorating_test < migrations/001-secrets-and-rating-discrepancies.sql`). The PHP
  site works on the changed database too (tested locally).

## The switch from PHP: switch-to-kpp.sh (kovarex, over SSH, once)

With the database backed up and production's `/home/protected/gorating-config.json` written (mode
640, group web; the database client's login, `/home/protected/deploy/my.cnf`, is written from it):

```sh
fetch -o /home/protected/switch-to-kpp.sh https://raw.githubusercontent.com/kovarex/gorating/master/deploy/switch-to-kpp.sh
sh /home/protected/switch-to-kpp.sh
```

It checks the configuration, the database client and the PHP version (changing nothing), asks for
yes, moves the PHP files (`*.php`, `src/`, `.htaccess`) to /home/protected/php-site, runs
`kpp-deploy.sh --migrate` (migrations 001 and 002, the latest release, its smoke tests) and puts the
PHP version back when anything fails. `sh switch-to-kpp.sh --back-to-php` puts it back later. Then the
hourly scheduled task becomes a command (user web):
`cd /home/public && GORATING_CONFIG=/home/protected/gorating-config.json ./gorating.cgi --job update_tournament_list`.

## Installing a release with kpp-deploy.sh (kovarex, over SSH)

Every push to master that passes CI is published as the GitHub Release `kpp-<run>`, the
latest; `kpp-deploy.sh` installs it (the files below, laid out as there). Once per site:

```sh
fetch -o /home/protected/kpp-deploy.sh https://raw.githubusercontent.com/kovarex/kpp/master/tools/deploy/kpp-deploy.sh
mkdir -p /home/protected/deploy && chmod 700 /home/protected/deploy
# the database client's defaults, for migrations and their record (yours; never in git):
printf '[client]\nhost=<the MariaDB process host>\nuser=<user>\npassword=<password>\ndatabase=<the site database>\n' > /home/protected/deploy/my.cnf
chmod 600 /home/protected/deploy/my.cnf
# the migrations applied by hand before (001 and 002 on a copy that already has them):
sh /home/protected/kpp-deploy.sh --mark-applied 001-secrets-and-rating-discrepancies.sql 002-rating-update-start.sql
```

Then each time: `sh /home/protected/kpp-deploy.sh` (the latest; `--migrate` when it lists pending
migrations), `--status` (the installed release and the migrations applied), `--rollback` (the
previous program and files back). The web root, the configuration's place
(`/home/protected/gorating-config.json`, group `web`, mode 640) and the smoke paths are the
script's defaults for gorating on NearlyFreeSpeech.NET; `DEPLOY_CONF` names a file that changes
them. The package's kpp-deploy.sh is the one it was tested with (BUILD.txt names its commit).

## 2. The files (kovarex, over SSH)

The package **gorating-test-site.zip** holds them, laid out as on NFS: built with kpp's
`--release --target freebsd` (DEPLOY-CHECKLIST.md, "Build it yourself", says how), packed with
deploy/'s files. DEPLOY-CHECKLIST.md is the short list of these steps.

```
public/gorating.cgi       the program: a static FreeBSD 14.3 executable, kpp --release --target freebsd
public/.htaccess          deploy/.htaccess: every request that is not a static file goes to the
                          program, as Apache sent them to start.php
public/site.<hash>.css, site.<hash>.js   assets/ bundled and minified (assets/build.sh), named by
                          their contents: a browser keeps them a year (.htaccess)
public/img/               the PHP checkout's, unchanged
protected/gorating-config.example.json   deploy/'s: fill it in as gorating-config.json
migrations/               the database changes (step 1)
DEPLOY.md, BUILD.txt      this runbook; the commits it was built from and the program's SHA-256
kpp-deploy.sh             installs the package on the server (kpp's tools/deploy, at the commit BUILD.txt names)
```

`public/` goes to `/home/public/`, the filled-in config to `/home/protected/gorating-config.json`.
`chmod 755 /home/public/gorating.cgi`, `chmod 640 /home/protected/gorating-config.json`.

**gorating-config.json** (the passwords are kovarex's; nothing here goes into git): the example's
placeholders replaced:
```json
{"database": {"host": "<the MariaDB process's host>", "port": 3306, "user": "gorating_test",
              "password": "<its password>", "name": "gorating_test"},
 "resourceAddress": "",
 "sessionSecret": "<64 random hex characters: openssl rand -hex 32>",
 "secureCookies": true,
 "siteAddress": "https://gorating-test.nfshost.com",
 "mailCommand": "/usr/sbin/sendmail -t -i",
 "scrapeDoToken": "<the scrape.do token, as config.php has it>"}
```

The site uses no other files and writes none: sessions are signed cookies, mail goes to sendmail.

## 3. Checks on the test site (kovarex, a browser)

1. The home page, a player page, the players list with a search, a tournament.
2. Log in with an existing account (the password is the same as on gorating.com: the hashes are).
3. Report a game against a test account, edit it, delete it and restore it (an admin), and check
   the ratings on both players' pages.
4. An invite to one's own address: the mail arrives, its link registers.
5. As an admin, the menu's "Rating discrepancies" (empty until a tournament is processed again;
   check_rating_changes does that).
6. Apache's error log (`/home/logs/error_log`) has the program's messages for any 500.

## 4. The switch (kovarex decides when)

- The cron job (NFS panel, Scheduled Tasks) that calls `update_tournament_list` hourly gets
  a command instead of an address: `cd /home/public && GORATING_CONFIG=/home/protected/gorating-config.json ./gorating.cgi --job update_tournament_list` (the jobs' addresses answer only the
  logged-in owner, or a jobSecret if the configuration has one; PHP's were open).
- Then: `migrations/` on the production database (PHP keeps working on it, so this can come
  first), the same files on the gorating.com site, its config pointing at the production database,
  and the PHP files moved aside (kept, so going back is renaming them).
- Everyone logs in once: PHP's sessions do not carry over (kovarex agreed, 2026-11-02).
