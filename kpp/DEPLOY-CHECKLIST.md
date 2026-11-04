# gorating test site: deploy checklist (the kpp version)

For kovarex, on his NearlyFreeSpeech.NET (NFS) account. Nothing here touches gorating.com or its
database. DEPLOY.md has the reasons behind each step.

**The package** (gorating-test-site.zip):

```
public/gorating.cgi        the program: a static FreeBSD 14.3 x86-64 executable
public/.htaccess           sends every request that is not a file to the program (CGI)
public/site.<hash>.css, site.<hash>.js   the CSS and JS bundled (kpp/assets/build.sh), kept a year
public/img/                the PHP version's, unchanged
protected/gorating-config.example.json   the configuration to fill in (placeholders only)
migrations/001-*.sql, 002-*.sql          the database changes, in order
DEPLOY.md, DEPLOY-CHECKLIST.md, BUILD.txt   the runbook, this list, what it was built from
```

## 1. The site (NFS panel)

- [ ] Sites > Create a site: short name e.g. `gorating-test`, server type **Apache 2.4 Generic**
      (static files and CGI; no PHP needed). Its address: `https://gorating-test.nfshost.com`.

## 2. The database

In the MariaDB process (NFS panel > MySQL Processes; its host name is on that page):

- [ ] A database `gorating_test`, and a user `gorating_test` with all privileges on
      `gorating_test.*` only (phpMyAdmin, or the SQL below as the process's admin user):
      ```sql
      CREATE DATABASE gorating_test;
      CREATE USER 'gorating_test'@'%' IDENTIFIED BY '<a new password>';
      GRANT ALL PRIVILEGES ON gorating_test.* TO 'gorating_test'@'%';
      ```
- [ ] A copy of production into it, over SSH, as the process's admin user (the dump's triggers and
      procedure name their definer):
      ```sh
      mariadb-dump -h <db host> -u <admin> -p --single-transaction --routines --triggers --hex-blob <production database> > gorating-copy.sql
      mariadb -h <db host> -u <admin> -p gorating_test < gorating-copy.sql
      ```
- [ ] The migrations, in order, with the command-line client (002 uses `DELIMITER`, which
      phpMyAdmin's SQL box does not take as written):
      ```sh
      mariadb -h <db host> -u <admin> -p gorating_test < migrations/001-secrets-and-rating-discrepancies.sql
      mariadb -h <db host> -u <admin> -p gorating_test < migrations/002-rating-update-start.sql
      ```

## 3. The configuration

- [ ] Copy `protected/gorating-config.example.json` to `gorating-config.json` and fill in:

| Field | What |
|---|---|
| `database.host` | the MariaDB process's host name (the panel's MySQL Processes page) |
| `database.port` | 3306 |
| `database.user`, `database.password` | `gorating_test` and its password (step 2) |
| `database.name` | `gorating_test` |
| `resourceAddress` | `""` (Apache serves the CSS, the JS and img/ itself) |
| `sessionSecret` | 64 random hex characters: `openssl rand -hex 32` (at least 32 characters) |
| `secureCookies` | `true` (the site is HTTPS) |
| `siteAddress` | `https://gorating-test.nfshost.com` (links in mails) |
| `mailCommand` | `/usr/sbin/sendmail -t -i` |
| `jobSecret` | 64 random hex characters: the jobs' `?secret=` (empty: only the owner, logged in, runs them) |
| `scrapeDoToken` | the scrape.do token, as the PHP version's config.php has it (empty: EGD directly) |

## 4. Upload (scp or WinSCP)

- [ ] `public/` into `/home/public/`, `.htaccess` included (WinSCP: Options > Preferences > Panels >
      "Show hidden files"; scp: `scp -r public/. <user>@<ssh host>:/home/public/`).
- [ ] `gorating-config.json` to `/home/protected/gorating-config.json`.
- [ ] Over SSH:
      ```sh
      chmod 755 /home/public/gorating.cgi
      chmod 640 /home/protected/gorating-config.json
      ```
      The config must be readable by the web server and by no one else: give it the web server's
      group (`chgrp web /home/protected/gorating-config.json`).
- [ ] Check both (an upload can drop the executable bit, and Apache then answers 500 with nothing
      from the program in the error log; it happened on the first test site, 2026-11-04):
      ```sh
      ls -l /home/public/gorating.cgi /home/protected/gorating-config.json
      ```
      `gorating.cgi` must show `-rwxr-xr-x`, the config `-rw-r----- ... web`.

## 5. Check that it works

- [ ] Over SSH, the program as Apache runs it (it prints the response: headers, then HTML):
      ```sh
      cd /home/public
      REQUEST_METHOD=GET REQUEST_URI=/about GORATING_CONFIG=/home/protected/gorating-config.json ./gorating.cgi | head -20
      REQUEST_METHOD=GET REQUEST_URI=/players GORATING_CONFIG=/home/protected/gorating-config.json ./gorating.cgi | head -40
      ```
      The first is the About page (no database); the second lists players (the database).
- [ ] In a browser, `https://gorating-test.nfshost.com/`: the home page, a player, the players list
      with a search, a tournament.
- [ ] Log in with your gorating.com account (the same password: the copy has its hash).
- [ ] Report a game against a test account, edit it, delete and restore it (as admin), and check
      both players' ratings.
- [ ] An invite to your own address: the mail arrives and its link registers.

## 6. If it fails, send

- the last lines of `/home/logs/error_log` from the time of the failing request (the program writes
  its error there: Apache shows only "500 Internal Server Error");
- the output of the two SSH commands in step 5, with `2>&1` added (the error goes to stderr);
- BUILD.txt.

Not the configuration file: it has the passwords.

## Build it yourself (Windows)

What it needs (2026-11-04):
- **kpp v0.2.229 or later** (the installer or the zip from github.com/kovarex/kpp/releases), the
  first release with httpclient's FreeBSD client and `Library::systemLibrary`;
- **Git for Windows** (its `xz` unpacks FreeBSD's archive);
- **llvm-objcopy**, which the release does not ship: Visual Studio's (the "C++ Clang tools"
  component; the sysroot script finds it), or LLVM's own installer's, named by KPP_CLANG (its
  clang.exe, llvm-objcopy.exe beside it).

The release does not carry the official libraries or the FreeBSD sysroot, so a kpp checkout of the
same version provides them. Done this way from nothing on 2026-11-04 (kpp v0.2.231, the release's
own toolchain): the sysroot took 4.5 minutes, the program 4 s.

```bat
:: kpp's repository at the release's version: libraries\ and the sysroot script
git clone --depth 1 --branch v0.2.229 https://github.com/kovarex/kpp kpp-source

:: The FreeBSD 14.3 sysroot (once): downloads FreeBSD 14.3-RELEASE's base.txz (210 MB) from
:: ftp-archive.freebsd.org, checks its pinned SHA-256 (E38B5CF7...200A92), and unpacks its C
:: headers and libraries (251 MB) into kpp-source\compiler\build\sysroots\freebsd-x86_64.
powershell -ExecutionPolicy Bypass -File kpp-source\tools\sysroot\freebsd.ps1

:: gorating's kpp branch; the libraries come from the checkout (no registry yet)
git clone --branch kpp https://github.com/kovarex/gorating
cd gorating\kpp
mklink /J libraries ..\..\kpp-source\libraries
kpp --release --target freebsd --sysroot ..\..\kpp-source\compiler\build\sysroots\freebsd-x86_64 gorating
```

The program is `build\release\gorating.exe` (a FreeBSD executable, whatever its name says):
upload it as `gorating.cgi`. The rest of the package is in the repository: `kpp/deploy/`
(.htaccess, the example configuration), `kpp/migrations/`, and img/ in the repository's root.
The CSS and JS come from `sh assets/build.sh` in kpp/ (Node.js), run before the build: it
writes build/assets/site.<hash>.css and .js and prints the options the build needs to link them
(`--config siteCss=... --config siteJs=...`, added to the kpp command above); upload both files
next to gorating.cgi.
