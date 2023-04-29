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
define('NORMAL', 0);
define('CAPTURING', 1);
define('PROMOTING', 2);
define('CAPTURING_AND_PROMOTING', 3);
define('EN_PASSANT_VULNERABLE', 4);
define('CAPTURED', 5);
