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

$this->files = [1 => "a", 2 => "b", 3 => "c", 4 => "d", 5 => "e", 6 => "f", 7 => "g", 8 => "h"];

// All valid army names
$this->all_army_names = array(
  CLASSIC => "classic",
  NEMESIS => "nemesis",
  EMPOWERED => "empowered",
  REAPER => "reaper",
  TWOKINGS => "twokings",
  ANIMAL => "animal"
);

// The board layouts for all armies
$this->all_armies_layouts = array(
  "classic" => array(
    LAYOUT_ROOKA => "rook",
    LAYOUT_KNIGHTA => "knight",
    LAYOUT_BISHOPA => "bishop",
    LAYOUT_QUEEN => "queen",
    LAYOUT_KING => "king",
    LAYOUT_BISHOPB => "bishop",
    LAYOUT_KNIGHTB => "knight",
    LAYOUT_ROOKB => "rook",
    LAYOUT_PAWNA => "pawn",
    LAYOUT_PAWNB => "pawn",
    LAYOUT_PAWNC => "pawn",
    LAYOUT_PAWND => "pawn",
    LAYOUT_PAWNE => "pawn",
    LAYOUT_PAWNF => "pawn",
    LAYOUT_PAWNG => "pawn",
    LAYOUT_PAWNH => "pawn"
  ),

  "nemesis" => array(
    LAYOUT_ROOKA => "rook",
    LAYOUT_KNIGHTA => "knight",
    LAYOUT_BISHOPA => "bishop",
    LAYOUT_QUEEN => "nemesis",
    LAYOUT_KING => "king",
    LAYOUT_BISHOPB => "bishop",
    LAYOUT_KNIGHTB => "knight",
    LAYOUT_ROOKB => "rook",
    LAYOUT_PAWNA => "nemesispawn",
    LAYOUT_PAWNB => "nemesispawn",
    LAYOUT_PAWNC => "nemesispawn",
    LAYOUT_PAWND => "nemesispawn",
    LAYOUT_PAWNE => "nemesispawn",
    LAYOUT_PAWNF => "nemesispawn",
    LAYOUT_PAWNG => "nemesispawn",
    LAYOUT_PAWNH => "nemesispawn"
  ),

  "empowered" => array(
    LAYOUT_ROOKA => "empoweredrook",
    LAYOUT_KNIGHTA => "empoweredknight",
    LAYOUT_BISHOPA => "empoweredbishop",
    LAYOUT_QUEEN => "elegantqueen",
    LAYOUT_KING => "king",
    LAYOUT_BISHOPB => "empoweredbishop",
    LAYOUT_KNIGHTB => "empoweredknight",
    LAYOUT_ROOKB => "empoweredrook",
    LAYOUT_PAWNA => "pawn",
    LAYOUT_PAWNB => "pawn",
    LAYOUT_PAWNC => "pawn",
    LAYOUT_PAWND => "pawn",
    LAYOUT_PAWNE => "pawn",
    LAYOUT_PAWNF => "pawn",
    LAYOUT_PAWNG => "pawn",
    LAYOUT_PAWNH => "pawn"
  ),

  "reaper" => array(
    LAYOUT_ROOKA => "ghost",
    LAYOUT_KNIGHTA => "knight",
    LAYOUT_BISHOPA => "bishop",
    LAYOUT_QUEEN => "reaper",
    LAYOUT_KING => "king",
    LAYOUT_BISHOPB => "bishop",
    LAYOUT_KNIGHTB => "knight",
    LAYOUT_ROOKB => "ghost",
    LAYOUT_PAWNA => "pawn",
    LAYOUT_PAWNB => "pawn",
    LAYOUT_PAWNC => "pawn",
    LAYOUT_PAWND => "pawn",
    LAYOUT_PAWNE => "pawn",
    LAYOUT_PAWNF => "pawn",
    LAYOUT_PAWNG => "pawn",
    LAYOUT_PAWNH => "pawn"
  ),

  "twokings" => array(
    LAYOUT_ROOKA => "rook",
    LAYOUT_KNIGHTA => "knight",
    LAYOUT_BISHOPA => "bishop",
    LAYOUT_QUEEN => "warriorking",
    LAYOUT_KING => "warriorking",
    LAYOUT_BISHOPB => "bishop",
    LAYOUT_KNIGHTB => "knight",
    LAYOUT_ROOKB => "rook",
    LAYOUT_PAWNA => "pawn",
    LAYOUT_PAWNB => "pawn",
    LAYOUT_PAWNC => "pawn",
    LAYOUT_PAWND => "pawn",
    LAYOUT_PAWNE => "pawn",
    LAYOUT_PAWNF => "pawn",
    LAYOUT_PAWNG => "pawn",
    LAYOUT_PAWNH => "pawn"
  ),

  "animal" => array(
    LAYOUT_ROOKA => "elephant",
    LAYOUT_KNIGHTA => "wildhorse",
    LAYOUT_BISHOPA => "tiger",
    LAYOUT_QUEEN => "junglequeen",
    LAYOUT_KING => "king",
    LAYOUT_BISHOPB => "tiger",
    LAYOUT_KNIGHTB => "wildhorse",
    LAYOUT_ROOKB => "elephant",
    LAYOUT_PAWNA => "pawn",
    LAYOUT_PAWNB => "pawn",
    LAYOUT_PAWNC => "pawn",
    LAYOUT_PAWND => "pawn",
    LAYOUT_PAWNE => "pawn",
    LAYOUT_PAWNF => "pawn",
    LAYOUT_PAWNG => "pawn",
    LAYOUT_PAWNH => "pawn"
  ),

  "empty" => array(
    LAYOUT_ROOKA => "empty",
    LAYOUT_KNIGHTA => "empty",
    LAYOUT_BISHOPA => "empty",
    LAYOUT_QUEEN => "empty",
    LAYOUT_KING => "empty",
    LAYOUT_BISHOPB => "empty",
    LAYOUT_KNIGHTB => "empty",
    LAYOUT_ROOKB => "empty",
    LAYOUT_PAWNA => "empty",
    LAYOUT_PAWNB => "empty",
    LAYOUT_PAWNC => "empty",
    LAYOUT_PAWND => "empty",
    LAYOUT_PAWNE => "empty",
    LAYOUT_PAWNF => "empty",
    LAYOUT_PAWNG => "empty",
    LAYOUT_PAWNH => "empty"
  )
);

$this->layout_x = array(
  LAYOUT_ROOKA => 1,
  LAYOUT_KNIGHTA => 2,
  LAYOUT_BISHOPA => 3,
  LAYOUT_QUEEN => 4,
  LAYOUT_KING => 5,
  LAYOUT_BISHOPB => 6,
  LAYOUT_KNIGHTB => 7,
  LAYOUT_ROOKB => 8,
  LAYOUT_PAWNA => 1,
  LAYOUT_PAWNB => 2,
  LAYOUT_PAWNC => 3,
  LAYOUT_PAWND => 4,
  LAYOUT_PAWNE => 5,
  LAYOUT_PAWNF => 6,
  LAYOUT_PAWNG => 7,
  LAYOUT_PAWNH => 8
);

$this->layout_y = array(
  LAYOUT_ROOKA => ["000000" => 8, "ffffff" => 1],
  LAYOUT_KNIGHTA => ["000000" => 8, "ffffff" => 1],
  LAYOUT_BISHOPA => ["000000" => 8, "ffffff" => 1],
  LAYOUT_QUEEN => ["000000" => 8, "ffffff" => 1],
  LAYOUT_KING => ["000000" => 8, "ffffff" => 1],
  LAYOUT_BISHOPB => ["000000" => 8, "ffffff" => 1],
  LAYOUT_KNIGHTB => ["000000" => 8, "ffffff" => 1],
  LAYOUT_ROOKB => ["000000" => 8, "ffffff" => 1],
  LAYOUT_PAWNA => ["000000" => 7, "ffffff" => 2],
  LAYOUT_PAWNB => ["000000" => 7, "ffffff" => 2],
  LAYOUT_PAWNC => ["000000" => 7, "ffffff" => 2],
  LAYOUT_PAWND => ["000000" => 7, "ffffff" => 2],
  LAYOUT_PAWNE => ["000000" => 7, "ffffff" => 2],
  LAYOUT_PAWNF => ["000000" => 7, "ffffff" => 2],
  LAYOUT_PAWNG => ["000000" => 7, "ffffff" => 2],
  LAYOUT_PAWNH => ["000000" => 7, "ffffff" => 2]
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
  "empoweredknight" => clienttranslate("Empowered Knight"),
  "empoweredbishop" => clienttranslate("Empowered Bishop"),
  "empoweredrook" => clienttranslate("Empowered Rook"),
  "elegantqueen" => clienttranslate("Elegant Queen"),
  "ghost" => clienttranslate("Ghost"),
  "wildhorse" => clienttranslate("Wild Horse"),
  "tiger" => clienttranslate("Tiger"),
  "elephant" => clienttranslate("Elephant"),
  "junglequeen" => clienttranslate("Jungle Queen")
);

$this->army_tooltips = array(
  "classic" => clienttranslate("Ordinary chess pieces. The only army that can castle."),
  "nemesis" => clienttranslate("A focused attack on the enemy king."),
  "empowered" => clienttranslate("Stronger knights, bishops, and rooks."),
  "reaper" => clienttranslate("A powerful queen."),
  "twokings" => clienttranslate("Two powerful kings."),
  "animal" => clienttranslate("The wild card.")
);

$this->piece_tooltips = array(
  "pawn" => array(
    "help_string" => clienttranslate("Pawn"),
    "action_string" => clienttranslate("An ordinary chess pawn.")
  ),

  "knight" => array(
    "help_string" => $this->button_labels["knight"],
    "action_string" => clienttranslate("An ordinary chess knight.")
  ),

  "bishop" => array(
    "help_string" => $this->button_labels["bishop"],
    "action_string" => clienttranslate("An ordinary chess bishop.")
  ),

  "rook" => array(
    "help_string" => $this->button_labels["rook"],
    "action_string" => clienttranslate("An ordinary chess rook.")
  ),

  "queen" => array(
    "help_string" => $this->button_labels["queen"],
    "action_string" => clienttranslate("An ordinary chess queen.")
  ),

  "king" => array(
    "help_string" => clienttranslate("King"),
    "action_string" => clienttranslate("An ordinary chess king.")
  ),

  "nemesispawn" => array(
    "help_string" => clienttranslate("Nemesis Pawn"),
    "action_string" => clienttranslate("A pawn which cannot travel 2 squares at once but can make non-capturing moves towards an enemy king.")
  ),

  "nemesis" => array(
    "help_string" => $this->button_labels["nemesis"],
    "action_string" => clienttranslate("A queen which cannot capture or be captured, except by an enemy king.")
  ),

  "empoweredknight" => array(
    "help_string" => $this->button_labels["empoweredknight"],
    "action_string" => clienttranslate("A knight which gains the movement abilities of orthogonally adjacent empowered bishops and rooks.")
  ),

  "empoweredbishop" => array(
    "help_string" => $this->button_labels["empoweredbishop"],
    "action_string" => clienttranslate("A bishop which gains the movement abilities of orthogonally adjacent empowered knights and rooks.")
  ),

  "empoweredrook" => array(
    "help_string" => $this->button_labels["empoweredrook"],
    "action_string" => clienttranslate("A rook which gains the movement abilities of orthogonally adjacent empowered knights and bishops.")
  ),

  "elegantqueen" => array(
    "help_string" => $this->button_labels["elegantqueen"],
    "action_string" => clienttranslate("Moves as a king.")
  ),

  "ghost" => array(
    "help_string" => $this->button_labels["ghost"],
    "action_string" => clienttranslate("Can teleport to any open square. Cannot capture or be captured.")
  ),

  "reaper" => array(
    "help_string" => $this->button_labels["reaper"],
    "action_string" => clienttranslate("Can teleport anywhere but the enemy's backline. Cannot capture kings.")
  ),

  "warriorking" => array(
    "help_string" => clienttranslate("Warrior King"),
    "action_string" => clienttranslate("A king which can move to its own square to whirlwind and capture all adjacent pieces. May take an action after the normal turn.")
  ),

  "wildhorse" => array(
    "help_string" => $this->button_labels["wildhorse"],
    "action_string" => clienttranslate("A knight which can capture friendly pieces.")
  ),

  "tiger" => array(
    "help_string" => $this->button_labels["tiger"],
    "action_string" => clienttranslate("A bishop with a range of 2. When it captures, it moves back to the square it attacked from.")
  ),

  "elephant" => array(
    "help_string" => $this->button_labels["elephant"],
    "action_string" => clienttranslate("A rook with a range of 3. Can capture friendly pieces. If it captures, it must move its full range and capture everything in its path. Cannot be captured from more than 2 squares away.")
  ),

  "junglequeen" => array(
    "help_string" => $this->button_labels["junglequeen"],
    "action_string" => clienttranslate("Can move as a rook and a knight.")
  ),

  "empty" => array(
    "help_string" => clienttranslate("Your opponent is selecting an army."),
    "action_string" => ""
  )
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
  CHECKMATE => clienttranslate("checkmate"),
  STALEMATE => clienttranslate("stalemate"),
  MIDLINE_INVASION => clienttranslate("midline invasion"),
  THREEFOLD_REPETITION => clienttranslate("threefold repetition"),
  FIFTY_MOVE_RULE => clienttranslate("the 50 move rule"),
  AGREED_TO_DRAW => clienttranslate("agreement"),
  CONCESSION => clienttranslate("concession")
);

/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/
