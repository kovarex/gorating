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
  password, the owner's statistics toggle (each query and its time on the page; a cookie that counts
  for the owner only). Admin pages: deleted games, the change log.
- `src/jobs/`: the background jobs: update_tournament_list (hourly), check_new_tournaments,
  process_tournament(_batch), update_rating, check_rating_changes, collect_manual_rating_changes.
  They take `?secret=` with the configuration's
  `jobSecret` (PHP's were open to anyone). They fetch EGD's pages through scrape.do with the
  configuration's `scrapeDoToken`, as PHP did (EGD's front end answers 403 to a plain client).
  `src/egd/`: the reader of EGD's pages.
- `migrations/`: the kpp version's changes to the database, run once in order (DEPLOY.md): the
  invite and password reset secrets are 128 bits (PHP's columns held an int), and
  `rating_discrepancy` notes what processing a tournament again finds different from a stored
  game: EGD's ratings changed (the game is corrected, as PHP did), gorating's own computed again
  differ (left as it is: kovarex, 2026-11-03, an error to notice and decide about later), or a game
  EGD added. Admins see them under "Rating discrepancies". `migrations/002` fixes
  start_rating_update, which the triggers call when a game is deleted, restored or edited: a
  change during an update moved its start later, and a game whose winner has no gorating rating
  started none.
- `src/Mail.kpp`: mail through the configuration's `mailCommand` (sendmail on the server) or into
  its `mailDirectory` (locally). `src/Platform.kpp`: the operating system's secure random source.
- `testdata/compare.sh`: fetches paths from the PHP site (port 8081) and this one (8082) and
  compares the HTML after masking the differences below; `PHP_JAR` and `KPP_JAR` name curl cookie
  files to compare as a logged-in user.
- `testdata/scenario-reset.sh`, `testdata/scenario-compare.sh`: two copies of the made-up data
  (`gorating_php`, `gorating_kpp`); after the same requests went to the PHP site on one and this
  one on the other, the second compares every table a page writes (the time a row was written and
  random secrets masked).
- `testdata/recompute-check.sh`: checks that recomputing ratings gives what processing a
  tournament gave (a saved tournament, a recompute from its start, a deleted game, processing it
  again) and the update's start; PHP's recomputation fails it.
- `testdata/generate.py`: made-up data for a local database (no real person in it).
- `deploy/`: the test site's .htaccess and an example configuration, which the test site's package
  holds beside the FreeBSD program (DEPLOY.md; DEPLOY-CHECKLIST.md, the short list, and how to build
  the program oneself).

**Tests.** `kpp --test` here: src/tests/ checks the rating formula, ranks, handicaps, rating jumps
and PHP's printing of numbers against what PHP's code gives (RatingVectors.kpp, written by
`php testdata/generate_rating_tests.php`: 1,680 rating computations equal to the bit), the EGD
reader, and PHP's is_numeric and loose equality.

**Building.** `kpp` in this directory, with `libraries` a link to kpp's `libraries` directory
(`mklink /J libraries <kpp checkout>\libraries`) until they are in kpp's registry.

**Differences from the PHP version, on purpose:** every text is escaped (names, comments,
messages); player links are absolute (`/kovarex`), and a username that is also a page's name links
to `/player?id=`; a table's range on its last page is right; a rating before a game counts no
deleted game (kovarex, 2026-11-02); an EGD opponent's whole name is kept (PHP kept two words);
a column sorted by a value other than its name shows its arrow, and rows with equal values keep
one order; invite and reset secrets are 32 hexadecimal digits; the rating discrepancies are new;
recomputing ratings computes a game as it was computed in the first place (kovarex, 2026-11-03):
a jigo is half a win, a player with no rating before starts from the one the game was computed
with (PHP: 0), a tournament's games from the ratings before it whatever order the update reaches
its rounds in (PHP chained them), and a deleted tournament game does not count when the tournament
is processed again; a tournament EGD adds or corrects after later games were computed starts a
recomputation of its players' later games, which the hourly update_tournament_list moves on (PHP's
moved only when a game was deleted or edited). Deleting and restoring a game is a POST form with a
confirmation (PHP: a script opened a GET address); the edit form is shown only to whoever may save
it (PHP showed it to everyone and refused the save); a column that cannot be sorted (edit, delete,
restore) has no sort link, and a list's links keep only the filters that are set; PHP's HTML
mistakes (`selected="select"`, a `</form>` inside a label, `</br>`) and the invites page's typos are
fixed; a page refused for lack of rights shows its message in the message box, and a report page
without a player says so where PHP printed warnings.

**Deploying:** DEPLOY.md, a runbook for kovarex (a test site on NFS first), and DEPLOY-CHECKLIST.md,
its short list with "Build it yourself": kpp v0.2.229 or later, its FreeBSD sysroot, `kpp --release
--target freebsd`.

## Not yet

- A run on FreeBSD: the program builds for FreeBSD 14.3 and links, but has not run there. The plan:
  CI's FreeBSD VM, with a MariaDB loaded with the made-up data (testdata/generate.py), the same page
  comparison as on Windows.
