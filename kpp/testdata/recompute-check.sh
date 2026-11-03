#!/bin/bash
# Checks that recomputing ratings (the rating update a deleted or edited game starts) gives what
# processing a tournament gave: on a fresh copy of the made-up data (scenario-reset.sh), processes
# a saved tournament with half its players registered before it, recomputes every game
# from the tournament's start, and counts the games whose gorating ratings moved.
# Usage: recompute-check.sh DATABASE URL (gorating_kpp http://127.0.0.1:8084?secret=..., or
# gorating_php http://127.0.0.1:8083?) [KEY: a tournament whose page is in egdPageDirectory,
# T261031A by default].
# A tournament with a jigo tests the most: a saved page with one game changed to Jigo on both
# sides serves (saved under the name egdPageName gives its key).
ENV=${GORATING_ENV:?set GORATING_ENV to the local setup: mariadb/}
M=$ENV/mariadb/bin/mariadb.exe
DB=$1; URL=$2; KEY=${3:-T261031A}
q() { $M -h127.0.0.1 -P3307 -uroot -N $DB -e "$1" 2>/dev/null; }
job() { curl -s "${URL%%\?*}/$1?${URL#*\?}&egd_tournament_key=$KEY"; }
job process_tournament > /dev/null
T=$(q "SELECT id FROM egd_tournament WHERE egd_key='$KEY'")
# Register every other player before the tournament, starting from EGD's rating of them, and
# process the tournament again from nothing so their games get gorating ratings.
q "UPDATE user u JOIN (SELECT p.id, (SELECT IF(g.winner_user_id=p.id, g.winner_old_egd_rating, g.loser_old_egd_rating) FROM game g
     WHERE g.egd_tournament_id=$T AND (g.winner_user_id=p.id OR g.loser_user_id=p.id) ORDER BY g.egd_tournament_round LIMIT 1) AS egd
     FROM egd_tournament_result r JOIN user p ON p.id=r.user_id WHERE r.egd_tournament_id=$T AND p.id % 2 = 0) x ON x.id=u.id
   SET u.register_timestamp='2026-01-01 00:00:00', u.register_rating=x.egd"
q "DELETE FROM egd_tournament_result WHERE egd_tournament_id=$T; DELETE FROM egd_tournament WHERE id=$T"
job process_tournament > /dev/null
T=$(q "SELECT id FROM egd_tournament WHERE egd_key='$KEY'")
snapshot() { q "SELECT id, winner_old_rating, winner_new_rating, loser_old_rating, loser_new_rating FROM game WHERE egd_tournament_id=$T ORDER BY id"; }
snapshot > /tmp/recompute-before.txt
echo "$(q "SELECT COUNT(*) FROM game WHERE egd_tournament_id=$T AND (winner_old_rating IS NOT NULL OR loser_old_rating IS NOT NULL)") games with gorating ratings, $(q "SELECT COUNT(*) FROM game WHERE egd_tournament_id=$T AND jigo") jigo"
# Recompute everything from the tournament's start.
for id in $(q "SELECT id FROM user WHERE register_timestamp='2026-01-01 00:00:00'"); do
  q "CALL start_rating_update($id, (SELECT register_rating FROM user WHERE id=$id), $id, (SELECT register_rating FROM user WHERE id=$id),
       (SELECT timestamp FROM egd_tournament WHERE id=$T))"
done
for i in 1 2 3 4 5 6 7 8; do
  [ "$(q "SELECT value FROM variable WHERE name='rating_update_in_progress'")" = "0" ] && break
  job update_rating > /dev/null
done
snapshot > /tmp/recompute-after.txt
python -c "
import sys
a=[l.split('\t') for l in open(sys.argv[1]).read().split('\n') if l]; b=[l.split('\t') for l in open(sys.argv[2]).read().split('\n') if l]
f=lambda v: None if v=='NULL' else float(v)
moved=[(x[0],x[1:],y[1:]) for x,y in zip(a,b) if any((f(p) is None)!=(f(q) is None) or (f(p) is not None and abs(f(p)-f(q))>0.0001) for p,q in zip(x[1:],y[1:]))]
print(len(moved),'games moved')
for m in moved[:5]: print('  game',m[0],'processed',m[1],'recomputed',m[2])
" $(cygpath -w /tmp/recompute-before.txt) $(cygpath -w /tmp/recompute-after.txt)
# A game deleted whose winner has no gorating rating and whose loser has one starts an update
# (migrations/002), and after it, processing the tournament again finds nothing different.
G=$(q "SELECT id FROM game WHERE egd_tournament_id=$T AND winner_old_rating IS NULL AND loser_old_rating IS NOT NULL ORDER BY egd_tournament_round LIMIT 1")
q "UPDATE game SET deleted=1 WHERE id=$G"
echo "game $G deleted: update in progress $(q "SELECT value FROM variable WHERE name='rating_update_in_progress'")"
for i in 1 2 3 4 5 6 7 8; do
  [ "$(q "SELECT value FROM variable WHERE name='rating_update_in_progress'")" = "0" ] && break
  job update_rating > /dev/null
done
job check_rating_changes > /dev/null
q "DELETE FROM rating_discrepancy" 2>/dev/null
job process_tournament > /dev/null
echo "processed again: $(q "SELECT COUNT(*) FROM rating_discrepancy" || echo none) discrepancies"
# A second change while an update is in progress keeps the update's earlier start.
q "CALL start_rating_update($(q "SELECT MIN(id) FROM user"), 1500, $(q "SELECT MIN(id) FROM user"), 1500, '2026-10-30 00:00:00')"
q "CALL start_rating_update($(q "SELECT MIN(id) FROM user"), 1500, $(q "SELECT MIN(id) FROM user"), 1500, '2027-01-01 00:00:00')"
echo "two changes: update starts at $(q "SELECT value FROM variable WHERE name='rating_update_timestamp'")"
# A tournament added after a later game was computed: the later game is recomputed from it.
q "UPDATE variable SET value='0' WHERE name='rating_update_in_progress'; DELETE FROM rating_update_value"
read A B <<< "$(q "SELECT r1.user_id, r2.user_id FROM egd_tournament_result r1 JOIN egd_tournament_result r2 ON r2.egd_tournament_id=r1.egd_tournament_id
  JOIN user u1 ON u1.id=r1.user_id JOIN user u2 ON u2.id=r2.user_id WHERE r1.egd_tournament_id=$T AND r1.user_id < r2.user_id
  AND u1.register_rating IS NOT NULL AND u2.register_rating IS NOT NULL LIMIT 1")"
q "INSERT INTO game(winner_user_id, loser_user_id, game_type_id, timestamp, winner_old_rating, winner_new_rating, loser_old_rating, loser_new_rating)
   VALUES($A, $B, 5, (SELECT timestamp + INTERVAL 1 DAY FROM egd_tournament WHERE id=$T), 1500, 1510, 1500, 1490)"
F=$(q "SELECT MAX(id) FROM game")
q "DELETE FROM egd_tournament_result WHERE egd_tournament_id=$T; DELETE FROM egd_tournament WHERE id=$T"
job process_tournament > /dev/null
T=$(q "SELECT id FROM egd_tournament WHERE egd_key='$KEY'")
echo "tournament added before game $F: update in progress $(q "SELECT value FROM variable WHERE name='rating_update_in_progress'")"
for i in 1 2 3 4; do job update_rating > /dev/null; done
last() { q "SELECT IF(winner_user_id=$1, winner_new_rating, loser_new_rating) FROM game WHERE egd_tournament_id=$T AND deleted=0 AND (winner_user_id=$1 OR loser_user_id=$1) ORDER BY egd_tournament_round DESC LIMIT 1"; }
echo "game $F starts from $(q "SELECT winner_old_rating, loser_old_rating FROM game WHERE id=$F" | tr '\t' ' '), the tournament left $(last $A) $(last $B)"
echo "their ratings: $(q "SELECT rating FROM user WHERE id IN ($A, $B) ORDER BY id" | tr '\n' ' ')game $F left $(q "SELECT winner_new_rating, loser_new_rating FROM game WHERE id=$F" | tr '\t' ' ')"
