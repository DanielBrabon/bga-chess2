<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * ChessSequel implementation : © <Daniel Brabon> <dev.d8dms@simplelogin.co>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * ChessSequel game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

// The variables defined here are accessible everywhere in your game logic file (and also view.php file)

// All valid army names
$this->all_army_names = array("classic", "nemesis", "empowered", "reaper", "twokings", "animal", "testarmy");

// The starting board layouts for all armies on white side
$this->all_armies_starting_layout = array(
  "classic" => array(
    "pawn_1" => array(1, 2, "pawn"),
    "pawn_2" => array(2, 2, "pawn"),
    "pawn_3" => array(3, 2, "pawn"),
    "pawn_4" => array(4, 2, "pawn"),
    "pawn_5" => array(5, 2, "pawn"),
    "pawn_6" => array(6, 2, "pawn"),
    "pawn_7" => array(7, 2, "pawn"),
    "pawn_8" => array(8, 2, "pawn"),
    "rook_1" => array(1, 1, "rook"),
    "rook_2" => array(8, 1, "rook"),
    "knight_1" => array(2, 1, "knight"),
    "knight_2" => array(7, 1, "knight"),
    "bishop_1" => array(3, 1, "bishop"),
    "bishop_2" => array(6, 1, "bishop"),
    "queen" => array(4, 1, "queen"),
    "king" => array(5, 1, "king")
  ),

  "nemesis" => array(
    "npawn_1" => array(1, 2, "nemesispawn"),
    "npawn_2" => array(2, 2, "nemesispawn"),
    "npawn_3" => array(3, 2, "nemesispawn"),
    "npawn_4" => array(4, 2, "nemesispawn"),
    "npawn_5" => array(5, 2, "nemesispawn"),
    "npawn_6" => array(6, 2, "nemesispawn"),
    "npawn_7" => array(7, 2, "nemesispawn"),
    "npawn_8" => array(8, 2, "nemesispawn"),
    "rook_1" => array(1, 1, "rook"),
    "rook_2" => array(8, 1, "rook"),
    "knight_1" => array(2, 1, "knight"),
    "knight_2" => array(7, 1, "knight"),
    "bishop_1" => array(3, 1, "bishop"),
    "bishop_2" => array(6, 1, "bishop"),
    "nemesis" => array(4, 1, "nemesis"),
    "king" => array(5, 1, "king")
  ),

  "empowered" => array(
    "pawn_1" => array(1, 2, "pawn"),
    "pawn_2" => array(2, 2, "pawn"),
    "pawn_3" => array(3, 2, "pawn"),
    "pawn_4" => array(4, 2, "pawn"),
    "pawn_5" => array(5, 2, "pawn"),
    "pawn_6" => array(6, 2, "pawn"),
    "pawn_7" => array(7, 2, "pawn"),
    "pawn_8" => array(8, 2, "pawn"),
    "erook_1" => array(1, 1, "empoweredrook"),
    "erook_2" => array(8, 1, "empoweredrook"),
    "eknight_1" => array(2, 1, "empoweredknight"),
    "eknight_2" => array(7, 1, "empoweredknight"),
    "ebishop_1" => array(3, 1, "empoweredbishop"),
    "ebishop_2" => array(6, 1, "empoweredbishop"),
    "equeen" => array(4, 1, "elegantqueen"),
    "king" => array(5, 1, "king")
  ),

  "reaper" => array(
    "pawn_1" => array(1, 2, "pawn"),
    "pawn_2" => array(2, 2, "pawn"),
    "pawn_3" => array(3, 2, "pawn"),
    "pawn_4" => array(4, 2, "pawn"),
    "pawn_5" => array(5, 2, "pawn"),
    "pawn_6" => array(6, 2, "pawn"),
    "pawn_7" => array(7, 2, "pawn"),
    "pawn_8" => array(8, 2, "pawn"),
    "ghost_1" => array(1, 1, "ghost"),
    "ghost_2" => array(8, 1, "ghost"),
    "knight_1" => array(2, 1, "knight"),
    "knight_2" => array(7, 1, "knight"),
    "bishop_1" => array(3, 1, "bishop"),
    "bishop_2" => array(6, 1, "bishop"),
    "reaper" => array(4, 1, "reaper"),
    "king" => array(5, 1, "king")
  ),

  "twokings" => array(
    "pawn_1" => array(1, 2, "pawn"),
    "pawn_2" => array(2, 2, "pawn"),
    "pawn_3" => array(3, 2, "pawn"),
    "pawn_4" => array(4, 2, "pawn"),
    "pawn_5" => array(5, 2, "pawn"),
    "pawn_6" => array(6, 2, "pawn"),
    "pawn_7" => array(7, 2, "pawn"),
    "pawn_8" => array(8, 2, "pawn"),
    "rook_1" => array(1, 1, "rook"),
    "rook_2" => array(8, 1, "rook"),
    "knight_1" => array(2, 1, "knight"),
    "knight_2" => array(7, 1, "knight"),
    "bishop_1" => array(3, 1, "bishop"),
    "bishop_2" => array(6, 1, "bishop"),
    "wking_1" => array(4, 1, "warriorking"),
    "wking_2" => array(5, 1, "warriorking")
  ),

  "animal" => array(
    "pawn_1" => array(1, 2, "pawn"),
    "pawn_2" => array(2, 2, "pawn"),
    "pawn_3" => array(3, 2, "pawn"),
    "pawn_4" => array(4, 2, "pawn"),
    "pawn_5" => array(5, 2, "pawn"),
    "pawn_6" => array(6, 2, "pawn"),
    "pawn_7" => array(7, 2, "pawn"),
    "pawn_8" => array(8, 2, "pawn"),
    "elephant_1" => array(1, 1, "elephant"),
    "elephant_2" => array(8, 1, "elephant"),
    "whorse_1" => array(2, 1, "wildhorse"),
    "whorse_2" => array(7, 1, "wildhorse"),
    "tiger_1" => array(3, 1, "tiger"),
    "tiger_2" => array(6, 1, "tiger"),
    "jqueen" => array(4, 1, "junglequeen"),
    "king" => array(5, 1, "king")
  ),

  "empty" => array(
    "empty_1" => array(1, 2, "empty"),
    "empty_2" => array(2, 2, "empty"),
    "empty_3" => array(3, 2, "empty"),
    "empty_4" => array(4, 2, "empty"),
    "empty_5" => array(5, 2, "empty"),
    "empty_6" => array(6, 2, "empty"),
    "empty_7" => array(7, 2, "empty"),
    "empty_8" => array(8, 2, "empty"),
    "empty_9" => array(1, 1, "empty"),
    "empty_10" => array(8, 1, "empty"),
    "empty_11" => array(2, 1, "empty"),
    "empty_12" => array(7, 1, "empty"),
    "empty_13" => array(3, 1, "empty"),
    "empty_14" => array(6, 1, "empty"),
    "empty_15" => array(4, 1, "empty"),
    "empty_16" => array(5, 1, "empty")
  ),

  "testarmy" => array(
    "pawn" => array(1, 2, "pawn"),
    "knight" => array(3, 4, "knight"),
    "bishop" => array(4, 3, "bishop"),
    "rook" => array(5, 3, "rook"),
    "king" => array(5, 2, "king"),
    "queen_1" => array(6, 4, "queen"),
    "queen_2" => array(6, 1, "queen"),
    "queen_3" => array(8, 1, "queen")
  )
);

$this->all_armies_promote_options = array(
  "classic" => array("knight", "bishop", "rook", "queen"),
  "nemesis" => array("knight", "bishop", "rook", "nemesis"),
  "empowered" => array("empoweredknight", "empoweredbishop", "empoweredrook", "elegantqueen"),
  "reaper" => array("knight", "bishop", "ghost", "reaper"),
  "twokings" => array("knight", "bishop", "rook"),
  "animal" => array("wildhorse", "tiger", "elephant", "junglequeen"),
  "testarmy" => array("knight", "bishop", "rook", "queen")
);

$this->button_labels = array(
  "classic" => clienttranslate("Classic"),
  "nemesis" => clienttranslate("Nemesis"),
  "empowered" => clienttranslate("Empowered"),
  "reaper" => clienttranslate("Reaper"),
  "twokings" => clienttranslate("Two Kings"),
  "animal" => clienttranslate("Animal"),
  "testarmy" => clienttranslate("Test Army")
);

$this->piece_ranks = array(
  "pawn" => 0,
  "knight" => 1,
  "bishop" => 1,
  "rook" => 2,
  "queen" => 3,
  "nemesispawn" => 0,
  "nemesis" => 3,
  "empoweredrook" => 2,
  "empoweredknight" => 1,
  "empoweredbishop" => 1,
  "elegantqueen" => 3,
  "ghost" => 2,
  "reaper" => 3,
  "elephant" => 2,
  "wildhorse" => 1,
  "tiger" => 1,
  "junglequeen" => 3
);

$this->attack_steps = array(
  "wpawn" => array([1, 1], [-1, 1]),
  "bpawn" => array([1, -1], [-1, -1]),
  "knight" => array([2, 1], [1, 2], [2, -1], [1, -2], [-2, 1], [-1, 2], [-2, -1], [-1, -2]),
  "bishop" => array([1, 1], [-1, 1], [-1, -1], [1, -1]),
  "rook" => array([1, 0], [-1, 0], [0, 1], [0, -1]),
  "king" => array([1, 0], [1, 1], [0, 1], [-1, 1], [-1, 0], [-1, -1], [0, -1], [1, -1]),
  "tiger" => array([1, 1], [-1, 1], [-1, -1], [1, -1])
);

$this->attack_reps = array(
  "wpawn" => 1,
  "bpawn" => 1,
  "knight" => 1,
  "bishop" => 7,
  "rook" => 7,
  "king" => 1,
  "tiger" => 2
);

$this->effective_types = array(
  "wpawn" => array("wpawn"),
  "bpawn" => array("bpawn"),
  "knight" => array("knight"),
  "bishop" => array("bishop"),
  "rook" => array("rook"),
  "king" => array("king"),
  "tiger" => array("tiger"),
  "queen" => array("bishop", "rook"),
  "nemesis" => array("bishop", "rook"),
  "empoweredknight" => array("knight"),
  "empoweredbishop" => array("bishop"),
  "empoweredrook" => array("rook"),
  "elegantqueen" => array("king"),
  "warriorking" => array("king"),
  "wildhorse" => array("knight"),
  "junglequeen" => array("knight", "rook")
);

$this->type_code = array(
  "pawn" => "p",
  "knight" => "n",
  "bishop" => "b",
  "rook" => "r",
  "queen" => "q",
  "king" => "k",
  "nemesispawn" => "o",
  "nemesis" => "m",
  "empoweredknight" => "i",
  "empoweredbishop" => "s",
  "empoweredrook" => "c",
  "elegantqueen" => "l",
  "ghost" => "g",
  "reaper" => "a",
  "warriorking" => "w",
  "elephant" => "e",
  "wildhorse" => "h",
  "tiger" => "t",
  "junglequeen" => "j"
);

/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/
