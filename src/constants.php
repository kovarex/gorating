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
?>
