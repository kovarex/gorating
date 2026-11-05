# gorating.com

The friendly go rating site, written in kpp ([github.com/kovarex/kpp](https://github.com/kovarex/kpp)).
It replaced the PHP version on 2026-11-05 (the PHP code is in git's history before that), on the same
MariaDB database (setup.sql: the schema, its triggers and procedures; migrations/: the changes since).

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
  They run from the server's shell, `gorating.cgi --job NAME [PARAMETER=VALUE...]` (the cron's
  command), or for the logged-in owner, nobody else (PHP's were open to anyone). They fetch EGD's pages through scrape.do with the
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
- The comparisons with the PHP version (testdata/compare.sh, scenario-*.sh, recompute-check.sh,
  egd-compare.sh) are in git's history before the PHP code was removed.
- `testdata/generate.py`: made-up data for a local database (no real person in it).
- `assets/`: the CSS and JS. `sh assets/build.sh` bundles `site.css` and `site.js` with what they
  import into one file each with esbuild (pinned, through npx: Node.js), minified and named by their
  contents (`site.<hash>.css`), and prints the `--config siteCss=... --config siteJs=...` that a
  build linking those names takes (CI's release build); without them the pages link `site.css` and
  `site.js`, which `sh assets/build.sh --plain` writes into build/assets for a local run
  (`gorating --port 8082` with resourceAddress "" serves them from there, and img/).
- `deploy/`: the site's .htaccess, an example configuration and switch-to-kpp.sh, which the
  package holds beside the FreeBSD program (DEPLOY.md; DEPLOY-CHECKLIST.md, the short list, and how
  to build the program oneself).

**Building.** `kpp` in this directory: an installed kpp (kpp-version names the release CI uses)
brings the libraries it uses.

**Code style** (kovarex, 2026-11-05: the code reads like Factorio's C++):
- kpp's integer names (`i32`, `i64`, `u8`, `usize`), never `int`, `int64_t` or `size_t` outside an
  `extern "C"` declaration (`kpp --check --c-type-warnings` lists them).
- One type per file, named after it; data members after the functions. A class with private data
  where its values keep an invariant (`Row`, `Form`), a struct where they are plain values.
- No constructor or operator the language writes itself: no empty `X() {}`, `operator==` defaulted.
- Text as pieces: `out << a << b`, `toString("game ", id, " deleted")`, and SQL the same way,
  `page.query("UPDATE user SET rating=", rating, " WHERE id=", quote(id))`; no `format("{}")`, no
  `.append(`, no String concatenation for SQL.
- A form is read through `Form`: `form.number<u8>("significant_digits", message)` parses or fails,
  and a failure (`form.fail(...)`) is a FormError that `handle` turns into the redirect with its
  message, so an action does not return at each check.
- HTML through `html::HtmlTemplate`s, one template per thing shown: an attribute that appears or not
  is a boolean attribute, `<option value="b" ?selected="{{black}}">`, not a second template.

**Tests** (src/tests/, `kpp --test` here; for FreeBSD, `kpp --target freebsd --no-run --test`
builds the test program):
- without a database: the rating formula, ranks, handicaps, rating jumps and PHP's printing of
  numbers against what PHP's code gave (RatingVectors.kpp, written by the PHP version's
  testdata/generate_rating_tests.php: 1,680 rating computations equal to the bit), PHP's
  is_numeric and loose equality, EGD's pages (made-up pages in EGD's markup; jobs/EgdPages.kpp reads
  them), sessions and passwords, the forms, escaping;
- with `GORATING_TEST_DB=host:port:user[:password]` naming a MariaDB server: the database
  gorating_kpp_tests is made fresh from setup.sql, migrations/ and testdata/fixture.sql
  (testdata/generate.py's made-up data), and the pages of testdata/pages.txt (as a visitor, a user,
  an admin and the owner) are compared with testdata/pages/. Those are this version's pages, checked
  against the PHP version's on the same database (2026-11-04: every difference is one of those on
  purpose below); `GORATING_UPDATE_PAGES=1` writes them anew after a deliberate change. A game is
  reported, edited, deleted and restored through the pages, each rating checked against the
  formula applied as PHP applies it (refusals included: a plain user's win, another user's edit).
  A made-up tournament is processed by its job from saved pages (testdata/egd-work, written by the
  test), and recomputing agrees with it (the checks recompute-check.sh made: a recompute moves no
  game, a deleted game leaves no discrepancy, two changes keep the earlier start, a tournament added
  after a later game recomputes that game); the jobs refuse anyone but the owner and the secret, and
  update_tournament_list queues and processes a new tournament of EGD's list.

**Differences from the PHP version, on purpose:** every text is escaped (names, comments,
messages); player links are absolute (`/kovarex`), and a username that is also a page's name links
to `/player?id=`; a table's range on its last page is right, and a player's table counts its rating changes with its games (it lists both); a tournament's table has a column for every round its games were played in (EGD's round count is below that for about 2,000 tournaments, whose later rounds PHP left out); a rating before a game counts no
deleted game (kovarex, 2026-11-02); an EGD opponent's whole name is kept (PHP kept two words);
a column sorted by a value other than its name shows its arrow, and rows with equal values keep
one order; invite and reset secrets are 32 hexadecimal digits; the rating discrepancies are new;
recomputing ratings computes a game as it was computed in the first place (kovarex, 2026-11-03):
a jigo is half a win, a player with no rating before starts from the one the game was computed
with (PHP: 0), a tournament's games from the ratings before it whatever order the update reaches
its rounds in (PHP chained them), and a deleted tournament game does not count when the tournament
is processed again; an update takes a player's games from the time they entered it, going forward
in time only (PHP went back to a newly reached player's earlier games, kovarex 2026-11-05); a tournament EGD adds or corrects after later games were computed starts a
recomputation of its players' later games, which the hourly update_tournament_list moves on (PHP's
moved only when a game was deleted or edited). Deleting and restoring a game is a POST form with a
confirmation (PHP: a script opened a GET address); the edit form is shown only to whoever may save
it (PHP showed it to everyone and refused the save); a column that cannot be sorted (edit, delete,
restore) has no sort link, and a list's links keep only the filters that are set; PHP's HTML
mistakes (`selected="select"`, a `</form>` inside a label, `</br>`) and the invites page's typos are
fixed (the login page wrote a second document head into the page); a page refused for lack of
rights shows its message in the message box, and a report page without a player says so where PHP
printed warnings; only a trusted user may report a win, as PHP's "Trusted users can report a win"
meant (its report form offered Win to everyone and its action took it).

**Deploying:** every push to master that passes CI (.github/workflows/kpp.yml: the tests on
Windows and in a FreeBSD 14.3 VM with MariaDB, the package installed there by kpp-deploy.sh) is the
release kpp-<run>; `sh /home/protected/kpp-deploy.sh` on the server installs the latest (DEPLOY.md;
DEPLOY-CHECKLIST.md, the short list).
