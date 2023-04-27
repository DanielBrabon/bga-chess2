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

$this->files = ["a", "b", "c", "d", "e", "f", "g", "h"];

// All valid army names
$this->all_army_names = array("classic", "nemesis", "empowered", "reaper", "twokings", "animal");

// The board layouts for all armies
$this->all_armies_layouts = array(
  "classic" => array(
    "rook", "knight", "bishop", "queen", "king", "bishop", "knight", "rook",
    "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn"
  ),

  "nemesis" => array(
    "rook", "knight", "bishop", "nemesis", "king", "bishop", "knight", "rook",
    "nemesispawn", "nemesispawn", "nemesispawn", "nemesispawn", "nemesispawn", "nemesispawn", "nemesispawn", "nemesispawn"
  ),

  "empowered" => array(
    "empoweredrook", "empoweredknight", "empoweredbishop", "elegantqueen", "king", "empoweredbishop", "empoweredknight", "empoweredrook",
    "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn"
  ),

  "reaper" => array(
    "ghost", "knight", "bishop", "reaper", "king", "bishop", "knight", "ghost",
    "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn"
  ),

  "twokings" => array(
    "rook", "knight", "bishop", "warriorking", "warriorking", "bishop", "knight", "rook",
    "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn"
  ),

  "animal" => array(
    "elephant", "wildhorse", "tiger", "junglequeen", "king", "tiger", "wildhorse", "elephant",
    "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn", "pawn"
  ),

  "empty" => array(
    "empty", "empty", "empty", "empty", "empty", "empty", "empty", "empty",
    "empty", "empty", "empty", "empty", "empty", "empty", "empty", "empty"
  )
);

$this->all_armies_promote_options = array(
  "classic" => array("knight", "bishop", "rook", "queen"),
  "nemesis" => array("knight", "bishop", "rook", "nemesis"),
  "empowered" => array("empoweredknight", "empoweredbishop", "empoweredrook", "elegantqueen"),
  "reaper" => array("knight", "bishop", "ghost", "reaper"),
  "twokings" => array("knight", "bishop", "rook"),
  "animal" => array("wildhorse", "tiger", "elephant", "junglequeen")
);

$this->button_labels = array(
  "classic" => clienttranslate("Classic"),
  "nemesis" => clienttranslate("Nemesis"),
  "empowered" => clienttranslate("Empowered"),
  "reaper" => clienttranslate("Reaper"),
  "twokings" => clienttranslate("Two Kings"),
  "animal" => clienttranslate("Animal"),
  "knight" => clienttranslate("Knight"),
  "bishop" => clienttranslate("Bishop"),
  "rook" => clienttranslate("Rook"),
  "queen" => clienttranslate("Queen"),
  "nemesis" => clienttranslate("Nemesis"),
  "empoweredknight" => clienttranslate("Empowered Knight"),
  "empoweredbishop" => clienttranslate("Empowered Bishop"),
  "empoweredrook" => clienttranslate("Empowered Rook"),
  "elegantqueen" => clienttranslate("Elegant Queen"),
  "ghost" => clienttranslate("Ghost"),
  "reaper" => clienttranslate("Reaper"),
  "wildhorse" => clienttranslate("Wild Horse"),
  "tiger" => clienttranslate("Tiger"),
  "elephant" => clienttranslate("Elephant"),
  "junglequeen" => clienttranslate("Jungle Queen"),
  "bid_0" => clienttranslate("Bid 0 Stones"),
  "bid_1" => clienttranslate("Bid 1 Stone"),
  "bid_2" => clienttranslate("Bid 2 Stones")
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

$this->end_conditions = array(
  0 => clienttranslate("checkmate"),
  1 => clienttranslate("stalemate"),
  2 => clienttranslate("midline invasion"),
  3 => clienttranslate("threefold repetition"),
  4 => clienttranslate("50 move rule"),
  5 => clienttranslate("agreed to draw"),
  6 => clienttranslate("concession")
);

/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/
