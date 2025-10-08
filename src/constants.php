<?php
define("ADMIN_LEVEL_OWNER", 1);
define("ADMIN_LEVEL_ADMIN", 2);
define("ADMIN_LEVEL_MOD", 3);
define("ADMIN_LEVEL_TRUSTED_USER", 4);
define("ADMIN_LEVEL_USER", 5);
define("ADMIN_LEVEL_UNREGISTERED", 6);

define("GAME_TYPE_EGD_A", 1);
define("GAME_TYPE_EGD_B", 2);
define("GAME_TYPE_EGD_C", 3);
define("GAME_TYPE_EGD_D", 4);
define("GAME_TYPE_SERIOUS", 5);
define("GAME_TYPE_RAPID", 6);
define("GAME_TYPE_BLITZ", 7);
define("GAME_TYPE_COUNT", 7);

define("RATING_CHANGE_TYPE_AUTOMATIC_EGD_RATING_RANK_RESET_OLD", 1);
define("RATING_CHANGE_TYPE_AUTOMATIC_EGD_RATING_RANK_RESET_NEW", 2);
define("RATING_CHANGE_TYPE_AUTOMATIC_EGD_RATING_RANK_RESET_UNKNOWN", 3);
define("RATING_CHANGE_TYPE_MANUAL_EGD_RANK_PROMOTION", 4);
define("RATING_CHANGE_TYPE_MANUAL_RANK_PROMOTION", 5);
define("RATING_CHANGE_TYPE_ERROR", 6);

define('GAME_TYPE_RATING_MODIFIER', [GAME_TYPE_EGD_A => 1,
                                     GAME_TYPE_EGD_B => 0.75,
                                     GAME_TYPE_EGD_C => 0.5,
                                     GAME_TYPE_EGD_D => 0.25,
                                     GAME_TYPE_SERIOUS => 0.5,
                                     GAME_TYPE_RAPID => 0.25,
                                     GAME_TYPE_BLITZ => 0.1]);

define("RATING_RANKS_PER_HANDICAP", 1.2);
define("RATING_RANKS_PER_KOMI", 0.1);

define("TABLE_PAGE_SIZE", 100);
define("TOURNAMENT_COUNT_TO_PRCESS_PER_BATCH", 30);
define("SECONDS_TO_ALLOW_EDIT_MY_GAMES", 60 * 60 * 24);

define("CHANGE_USER_FIRST_NAME", 1);
define("CHANGE_USER_LAST_NAME", 2);
define("CHANGE_USER_ADMIN_LEVEL", 3);
define("CHANGE_USER_EMAIL", 4);
define("CHANGE_USER_USERNAME", 5);
define("CHANGE_USER_EGD_PIN", 6);
define("CHANGE_USER_COUNTRY", 7);
define("CHANGE_USER_CLUB", 8);
define("CHANGE_GAME_WINNER", 9);
define("CHANGE_GAME_BLACK_PLAYER", 10);
define("CHANGE_GAME_HANDICAP", 11);
define("CHANGE_GAME_KOMI", 12);
define("CHANGE_GAME_LOCATION", 13);
define("CHANGE_GAME_WINNER_COMMENT", 14);
define("CHANGE_GAME_LOSER_COMMENT", 15);
define("CHANGE_GAME_SGF", 16);
define("CHANGE_GAME_TIMESTAMP", 17);
?>
