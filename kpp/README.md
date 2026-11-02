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
  (and /username), game, the SGF download; the
  others answer 501.
- `testdata/compare.sh`: fetches paths from the PHP site (port 8081) and this one (8082) and
  compares the HTML after masking the differences below; `PHP_JAR` and `KPP_JAR` name curl cookie
  files to compare as a logged-in user.
- `testdata/generate.py`: made-up data for a local database (no real person in it).

**Building.** `kpp` in this directory, with `libraries` a link to kpp's `libraries` directory
(`mklink /J libraries <kpp checkout>\libraries`) until they are in kpp's registry.

**Differences from the PHP version, on purpose:** every text is escaped (names, comments,
messages); player links are absolute (`/kovarex`), and a username that is also a page's name links
to `/player?id=`; a table's range on its last page is right.
