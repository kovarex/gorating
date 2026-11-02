# Deploying the kpp version (runbook)

These steps are for kovarex at a keyboard: they need his NearlyFreeSpeech.NET (NFS) account and the
database's passwords. **Nothing here has been run, and no Claude thread runs these steps or
connects to NFS.** The live site, gorating.com, and its production database are not touched until
the switch at the end, which kovarex decides.

## What has to exist first (in kpp, not here)

NFS runs FreeBSD. kpp builds this program for FreeBSD once two stages of kpp's targets plan
(kpp's docs/design.md, "Targets and hosts") are done:
- **stage 3, exceptions on FreeBSD and Linux:** the site reports every error by throwing (a form's
  bad field, a database error); without unwinding, an error ends the process;
- **stage 6, FreeBSD:** its sysroot (the base system's headers and libraries, for the C imports of
  sockets and the link) and a program built here that runs there.

Until then the site runs on one's own machine only (README.md).

## 1. A test site (kovarex, NFS panel)

- A new site, e.g. `gorating-test`, server type **Apache 2.4 Generic** (static files and CGI). Its
  address is `gorating-test.nfshost.com` unless an alias such as `test.gorating.com` is added.
- A database for it in the MariaDB process: a new database `gorating_test` and a user with rights
  on it only. Load a copy of production into it (`mariadb-dump --single-transaction --routines
  --triggers --hex-blob`, as for the local copy), never the production database itself.

## 2. The files (kovarex, over SSH)

```
/home/public/gorating.cgi        the program, built with: kpp --release --target freebsd
/home/public/.htaccess           below
/home/public/style2.css, common.js, img/   the PHP checkout's, unchanged
/home/protected/gorating-config.json        below; readable by the web server only
```

`chmod 755 /home/public/gorating.cgi`, `chmod 640 /home/protected/gorating-config.json`.

**.htaccess:** every request that is not one of the static files goes to the program, as Apache
sent them to start.php:
```
Options +ExecCGI
AddHandler cgi-script .cgi
SetEnv GORATING_CONFIG /home/protected/gorating-config.json
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ /gorating.cgi [L]
```

**gorating-config.json** (the passwords are kovarex's; nothing here goes into git):
```json
{"database": {"host": "<the MariaDB process's host>", "port": 3306, "user": "gorating_test",
              "password": "<its password>", "name": "gorating_test"},
 "resourceAddress": "",
 "sessionSecret": "<64 random hex characters: openssl rand -hex 32>",
 "secureCookies": true,
 "siteAddress": "https://gorating-test.nfshost.com",
 "mailCommand": "/usr/sbin/sendmail -t -i"}
```

The site uses no other files and writes none: sessions are signed cookies, mail goes to sendmail.

## 3. Checks on the test site (kovarex, a browser)

1. The home page, a player page, the players list with a search, a tournament.
2. Log in with an existing account (the password is the same as on gorating.com: the hashes are).
3. Report a game against a test account, edit it, delete it and restore it (an admin), and check
   the ratings on both players' pages.
4. An invite to one's own address: the mail arrives, its link registers.
5. Apache's error log (`/home/logs/error_log`) has the program's messages for any 500.

## 4. The switch (kovarex decides when)

- The cron job (NFS panel, Scheduled Tasks) that calls `update_tournament_list` hourly stays on
  the PHP site until the jobs are ported (README.md, "Not yet").
- Then: the same files on the gorating.com site, its config pointing at the production database,
  and the PHP files moved aside (kept, so going back is renaming them).
- Everyone logs in once: PHP's sessions do not carry over (kovarex agreed, 2026-11-02).
