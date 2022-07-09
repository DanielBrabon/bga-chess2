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
$this->all_army_names = array( "classic", "nemesis", "empowered", "reaper", "twokings", "animal", "testarmy", "testarmy2", "testenpassant" );

// The starting board layouts for all armies on white side
$this->all_armies_starting_layout = array(
  "classic" => array(
    "pawn_1" => array( 1, 2, "pawn" ), 
    "pawn_2" => array( 2, 2, "pawn" ),
    "pawn_3" => array( 3, 2, "pawn" ),
    "pawn_4" => array( 4, 2, "pawn" ),
    "pawn_5" => array( 5, 2, "pawn" ),
    "pawn_6" => array( 6, 2, "pawn" ),
    "pawn_7" => array( 7, 2, "pawn" ),
    "pawn_8" => array( 8, 2, "pawn" ),
    "rook_1" => array( 1, 1, "rook" ),
    "rook_2" => array( 8, 1, "rook" ),
    "knight_1" => array( 2, 1, "knight" ),
    "knight_2" => array( 7, 1, "knight" ),
    "bishop_1" => array( 3, 1, "bishop" ),
    "bishop_2" => array( 6, 1, "bishop" ),
    "queen" => array( 4, 1, "queen" ),
    "king" => array( 5, 1, "king" )
  ),

  "nemesis" => array(
    "npawn_1" => array( 1, 2, "nemesispawn" ), 
    "npawn_2" => array( 2, 2, "nemesispawn" ),
    "npawn_3" => array( 3, 2, "nemesispawn" ),
    "npawn_4" => array( 4, 2, "nemesispawn" ),
    "npawn_5" => array( 5, 2, "nemesispawn" ),
    "npawn_6" => array( 6, 2, "nemesispawn" ),
    "npawn_7" => array( 7, 2, "nemesispawn" ),
    "npawn_8" => array( 8, 2, "nemesispawn" ),
    "rook_1" => array( 1, 1, "rook" ),
    "rook_2" => array( 8, 1, "rook" ),
    "knight_1" => array( 2, 1, "knight" ),
    "knight_2" => array( 7, 1, "knight" ),
    "bishop_1" => array( 3, 1, "bishop" ),
    "bishop_2" => array( 6, 1, "bishop" ),
    "nemesis" => array( 4, 1, "nemesis" ),
    "king" => array( 5, 1, "king" )
  ),

  "empowered" => array(
    "pawn_1" => array( 1, 2, "pawn" ), 
    "pawn_2" => array( 2, 2, "pawn" ),
    "pawn_3" => array( 3, 2, "pawn" ),
    "pawn_4" => array( 4, 2, "pawn" ),
    "pawn_5" => array( 5, 2, "pawn" ),
    "pawn_6" => array( 6, 2, "pawn" ),
    "pawn_7" => array( 7, 2, "pawn" ),
    "pawn_8" => array( 8, 2, "pawn" ),
    "erook_1" => array( 1, 1, "empoweredrook" ),
    "erook_2" => array( 8, 1, "empoweredrook" ),
    "eknight_1" => array( 2, 1, "empoweredknight" ),
    "eknight_2" => array( 7, 1, "empoweredknight" ),
    "ebishop_1" => array( 3, 1, "empoweredbishop" ),
    "ebishop_2" => array( 6, 1, "empoweredbishop" ),
    "equeen" => array( 4, 1, "empoweredqueen" ),
    "king" => array( 5, 1, "king" )
  ), 

  "reaper" => array(
    "pawn_1" => array( 1, 2, "pawn" ), 
    "pawn_2" => array( 2, 2, "pawn" ),
    "pawn_3" => array( 3, 2, "pawn" ),
    "pawn_4" => array( 4, 2, "pawn" ),
    "pawn_5" => array( 5, 2, "pawn" ),
    "pawn_6" => array( 6, 2, "pawn" ),
    "pawn_7" => array( 7, 2, "pawn" ),
    "pawn_8" => array( 8, 2, "pawn" ),
    "ghost_1" => array( 1, 1, "ghost" ),
    "ghost_2" => array( 8, 1, "ghost" ),
    "knight_1" => array( 2, 1, "knight" ),
    "knight_2" => array( 7, 1, "knight" ),
    "bishop_1" => array( 3, 1, "bishop" ),
    "bishop_2" => array( 6, 1, "bishop" ),
    "reaper" => array( 4, 1, "reaper" ),
    "king" => array( 5, 1, "king" )
  ), 

  "twokings" => array(
    "pawn_1" => array( 1, 2, "pawn" ), 
    "pawn_2" => array( 2, 2, "pawn" ),
    "pawn_3" => array( 3, 2, "pawn" ),
    "pawn_4" => array( 4, 2, "pawn" ),
    "pawn_5" => array( 5, 2, "pawn" ),
    "pawn_6" => array( 6, 2, "pawn" ),
    "pawn_7" => array( 7, 2, "pawn" ),
    "pawn_8" => array( 8, 2, "pawn" ),
    "rook_1" => array( 1, 1, "rook" ),
    "rook_2" => array( 8, 1, "rook" ),
    "knight_1" => array( 2, 1, "knight" ),
    "knight_2" => array( 7, 1, "knight" ),
    "bishop_1" => array( 3, 1, "bishop" ),
    "bishop_2" => array( 6, 1, "bishop" ),
    "wking_1" => array( 4, 1, "warriorking" ),
    "wking_2" => array( 5, 1, "warriorking" )
  ), 

  "animal" => array(
    "pawn_1" => array( 1, 2, "pawn" ), 
    "pawn_2" => array( 2, 2, "pawn" ),
    "pawn_3" => array( 3, 2, "pawn" ),
    "pawn_4" => array( 4, 2, "pawn" ),
    "pawn_5" => array( 5, 2, "pawn" ),
    "pawn_6" => array( 6, 2, "pawn" ),
    "pawn_7" => array( 7, 2, "pawn" ),
    "pawn_8" => array( 8, 2, "pawn" ),
    "elephant_1" => array( 1, 1, "elephant" ),
    "elephant_2" => array( 8, 1, "elephant" ),
    "whorse_1" => array( 2, 1, "wildhorse" ),
    "whorse_2" => array( 7, 1, "wildhorse" ),
    "tiger_1" => array( 3, 1, "tiger" ),
    "tiger_2" => array( 6, 1, "tiger" ),
    "jqueen" => array( 4, 1, "junglequeen" ),
    "king" => array( 5, 1, "king" )
  ),

  "testarmy" => array(
    "pawn" => array( 1, 2, "pawn" ),
    "knight" => array( 3, 4, "knight" ),
    "bishop" => array( 4, 3, "bishop" ),
    "rook" => array( 5, 3, "rook" ),
    "king" => array( 5, 2, "king" ),
    "queen_1" => array( 6, 4, "queen" ),
    "queen_2" => array( 6, 1, "queen" ),
    "queen_3" => array( 8, 1, "queen" )
  ),

  "testarmy2" => array(
    "pawn_1" => array( 1, 2, "pawn" ), 
    "pawn_2" => array( 2, 2, "pawn" ),
    "pawn_3" => array( 3, 2, "pawn" ),
    "pawn_4" => array( 4, 2, "pawn" ),
    "pawn_5" => array( 5, 2, "pawn" ),
    "pawn_6" => array( 6, 2, "pawn" ),
    "pawn_7" => array( 7, 2, "pawn" ),
    "pawn_8" => array( 8, 2, "pawn" ),
    "rook_1" => array( 1, 1, "rook" ),
    "rook_2" => array( 8, 1, "rook" ),
    "knight_1" => array( 2, 1, "knight" ),
    "knight_2" => array( 7, 1, "knight" ),
    "bishop_1" => array( 3, 1, "bishop" ),
    "bishop_2" => array( 6, 1, "bishop" ),
    "queen" => array( 4, 1, "queen" ),
    "king" => array( 5, 1, "king" )
  ),

  "testenpassant" => array(
    "pawn_1" => array( 5, 5, "pawn" ),
    "pawn_2" => array( 6, 2, "pawn" ),
    "pawn_3" => array( 4, 2, "pawn" ),
    "king" => array( 2, 3, "king" ),
    "rook" => array( 7, 3, "rook" ),
  )
);

$this->all_pieces_possible_moves = array(
  "rook" => array( 7, array(1, 0), array(-1, 0), array(0, 1), array(0, -1) ),

  "knight" => array( 1, array(2, 1), array(1, 2), array(2, -1), array(1, -2), array(-2, 1), array(-1, 2), array(-2, -1), array(-1, -2) ),
  
  "bishop" => array( 7, array(1, 1), array(-1, 1), array(1, -1), array(-1, -1) ),

  "queen" => array( 7, array(1, 0), array(1, 1), array(0, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -1), array(1, -1) ),

  "king" => array( 1, array(1, 0), array(1, 1), array(0, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -1), array(1, -1), array(2, 0), array(-2, 0) ),

  "pawn" => array( 1, array(0, 1), array(0, 2), array(1, 1), array(-1, 1), array(0, -1), array(0, -2), array(1, -1), array(-1, -1) )
);

/*
  "pawn" => array(
    "target_square_empty" => array( array(0, 1) => 1 ),
    "first_move_+_target_square_empty" => array( array(0, 1) => 2 ),
    "target_square_enemy" => array( 
      array(1, 1) => 1, 
      array(-1, 1) => 1 
    ),
    "en_passant" => array( 
      array(1, 1) => 1, 
      array(-1, 1) => 1 
    )
  ),

  "nemesispawn" => array(
    "target_square_empty" => array( array(0, 1) => 1 ),
    "target_square_empty_and_closer_to_enemy_king" => array( 
      array(1, 0) => 1, 
      array(1, 1) => 1, 
      array(0, 1) => 1, 
      array(-1, 1) => 1, 
      array(-1, 0) => 1, 
      array(-1, -1) => 1, 
      array(0, -1) => 1, 
      array(1, -1) => 1
    ),
    "target_square_enemy" => array( 
      array(1, 1) => 1, 
      array(-1, 1) => 1 
    ),
    "en_passant" => array( 
      array(1, 1) => 1, 
      array(-1, 1) => 1 
    )
  ),

  "nemesis" => array(
    "target_square_empty" => array( 
      array(1, 0) => 7, 
      array(1, 1) => 7, 
      array(0, 1) => 7, 
      array(-1, 1) => 7,
      array(-1, 0) => 7, 
      array(-1, -1) => 7, 
      array(0, -1) => 7, 
      array(1, -1) => 7 
    )
  ),

  "empoweredrook" => array(
    "" => array( 
      array(1, 0) => 7, 
      array(-1, 0) => 7, 
      array(0, 1) => 7, 
      array(0, -1) => 7 
    ),
    "orthogonal_adjacent_square_friendly_empowered_knight" => array( 
      array(2, 1) => 1, 
      array(1, 2) => 1, 
      array(2, -1) => 1, 
      array(1, -2) => 1, 
      array(-2, 1) => 1, 
      array(-1, 2) => 1, 
      array(-2, -1) => 1, 
      array(-1, -2) => 1 
    ),
    "orthogonal_adjacent_square_friendly_empowered_bishop" => array( 
      array(1, 1) => 7, 
      array(-1, 1) => 7, 
      array(1, -1) => 7, 
      array(-1, -1) => 7, 
    )
  ),

  "empoweredknight" => array(
    "" => array( 
      array(2, 1) => 1, 
      array(1, 2) => 1, 
      array(2, -1) => 1, 
      array(1, -2) => 1, 
      array(-2, 1) => 1, 
      array(-1, 2) => 1, 
      array(-2, -1) => 1, 
      array(-1, -2) => 1 
    ),
    "orthogonal_adjacent_square_friendly_empowered_bishop" => array( 
      array(1, 1) => 7, 
      array(-1, 1) => 7, 
      array(1, -1) => 7, 
      array(-1, -1) => 7, 
    ),
    "orthogonal_adjacent_square_friendly_empowered_rook" => array( 
      array(1, 0) => 7, 
      array(-1, 0) => 7, 
      array(0, 1) => 7, 
      array(0, -1) => 7 
    )
  ),

  "empoweredbishop" => array(
    "" => array( 
      array(1, 1) => 7, 
      array(-1, 1) => 7, 
      array(1, -1) => 7, 
      array(-1, -1) => 7, 
    ),
    "orthogonal_adjacent_square_friendly_empowered_knight" => array( 
      array(2, 1) => 1, 
      array(1, 2) => 1, 
      array(2, -1) => 1, 
      array(1, -2) => 1, 
      array(-2, 1) => 1, 
      array(-1, 2) => 1, 
      array(-2, -1) => 1, 
      array(-1, -2) => 1 
    ),
    "orthogonal_adjacent_square_friendly_empowered_rook" => array( 
      array(1, 0) => 7, 
      array(-1, 0) => 7, 
      array(0, 1) => 7, 
      array(0, -1) => 7 
    )
  ),

  "empoweredqueen" => array(
    "" => array( 
      array(1, 0) => 1, 
      array(1, 1) => 1, 
      array(0, 1) => 1, 
      array(-1, 1) => 1, 
      array(-1, 0) => 1, 
      array(-1, -1) => 1, 
      array(0, -1) => 1, 
      array(1, -1) => 1
    )
  ),

  "ghost" => array(
    "target_square_empty" => "all_squares"
  ),

  "reaper" => array(
    "target_square_not_enemy_back_rank_not_enemy_king" => "all_squares"
  ),

  "warriorking" => array(
    "" => array( 
      array(1, 0) => 1, 
      array(1, 1) => 1, 
      array(0, 1) => 1, 
      array(-1, 1) => 1, 
      array(-1, 0) => 1, 
      array(-1, -1) => 1, 
      array(0, -1) => 1, 
      array(1, -1) => 1
    )
  ),

  "elephant" => array(
    // TO DO
  ),

  "wildhorse" => array(
    // TO DO
  ),

  "tiger" => array(
    // TO DO
  ),

  "junglequeen" => array(
    // TO DO
  )
  */

/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/




