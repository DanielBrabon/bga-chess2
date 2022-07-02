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
  * chesssequel.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class ChessSequel extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );        
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "chesssequel";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = array( "ffffff", "000000" );
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
       

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_color color, player_score score, player_stones stones, 
        player_king_move_available king_move_available, player_army army FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // Get information needed to show board state
        $result['board_state'] = $this->getBoard();

        // Get information about all pieces
        $result['pieces'] = $this->getAllPieceData();
  
        // Gathering variables from material.inc.php
        $result['all_army_names'] = $this->all_army_names;
        $result['all_armies_starting_layout'] = $this->all_armies_starting_layout;

        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        // Will need to involve full current board state and piece state
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getBoard()
    {
        //return self::getDoubleKeyCollectionFromDB( "SELECT board_x x, board_y y, board_player player
        //                                               FROM board", true );
        
        // Returns the full board state (the full contents of the board table)
        $sql = "SELECT board_file, board_rank, defending_piece, capturing_piece FROM board";
        return self::getDoubleKeyCollectionFromDB( $sql );
    }

    function getAllPieceData()
    {
        $sql = "SELECT piece_id, piece_color, piece_type, board_file, board_rank, moves_made, if_captured, 
        if_attacking, if_en_passant_vulnerable, if_performing_en_passant, if_performing_castle FROM pieces";
        return self::getCollectionFromDB( $sql );
    }

    function getLocationOfPiece( $piece_id )
    {
        $piece_data = $this->getAllPieceData()[$piece_id];
        return array( $piece_data['board_file'], $piece_data['board_rank'] );
    }

    function getPlayerArmyId( $player_id )
    {

    }

    function generateAllMovesForPlayer( $player_id, $all_piece_data, $board_state )
    {
        $all_moves_for_player = array();
        $corresponding_captures_for_player = array();

        $player_color = $this->getPlayerColorById( $player_id );

        $enemy_player_color = "000000";
        if ( $player_color === "000000" )
        {
            $enemy_player_color = "ffffff";
        }

        $all_enemy_attacked_squares = $this->getAllAttackedSquares( $enemy_player_color, $all_piece_data, $board_state );

        /*self::notifyPlayer( $active_player_id, "highlightAttackedSquares", "", array( 
            'attacked_squares' => $all_enemy_attacked_squares[0], 
            'semi_attacked_squares' => $all_enemy_attacked_squares[1] )
        );*/

        foreach ( $all_piece_data as $piece_id => $piece_data )
        {
            if ( $piece_data['piece_color'] === $player_color && $piece_data['if_captured'] === "0" )
            {
                $possible_moves_and_corresponding_captures = $this->generateMoves( $piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares );
                $possible_moves = $possible_moves_and_corresponding_captures['possible_moves'];
                $corresponding_captures = $possible_moves_and_corresponding_captures['corresponding_captures'];

                $all_moves_for_player[$piece_id] = $possible_moves;
                $corresponding_captures_for_player[$piece_id] = $corresponding_captures;
            }
        }

        return array( "all_moves" => $all_moves_for_player, "all_corresponding_captures" => $corresponding_captures_for_player );
    }

    // Returns an array of the squares of all current possible moves for this piece (the squares the player can click on to make the move)
    function generateMoves( $piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares )
    {
        $possible_moves = array();

        switch ( $all_piece_data[$piece_id]['piece_type'] )
        {
            case "knight":
                $possible_moves = $this->getAttackingSquares( $piece_id, $all_piece_data, $board_state )['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares( $piece_id, $all_piece_data, $board_state, $possible_moves );
                break;

            case "bishop":
                $possible_moves = $this->getAttackingSquares( $piece_id, $all_piece_data, $board_state )['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares( $piece_id, $all_piece_data, $board_state, $possible_moves );
                break;
            
            case "rook":
                $possible_moves = $this->getAttackingSquares( $piece_id, $all_piece_data, $board_state )['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares( $piece_id, $all_piece_data, $board_state, $possible_moves );
                break;
            
            case "queen":
                $possible_moves = $this->getAttackingSquares( $piece_id, $all_piece_data, $board_state )['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares( $piece_id, $all_piece_data, $board_state, $possible_moves );
                break;

            case "pawn":
                $possible_moves = $this->getAttackingSquares( $piece_id, $all_piece_data, $board_state )['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares( $piece_id, $all_piece_data, $board_state, $possible_moves );
                $possible_moves = $this->removeUnavailablePawnAttacks( $piece_id, $all_piece_data, $board_state, $possible_moves );
                $available_pawn_pushes = $this->getNonCapturingMoveSquares( $piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares );
                $possible_moves = array_merge( $possible_moves, $available_pawn_pushes );
                break;

            case "king":
                $possible_moves = $this->getAttackingSquares( $piece_id, $all_piece_data, $board_state )['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares( $piece_id, $all_piece_data, $board_state, $possible_moves );
                $available_castles = $this->getNonCapturingMoveSquares( $piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares );
                $possible_moves = array_merge( $possible_moves, $available_castles );
                break;
        }

        $possible_moves = $this->removeIllegalCaptures( $piece_id, $all_piece_data, $board_state, $possible_moves );
        $corresponding_captures = $this->getCorrespondingCaptures( $piece_id, $all_piece_data, $board_state, $possible_moves );

        $possible_moves_and_corresponding_captures = $this->removeSelfChecks( $piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares, $possible_moves, $corresponding_captures );

        //$this->printWithJavascript( $possible_moves_and_corresponding_captures );
        return $possible_moves_and_corresponding_captures;
    }

    function getAttackingSquares( $piece_id, $all_piece_data, $board_state )
    {
        $attacking_squares = array();
        $semi_attacking_squares = array();

        switch ( $all_piece_data[$piece_id]['piece_type'] )
        {
            case "knight":
                $attack_steps = array( array(2, 1), array(1, 2), array(2, -1), array(1, -2), array(-2, 1), array(-1, 2), array(-2, -1), array(-1, -2) );
                $attacks = $this->getSeenSquares( $piece_id, $all_piece_data, $board_state, $attack_steps, 1);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];

                break;

            case "bishop":
                $attack_steps = array( array(1, 1), array(-1, 1), array(-1, -1), array(1, -1) );
                $attacks = $this->getSeenSquares( $piece_id, $all_piece_data, $board_state, $attack_steps, 7);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];

                break;
            
            case "rook":
                $attack_steps = array( array(1, 0), array(-1, 0), array(0, 1), array(0, -1) );
                $attacks = $this->getSeenSquares( $piece_id, $all_piece_data, $board_state, $attack_steps, 7);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];

                break;
            
            case "queen":
                $attack_steps = array( array(1, 0), array(1, 1), array(0, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -1), array(1, -1) );
                $attacks = $this->getSeenSquares( $piece_id, $all_piece_data, $board_state, $attack_steps, 7);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];

                break;

            case "pawn":
                $forward_direction = 1;
                if ( $all_piece_data[$piece_id]['piece_color'] === "000000")
                {
                    $forward_direction = -1;
                }
                $attack_steps = array( array(1, $forward_direction), array(-1, $forward_direction) );
                $attacks = $this->getSeenSquares( $piece_id, $all_piece_data, $board_state, $attack_steps, 1);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];

                break;

            case "king":
                $attack_steps = array( array(1, 0), array(1, 1), array(0, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -1), array(1, -1) );
                $attacks = $this->getSeenSquares( $piece_id, $all_piece_data, $board_state, $attack_steps, 1);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];
                break;
        }

        return array( "attacking_squares" => $attacking_squares, "semi_attacking_squares" => $semi_attacking_squares );
    }

    function getNonCapturingMoveSquares( $piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares )
    {
        $move_squares = array();

        switch ( $all_piece_data[$piece_id]['piece_type'] )
        {
            case "pawn":
                foreach ( $this->getAvailablePawnPushes( $piece_id, $all_piece_data, $board_state ) as $pawn_push )
                {
                    $move_squares[] = $pawn_push;
                }
                break;

            case "king":
                foreach ( $this->getAvailableCastleMoves( $piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares ) as $castle_move )
                {
                    $move_squares[] = $castle_move;
                }
                break;
        }

        return $move_squares;
    }

    // Return all squares attacked or semi-attacked by pieces of the color $player_color
    function getAllAttackedSquares( $player_color, $all_piece_data, $board_state )
    {   
        $attacked_squares = array();

        // Creates an 8x8 array of empty arrays
        for ( $i = 1; $i <= 8; $i++ )
        {
            $attacked_squares[$i] = array();

            for ( $j = 1; $j <= 8; $j++ )
            {
                $attacked_squares[$i][$j] = array();
            }
        }

        $semi_attacked_squares = $attacked_squares;

        // For all uncaptured pieces of this color
        foreach ( $all_piece_data as $piece_data )
        {
            if ( $piece_data['if_captured'] === "0" && $piece_data['piece_color'] === $player_color )
            {
                $attacks = $this->getAttackingSquares( $piece_data['piece_id'], $all_piece_data, $board_state );
                $piece_attacked_squares = $attacks['attacking_squares'];
                $pieces_semi_attacked_squares = $attacks['semi_attacking_squares'];
                
                // For each of the attacked squares
                foreach ( $piece_attacked_squares as $piece_attacked_square )
                {
                    $attacked_squares[$piece_attacked_square[0]][$piece_attacked_square[1]][] = $piece_data['piece_id'];
                }
                // For each of the semi-attacked squares
                foreach ( $pieces_semi_attacked_squares as $pieces_semi_attacked_square )
                {
                    $semi_attacked_squares[$pieces_semi_attacked_square[0]][$pieces_semi_attacked_square[1]][] = $piece_data['piece_id'];
                }
            }
        }

        return array( "attacked_squares" => $attacked_squares, "semi_attacked_squares" => $semi_attacked_squares );
    }

    function getAvailableCastleMoves( $king_id, $all_piece_data, $board_state, $all_enemy_attacked_squares )
    {
        $castle_moves = array();

        // If the king already moved it cannot castle
        if ( $all_piece_data[$king_id]['moves_made'] != "0" )
        {
            return $castle_moves;
        }

        // Store the king's location
        $king_file = $all_piece_data[$king_id]['board_file'];
        $king_rank = $all_piece_data[$king_id]['board_rank'];

        // If the king is in check right now it cannot castle
        if ( count( $all_enemy_attacked_squares['attacked_squares'][$king_file][$king_rank] ) != 0 )
        {
            return $castle_moves;
        }

        // Check both directions for possible castle
        foreach ( array(-1, 1) as $direction )
        {
            $square = array( $king_file + $direction, $king_rank );

            // If the next square along in this direction is attacked, the king cannot castle on this side
            if ( count( $all_enemy_attacked_squares['attacked_squares'][$square[0]][$square[1]] ) != 0 )
            {
                continue;
            }

            while ( $square[0] > 0 && $square[0] < 9 )
            {
                // If there is a piece on this square
                if ( $board_state[$square[0]][$square[1]]['defending_piece'] != null )
                {
                    // Get the data for this encountered piece
                    $piece_on_square = $all_piece_data[$board_state[$square[0]][$square[1]]['defending_piece']];

                    // If it's a friendly unmoved rook, we can castle in this direction
                    if ( $piece_on_square['piece_color'] === $all_piece_data[$king_id]['piece_color'] && $piece_on_square['piece_type'] === "rook" && $piece_on_square['moves_made'] === "0" )
                    {
                        $castle_moves[] = $square;
                    }

                    break;
                }

                $square[0] += $direction;
            }
        }

        return $castle_moves;
    }

    function getAvailablePawnPushes( $pawn_id, $all_piece_data, $board_state )
    {
        $pawn_pushes = array();

        $forward_direction = 1;
        if ( $all_piece_data[$pawn_id]['piece_color'] === "000000")
        {
            $forward_direction = -1;
        }

        $pawn_location = array( (int)$all_piece_data[$pawn_id]['board_file'], (int)$all_piece_data[$pawn_id]['board_rank'] );

        // If one square forward is empty
        if ( $board_state[$pawn_location[0]][$pawn_location[1] + $forward_direction]['defending_piece'] === null )
        {
            $pawn_pushes[] = array( $pawn_location[0], $pawn_location[1] + $forward_direction );

            // If one and two squares forward are free and the pawn hasn't moved yet
            if ( $all_piece_data[$pawn_id]['moves_made'] === "0" && $board_state[$pawn_location[0]][$pawn_location[1] + 2 * $forward_direction]['defending_piece'] === null )
            {
                $pawn_pushes[] = array( $pawn_location[0], $pawn_location[1] + 2 * $forward_direction );
            }
        }

        return $pawn_pushes;
    }

    // Returns an array containing all capture squares for each possible move in $possible_moves
    function getCorrespondingCaptures( $piece_id, $all_piece_data, $board_state, $possible_moves )
    {
        $corresponding_captures = array();

        $simple_piece = false;

        switch ( $all_piece_data[$piece_id]['piece_type'] )
        {
            case "knight":
                $simple_piece = true;
                break;

            case "bishop":
                $simple_piece = true;
                break;

            case "rook":
                $simple_piece = true;
                break;

            case "queen":
                $simple_piece = true;
                break;

            case "pawn":
                foreach ( $possible_moves as $possible_move )
                {
                    // If it's an attacking move
                    if ( abs($possible_move[0] - $all_piece_data[$piece_id]['board_file']) === 1 )
                    {
                        // If it's an en passant move
                        if ( $board_state[$possible_move[0]][$possible_move[1]]['defending_piece'] === null )
                        {
                            $corresponding_captures[] = array( array($possible_move[0], (int)$all_piece_data[$piece_id]['board_rank']) );
                        }
                        // If it's a normal attack
                        else
                        {
                            $corresponding_captures[] = array( $possible_move );
                        }
                    }
                    // If it's not an attacking move
                    else
                    {
                        $corresponding_captures[] = array();
                    }
                }
                break;

            case "king":
                foreach ( $possible_moves as $possible_move )
                {
                    // If it's a castle move
                    if ( abs($possible_move[0] - $all_piece_data[$piece_id]['board_file']) === 2 )
                    {
                        $corresponding_captures[] = array();
                    }
                    else
                    {
                        $corresponding_captures[] = array( $possible_move );
                    }
                }
                break;

        }

        if ( $simple_piece )
        {
            foreach ( $possible_moves as $possible_move )
            {
                $corresponding_captures[] = array( $possible_move );
            }
        }

        //$this->printWithJavascript( "corresponding captures for moving piece ".$piece_id );
        //$this->printWithJavascript( $corresponding_captures );

        return $corresponding_captures;
    }

    // Seen square: If that square has a compatible defending piece then the relevant action can be taken
    // Semi-seen square: If that square has a compatible defending piece then the relevant action might be able to be taken if a piece along the path is removed
    function getSeenSquares( $piece_id, $all_piece_data, $board_state, $steps, $range )
    {
        $seen_squares = array();
        $semi_seen_squares = array();

        $piece_file = $all_piece_data[$piece_id]['board_file'];
        $piece_rank = $all_piece_data[$piece_id]['board_rank'];

        foreach ( $steps as $step )
        {
            $square_file = $piece_file;
            $square_rank = $piece_rank;

            $semi_seen = false;

            for ( $i = 1; $i <= $range; $i++ )
            {
                $square_file += $step[0];
                $square_rank += $step[1];

                // If the square is off the board, move to checking the next move step
                if ( $square_file < 1 || $square_file > 8 || $square_rank < 1 || $square_rank > 8 )
                {
                    break;
                }

                if ( $semi_seen )
                {
                    $semi_seen_squares[] = array( $square_file, $square_rank );
                }
                else
                {
                    $seen_squares[] = array( $square_file, $square_rank );
                }

                $piece_on_square = $board_state[$square_file][$square_rank]['defending_piece'];
                if ( !$semi_seen && $piece_on_square != null )
                {
                    $semi_seen = true;
                }
            }
        }

        return array( $seen_squares, $semi_seen_squares );
    }

    function removeFriendlyOccupiedSquares( $piece_id, $all_piece_data, $board_state, $squares_array )
    {
        // Loop through the provided array of squares
        foreach ( $squares_array as $index => $square )
        {
            // If a square has a friendly piece on it, remove this square from the array
            $piece_on_square = $board_state[$square[0]][$square[1]]['defending_piece'];
            if ( $piece_on_square != null && $all_piece_data[$piece_on_square]['piece_color'] === $all_piece_data[$piece_id]['piece_color'] )
            {
                unset( $squares_array[$index] );
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    function removeIllegalCaptures( $piece_id, $all_piece_data, $board_state, $capture_squares )
    {
        foreach ( $capture_squares as $index => $capture_square )
        {
            $piece_on_square = $board_state[$capture_square[0]][$capture_square[1]]['defending_piece'];
            if ( $piece_on_square != null )
            {
                switch ( $all_piece_data[$piece_on_square]['piece_type'] )
                {
                    case "ghost":
                        unset( $capture_squares[$index] );
                        break;

                    case "nemesis":
                        if ( $all_piece_data[$piece_id]['piece_type'] != "king" && $all_piece_data[$piece_id]['piece_type'] != "warriorking" )
                        {
                            unset( $capture_squares[$index] );
                        }
                        break;

                    case "elephant":
                        // TO DO
                        break;
                }
            } 
        }
        $capture_squares = array_values($capture_squares);

        return $capture_squares;
    }

    // Takes an array of squares and removes any which do not correspond to a currently available capture for the pawn with id $piece_id
    function removeUnavailablePawnAttacks( $piece_id, $all_piece_data, $board_state, $possible_attacks )
    {
        foreach ( $possible_attacks as $index => $possible_attack )
        {
            $piece_on_square = $board_state[$possible_attack[0]][$possible_attack[1]]['defending_piece'];

            // If the square is empty and there is no en passant available there, remove this attack from the array
            if ( $piece_on_square === null )
            {
                $piece_adjacent = $board_state[$possible_attack[0]][$all_piece_data[$piece_id]['board_rank']]['defending_piece'];
                
                if ( $piece_adjacent === null || $all_piece_data[$piece_adjacent]['piece_color'] === $all_piece_data[$piece_id]['piece_color'] || $all_piece_data[$piece_adjacent]['if_en_passant_vulnerable'] === "0" )
                {
                    unset( $possible_attacks[$index] );
                }
            }
        }
        $possible_attacks = array_values($possible_attacks);

        return $possible_attacks;
    }

    // Takes an array of possible moves and returns the same array but with any options removed that would leave the player's own king in check 
    function removeSelfChecks( $moving_piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares, $possible_moves, $corresponding_captures )
    {
        $move_piece_location = array( (int)$all_piece_data[$moving_piece_id]['board_file'], (int)$all_piece_data[$moving_piece_id]['board_rank'] );
        $move_piece_type = $all_piece_data[$moving_piece_id]['piece_type'];

        $king_locations = array();
        $king_ids = array();

        // Loop through pieces to find friendly kings
        foreach ( $all_piece_data as $piece_id => $piece_data )
        {
            if ( $piece_data['piece_color'] === $all_piece_data[$moving_piece_id]['piece_color'] && ( $piece_data['piece_type'] === "king" || $piece_data['piece_type'] === "warriorking" ) )
            {
                $king_locations[] = array( (int)$all_piece_data[$piece_id]['board_file'], (int)$all_piece_data[$piece_id]['board_rank'] );
                $king_ids[] = $piece_id;
            }
        }

        foreach ( $king_locations as $king_index => $king_location )
        {
            $enemies_attacking_king_location = $all_enemy_attacked_squares['attacked_squares'][$king_location[0]][$king_location[1]];
            $enemies_semi_attacking_king_location = $all_enemy_attacked_squares['semi_attacked_squares'][$king_location[0]][$king_location[1]];

            foreach ( $possible_moves as $move_index => $possible_move )
            {
                //$this->printWithJavascript("Moving piece: ".$moving_piece_id.", Possible move: ".$possible_move[0].", ".$possible_move[1]);

                // If the moving piece is a king or warriorking, it can't move onto an attacked square
                if ( $move_piece_type === "king" || $move_piece_type === "warriorking" )
                {
                    if ( count( $all_enemy_attacked_squares['attacked_squares'][$possible_move[0]][$possible_move[1]] ) != 0 )
                    {
                        unset( $possible_moves[$move_index] );
                        unset( $corresponding_captures[$move_index] );
                        continue;
                    } 
                }
                // If a king is neither attacked nor semi attacked, then another friendly piece moving could not put it into check
                elseif ( count( $enemies_attacking_king_location ) === 0 && count( $enemies_semi_attacking_king_location ) === 0 )
                {
                    continue;
                }

                // Any enemy pieces which are attacking the move piece and semi-attacking this king MIGHT be left attacking this king if this move were made
                $enemies_attacking_move_piece_location = $all_enemy_attacked_squares['attacked_squares'][$move_piece_location[0]][$move_piece_location[1]];
                $attackers_to_recheck = array_intersect( $enemies_attacking_move_piece_location, $enemies_semi_attacking_king_location );     
                
                if ( $move_piece_type === "pawn" || $move_piece_type === "warriorking" || $move_piece_type === "tiger" || $move_piece_type === "elephant" )
                {
                    foreach ( $corresponding_captures[$move_index] as $capture_location )
                    {
                        // If any pieces would be captured were this move made (by a piece which might not land where it captures)
                        if ( $board_state[$capture_location[0]][$capture_location[1]]['defending_piece'] != null )
                        {                        
                            // Any enemy pieces which are attacking the capture location and semi-attacking this king MIGHT be attacking the king if this move were made
                            $enemies_attacking_capture_location = $all_enemy_attacked_squares['attacked_squares'][$capture_location[0]][$capture_location[1]];
                            $cap_attackers_to_recheck = array_intersect( $enemies_attacking_capture_location, $enemies_semi_attacking_king_location );
                            $attackers_to_recheck = array_unique( array_merge( $attackers_to_recheck, $cap_attackers_to_recheck ) );
                        }
                    }
                }

                if ( count( $attackers_to_recheck ) === 0 && count( $enemies_attacking_king_location ) === 0 )
                {
                    continue;
                }

                $simulated_move = $this->simulatePossibleMove( $moving_piece_id, $all_piece_data, $board_state, $possible_move, $corresponding_captures[$move_index] );
                $all_piece_data_sim = $simulated_move['all_piece_data_sim'];
                $board_state_sim = $simulated_move['board_state_sim'];
                $king_location_sim = array( (int)$all_piece_data_sim[$king_ids[$king_index]]['board_file'], (int)$all_piece_data_sim[$king_ids[$king_index]]['board_rank'] );

                if ( count( $enemies_attacking_king_location ) != 0 && $this->arePiecesAttackingSquare( $all_piece_data_sim, $board_state_sim, $enemies_attacking_king_location, $king_location_sim ) )
                {
                    unset( $possible_moves[$move_index] );
                    unset( $corresponding_captures[$move_index] );
                    continue;
                }

                if ( count( $attackers_to_recheck ) != 0 && $this->arePiecesAttackingSquare( $all_piece_data_sim, $board_state_sim, $attackers_to_recheck, $king_location_sim ) )
                {
                    unset( $possible_moves[$move_index] );
                    unset( $corresponding_captures[$move_index] );
                    continue;
                } 
            }
        }
        

        $possible_moves = array_values($possible_moves);
        $corresponding_captures = array_values($corresponding_captures);
        return array( "possible_moves" => $possible_moves, "corresponding_captures" => $corresponding_captures );
    }

    function simulatePossibleMove( $piece_id, $all_piece_data, $board_state, $possible_move, $capture_squares_for_this_move )
    {
        // Starting location of the moving piece
        $piece_starting_file = $all_piece_data[$piece_id]['board_file'];
        $piece_starting_rank = $all_piece_data[$piece_id]['board_rank'];
        
        // Remove the moving piece from its starting location
        $board_state[$piece_starting_file][$piece_starting_rank]['defending_piece'] = null;

        // Set as captured any pieces which would be captured in this move
        foreach ( $capture_squares_for_this_move as $capture_square )
        {
            $piece_on_cap_square = $board_state[$capture_square[0]][$capture_square[1]]['defending_piece']; 

            if ( $piece_on_cap_square != null )
            {
                $board_state[$capture_square[0]][$capture_square[1]]['defending_piece'] = null;
                $all_piece_data[$piece_on_cap_square]['if_captured'] = "1";

                if ( $all_piece_data[$piece_id]['piece_type'] === "tiger" )
                {
                    $possible_move[0] = $piece_starting_file;
                    $possible_move[1] = $piece_starting_rank;
                }
            }
        }
        
        // Set the updated location of the moving piece
        $board_state[$possible_move[0]][$possible_move[1]]['defending_piece'] = $piece_id;
        $all_piece_data[$piece_id]['board_file'] = $possible_move[0];
        $all_piece_data[$piece_id]['board_rank'] = $possible_move[1];

        return array( "all_piece_data_sim" => $all_piece_data, "board_state_sim" => $board_state );
    }

    // Returns true if any of the pieces specified in $piece_ids are attacking $square
    function arePiecesAttackingSquare( $all_piece_data, $board_state, $piece_ids, $square )
    {
        //$this->printWithJavascript($square);

        foreach ( $piece_ids as $piece_id )
        {
            if ( $all_piece_data[$piece_id]['if_captured'] === "0" )
            {
                $attacking_squares = $this->getAttackingSquares( $piece_id, $all_piece_data, $board_state )['attacking_squares'];
                //$this->printWithJavascript("arePiecesAttackingSquare, attacking_squares:");
                //$this->printWithJavascript($attacking_squares);

                foreach ( $attacking_squares as $attacking_square )
                {
                    if ( $attacking_square === $square )
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // Can be called anywhere in the game.php, just calls console.log on the client side with whatever argument you pass in
    function printWithJavascript( $x )
    {
        self::notifyAllPlayers( "printWithJavascript", "", array( 'x' => $x ) );
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in chesssequel.action.php)
    */

    function pickArmy( $army_name )
    {
        // Check this action is allowed according to the game state
        $this->checkAction( 'pickArmy' );

        // Check it's a valid army name according to the array in material.inc.php
        if (in_array( $army_name, $this->all_army_names ))
        {
            // Get the id of the CURRENT player (there are multiple active players in armySelect)
            // In the BGA framework, the CURRENT player is the player who played the current player action (player who made the AJAX request)
            $player_id = $this->getCurrentPlayerId();

            // Updates the current player's army in the database
            $sql = "UPDATE player 
            SET player_army = '$army_name'
            WHERE player_id = $player_id";
            
            self::DbQuery( $sql );

            $player_color = $this->getCurrentPlayerColor();

            // Send notification to that player with information about choosing that army
            self::notifyAllPlayers( "pickArmy", "", array( 'army_name' => $army_name, 'player_color' => $player_color ) );
        }
        else
            throw new BgaSystemException( "Invalid army selection" );
    }

    function confirmArmy()
    {
        // Check this action is allowed according to the game state
        $this->checkAction( 'confirmArmy' );

        // Get the id of the CURRENT player (there are multiple active players in armySelect)
        // In the BGA framework, the CURRENT player is the player who played the current player action (player who made the AJAX request)
        $player_id = $this->getCurrentPlayerId();

        // Send notification
        self::notifyAllPlayers( "confirmArmy", clienttranslate( '${player_name} has confirmed their army choice.' ), 
        array( 'player_id' => $player_id, 'player_name' => $this->getCurrentPlayerName() ) );

        // Deactivate player. If none left, transition to 'boardSetup' state
        $this->gamestate->setPlayerNonMultiactive($player_id, 'boardSetup');
    }

    function movePiece( $target_file, $target_rank, $moving_piece_id )
    {
        // Check this action is allowed according to the game state
        $this->checkAction( 'movePiece' );

        // Get some information
        $player_id = $this->getActivePlayerId();
        $player_color = $this->getPlayerColorById( $player_id );
        $enemy_player_color = "000000";
        if ( $player_color === "000000" )
        {
            $enemy_player_color = "ffffff";
        }

        $moving_piece_starting_location = $this->getLocationOfPiece($moving_piece_id);
        $all_piece_data = $this->getAllPieceData();
        $board_state = $this->getBoard();
        $all_enemy_attacked_squares = $this->getAllAttackedSquares( $enemy_player_color, $all_piece_data, $board_state );

        /*self::notifyPlayer( $active_player_id, "highlightAttackedSquares", "", array( 
            'attacked_squares' => $all_enemy_attacked_squares['attacked_squares'], 
            'semi_attacked_squares' => $all_enemy_attacked_squares['semi_attacked_squares'] )
        );*/

        // Check that the player is trying to move their own piece
        if ( $all_piece_data[$moving_piece_id]['piece_color'] != $player_color )
        {
            return;
        }
        
        $possible_moves_and_corresponding_captures = $this->generateMoves( $moving_piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares );
        $possible_moves = $possible_moves_and_corresponding_captures['possible_moves'];
        $corresponding_captures = $possible_moves_and_corresponding_captures['corresponding_captures'];

        // If this is a valid move according to generateMoves
        if ( in_array( [$target_file, $target_rank], $possible_moves ) )
        {
            $this->printWithJavascript("The target location IS in the array of valid moves");
            
            $if_attacking = "0";
            
            // If the moving piece is a pawn
            if ( $all_piece_data[$moving_piece_id]['piece_type'] === "pawn" )
            {
                // If the moving pawn is making its inital double move, set its if_en_passant_vulnerable value to 2
                if ( abs($moving_piece_starting_location[1] - $target_rank) === 2 )
                {
                    $sql = "UPDATE pieces SET if_en_passant_vulnerable=2 WHERE piece_id='$moving_piece_id'";
                    self::DbQuery( $sql );
                }

                // If the moving pawn is instead performing an en passant capture
                elseif ( abs($moving_piece_starting_location[0] - $target_file) === 1 && $board_state[$target_file][$target_rank]['defending_piece'] === null )
                {
                    $this->printWithJavascript("performing en passant");
                    // Have it attack the square of the piece being captured but with the if_performing_en_passant value telling whereNext how to resolve this attack correctly
                    $target_rank = $moving_piece_starting_location[1];
                    $sql = "UPDATE pieces SET if_performing_en_passant=1 WHERE piece_id='$moving_piece_id'";
                    self::DbQuery( $sql );
                }
            }

            // If the moving piece is a king
            if ( $all_piece_data[$moving_piece_id]['piece_type'] === "king" )
            {
                // If this king is castling
                if ( abs( $moving_piece_starting_location[0] - $target_file ) === 2 )
                {
                    $sql = "UPDATE pieces SET if_performing_castle=1 WHERE piece_id='$moving_piece_id'";
                    self::DbQuery( $sql );
                }
            }
            
            $sql = "SELECT moves_made FROM pieces WHERE piece_id='$moving_piece_id'";
            $moving_piece_updated_moves_made = self::getUniqueValueFromDB( $sql ) + 1;

            // Update the database
            $sql = "UPDATE pieces SET board_file=$target_file, board_rank=$target_rank, moves_made=$moving_piece_updated_moves_made WHERE piece_id='$moving_piece_id'";
            self::DbQuery( $sql );

            $sql = "UPDATE board SET defending_piece=null WHERE board_file=$moving_piece_starting_location[0] AND board_rank=$moving_piece_starting_location[1]";
            self::DbQuery( $sql );

            // If there is an enemy piece on the square being moved to, the moving piece attacks
            if ( $board_state[$target_file][$target_rank]['defending_piece'] != null )
            {
                $sql = "UPDATE pieces SET if_attacking='1' WHERE piece_id='$moving_piece_id'";
                self::DbQuery( $sql );

                $sql = "UPDATE board SET capturing_piece='$moving_piece_id' WHERE board_file=$target_file AND board_rank=$target_rank";
                self::DbQuery( $sql );
            }
            else
            {
                $sql = "UPDATE board SET defending_piece='$moving_piece_id' WHERE board_file=$target_file AND board_rank=$target_rank";
                self::DbQuery( $sql );
            }

            // Send notifications
            self::notifyAllPlayers( "movePiece", clienttranslate( 'Move made' ), array( 
                "moving_piece_id" => $moving_piece_id, 
                "target_file" => $target_file, 
                "target_rank" => $target_rank,
                "if_attacking" => $if_attacking,
                "moving_piece_starting_location_file" => $moving_piece_starting_location[0],
                "moving_piece_starting_location_rank" => $moving_piece_starting_location[1] ) 
            );

            // Change player state
            $this->gamestate->nextState( 'whereNext' );
        }
        else
        {
            $this->printWithJavascript("The target location is NOT in the array of valid moves");
        }
    }

    // The possible action during the duel state
    function pickBidAmount( $bid_amount )
    {
        // Check this move is allowed according to the game state
        $this->checkAction( 'pickBidAmount' );

        // Get the id of the CURRENT player (there are multiple active players in pickBidAmount)
        $player_id = $this->getCurrentPlayerId();

        // Some logic to process and validate bidding stones

        // Deactivate player. If none left, transition to 'resolveDuel' state
        $this->gamestate->setPlayerNonMultiactive($player_id, 'resolveDuel');
    }

    // A possible action during the playerTurn state
    function promote( $piece_id )
    {
        // Check this move is allowed according to the game state
        $this->checkAction( 'promote' );

        // Get the id of the active player
        $player_id = $this->getActivePlayerId();

        // Get the board state
        $board = $this->getBoard();

        // Get the army id of the active player
        $active_army_id = $this->getPlayerArmyId( $player_id );
    }

    // A possible action during the playerTurn state
    function pass()
    {
        // Check this move is allowed according to the game state
        $this->checkAction( 'pass' );

        // Get the id of the active player
        $player_id = $this->getActivePlayerId();        
    }

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPlayerMove()
    {
        $active_player_id = $this->getActivePlayerId();
        $all_piece_data = $this->getAllPieceData();
        $board_state = $this->getboard();

        $all_legal_moves_and_corresponding_captures = $this->generateAllMovesForPlayer( $active_player_id, $all_piece_data, $board_state );
        $all_legal_moves = $all_legal_moves_and_corresponding_captures['all_moves'];
        $all_corresponding_captures = $all_legal_moves_and_corresponding_captures['all_corresponding_captures'];

        return array( "allLegalMoves" => $all_legal_moves, "allCorrespondingCaptures" => $all_corresponding_captures );
    }

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    // Enter the starting board state into the database  
    function stBoardSetup()
    {
        // Get data on players and board layouts
        $all_datas = $this->getAllDatas();

        $pieces_table_update_information = array();
        $board_table_update_information = array(); // An array to hold information on the pieces, this will be used to update the board table with pieces

        // Adding a row into the board database table for each square on the board
        $sql = "INSERT INTO board (board_file,board_rank) VALUES ";
        $sql_values = array();

        for ( $i = 1; $i <= 8; $i++ )
        {
            for ( $j = 1; $j <= 8; $j++ )
            {
                $sql_values[] = "('$i','$j')";
            }
        }
        
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );

        // Adding a row to the pieces database table for each piece in each player's starting layout
        $sql = "INSERT INTO pieces (piece_id,piece_color,piece_type,board_file,board_rank) VALUES ";
        $sql_values = array();

        // For each player
        foreach( $all_datas['players'] as $player_data )
        {
            // Get the starting layout for this player's chosen army, and their color
            $army_starting_layout = $all_datas['all_armies_starting_layout'][$player_data['army']];
            $player_color = $player_data['color'];

            // Add to $sql_values the information for a database entry for each piece in the starting layout
            foreach( $army_starting_layout as $piece_name => $piece_info)
            {
                // Adjust ranks for black player pieces
                $piece_rank = 0;
                if ( $player_color === "000000")
                {
                    $piece_rank = 9 - $piece_info[1];
                }
                else
                {
                    $piece_rank = $piece_info[1];
                }

                $piece_id = $player_color.'_'.$piece_name;
                $sql_values[] = "('$piece_id','$player_color','$piece_info[2]','$piece_info[0]','$piece_rank')";
                $pieces_table_update_information[] = array( $piece_id, $player_color, $piece_info[2], $piece_info[0], $piece_rank );

                $board_table_update_information[] = array( $piece_info[0], $piece_rank, $piece_id );
            }
        }

        // Send the information to the pieces database table
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );

        // Adding the starting pieces into the board table
        foreach( $board_table_update_information as $value )
        {
            $sql = "UPDATE board SET defending_piece='$value[2]' WHERE board_file='$value[0]' AND board_rank='$value[1]'";
            self::DbQuery( $sql );
        }

        // Notifying players of the changes to gamedatas
        self::notifyAllPlayers( "stBoardSetup", "", array( 
            'pieces_table_update_information' => $pieces_table_update_information,
            'board_table_update_information' => $board_table_update_information) 
        );

        $this->activeNextPlayer();
        $this->gamestate->nextState( 'nextPlayer' );
    }

    function stNextPlayer()
    {
        $this->activeNextPlayer();
        $this->gamestate->nextState( 'playerMove' );
    }

    function stWhereNext()
    {
        // Get the current board and pieces state
        $board_state = $this->getBoard();
        $all_piece_data = $this->getAllPieceData();

        // Loop through all squares on the board to find any attacking pieces
        // These attacks currently just resolve like normal chess (no duels)
        for ( $i = 1; $i <= 8; $i++ )
        {
            for ( $j = 1; $j <= 8; $j++ )
            {
                // If there is an attacking piece on square i, j
                if ( $board_state[$i][$j]['capturing_piece'] != null )
                {
                    $capturing_piece_id = $board_state[$i][$j]['capturing_piece'];
                    $defending_piece_id = $board_state[$i][$j]['defending_piece'];

                    // Update the database to remove the defending piece and set the attacking piece as the new defender on that square
                    $sql = "UPDATE pieces SET if_captured='1' WHERE piece_id='$defending_piece_id'";
                    self::DbQuery( $sql );

                    $sql = "UPDATE pieces SET if_attacking='0' WHERE piece_id='$capturing_piece_id'";
                    self::DbQuery( $sql );

                    $sql = "UPDATE board SET defending_piece='$capturing_piece_id', capturing_piece=null WHERE board_file=$i AND board_rank=$j";
                    self::DbQuery( $sql );

                    $if_en_passant = $all_piece_data[$capturing_piece_id]['if_performing_en_passant'];
                    $piece_color = $all_piece_data[$capturing_piece_id]['piece_color'];
                    
                    // This is an en passant attack so update the database and set $if_en_passant and $piece_color accordingly
                    if ( $if_en_passant === "1" )
                    {
                        $rank_to_use = $j + 1;
                        if ( $piece_color === "000000" )
                        {
                            $rank_to_use -= 2;
                        }
                        
                        $sql = "UPDATE board SET defending_piece='$capturing_piece_id' WHERE board_file=$i AND board_rank=$rank_to_use";
                        self::DbQuery( $sql );

                        $sql = "UPDATE board SET defending_piece=null WHERE board_file=$i AND board_rank=$j";
                        self::DbQuery( $sql );

                        $sql = "UPDATE pieces SET board_rank=$rank_to_use, if_performing_en_passant=0 WHERE piece_id='$capturing_piece_id'";
                        self::DbQuery( $sql );
                    }

                    // Notify all players about the resolved attack
                    self::notifyAllPlayers( "resolveAttack", "", array( 
                        'defending_piece_id' => $defending_piece_id,
                        'capturing_piece_id' => $capturing_piece_id,
                        'board_file' => $i,
                        'board_rank' => $j,
                        'if_en_passant' => $if_en_passant,
                        'piece_color' => $piece_color) 
                    );
                }
            }
        }

        // Loop through all pieces and check for the king being captured, a pawn making its first move, and castling
        foreach( $all_piece_data as $piece_id => $piece_data )
        {
            // Check for win conditions (in the end it will be checkmate/midline invasion but for testing it's just capturing the king) and resolve
            // If a king has been captured, the capturer wins the game
            if ( $piece_data['piece_type'] === "king" && $piece_data['if_captured'] === "1" )
            {
                // Give the winner a point and end the game
                $active_player_id = $this->getActivePlayerId();
                $sql = "UPDATE player SET player_score=1 WHERE player_id=$active_player_id";
                self::DbQuery( $sql );

                $this->gamestate->nextState( 'gameEnd' );
            }

            // This ticks down the if_en_passant_vulnerable value by 1 at the end of each turn so an en passant capture can only be performed for one turn
            if ( $piece_data['if_en_passant_vulnerable'] != "0" )
            {
                $if_en_passant_vulnerable = $piece_data['if_en_passant_vulnerable'] - 1;
                $sql = "UPDATE pieces SET if_en_passant_vulnerable=$if_en_passant_vulnerable WHERE piece_id='$piece_id'";
                self::DbQuery( $sql );
            }

            // If a king is castling this turn
            if ( $piece_data['if_performing_castle'] === "1" )
            {
                // Update the pieces database table to no longer have this piece castling
                $sql = "UPDATE pieces SET if_performing_castle=0 WHERE piece_id='$piece_id'";
                self::DbQuery( $sql );

                // Find the rook
                $rook_starting_file = 1;
                $rook_move_direction = 1;
                if ( $piece_data['board_file'] === "7" )
                {
                    $rook_starting_file = 8;
                    $rook_move_direction = -1;
                }
                $square_with_rook = $board_state[$rook_starting_file][$piece_data['board_rank']];
                $castling_rook_id = $square_with_rook['defending_piece'];

                $rook_destination_file = $piece_data['board_file'] + $rook_move_direction;
                $rook_destination_rank = $piece_data['board_rank'];

                // Update the rook's position in the pieces and board database tables
                $sql = "UPDATE pieces SET board_file=$rook_destination_file, board_rank=$rook_destination_rank WHERE piece_id='$castling_rook_id'";
                self::DbQuery( $sql );

                $sql = "UPDATE board SET defending_piece=null WHERE board_file='$rook_starting_file' AND board_rank='$rook_destination_rank'";
                self::DbQuery( $sql );

                $sql = "UPDATE board SET defending_piece='$castling_rook_id' WHERE board_file='$rook_destination_file' AND board_rank='$rook_destination_rank'";
                self::DbQuery( $sql );

                // Notify all players using the movePiece notification
                self::notifyAllPlayers( "movePiece", "", array( 
                    "moving_piece_id" => $castling_rook_id, 
                    "target_file" => $rook_destination_file, 
                    "target_rank" => $rook_destination_rank,
                    "if_attacking" => "0",
                    "moving_piece_starting_location_file" => $rook_starting_file,
                    "moving_piece_starting_location_rank" => $rook_destination_rank ) 
                );
            }
        }

        // Check for threefold repetition, 50 turn rule(?), out of resources and resolve

        // Check for extra king move for current player and resolve

        // If none of the above prevented it, move to nextPlayer
        $this->gamestate->nextState( 'nextPlayer' );
    }

    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
