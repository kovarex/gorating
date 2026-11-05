# Writes made-up data for gorating's tables (no real person in it) as SQL, for a local database
# made from the production schema: users (registered and EGD-only), EGD tournaments with
# results, games of every type with their rating changes, invites, edits and visits.
# Every registered user's password is "test" (a cost-4 bcrypt hash).
#
# Usage: python generate.py > fixture.sql, then load it into a database that has the schema
# (prod.sql) and the procedures (setup.sql), with no rows in user, game and the rest.
import math
import sys
import random

random.seed(20261102)
sys.stdout.reconfigure(encoding="utf-8", newline="\n")

PASSWORD_TEST = "$2y$04$testdatatestdatatestduVIB.i.Q5c56eosJEGcs0EX4QvQfikSO"

GAME_TYPE_EGD = {1: 1.0, 2: 0.75, 3: 0.5, 4: 0.25}
GAME_TYPE_FRIENDLY = {5: 0.5, 6: 0.25, 7: 0.1}
MODIFIER = {**GAME_TYPE_EGD, **GAME_TYPE_FRIENDLY}

FIRST = ["Jan", "Petr", "Eva", "Anna", "Tomáš", "Lukáš", "Jana", "Pavel", "Marek", "Zuzana",
         "Hans", "Greta", "Piotr", "Agnieszka", "Juraj", "Ondřej", "Kateřina", "Jiří", "Lena",
         "Marco", "Sofia", "Erik", "Ingrid", "Mateo", "Chloé", "Yuki", "Min-jun", "O'Neil",
         "Tom & Jerry", "<b>Bold</b>"]
LAST = ["Novák", "Svoboda", "Dvořák", "Černá", "Procházka", "Kučera", "Veselý", "Horák",
        "Müller", "Schmidt", "Kowalski", "Nowak", "Horváth", "Rossi", "García", "Dubois",
        "Andersson", "Tanaka", "Kim", "Smith", "O'Brien", "Lee", "Weiß", "Zieliński", "Szabó"]
CITIES = ["Prague", "Brno", "Berlin", "Warsaw", "Bratislava", "Vienna", "Budapest", "Paris",
          "Hamburg", "Kraków", "Leipzig", "Ostrava"]
COMMENTS = [None, None, None, "Good game", "I blundered the corner", "Close endgame",
            "Lost on time", "Ko fight decided it", "gg", "Učebnicová partie", "<i>tesuji</i> & ladder"]


def sql(value):
    if value is None:
        return "NULL"
    if isinstance(value, bool):
        return "1" if value else "0"
    if isinstance(value, (int, float)):
        return repr(value)
    return "'" + str(value).replace("\\", "\\\\").replace("'", "\\'") + "'"


def insert(table, rows):
    if not rows:
        return
    columns = list(rows[0].keys())
    for start in range(0, len(rows), 500):
        chunk = rows[start:start + 500]
        print("INSERT INTO `%s` (%s) VALUES" % (table, ", ".join("`%s`" % c for c in columns)))
        print(",\n".join("(" + ", ".join(sql(r[c]) for c in columns) + ")" for r in chunk) + ";")


def timestamp(day, second):
    # Days from 2024-01-01, as 'YYYY-MM-DD HH:MM:SS'.
    import datetime
    t = datetime.datetime(2024, 1, 1) + datetime.timedelta(days=day, seconds=second)
    return t.strftime("%Y-%m-%d %H:%M:%S")


# gorating's rating formula (src/rating_helper.php).
def beta(rating):
    return -7 * math.log(3300 - rating)


def new_rating(rating, opponent, result, game_type, extra_handicap, extra_komi):
    adjusted = rating + 100 * (extra_handicap * 1.2 + extra_komi * 0.1)
    se = 1.0 / (1.0 + math.exp(beta(opponent) - beta(adjusted)))
    con = ((3300 - rating) / 200) ** 1.6
    bonus = math.log(1 + math.exp((2300 - rating) / 80)) / 5
    return rating + MODIFIER[game_type] * (con * (result - se) + bonus)


users = []
COUNTRIES = [1, 1, 1, 1, 2, 2, 3, 4, 4, 19, 22, 12, 27, 52, 53]
for i in range(1, 241):
    registered = i <= 60
    egd = i > 20
    rating = round(random.uniform(-300, 2600) if not egd else random.uniform(100, 2700), 3)
    first = random.choice(FIRST)
    last = random.choice(LAST)
    users.append({
        "id": i,
        "username": ("player%d" % i if i > 1 else "owner") if registered else None,
        "first_name": first,
        "last_name": last,
        "email": "player%d@example.test" % i if registered else None,
        "egd_pin": 10000000 + i * 37 if egd else None,
        "egd_rating": rating if egd else None,
        "rating": rating,
        "country_id": random.choice(COUNTRIES),
        "password": PASSWORD_TEST if registered else None,
        # 1 owner, 2 admin, 3 mod, 4 trusted, 5 user, 6 unregistered
        "admin_level_id": (1 if i == 1 else 2 if i == 2 else 3 if i <= 4 else 4 if i <= 10 else 5) if registered else 6,
        "invited_by_user_id": (1 if i <= 10 else random.randint(1, i - 1)) if registered and i > 1 else None,
        "register_timestamp": timestamp(random.randint(0, 200), random.randint(0, 86399)) if registered else None,
        "club": random.choice(["xxx", "Prah", "Brno", "Berl", "Wars"]),
        "register_rating": rating if registered else None,
        "setting_significant_digits_in_rating": random.choice([None, None, 0, 1, 2, 3]) if registered else None,
        "setting_rating_change_format": random.choice([None, 1, 2]) if registered else None,
    })

current = {u["id"]: u["rating"] for u in users}
current_egd = {u["id"]: u["egd_rating"] for u in users}

tournaments = []
results = []
events = []  # (day, second, kind, data): games in time order
for t in range(1, 21):
    day = random.randint(0, 650)
    rounds = random.randint(4, 7)
    players = random.sample(range(21, 241), random.randint(10, 30))
    key = "T%02d%02d%02dA" % (24 + day // 365, 1 + (day % 365) // 31, 1 + day % 28)
    key = key[:-1] + chr(ord("A") + t % 26)
    tournaments.append({
        "id": t, "egd_key": key,
        # EGD gives some tournaments no name.
        "name": None if t % 7 == 0 else
                random.choice(["%s Open", "%s Cup", "%s Spring Tournament", "Go Festival %s"]) % random.choice(CITIES),
        "player_count": len(players), "round_count": rounds,
        "timestamp": timestamp(day, 0), "country_id": random.choice(COUNTRIES),
        "game_type_id": random.choice([1, 2, 2, 3]), "city": random.choice(CITIES),
    })
    for place, pid in enumerate(players, 1):
        results.append({"id": len(results) + 1, "egd_tournament_id": t, "user_id": pid, "placement": place})
    for r in range(1, rounds + 1):
        shuffled = players[:]
        random.shuffle(shuffled)
        for a, b in zip(shuffled[0::2], shuffled[1::2]):
            events.append((day, r * 3600, "egd", (t, r, a, b)))

for g in range(2500):
    day = random.randint(0, 670)
    a, b = random.sample(range(1, 61), 2) if random.random() < 0.8 else (random.randint(1, 60), random.randint(21, 240))
    if a == b:
        continue
    events.append((day, random.randint(0, 86399), "friendly", (a, b)))

events.sort(key=lambda e: (e[0], e[1]))
games = []
for day, second, kind, data in events:
    if kind == "egd":
        t, r, a, b = data
        game_type = tournaments[t - 1]["game_type_id"]
    else:
        a, b = data
        t, r = None, None
        game_type = random.choice([5, 5, 6, 6, 7])
    handicap = 0
    komi = 6.5
    stronger, weaker = (a, b) if current[a] >= current[b] else (b, a)
    difference = (current[stronger] - current[weaker]) / 100
    if kind == "friendly" and difference >= 2 and random.random() < 0.6:
        handicap = min(9, int(difference))
        komi = 0.5
    # The weaker player wins with the expected probability, roughly.
    p_stronger = 1 / (1 + math.exp(-(difference - handicap * 1.2) * 0.6))
    winner, loser = (stronger, weaker) if random.random() < p_stronger else (weaker, stronger)
    winner_is_black = (winner == weaker) if handicap else random.random() < 0.5
    # Extra handicap and komi as the black (weaker) player gets them, seen from each side.
    sign_w = 1 if (winner == weaker and handicap) else (-1 if handicap else 0)
    w_old, l_old = current[winner], current[loser]
    w_new = new_rating(w_old, l_old, 1, game_type, sign_w * handicap, 0)
    l_new = new_rating(l_old, w_old, 0, game_type, -sign_w * handicap, 0)
    current[winner], current[loser] = w_new, l_new
    egd_old_w, egd_old_l = current_egd[winner], current_egd[loser]
    egd_new_w = egd_new_l = None
    if kind == "egd" and egd_old_w is not None and egd_old_l is not None:
        egd_new_w = new_rating(egd_old_w, egd_old_l, 1, game_type, 0, 0)
        egd_new_l = new_rating(egd_old_l, egd_old_w, 0, game_type, 0, 0)
        current_egd[winner], current_egd[loser] = egd_new_w, egd_new_l
    games.append({
        "id": len(games) + 1, "winner_user_id": winner, "loser_user_id": loser,
        "game_type_id": game_type, "timestamp": timestamp(day, second),
        "location": None if kind == "egd" else random.choice([None, "Prague go club", "online", "Brno", "U Fleků"]),
        "winner_comment": None if kind == "egd" else random.choice(COMMENTS),
        "loser_comment": None if kind == "egd" else random.choice(COMMENTS),
        "sgf": None if kind == "egd" or random.random() < 0.9 else "(;GM[1]FF[4]SZ[19];B[pd];W[dp];B[pp];W[dd])",
        "winner_old_rating": w_old, "winner_new_rating": w_new,
        "loser_old_rating": l_old, "loser_new_rating": l_new,
        "winner_old_egd_rating": egd_old_w if egd_new_w is not None else None,
        "winner_new_egd_rating": egd_new_w,
        "loser_old_egd_rating": egd_old_l if egd_new_l is not None else None,
        "loser_new_egd_rating": egd_new_l,
        "winner_is_black": winner_is_black, "handicap": handicap, "komi": komi,
        "egd_tournament_id": t, "egd_tournament_round": r,
        "rating_update_version": 0,
        "deleted": kind == "friendly" and random.random() < 0.01,
        "jigo": False,
    })

for u in users:
    u["rating"] = current[u["id"]]
    if u["egd_rating"] is not None:
        u["egd_rating"] = current_egd[u["id"]]

invites = []
for i in range(1, 16):
    invites.append({
        "id": i, "from_user_id": random.randint(1, 60),
        "user_id": random.randint(61, 240) if i % 3 == 0 else None,
        "first_name": random.choice(FIRST), "last_name": random.choice(LAST),
        "email": "invited%d@example.test" % i, "secret": random.randint(1, 2**31 - 1),
        "rating": random.choice([None, 100, 1200.0, 1850.5]),
        "timestamp": timestamp(600 + i, i * 100),
    })

changes = []
for i in range(1, 41):
    game = random.choice(games)
    changes.append({
        "id": i, "change_type_id": random.choice([13, 14, 15, 11, 12]),
        "game_id": game["id"], "user_id": None,
        "old_value": "old value %d" % i, "new_value": "new value %d" % i,
        "executed_by_user_id": random.randint(1, 4), "timestamp": timestamp(600 + i // 2, i * 60),
    })

rating_changes = []
for i in range(1, 11):
    uid = random.randint(21, 240)
    old = round(random.uniform(1000, 2400), 3)
    rating_changes.append({
        "id": i, "old_egd_rating": old, "new_egd_rating": (math.floor(old / 100) + 1) * 100,
        "old_rating": old, "new_rating": (math.floor(old / 100) + 1) * 100,
        "user_id": uid, "executed_by_user_id": None, "comment": None,
        "rating_change_type_id": random.choice([1, 2, 4]), "timestamp": timestamp(300 + i * 30, 0),
    })

PATHS = ["/", "/players", "/tournaments", "/about", "/player?id=%d", "/game?id=%d", "/tournament?id=%d",
         "/players?sort=rating&d=down", "/players?search=Nov", "/players?country_code=CZ",
         "/player%d", "/login", "/tournaments?page=2"]
visits = []
for i in range(1, 2001):
    path = random.choice(PATHS)
    if "%d" in path:
        path = path % random.randint(1, 60)
    visits.append({"id": i, "url": path, "source": "192.0.2.%d" % random.randint(1, 254),
                   "timestamp": timestamp(500 + i // 20, i * 7 % 86400)})

print("-- Made-up gorating data from kpp/testdata/generate.py; no real person in it.")
print("SET NAMES utf8mb4;")
print("SET FOREIGN_KEY_CHECKS=0;")
insert("user", users)
insert("egd_tournament", tournaments)
insert("egd_tournament_result", results)
insert("game", games)
insert("invite", invites)
insert("change", changes)
insert("rating_change", rating_changes)
insert("visit", visits)
print("SET FOREIGN_KEY_CHECKS=1;")
