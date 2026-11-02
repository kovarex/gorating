# gorating.com in kpp

The PHP site rewritten in kpp ([github.com/kovarex/kpp](https://github.com/kovarex/kpp)), page by
page, against the same MariaDB database (its triggers and procedures stay as they are for now).

- `src/Main.kpp`: the router (start.php). Under Apache it runs as CGI; `gorating --port 8082`
  serves on one's own machine.
- `src/Config.kpp`: `gorating-config.json` (never committed: it has the database's password and
  the session secret), or the file `GORATING_CONFIG` names.
- `src/Session.kpp`: login, logout, who is logged in. The cookie `gorating_id` is signed (HMAC-SHA256
  with the configuration's `sessionSecret`, over the user id, the expiry and the password hash):
  nothing is stored on the server, and a changed password ends that user's sessions. Passwords are
  checked against the existing bcrypt hashes, so everyone logs in once with the same password.
- `src/Table.kpp`: table_viewer.php. `src/Layout.kpp`: the header, menu and footer.
- `src/pages/`: one file per page. Ported: home, about, tournaments, tournament, players, player
  (and /username), game, the SGF download, login, settings; the
  others answer 501. Actions: report a game, edit, delete and restore one (with the change log and
  the rating update), edit a player's name, invite (a new or an EGD player), register, reset a
  password. Admin pages: deleted games, the change log.
- `src/Mail.kpp`: mail through the configuration's `mailCommand` (sendmail on the server) or into
  its `mailDirectory` (locally). `src/Platform.kpp`: the operating system's secure random source.
- `testdata/compare.sh`: fetches paths from the PHP site (port 8081) and this one (8082) and
  compares the HTML after masking the differences below; `PHP_JAR` and `KPP_JAR` name curl cookie
  files to compare as a logged-in user.
- `testdata/scenario-reset.sh`, `testdata/scenario-compare.sh`: two copies of the made-up data
  (`gorating_php`, `gorating_kpp`); after the same requests went to the PHP site on one and this
  one on the other, the second compares every table a page writes (the time a row was written and
  random secrets masked).
- `testdata/generate.py`: made-up data for a local database (no real person in it).

**Building.** `kpp` in this directory, with `libraries` a link to kpp's `libraries` directory
(`mklink /J libraries <kpp checkout>\libraries`) until they are in kpp's registry.

**Differences from the PHP version, on purpose:** every text is escaped (names, comments,
messages); player links are absolute (`/kovarex`), and a username that is also a page's name links
to `/player?id=`; a table's range on its last page is right.

**Deploying:** DEPLOY.md, a runbook for kovarex (a test site on NFS first). It waits for kpp to
build FreeBSD programs (exceptions and the FreeBSD sysroot, kpp's docs/design.md "Targets and
hosts").

## Not yet

- The background jobs: `update_tournament_list` (hourly cron), tournament processing, the rating
  checks against EGD. They fetch EGD's pages over HTTPS through scrape.do, and kpp has no HTTPS
  client yet. The PHP site keeps running them until then.
- The owner's statistics toggle (each query's time on the page).
- For kovarex to decide: a rating before a game is read from deleted games too (RatingUpdate.kpp,
  kept as PHP does it); invite and reset secrets are 31 bits because their columns are int.
