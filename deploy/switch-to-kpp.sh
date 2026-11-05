#!/bin/sh
# switch-to-kpp.sh: gorating.com from the PHP version to the kpp version in one go, run by hand on the
# server (kovarex, 2026-11-05: "can't you put it all into one script?"). Back up the database first.
#
#   sh switch-to-kpp.sh               check everything, ask once, then switch
#   sh switch-to-kpp.sh --back-to-php put the PHP version back (the database keeps migrations 001
#                                     and 002, which the PHP version works with)
#
# What it does, stopping at the first problem with what to do:
#  1. checks, changing nothing but writing the database client's login when it has none
#     (/home/protected/deploy/my.cnf, from the configuration's "database", mode 600): the kpp
#     configuration (/home/protected/gorating-config.json: its fields, mode 640, group web), the
#     database reached, the PHP version in /home/public, kpp-deploy.sh (fetched if missing);
#  2. asks you to type yes;
#  3. moves the PHP version's files (/home/public/*.php, src/ and .htaccess) to /home/protected/php-site;
#  4. runs kpp-deploy.sh --migrate: migrations 001 and 002 on the database, the latest kpp release
#     installed (program, CSS, JS, images, .htaccess), its smoke tests (/about, /players);
#  5. if that fails, puts the PHP version back and says so.
# Then: the hourly task runs `gorating.cgi --job update_tournament_list`, and everyone logs in again.
#
# The paths are kpp-deploy.sh's (its first lines, or DEPLOY_CONF's file), which the tests change.

PUBLIC=/home/public
CONFIG=/home/protected/gorating-config.json
CONFIG_GROUP=web
STATE=/home/protected/deploy
MYCNF=/home/protected/deploy/my.cnf
DATABASE="mysql --defaults-extra-file=$MYCNF"
DEPLOY=/home/protected/kpp-deploy.sh
# kpp-deploy.sh as gorating's CI tests it (kpp.yml's KPP_DEPLOY_COMMIT).
DEPLOY_URL=https://raw.githubusercontent.com/kovarex/kpp/f23622e92aa0f97d1eab1bfe5d30fead9a92643a/tools/deploy/kpp-deploy.sh
[ -n "$DEPLOY_CONF" ] && . "$DEPLOY_CONF"
ASIDE=$(dirname "$CONFIG")/php-site

fail() { echo "switch: $*" >&2; exit 1; }
say() { echo "switch: $*"; }

back_to_php()
{
  [ -d "$ASIDE" ] || fail "no $ASIDE: the PHP version was not moved by this script"
  if [ -f "$PUBLIC/gorating.cgi" ]; then mv -f "$PUBLIC/gorating.cgi" "$PUBLIC/gorating.cgi.kpp" || fail "cannot move $PUBLIC/gorating.cgi aside"; fi
  (cd "$ASIDE" && for f in * .htaccess; do [ -e "$f" ] && echo "$f"; done) | while read -r f; do
    rm -rf "$PUBLIC/$f" && mv "$ASIDE/$f" "$PUBLIC/$f" || exit 1
  done || fail "moving the PHP files back from $ASIDE failed: move them into $PUBLIC by hand"
  rmdir "$ASIDE" 2> /dev/null || true
  say "the PHP version is back in $PUBLIC (the kpp program is $PUBLIC/gorating.cgi.kpp)"
}

if [ "$1" = --back-to-php ]; then
  back_to_php
  exit 0
fi
[ $# -eq 0 ] || [ "$1" = --from ] || fail "unknown option $1 (see the head of $0)"

# 1. Checks.
[ ! -e "$ASIDE" ] || fail "$ASIDE exists: switched already? (--back-to-php puts the PHP version back)"
[ -f "$PUBLIC/start.php" ] || say "no $PUBLIC/start.php: the PHP version is not there (removed already?), so there is no going back to it from here"
[ -f "$CONFIG" ] || fail "no $CONFIG: copy gorating-config.example.json (in the package, or deploy/ in git) there and fill it in (DEPLOY-CHECKLIST.md)"
for field in '"database"' '"sessionSecret"' '"siteAddress"' '"mailCommand"'; do
  grep -q "$field" "$CONFIG" || fail "$CONFIG has no $field (DEPLOY-CHECKLIST.md)"
done
grep -q '": *"<' "$CONFIG" && fail "$CONFIG still has a placeholder (\"<...>\")"
grep -q 'gorating-test\|gorating_test' "$CONFIG" && fail "$CONFIG has the test site's values (gorating_test, gorating-test.nfshost.com): production's database and https://gorating.com"
if stat -f '%Sp %Sg' "$CONFIG" > /dev/null 2>&1; then mode=$(stat -f '%Sp %Sg' "$CONFIG"); else mode=$(stat -c '%A %G' "$CONFIG"); fi
[ "$mode" = "-rw-r----- $CONFIG_GROUP" ] || fail "$CONFIG is $mode: chgrp $CONFIG_GROUP $CONFIG; chmod 640 $CONFIG"
# The database client's login (my.cnf, which kpp-deploy.sh's DATABASE reads): written from the
# configuration's "database" when there is none (kovarex: "the data is already in gorating-config.json").
if [ ! -f "$MYCNF" ]; then
  db=$(tr '\n' ' ' < "$CONFIG" | sed -n 's/.*"database" *: *{\([^}]*\)}.*/\1/p')
  case "$db" in *'\'*) fail "$CONFIG's database has a backslash (an escape): write $MYCNF by hand ([client] host, port, user, password, database)" ;; esac
  field() { echo "$db" | sed -n "s/.*\"$1\" *: *\"\([^\"]*\)\".*/\1/p"; }
  host=$(field host); user=$(field user); password=$(field password); name=$(field name)
  port=$(echo "$db" | sed -n 's/.*"port" *: *\([0-9][0-9]*\).*/\1/p')
  [ -n "$host" ] && [ -n "$user" ] && [ -n "$name" ] || fail "cannot read host, user and name from $CONFIG's database: write $MYCNF by hand"
  mkdir -p "$(dirname "$MYCNF")" || fail "cannot make $(dirname "$MYCNF")"
  (umask 077 && printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\ndatabase=%s\n' "$host" "${port:-3306}" "$user" "$password" "$name" > "$MYCNF") || fail "cannot write $MYCNF"
  chmod 600 "$MYCNF"
  say "wrote $MYCNF from $CONFIG's database"
fi
echo "SELECT COUNT(*) FROM user" | $DATABASE > /dev/null || fail "the database client ($DATABASE) does not reach the database: write its my.cnf ([client] host, user, password, database; chmod 600)"
if [ ! -f "$DEPLOY" ]; then
  if command -v fetch > /dev/null 2>&1; then fetch -q -o "$DEPLOY" "$DEPLOY_URL"; else curl -fsSL -o "$DEPLOY" "$DEPLOY_URL"; fi || fail "cannot download kpp-deploy.sh from $DEPLOY_URL"
  say "downloaded $DEPLOY"
fi
moving=$(cd "$PUBLIC" && for f in *.php src .htaccess; do [ -e "$f" ] && echo "$f"; done)
say "all checks passed. The switch will:"
echo "  - move to $ASIDE: $(echo $moving)"
echo "  - run $DEPLOY --migrate: migrations 001, 002, the latest kpp release, its smoke tests"
echo "  - on any failure, move the PHP files back"
printf 'switch: type yes to switch now: '
read -r answer
[ "$answer" = yes ] || fail "not switched (nothing changed)"

# 3. The PHP version aside.
mkdir -p "$ASIDE" || fail "cannot make $ASIDE"
echo "$moving" | while read -r f; do
  [ -n "$f" ] || continue
  mv "$PUBLIC/$f" "$ASIDE/$f" || exit 1
done || { back_to_php; fail "moving the PHP files to $ASIDE failed: the PHP version is back"; }
say "the PHP version is in $ASIDE"

# 4. Migrations, the release, its smoke tests (kpp-deploy.sh makes its migrations' table first).
sh "$DEPLOY" --mark-applied > /dev/null || { back_to_php; fail "kpp-deploy.sh cannot make its migrations' table: the PHP version is back"; }
if ! sh "$DEPLOY" --migrate "$@"; then
  back_to_php
  fail "kpp-deploy.sh failed (above): the PHP version is back; migrations it ran stay (the PHP version works with them)"
fi

say "gorating.com runs the kpp version. Now:"
echo "  - the hourly scheduled task, a command (user web): cd $PUBLIC && GORATING_CONFIG=$CONFIG ./gorating.cgi --job update_tournament_list --quiet"
echo "    (its next run also finishes the rating update in progress)"
echo "  - check in a browser: the home page, a player, the players' search, a tournament, a game; log in (everyone logs in again)"
echo "  - back to PHP: sh $0 --back-to-php"
