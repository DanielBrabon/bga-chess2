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

$this->army_options = [ARMY_CLASSIC, ARMY_NEMESIS, ARMY_EMPOWERED, ARMY_REAPER, ARMY_TWOKINGS, ARMY_ANIMAL];

$this->army_material = array(
  ARMY_CLASSIC => array(
    "layout" => array(
      LAYOUT_ROOKA => ROOK,
      LAYOUT_KNIGHTA => KNIGHT,
      LAYOUT_BISHOPA => BISHOP,
      LAYOUT_QUEEN => QUEEN,
      LAYOUT_KING => KING,
      LAYOUT_BISHOPB => BISHOP,
      LAYOUT_KNIGHTB => KNIGHT,
      LAYOUT_ROOKB => ROOK,
      LAYOUT_PAWNA => PAWN,
      LAYOUT_PAWNB => PAWN,
      LAYOUT_PAWNC => PAWN,
      LAYOUT_PAWND => PAWN,
      LAYOUT_PAWNE => PAWN,
      LAYOUT_PAWNF => PAWN,
      LAYOUT_PAWNG => PAWN,
      LAYOUT_PAWNH => PAWN
    ),
    "promote_options" => [KNIGHT, BISHOP, ROOK, QUEEN],
    "label" => clienttranslate("Classic"),
    "tooltip" => clienttranslate("Ordinary chess pieces. The only army that can castle.")
  ),

  ARMY_NEMESIS => array(
    "layout" => array(
      LAYOUT_ROOKA => ROOK,
      LAYOUT_KNIGHTA => KNIGHT,
      LAYOUT_BISHOPA => BISHOP,
      LAYOUT_QUEEN => NEMESIS,
      LAYOUT_KING => KING,
      LAYOUT_BISHOPB => BISHOP,
      LAYOUT_KNIGHTB => KNIGHT,
      LAYOUT_ROOKB => ROOK,
      LAYOUT_PAWNA => NEMESISPAWN,
      LAYOUT_PAWNB => NEMESISPAWN,
      LAYOUT_PAWNC => NEMESISPAWN,
      LAYOUT_PAWND => NEMESISPAWN,
      LAYOUT_PAWNE => NEMESISPAWN,
      LAYOUT_PAWNF => NEMESISPAWN,
      LAYOUT_PAWNG => NEMESISPAWN,
      LAYOUT_PAWNH => NEMESISPAWN
    ),
    "promote_options" => [KNIGHT, BISHOP, ROOK, NEMESIS],
    "label" => clienttranslate("Nemesis"),
    "tooltip" => clienttranslate("A focused attack on the enemy king.")
  ),

  ARMY_EMPOWERED => array(
    "layout" => array(
      LAYOUT_ROOKA => EMPOWEREDROOK,
      LAYOUT_KNIGHTA => EMPOWEREDKNIGHT,
      LAYOUT_BISHOPA => EMPOWEREDBISHOP,
      LAYOUT_QUEEN => ELEGANTQUEEN,
      LAYOUT_KING => KING,
      LAYOUT_BISHOPB => EMPOWEREDBISHOP,
      LAYOUT_KNIGHTB => EMPOWEREDKNIGHT,
      LAYOUT_ROOKB => EMPOWEREDROOK,
      LAYOUT_PAWNA => PAWN,
      LAYOUT_PAWNB => PAWN,
      LAYOUT_PAWNC => PAWN,
      LAYOUT_PAWND => PAWN,
      LAYOUT_PAWNE => PAWN,
      LAYOUT_PAWNF => PAWN,
      LAYOUT_PAWNG => PAWN,
      LAYOUT_PAWNH => PAWN
    ),
    "promote_options" => [EMPOWEREDKNIGHT, EMPOWEREDBISHOP, EMPOWEREDROOK, ELEGANTQUEEN],
    "label" => clienttranslate("Empowered"),
    "tooltip" => clienttranslate("Stronger knights, bishops, and rooks.")
  ),

  ARMY_REAPER => array(
    "layout" => array(
      LAYOUT_ROOKA => GHOST,
      LAYOUT_KNIGHTA => KNIGHT,
      LAYOUT_BISHOPA => BISHOP,
      LAYOUT_QUEEN => REAPER,
      LAYOUT_KING => KING,
      LAYOUT_BISHOPB => BISHOP,
      LAYOUT_KNIGHTB => KNIGHT,
      LAYOUT_ROOKB => GHOST,
      LAYOUT_PAWNA => PAWN,
      LAYOUT_PAWNB => PAWN,
      LAYOUT_PAWNC => PAWN,
      LAYOUT_PAWND => PAWN,
      LAYOUT_PAWNE => PAWN,
      LAYOUT_PAWNF => PAWN,
      LAYOUT_PAWNG => PAWN,
      LAYOUT_PAWNH => PAWN
    ),
    "promote_options" => [KNIGHT, BISHOP, GHOST, REAPER],
    "label" => clienttranslate("Reaper"),
    "tooltip" => clienttranslate("A powerful queen.")
  ),

  ARMY_TWOKINGS => array(
    "layout" => array(
      LAYOUT_ROOKA => ROOK,
      LAYOUT_KNIGHTA => KNIGHT,
      LAYOUT_BISHOPA => BISHOP,
      LAYOUT_QUEEN => WARRIORKING,
      LAYOUT_KING => WARRIORKING,
      LAYOUT_BISHOPB => BISHOP,
      LAYOUT_KNIGHTB => KNIGHT,
      LAYOUT_ROOKB => ROOK,
      LAYOUT_PAWNA => PAWN,
      LAYOUT_PAWNB => PAWN,
      LAYOUT_PAWNC => PAWN,
      LAYOUT_PAWND => PAWN,
      LAYOUT_PAWNE => PAWN,
      LAYOUT_PAWNF => PAWN,
      LAYOUT_PAWNG => PAWN,
      LAYOUT_PAWNH => PAWN
    ),
    "promote_options" => [KNIGHT, BISHOP, ROOK],
    "label" => clienttranslate("Two Kings"),
    "tooltip" => clienttranslate("Two powerful kings.")
  ),

  ARMY_ANIMAL => array(
    "layout" => array(
      LAYOUT_ROOKA => ELEPHANT,
      LAYOUT_KNIGHTA => WILDHORSE,
      LAYOUT_BISHOPA => TIGER,
      LAYOUT_QUEEN => JUNGLEQUEEN,
      LAYOUT_KING => KING,
      LAYOUT_BISHOPB => TIGER,
      LAYOUT_KNIGHTB => WILDHORSE,
      LAYOUT_ROOKB => ELEPHANT,
      LAYOUT_PAWNA => PAWN,
      LAYOUT_PAWNB => PAWN,
      LAYOUT_PAWNC => PAWN,
      LAYOUT_PAWND => PAWN,
      LAYOUT_PAWNE => PAWN,
      LAYOUT_PAWNF => PAWN,
      LAYOUT_PAWNG => PAWN,
      LAYOUT_PAWNH => PAWN
    ),
    "promote_options" => [WILDHORSE, TIGER, ELEPHANT, JUNGLEQUEEN],
    "label" => clienttranslate("Animal"),
    "tooltip" => clienttranslate("The wild card.")
  ),

  ARMY_EMPTY => array(
    "layout" => array(
      LAYOUT_ROOKA => EMPTY_PIECE,
      LAYOUT_KNIGHTA => EMPTY_PIECE,
      LAYOUT_BISHOPA => EMPTY_PIECE,
      LAYOUT_QUEEN => EMPTY_PIECE,
      LAYOUT_KING => EMPTY_PIECE,
      LAYOUT_BISHOPB => EMPTY_PIECE,
      LAYOUT_KNIGHTB => EMPTY_PIECE,
      LAYOUT_ROOKB => EMPTY_PIECE,
      LAYOUT_PAWNA => EMPTY_PIECE,
      LAYOUT_PAWNB => EMPTY_PIECE,
      LAYOUT_PAWNC => EMPTY_PIECE,
      LAYOUT_PAWND => EMPTY_PIECE,
      LAYOUT_PAWNE => EMPTY_PIECE,
      LAYOUT_PAWNF => EMPTY_PIECE,
      LAYOUT_PAWNG => EMPTY_PIECE,
      LAYOUT_PAWNH => EMPTY_PIECE
    )
  )
);

$this->layout_slot_material = array(
  LAYOUT_ROOKA => array("x" => 1, "y" => ["000000" => 8, "ffffff" => 1]),
  LAYOUT_KNIGHTA => array("x" => 2, "y" => ["000000" => 8, "ffffff" => 1]),
  LAYOUT_BISHOPA => array("x" => 3, "y" => ["000000" => 8, "ffffff" => 1]),
  LAYOUT_QUEEN => array("x" => 4, "y" => ["000000" => 8, "ffffff" => 1]),
  LAYOUT_KING => array("x" => 5, "y" => ["000000" => 8, "ffffff" => 1]),
  LAYOUT_BISHOPB => array("x" => 6, "y" => ["000000" => 8, "ffffff" => 1]),
  LAYOUT_KNIGHTB => array("x" => 7, "y" => ["000000" => 8, "ffffff" => 1]),
  LAYOUT_ROOKB => array("x" => 8, "y" => ["000000" => 8, "ffffff" => 1]),
  LAYOUT_PAWNA => array("x" => 1, "y" => ["000000" => 7, "ffffff" => 2]),
  LAYOUT_PAWNB => array("x" => 2, "y" => ["000000" => 7, "ffffff" => 2]),
  LAYOUT_PAWNC => array("x" => 3, "y" => ["000000" => 7, "ffffff" => 2]),
  LAYOUT_PAWND => array("x" => 4, "y" => ["000000" => 7, "ffffff" => 2]),
  LAYOUT_PAWNE => array("x" => 5, "y" => ["000000" => 7, "ffffff" => 2]),
  LAYOUT_PAWNF => array("x" => 6, "y" => ["000000" => 7, "ffffff" => 2]),
  LAYOUT_PAWNG => array("x" => 7, "y" => ["000000" => 7, "ffffff" => 2]),
  LAYOUT_PAWNH => array("x" => 8, "y" => ["000000" => 7, "ffffff" => 2])
);

$this->piece_type_material = array(
  PAWN => array(
    "ui_name" => "pawn",
    "rank" => 0,
    "type_code" => "p",
    "label" => clienttranslate("Pawn"),
    "tooltip" => clienttranslate("An ordinary chess pawn.")
  ),

  KNIGHT => array(
    "ui_name" => "knight",
    "rank" => 1,
    "type_code" => "n",
    "label" => clienttranslate("Knight"),
    "tooltip" => clienttranslate("An ordinary chess knight.")
  ),

  BISHOP => array(
    "ui_name" => "bishop",
    "rank" => 1,
    "type_code" => "b",
    "label" => clienttranslate("Bishop"),
    "tooltip" => clienttranslate("An ordinary chess bishop.")
  ),

  ROOK => array(
    "ui_name" => "rook",
    "rank" => 2,
    "type_code" => "r",
    "label" => clienttranslate("Rook"),
    "tooltip" => clienttranslate("An ordinary chess rook.")
  ),

  QUEEN => array(
    "ui_name" => "queen",
    "rank" => 3,
    "type_code" => "q",
    "label" => clienttranslate("Queen"),
    "tooltip" => clienttranslate("An ordinary chess queen.")
  ),

  KING => array(
    "ui_name" => "king",
    "type_code" => "k",
    "label" => clienttranslate("King"),
    "tooltip" => clienttranslate("An ordinary chess king.")
  ),

  NEMESISPAWN => array(
    "ui_name" => "nemesispawn",
    "rank" => 0,
    "type_code" => "o",
    "label" => clienttranslate("Nemesis Pawn"),
    "tooltip" => clienttranslate("A pawn which cannot travel 2 squares at once but can make non-capturing moves towards an enemy king.")
  ),

  NEMESIS => array(
    "ui_name" => "nemesis",
    "rank" => 3,
    "type_code" => "m",
    "label" => clienttranslate("Nemesis"),
    "tooltip" => clienttranslate("A queen which cannot capture or be captured, except by an enemy king.")
  ),

  EMPOWEREDKNIGHT => array(
    "ui_name" => "empoweredknight",
    "rank" => 1,
    "type_code" => "i",
    "label" => clienttranslate("Empowered Knight"),
    "tooltip" => clienttranslate("A knight which gains the movement abilities of orthogonally adjacent empowered bishops and rooks.")
  ),

  EMPOWEREDBISHOP => array(
    "ui_name" => "empoweredbishop",
    "rank" => 1,
    "type_code" => "s",
    "label" => clienttranslate("Empowered Bishop"),
    "tooltip" => clienttranslate("A bishop which gains the movement abilities of orthogonally adjacent empowered knights and rooks.")
  ),

  EMPOWEREDROOK => array(
    "ui_name" => "empoweredrook",
    "rank" => 2,
    "type_code" => "c",
    "label" => clienttranslate("Empowered Rook"),
    "tooltip" => clienttranslate("A rook which gains the movement abilities of orthogonally adjacent empowered knights and bishops.")
  ),

  ELEGANTQUEEN => array(
    "ui_name" => "elegantqueen",
    "rank" => 3,
    "type_code" => "l",
    "label" => clienttranslate("Elegant Queen"),
    "tooltip" => clienttranslate("Moves as a king.")
  ),

  GHOST => array(
    "ui_name" => "ghost",
    "rank" => 2,
    "type_code" => "g",
    "label" => clienttranslate("Ghost"),
    "tooltip" => clienttranslate("Can teleport to any open square. Cannot capture or be captured.")
  ),

  REAPER => array(
    "ui_name" => "reaper",
    "rank" => 3,
    "type_code" => "a",
    "label" => clienttranslate("Reaper"),
    "tooltip" => clienttranslate("Can teleport anywhere but the enemy's backline. Cannot capture kings.")
  ),

  WARRIORKING => array(
    "ui_name" => "warriorking",
    "type_code" => "w",
    "label" => clienttranslate("Warrior King"),
    "tooltip" => clienttranslate("A king which can move to its own square to whirlwind and capture all adjacent pieces. May take an action after the normal turn.")
  ),

  WILDHORSE => array(
    "ui_name" => "wildhorse",
    "rank" => 1,
    "type_code" => "h",
    "label" => clienttranslate("Wild Horse"),
    "tooltip" => clienttranslate("A knight which can capture friendly pieces.")
  ),

  TIGER => array(
    "ui_name" => "tiger",
    "rank" => 1,
    "type_code" => "t",
    "label" => clienttranslate("Tiger"),
    "tooltip" => clienttranslate("A bishop with a range of 2. When it captures, it moves back to the square it attacked from.")
  ),

  ELEPHANT => array(
    "ui_name" => "elephant",
    "rank" => 2,
    "type_code" => "e",
    "label" => clienttranslate("Elephant"),
    "tooltip" => clienttranslate("A rook with a range of 3. Can capture friendly pieces. If it captures, it must move its full range and capture everything in its path. Cannot be captured from more than 2 squares away.")
  ),

  JUNGLEQUEEN => array(
    "ui_name" => "junglequeen",
    "rank" => 3,
    "type_code" => "j",
    "label" => clienttranslate("Jungle Queen"),
    "tooltip" => clienttranslate("Can move as a rook and a knight.")
  ),

  EMPTY_PIECE => array(
    "ui_name" => "empty",
    "label" => clienttranslate("Your opponent is selecting an army."),
    "tooltip" => ""
  )
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
