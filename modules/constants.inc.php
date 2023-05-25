<?php

// Rulesets
define('RULESET_TWO_POINT_FOUR', 2);
define('RULESET_THREE_POINT_ZERO', 3);

// Options
define('OPTION_RULESET', 100);

// Armies
define('CLASSIC', 0);
define('NEMESIS', 1);
define('EMPOWERED', 2);
define('REAPER', 3);
define('TWOKINGS', 4);
define('ANIMAL', 5);

// Layout indices
define('LAYOUT_ROOKA', 0);
define('LAYOUT_KNIGHTA', 1);
define('LAYOUT_BISHOPA', 2);
define('LAYOUT_QUEEN', 3);
define('LAYOUT_KING', 4);
define('LAYOUT_BISHOPB', 5);
define('LAYOUT_KNIGHTB', 6);
define('LAYOUT_ROOKB', 7);
define('LAYOUT_PAWNA', 8);
define('LAYOUT_PAWNB', 9);
define('LAYOUT_PAWNC', 10);
define('LAYOUT_PAWND', 11);
define('LAYOUT_PAWNE', 12);
define('LAYOUT_PAWNF', 13);
define('LAYOUT_PAWNG', 14);
define('LAYOUT_PAWNH', 15);

// Game end conditions
define('CHECKMATE', 0);
define('STALEMATE', 1);
define('MIDLINE_INVASION', 2);
define('THREEFOLD_REPETITION', 3);
define('FIFTY_MOVE_RULE', 4);
define('AGREED_TO_DRAW', 5);
define('CONCESSION', 6);

// Piece states
define('NEUTRAL', 0);
define('CAPTURING', 1);
define('PROMOTING', 2);
define('CAPTURING_AND_PROMOTING', 3);
define('EN_PASSANT_VULNERABLE', 4);
define('CAPTURED', 5);
define('IN_CHECK', 6);

// Game states
define('ST_GAME_SETUP', 1);
define('ST_ARMY_SELECT', 2);
define('ST_PROCESS_ARMY_SELECTION', 3);
define('ST_PLAYER_MOVE', 4);
define('ST_PROCESS_MOVE', 5);
define('ST_PAWN_PROMOTION', 6);
define('ST_PROCESS_PROMOTION', 7);
define('ST_DUEL_OFFER', 8);
define('ST_PROCESS_DUEL_REJECTED', 9);
define('ST_DUEL_BIDDING', 10);
define('ST_PROCESS_DUEL_OUTCOME', 11);
define('ST_CALLED_BLUFF', 12);
define('ST_PROCESS_BLUFF_CHOICE', 13);
define('ST_PLAYER_KING_MOVE', 14);
define('ST_PROCESS_PASS', 15);
define('ST_OFFER_DRAW', 16);
define('ST_DRAW_OFFER', 17);
define('ST_PROCESS_DRAW_REJECTED', 18);
define('ST_GAME_END', 99);

// Statistics
define('STAT_END_CONDITION', 10);
define('STAT_MOVES_NUMBER', 11);
define('STAT_ARMY', 12);
define('STAT_ENEMIES_CAPTURED', 13);
define('STAT_FRIENDLIES_CAPTURED', 14);
define('STAT_DUELS_INITIATED', 15);
define('STAT_STONES_BID', 16);
define('STAT_DUEL_CAPTURES', 17);
define('STAT_BLUFFS_CALLED', 18);
