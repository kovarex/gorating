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

## 2. The files (kovarex, over SSH)

The package **gorating-test-site.zip** holds them, laid out as on NFS: built with kpp's
`--release --target freebsd` (DEPLOY-CHECKLIST.md, "Build it yourself", says how), packed with
kpp/deploy's files. DEPLOY-CHECKLIST.md is the short list of these steps.

```
public/gorating.cgi       the program: a static FreeBSD 14.3 executable, kpp --release --target freebsd
public/.htaccess          kpp/deploy/.htaccess: every request that is not a static file goes to the
                          program, as Apache sent them to start.php
public/site.<hash>.css, site.<hash>.js   kpp/assets bundled and minified (assets/build.sh), named by
                          their contents: a browser keeps them a year (.htaccess)
public/img/               the PHP checkout's, unchanged
protected/gorating-config.example.json   kpp/deploy's: fill it in as gorating-config.json
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
 "jobSecret": "<64 random hex characters, also in the cron's address: ...?secret=...>",
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
  `?secret=<jobSecret>` added to its address: the kpp version's jobs need it (PHP's were open).
- Then: `migrations/` on the production database (PHP keeps working on it, so this can come
  first), the same files on the gorating.com site, its config pointing at the production database,
  and the PHP files moved aside (kept, so going back is renaming them).
- Everyone logs in once: PHP's sessions do not carry over (kovarex agreed, 2026-11-02).
