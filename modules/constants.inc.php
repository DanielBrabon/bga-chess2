<?php

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
