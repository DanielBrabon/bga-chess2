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
        player_king_move_available king_move_available, player_piece_clicked piece_clicked, player_army army FROM player ";
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
        $sql = "SELECT board_file, board_rank, defending_piece, attacking_piece FROM board";
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

    function moveGeneration( $piece_id, $piece_type, $all_piece_data, $board_state )
    {
        $potential_move_destinations = array();
        $potential_move_captures = array();

        switch ( $piece_type )
        {
            case "pawn":
                $this->printWithJavascript( "pawn clicked" );
                break;

            case "knight":
                $this->printWithJavascript( "knight clicked" );

                $move_steps = array( array(2, 1), array(1, 2), array(2, -1), array(1, -2), array(-2, 1), array(-1, 2), array(-2, -1), array(-1, -2) );
                $potential_move_destinations = $this->simplePiecePotentialMoves( $piece_id, $all_piece_data, $board_state, $move_steps, 1 );
                $potential_move_captures = $potential_move_destinations;
                break;

            case "bishop":
                $this->printWithJavascript( "bishop clicked" );

                $move_steps = array( array(1, 1), array(-1, 1), array(-1, -1), array(1, -1) );
                $potential_move_destinations = $this->simplePiecePotentialMoves( $piece_id, $all_piece_data, $board_state, $move_steps, 7 );
                $potential_move_captures = $potential_move_destinations;
                break;
            
            case "rook":
                $this->printWithJavascript( "rook clicked" );

                $move_steps = array( array(1, 0), array(-1, 0), array(0, 1), array(0, -1) );
                $potential_move_destinations = $this->simplePiecePotentialMoves( $piece_id, $all_piece_data, $board_state, $move_steps, 7 );
                $potential_move_captures = $potential_move_destinations;
                break;
            
            case "queen":
                $this->printWithJavascript( "queen clicked" );

                $move_steps = array( array(1, 0), array(1, 1), array(0, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -1), array(1, -1) );
                $potential_move_destinations = $this->simplePiecePotentialMoves( $piece_id, $all_piece_data, $board_state, $move_steps, 7 );
                $potential_move_captures = $potential_move_destinations;
                break;
            
            case "king":
                $this->printWithJavascript( "king clicked" );
                break;
        }

        $potential_move_destinations = $this->filterIllegalCaptures( $piece_id, $all_piece_data, $board_state, $potential_moves );
        $potential_move_destinations = $this->filterSelfChecks(  );

        return array( $potential_move_destinations, $potential_move_captures);
    }

    function simplePiecePotentialMoves( $piece_id, $all_piece_data, $board_state, $move_steps, $range )
    {
        $seen_squares = $this->getSeenSquares( $piece_id, $all_piece_data, $board_state, $move_steps, $range );
        $potential_moves = $this->filterFriendlyOccupiedSquares( $piece_id, $all_piece_data, $board_state, $seen_squares );

        return $potential_moves;
    }

    function getSeenSquares( $piece_id, $all_piece_data, $board_state, $move_steps, $range )
    {
        $seen_squares = array();

        $piece_file = $all_piece_data[$piece_id]['board_file'];
        $piece_rank = $all_piece_data[$piece_id]['board_rank'];

        foreach ( $move_steps as $move_step )
        {
            $square_file = $piece_file;
            $square_rank = $piece_rank;

            for ( $i = 1; $i <= $range; $i++ )
            {
                $square_file += $move_step[0];
                $square_rank += $move_step[1];

                // If the square is off the board, move to checking the next move step
                if ( $square_file < 1 || $square_file > 8 || $square_rank < 1 || $square_rank > 8 )
                {
                    break;
                }

                $seen_squares[] = array( $square_file, $square_rank );

                $piece_on_square = $board_state[$square_file][$square_rank]['defending_piece'];
                if ( $piece_on_square != null )
                {
                    break;
                }
            }
        }

        return $seen_squares;
    }

    function filterFriendlyOccupiedSquares( $piece_id, $all_piece_data, $board_state, $squares_array )
    {
        foreach ( $squares_array as $index => $square )
        {
            if ( $all_piece_data[ $board_state[$square[0]][$square[1]]['defending_piece'] ]['piece_color'] === $all_piece_data[$piece_id]['piece_color'] )
            {
                unset( $squares_array[$index] );
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    /*
    might need this if unsetting doesn't work as intended
    function removeArrayElements( $array, $indices_to_remove )
    {
        foreach ( $indices_to_remove as $index_to_remove )
        {
            unset( $array[$index_to_remove] );
        }
        $array = array_values($array);

        return $array;
    }*/

    function filterIllegalCaptures( $piece_id, $all_piece_data, $board_state, $potential_moves )
    {
        foreach ( $potential_moves as $index => $potential_move )
        {
            $piece_on_square = $board_state[$potential_move[0]][$potential_move[1]]['defending_piece'];
            if ( $piece_on_square != null )
            {
                switch ( $all_piece_data[$piece_on_square]['piece_type'] )
                {
                    case "ghost":
                        unset( $potential_moves[$index] );
                        break;

                    case "nemesis":
                        // TO DO
                        break;

                    case "elephant":
                        // TO DO
                        break;
                }
            } 
        }
        $potential_moves = array_values($potential_moves);

        return $potential_moves;
    }

    // Find the 'danger squares': squares to which enemy pieces could move and capture the king
    function getKingDangerSquares( $piece_id, $all_piece_data, $board_state )
    {   
        $danger_squares = array();

        // Creates an 8x8 array of empty arrays
        for ( $i = 1; $i <= 8; $i++ )
        {
            $danger_squares[$i] = array();

            for ( $j = 1; $j <= 8; $i++ )
            {
                $danger_squares[$i][$j] = array();
            }
        }

        // For all uncaptured enemy pieces
        foreach ( $all_piece_data as $piece_data )
        {
            if ( $piece_data['if_captured'] === "0" && $piece_data['piece_color'] != $all_piece_data[$piece_id]['piece_color'] )
            {
                // Generate all the squares on which the king would be vulnerable to capture by this enemy piece in the current game situation
                $enemy_piece_attacked_squares = $this->moveGeneration( $piece_data['piece_id'], $piece_data['piece_type'], $all_piece_data, $board_state );
                
                // For each of these moves
                foreach ( $enemy_piece_attacked_squares as $enemy_piece_attacked_square )
                {
                    // Add that piece's id to that square's array
                    $danger_squares[$enemy_piece_attacked_square[0]][$enemy_piece_attacked_square[1]][] = $piece_data['piece_id'];
                }
            }
        }

        return $danger_squares;
    }

    // Takes an array of generated moves and returns the same array but with any options removed that would leave the player's own king in check 
    function filterSelfChecks( $piece_id, $all_piece_data, $board_state, $generated_moves, $danger_squares )
    {        
        // An array to hold any indices of the $generated_moves array corresponding to illegal self-check moves
        $self_check_move_indices = array();

        // For each generated move, see if this move would put the moving player into check
        foreach ( $generated_moves as $move_index => $generated_move )
        {
            // Make a separate copy of the board state and piece data arrays to modify
            $board_state_copy = $board_state;
            $all_piece_data_copy = $all_piece_data;
            
            // Change these copies to 'simulate' this generated move
            $piece_current_file = $all_piece_data[$piece_id]['board_file'];
            $piece_current_rank = $all_piece_data[$piece_id]['board_rank'];
                        
            $board_state_copy[$piece_current_file][$piece_current_rank]['defending_piece'] = null;
            
            $piece_on_move_destination = $board_state_copy[$generated_move[0]][$generated_move[1]]['defending_piece'];
            $attack_happening = 0;
            if ( $piece_on_move_destination != null )
            {
                $all_piece_data_copy[$piece_on_move_destination]['if_captured'] = "1";
                $attack_happening = 1;
            }
            
            $board_state_copy[$generated_move[0]][$generated_move[1]]['defending_piece'] = $piece_id;
            
            $all_piece_data_copy[$piece_id]['board_file'] = $generated_move[0];
            $all_piece_data_copy[$piece_id]['board_rank'] = $generated_move[1];

            // Find friendly kings
            foreach ( $all_piece_data_copy as $piece_data )
            {
                if ( $piece_data['piece_color'] === $all_piece_data_copy[$piece_id]['piece_color'] && $piece_data['piece_type'] === "king" )
                {
                    // If it's on a danger square in this simulated move, the move is illegal
                    if ( count($danger_squares[ $piece_data['board_file'] ][ $piece_data['board_rank'] ]) != 0 )
                    {
                        $self_check_move_indices[] = $move_index;
                        break;
                    }
                }
            }

            // If the moved piece was on a danger square before the simulated move
            if ( count($danger_squares[ $all_piece_data[$piece_id]['board_file'] ][ $all_piece_data[$piece_id]['board_rank'] ]) != 0 )
            {
                // Run move generation again for enemy long-range pieces attacking the moving piece's starting square
                foreach ( $danger_squares[ $all_piece_data[$piece_id]['board_file'] ][ $all_piece_data[$piece_id]['board_rank'] ] as $danger_piece_id )
                {
                    if ( $all_piece_data_copy[$danger_piece_id]['piece_type'] != "pawn" && $all_piece_data_copy[$danger_piece_id]['piece_type'] != "knight" && $all_piece_data_copy[$danger_piece_id]['piece_type'] != "king" )
                    {
                        $danger_piece_moves = $this->moveGeneration( $danger_piece_id, $all_piece_data_copy[$danger_piece_id]['piece_type'], $all_piece_data_copy, $board_state_copy );

                        foreach ( $danger_piece_moves as $danger_piece_move )
                        {
                            // If any can capture the king after the simulated move, the move is illegal
                            $attacked_piece = $board_state_copy[$danger_piece_move[0]][$danger_piece_move[1]]['defending_piece'];
                            if ( $attacked_piece != null && $all_piece_data_copy[$attacked_piece]['piece_type'] === "king" )
                            {
                                $self_check_move_indices[] = $move_index;
                                break(3);
                            }
                        }
                    }
                }
            }

            // If there is a capture happening
            if ( $attack_happening === 1 )
            {
                // Run move generation again for pieces attacking 
            }
        }

        foreach ( $self_check_move_indices as $self_check_move_index )
        {
            unset( $generated_moves[$self_check_move_index] );
        }
        $generated_moves = array_values($generated_moves);

        // Return the filtered array of moves
        return $generated_moves;
    }

    // Removes invalid conditional move options from an array of generated moves for a pawn
    function filterPawnMoves( $piece_id, $all_piece_data, $board_state, $generated_moves )
    {
        $illegal_move_indices = array();

        // Remove any moves in the wrong direction based on piece colour
        $piece_color = $all_piece_data[$piece_id]['piece_color'];
        $piece_rank = $all_piece_data[$piece_id]['board_rank'];
        $forward_board_unit = 1;
        if ( $piece_color === "000000" )
        {
            $forward_board_unit = -1;
        }

        foreach ( $generated_moves as $move_index => $generated_move )
        {
            if ( ($generated_move[1] - $piece_rank) * $forward_board_unit < 0 )
            {
                $illegal_move_indices[] = $move_index;
            }
        }
        foreach ( $illegal_move_indices as $illegal_move_index )
        {
            unset( $generated_moves[$illegal_move_index] );
        }
        $generated_moves = array_values($generated_moves);
        $illegal_move_indices = array();

        // Remove forward captures
        foreach ( $generated_moves as $move_index => $generated_move )
        {
            if ( (string)$generated_move[0] === $all_piece_data[$piece_id]['board_file'] && $board_state[$generated_move[0]][$generated_move[1]]['defending_piece'] != null )
            {
                $illegal_move_indices[] = $move_index;
            }
        }
        foreach ( $illegal_move_indices as $illegal_move_index )
        {
            unset( $generated_moves[$illegal_move_index] );
        }
        $generated_moves = array_values($generated_moves);
        $illegal_move_indices = array();

        // Remove double pawn push if this pawn has already moved or another piece is one square in front of this pawn
        if ( $all_piece_data[$piece_id]['moves_made'] != "0" || $board_state[$all_piece_data[$piece_id]['board_file']][$all_piece_data[$piece_id]['board_rank'] + $forward_board_unit]['defending_piece'] != null )
        {
            foreach ( $generated_moves as $move_index => $generated_move )
            {
                if ( ($generated_move[1] - $piece_rank) * $forward_board_unit === 2 )
                {
                    $illegal_move_indices[] = $move_index;
                }
            }
        }
        foreach ( $illegal_move_indices as $illegal_move_index )
        {
            unset( $generated_moves[$illegal_move_index] );
        }
        $generated_moves = array_values($generated_moves);
        $illegal_move_indices = array();

        // Remove diagonal moves if not a regular or en passant capture
        foreach ( $generated_moves as $move_index => $generated_move )
        {
            // If it's a diagonal move
            if ( abs( $generated_move[0] - $all_piece_data[$piece_id]['board_file'] ) === 1 )
            {
                // If there isn't an enemy piece on the square 
                if ( $board_state[$generated_move[0]][$generated_move[1]]['defending_piece'] === null)
                {
                    // And there isn't an en passant enabled enemy piece to the side (on the side of the diagonal move)
                    $side_piece_id = $board_state[$generated_move[0]][$all_piece_data[$piece_id]['board_rank']]['defending_piece'];
                    if ( $side_piece_id === null || !( $all_piece_data[$side_piece_id]['piece_color'] != $piece_color && $all_piece_data[$side_piece_id]['if_en_passant_vulnerable'] != "0" ) )
                    {
                        $illegal_move_indices[] = $move_index;
                    }
                }
            }
        }
        foreach ( $illegal_move_indices as $illegal_move_index )
        {
            unset( $generated_moves[$illegal_move_index] );
        }
        $generated_moves = array_values($generated_moves);

        return $generated_moves;
    }

    // Removes invalid conditional move options from an array of generated moves for a king
    function filterKingMoves( $piece_id, $all_piece_data, $board_state, $generated_moves, $danger_squares )
    {
        $illegal_move_indices = array();

        // Remove the castle move option if king or rook has already moved or there are pieces between
        foreach ( $generated_moves as $move_index => $generated_move)
        {
            $change_in_file = $generated_move[0] - $all_piece_data[$piece_id]['board_file'];

            // This is a castle move
            if ( abs( $change_in_file ) === 2 )
            {
                // If the king already moved, this castle move is illegal
                if ( $all_piece_data[$piece_id]['moves_made'] != "0" )
                {
                    $illegal_move_indices[] = $move_index;
                    continue;
                }

                // If the king is in check, this castle move is illegal
                $king_current_file = $all_piece_data[$piece_id]['board_file'];
                $king_current_rank = $all_piece_data[$piece_id]['board_rank'];

                if ( count( $danger_squares[$king_current_file][$king_current_rank] ) != 0 )
                {
                    $illegal_move_indices[] = $move_index;
                    continue;
                }

                // If the king would be moving "through" check, this castle move is illegal
                $castle_direction = $change_in_file / 2;
                if ( count( $danger_squares[$king_current_file + $castle_direction][$king_current_rank] ) != 0 )
                {
                    $illegal_move_indices[] = $move_index;
                    continue;
                }

                // Loop through the squares between the king and the edge of the board in the direction of this generated castle move
                $square_to_check = array( $king_current_file + $castle_direction, $king_current_rank );
                while ( $square_to_check[0] > 0 && $square_to_check[0] < 9 )
                {
                    // If there is a piece on this square
                    if ( $board_state[$square_to_check[0]][$square_to_check[1]]['defending_piece'] != null )
                    {
                        // If this piece is not a friendly, unmoved rook, this castle move is illegal
                        $piece_on_checking_square = $all_piece_data[$board_state[$square_to_check[0]][$square_to_check[1]]['defending_piece']];
                        if ( !($piece_on_checking_square['piece_color'] === $all_piece_data[$piece_id]['piece_color'] && $piece_on_checking_square['piece_type'] === "rook" && $piece_on_checking_square['moves_made'] === "0") )
                        {
                            $illegal_move_indices[] = $move_index;
                            break;
                        }
                    }

                    // If we have reached the edge of the board without encountering any pieces at all (the rook is gone), this castle move is illegal
                    if ( ($square_to_check[0] === 8 || $square_to_check[0] === 1) && $board_state[$square_to_check[0]][$square_to_check[1]]['defending_piece'] === null )
                    {
                        $illegal_move_indices[] = $move_index;
                        break;
                    }

                    $square_to_check[0] += $castle_direction;
                }
            }
        }

        // Remove the illegal castle moves
        foreach ( $illegal_move_indices as $illegal_move_index )
        {
            unset( $generated_moves[$illegal_move_index] );
        }
        $generated_moves = array_values($generated_moves);

        return $generated_moves;
    }

    // Just for testing
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

    function findValidMoves( $piece_id )
    {
        //$this->printWithJavascript( "findValidMoves called" );

        // Check this action is allowed according to the game state
        $this->checkAction( 'findValidMoves' );

        $all_piece_data = $this->getAllPieceData();
        $active_player_id = $this->getActivePlayerId();

        // Check that the player is clicking on their own piece
        if ( $this->getPlayerColorById( $active_player_id ) === $all_piece_data[$piece_id]['piece_color'] )
        {
            // Gather some information
            $piece_type = $all_piece_data[$piece_id]['piece_type'];
            $board_state = $this->getBoard();

            // Call move generation for this piece
            $generated_moves = $this->moveGeneration( $piece_id, $piece_type, $all_piece_data, $board_state );

            // Updates the player_piece_clicked field in the player database table to the id of the piece clicked
            $sql = "UPDATE player SET player_piece_clicked='$piece_id' WHERE player_id='$active_player_id'";
            self::DbQuery( $sql );

            // Send notification with information on which piece was clicked and all squares to which it can legally move
            self::notifyPlayer( $active_player_id, "findValidMoves", "", array( 
                'piece_clicked' => $piece_id, 
                'valid_moves' => $generated_moves,
                'player_id' => $active_player_id )
            );
        }

        return $generated_moves;
    }

    function movePiece( $target_file, $target_rank )
    {
        // Check this action is allowed according to the game state
        $this->checkAction( 'movePiece' );

        // Get some information
        $player_id = $this->getActivePlayerId();
        $sql = "SELECT player_piece_clicked FROM player WHERE player_id='$player_id'";
        $moving_piece_id = self::getUniqueValueFromDB( $sql );
        $moving_piece_starting_location = $this->getLocationOfPiece($moving_piece_id);
            
        // If this is a valid move according to findValidMoves
        if ( in_array( [$target_file, $target_rank], $this->findValidMoves( $moving_piece_id ) ) )
        {
            $this->printWithJavascript("The target location IS in the array of valid moves");
            
            $if_attacking = "0";
            
            $board_state = $this->getBoard();
            $all_piece_data = $this->getAllPieceData();
            
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

                $sql = "UPDATE board SET attacking_piece='$moving_piece_id' WHERE board_file=$target_file AND board_rank=$target_rank";
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
                if ( $board_state[$i][$j]['attacking_piece'] != null )
                {
                    $attacking_piece_id = $board_state[$i][$j]['attacking_piece'];
                    $defending_piece_id = $board_state[$i][$j]['defending_piece'];

                    // Update the database to remove the defending piece and set the attacking piece as the new defender on that square
                    $sql = "UPDATE pieces SET if_captured='1' WHERE piece_id='$defending_piece_id'";
                    self::DbQuery( $sql );

                    $sql = "UPDATE pieces SET if_attacking='0' WHERE piece_id='$attacking_piece_id'";
                    self::DbQuery( $sql );

                    $sql = "UPDATE board SET defending_piece='$attacking_piece_id', attacking_piece=null WHERE board_file=$i AND board_rank=$j";
                    self::DbQuery( $sql );

                    $if_en_passant = $all_piece_data[$attacking_piece_id]['if_performing_en_passant'];
                    $piece_color = $all_piece_data[$attacking_piece_id]['piece_color'];
                    
                    // This is an en passant attack so update the database and set $if_en_passant and $piece_color accordingly
                    if ( $if_en_passant === "1" )
                    {
                        $rank_to_use = $j + 1;
                        if ( $piece_color === "000000" )
                        {
                            $rank_to_use -= 2;
                        }
                        
                        $sql = "UPDATE board SET defending_piece='$attacking_piece_id' WHERE board_file=$i AND board_rank=$rank_to_use";
                        self::DbQuery( $sql );

                        $sql = "UPDATE board SET defending_piece=null WHERE board_file=$i AND board_rank=$j";
                        self::DbQuery( $sql );

                        $sql = "UPDATE pieces SET board_rank=$rank_to_use, if_performing_en_passant=0 WHERE piece_id='$attacking_piece_id'";
                        self::DbQuery( $sql );
                    }

                    // Notify all players about the resolved attack
                    self::notifyAllPlayers( "resolveAttack", "", array( 
                        'defending_piece_id' => $defending_piece_id,
                        'attacking_piece_id' => $attacking_piece_id,
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
