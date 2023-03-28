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


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');


class ChessSequel extends Table
{
    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels(array(
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ));
    }

    protected function getGameName()
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
    protected function setupNewGame($players, $options = array())
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = array("ffffff", "000000");

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here

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

        // $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $result['players'] = $this->getAllPlayerData();

        // Get information about all pieces
        $result['pieces'] = self::getCollectionFromDB("SELECT * FROM pieces");

        // Get information about the capture queue
        $result['capture_queue'] = self::getCollectionFromDB("SELECT * FROM capture_queue");

        // Get information about legal moves this turn
        $result['legal_moves'] = self::getObjectListFromDB("SELECT moving_piece_id, board_file, board_rank FROM legal_moves");

        // Gathering variables from material.inc.php
        $result['all_army_names'] = $this->all_army_names;
        $result['all_armies_starting_layout'] = $this->all_armies_starting_layout;
        $result['button_labels'] = $this->button_labels;

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

    function getAllPlayerData()
    {
        $sql = "SELECT player_id id, player_color color, player_score score, player_stones stones, 
        player_king_move_available king_move_available, player_army army, player_king_id king_id, player_king_id_2 king_id_2 FROM player";
        return self::getCollectionFromDb($sql);
    }

    function getPlayerKingIds($player_id)
    {
        $sql = "SELECT player_king_id, player_king_id_2 FROM player WHERE player_id='$player_id'";
        $king_ids = self::getObjectFromDB($sql);

        if ($king_ids['player_king_id_2'] === null) {
            unset($king_ids['player_king_id_2']);
        }

        return $king_ids;
    }

    function getBoardState($all_piece_data)
    {
        $board_state = [];

        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 8; $j++) {
                $board_state[$i][$j] = array(
                    'board_file' => (string) $i,
                    'board_rank' => (string) $j,
                    'defending_piece' => null,
                    'capturing_piece' => null
                );
            }
        }

        foreach ($all_piece_data as $piece_id => $piece_data) {
            if ($piece_data['captured']) {
                continue;
            }

            if ($piece_data['capturing']) {
                $board_state[$piece_data['board_file']][$piece_data['board_rank']]['capturing_piece'] = $piece_id;
            } else {
                $board_state[$piece_data['board_file']][$piece_data['board_rank']]['defending_piece'] = $piece_id;
            }
        }

        return $board_state;
    }

    function generateAllMovesForPlayer($player_id, $all_piece_data, $board_state)
    {
        $all_moves_for_player = array();
        $corresponding_captures_for_player = array();

        $player_color = $this->getPlayerColorById($player_id);

        $enemy_player_color = "000000";
        if ($player_color === "000000") {
            $enemy_player_color = "ffffff";
        }

        $friendly_king_ids = $this->getPlayerKingIds($player_id);

        $all_enemy_attacked_squares = $this->getAllAttackedSquares($enemy_player_color, $all_piece_data, $board_state);
        //$this->printWithJavascript($all_enemy_attacked_squares);

        /*self::notifyPlayer( $active_player_id, "highlightAttackedSquares", "", array( 
            'attacked_squares' => $all_enemy_attacked_squares[0], 
            'semi_attacked_squares' => $all_enemy_attacked_squares[1] )
        );*/

        foreach ($all_piece_data as $piece_id => $piece_data) {
            if ($piece_data['piece_color'] === $player_color && $piece_data['captured'] === "0") {
                $possible_moves_and_corresponding_captures = $this->generateMoves($piece_id, $all_piece_data, $board_state, $friendly_king_ids, $all_enemy_attacked_squares);
                $possible_moves = $possible_moves_and_corresponding_captures['possible_moves'];
                $corresponding_captures = $possible_moves_and_corresponding_captures['corresponding_captures'];

                $all_moves_for_player[$piece_id] = $possible_moves;
                $corresponding_captures_for_player[$piece_id] = $corresponding_captures;
            }
        }

        return array("all_moves" => $all_moves_for_player, "all_corresponding_captures" => $corresponding_captures_for_player);
    }

    // Returns an array of the squares of all current possible moves for this piece (the squares the player can click on to make the move)
    function generateMoves($piece_id, $all_piece_data, $board_state, $friendly_king_ids, $all_enemy_attacked_squares)
    {
        $possible_moves = array();

        switch ($all_piece_data[$piece_id]['piece_type']) {
            case "knight":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "knight", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "bishop":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "bishop", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "rook":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "rook", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "queen":
                $bishop_moves = $this->getAttackingMoveSquares($piece_id, "bishop", $all_piece_data, $board_state)['attacking_squares'];
                $rook_moves = $this->getAttackingMoveSquares($piece_id, "rook", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = array_merge($bishop_moves, $rook_moves);
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "pawn":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "pawn", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                $possible_moves = $this->removeUnavailablePawnAttacks($piece_id, $all_piece_data, $board_state, $possible_moves);
                $available_pawn_pushes = $this->getNonCapturingMoveSquares($piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares);
                $possible_moves = array_merge($possible_moves, $available_pawn_pushes);
                break;

            case "king":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "king", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                $available_castles = $this->getNonCapturingMoveSquares($piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares);
                $possible_moves = array_merge($possible_moves, $available_castles);
                break;

            case "nemesis":
                $bishop_moves = $this->getAttackingMoveSquares($piece_id, "bishop", $all_piece_data, $board_state)['attacking_squares'];
                $rook_moves = $this->getAttackingMoveSquares($piece_id, "rook", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = array_merge($bishop_moves, $rook_moves);
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                $possible_moves = $this->removeEnemyOccupiedNonKingSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "nemesispawn":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "nemesispawn", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                $possible_moves = $this->removeUnavailablePawnAttacks($piece_id, $all_piece_data, $board_state, $possible_moves);
                $available_pawn_pushes = $this->getNonCapturingMoveSquares($piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares);
                $possible_moves = array_merge($possible_moves, $available_pawn_pushes);
                break;

            case "reaper":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "reaper", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                $possible_moves = $this->removeEnemyOccupiedKingSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "ghost":
                $possible_moves = $this->getNonCapturingMoveSquares($piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares);
                break;

            case "empoweredknight":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "empoweredknight", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "empoweredbishop":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "empoweredbishop", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "empoweredrook":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "empoweredrook", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "elegantqueen":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "king", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "warriorking":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "warriorking", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "wildhorse":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "knight", $all_piece_data, $board_state)['attacking_squares'];
                break;

            case "tiger":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "tiger", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;

            case "elephant":
                $possible_moves = $this->getAttackingMoveSquares($piece_id, "elephant", $all_piece_data, $board_state)['attacking_squares'];
                $available_short_moves = $this->getNonCapturingMoveSquares($piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares);
                $possible_moves = array_merge($possible_moves, $available_short_moves);
                break;

            case "junglequeen":
                $knight_moves = $this->getAttackingMoveSquares($piece_id, "knight", $all_piece_data, $board_state)['attacking_squares'];
                $rook_moves = $this->getAttackingMoveSquares($piece_id, "rook", $all_piece_data, $board_state)['attacking_squares'];
                $possible_moves = array_merge($knight_moves, $rook_moves);
                $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $possible_moves);
                break;
        }

        $corresponding_captures = $this->getCorrespondingCaptures($piece_id, $all_piece_data, $board_state, $possible_moves);
        $moves_and_captures = $this->removeIllegalCaptureMoves($piece_id, $all_piece_data, $board_state, $possible_moves, $corresponding_captures);

        //$this->printWithJavascript( "generateMoves called for piece: ".$piece_id );
        //$this->printWithJavascript( "possible_moves:" );
        //$this->printWithJavascript( $moves_and_captures['possible_moves'] );

        //$this->printWithJavascript( "corresponding_captures:" );
        //$this->printWithJavascript( $moves_and_captures['corresponding_captures'] );

        $possible_moves_and_corresponding_captures = $this->removeSelfChecks($piece_id, $all_piece_data, $board_state, $friendly_king_ids, $all_enemy_attacked_squares, $moves_and_captures['possible_moves'], $moves_and_captures['corresponding_captures']);

        //$this->printWithJavascript( "possible_moves_and_corresponding_captures:" );
        //$this->printWithJavascript( $possible_moves_and_corresponding_captures );
        return $possible_moves_and_corresponding_captures;
    }

    function getAttackingMoveSquares($piece_id, $piece_type, $all_piece_data, $board_state)
    {
        $attacking_squares = array();
        $semi_attacking_squares = array();

        switch ($piece_type) {
            case "knight":
                $attack_steps = array(array(2, 1), array(1, 2), array(2, -1), array(1, -2), array(-2, 1), array(-1, 2), array(-2, -1), array(-1, -2));
                $attacks = $this->getSeenSquares($piece_id, $all_piece_data, $board_state, $attack_steps, 1);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];
                break;

            case "bishop":
                $attack_steps = array(array(1, 1), array(-1, 1), array(-1, -1), array(1, -1));
                $attacks = $this->getSeenSquares($piece_id, $all_piece_data, $board_state, $attack_steps, 7);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];
                break;

            case "rook":
                $attack_steps = array(array(1, 0), array(-1, 0), array(0, 1), array(0, -1));
                $attacks = $this->getSeenSquares($piece_id, $all_piece_data, $board_state, $attack_steps, 7);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];
                break;

            case "queen":
                $bishop_attacks = $this->getAttackingMoveSquares($piece_id, "bishop", $all_piece_data, $board_state);
                $rook_attacks = $this->getAttackingMoveSquares($piece_id, "rook", $all_piece_data, $board_state);
                $attacking_squares = array_merge($bishop_attacks['attacking_squares'], $rook_attacks['attacking_squares']);
                $semi_attacking_squares = array_merge($bishop_attacks['semi_attacking_squares'], $rook_attacks['semi_attacking_squares']);
                break;

            case "pawn":
                $forward_direction = 1;
                if ($all_piece_data[$piece_id]['piece_color'] === "000000") {
                    $forward_direction = -1;
                }
                $attack_steps = array(array(1, $forward_direction), array(-1, $forward_direction));
                $attacks = $this->getSeenSquares($piece_id, $all_piece_data, $board_state, $attack_steps, 1);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];
                break;

            case "king":
                $attack_steps = array(array(1, 0), array(1, 1), array(0, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -1), array(1, -1));
                $attacks = $this->getSeenSquares($piece_id, $all_piece_data, $board_state, $attack_steps, 1);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];
                break;

            case "nemesis":
                $queen_attacks = $this->getAttackingMoveSquares($piece_id, "queen", $all_piece_data, $board_state);
                $attacking_squares = $queen_attacks['attacking_squares'];
                $semi_attacking_squares = $queen_attacks['semi_attacking_squares'];
                break;

            case "nemesispawn":
                $forward_direction = 1;
                if ($all_piece_data[$piece_id]['piece_color'] === "000000") {
                    $forward_direction = -1;
                }
                $attack_steps = array(array(1, $forward_direction), array(-1, $forward_direction));
                $attacks = $this->getSeenSquares($piece_id, $all_piece_data, $board_state, $attack_steps, 1);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];
                break;

            case "reaper":
                $start_rank = 1;
                if ($all_piece_data[$piece_id]['piece_color'] === "000000") {
                    $start_rank = 2;
                }

                for ($i = 1; $i <= 8; $i++) {
                    for ($j = $start_rank; $j <= $start_rank + 6; $j++) {
                        $attacking_squares[] = array($i, $j);
                    }
                }
                break;

            case "empoweredknight":
                $knight_attacks = $this->getAttackingMoveSquares($piece_id, "knight", $all_piece_data, $board_state);
                $attacking_squares = $knight_attacks['attacking_squares'];
                $semi_attacking_squares = $knight_attacks['semi_attacking_squares'];

                $empowerments = $this->getEmpowerments($piece_id, $all_piece_data, $board_state);
                foreach ($empowerments as $empowerment) {
                    $attacks = $this->getAttackingMoveSquares($piece_id, $empowerment, $all_piece_data, $board_state);
                    $attacking_squares = array_merge($attacking_squares, $attacks['attacking_squares']);
                    $semi_attacking_squares = array_merge($semi_attacking_squares, $attacks['semi_attacking_squares']);
                }
                break;

            case "empoweredbishop":
                $bishop_attacks = $this->getAttackingMoveSquares($piece_id, "bishop", $all_piece_data, $board_state);
                $attacking_squares = $bishop_attacks['attacking_squares'];
                $semi_attacking_squares = $bishop_attacks['semi_attacking_squares'];

                $empowerments = $this->getEmpowerments($piece_id, $all_piece_data, $board_state);
                foreach ($empowerments as $empowerment) {
                    $attacks = $this->getAttackingMoveSquares($piece_id, $empowerment, $all_piece_data, $board_state);
                    $attacking_squares = array_merge($attacking_squares, $attacks['attacking_squares']);
                    $semi_attacking_squares = array_merge($semi_attacking_squares, $attacks['semi_attacking_squares']);
                }
                break;

            case "empoweredrook":
                $rook_attacks = $this->getAttackingMoveSquares($piece_id, "rook", $all_piece_data, $board_state);
                $attacking_squares = $rook_attacks['attacking_squares'];
                $semi_attacking_squares = $rook_attacks['semi_attacking_squares'];

                $empowerments = $this->getEmpowerments($piece_id, $all_piece_data, $board_state);
                foreach ($empowerments as $empowerment) {
                    $attacks = $this->getAttackingMoveSquares($piece_id, $empowerment, $all_piece_data, $board_state);
                    $attacking_squares = array_merge($attacking_squares, $attacks['attacking_squares']);
                    $semi_attacking_squares = array_merge($semi_attacking_squares, $attacks['semi_attacking_squares']);
                }
                break;

            case "elegantqueen":
                $king_attacks = $this->getAttackingMoveSquares($piece_id, "king", $all_piece_data, $board_state);
                $attacking_squares = $king_attacks['attacking_squares'];
                $semi_attacking_squares = $king_attacks['semi_attacking_squares'];
                break;

            case "warriorking":
                $king_attacks = $this->getAttackingMoveSquares($piece_id, "king", $all_piece_data, $board_state);
                $attacking_squares = $king_attacks['attacking_squares'];
                //$this->printWithJavascript($attacking_squares);
                $attacking_squares[] = array((int) $all_piece_data[$piece_id]['board_file'], (int) $all_piece_data[$piece_id]['board_rank']);
                //$this->printWithJavascript($attacking_squares);
                $semi_attacking_squares = $king_attacks['semi_attacking_squares'];
                //$this->printWithJavascript($semi_attacking_squares);
                break;

            case "wildhorse":
                $knight_attacks = $this->getAttackingMoveSquares($piece_id, "knight", $all_piece_data, $board_state);
                $attacking_squares = $knight_attacks['attacking_squares'];
                $semi_attacking_squares = $knight_attacks['semi_attacking_squares'];
                break;

            case "tiger":
                $attack_steps = array(array(1, 1), array(-1, 1), array(-1, -1), array(1, -1));
                $attacks = $this->getSeenSquares($piece_id, $all_piece_data, $board_state, $attack_steps, 2);
                $attacking_squares = $attacks[0];
                $semi_attacking_squares = $attacks[1];
                break;

            case "elephant":
                $attacking_squares = $this->getElephantAttackingMoveSquares($piece_id, $all_piece_data, $board_state);
                break;

            case "junglequeen":
                $knight_attacks = $this->getAttackingMoveSquares($piece_id, "knight", $all_piece_data, $board_state);
                $rook_attacks = $this->getAttackingMoveSquares($piece_id, "rook", $all_piece_data, $board_state);
                $attacking_squares = array_merge($knight_attacks['attacking_squares'], $rook_attacks['attacking_squares']);
                $semi_attacking_squares = array_merge($knight_attacks['semi_attacking_squares'], $rook_attacks['semi_attacking_squares']);
                break;
        }

        return array("attacking_squares" => $attacking_squares, "semi_attacking_squares" => $semi_attacking_squares);
    }

    function getElephantAttackingMoveSquares($elephant_id, $all_piece_data, $board_state)
    {
        $attacking_move_squares = array();

        $elephant_location = array((int) $all_piece_data[$elephant_id]['board_file'], (int) $all_piece_data[$elephant_id]['board_rank']);

        $directions = array(array(1, 0), array(0, 1), array(-1, 0), array(0, -1));

        foreach ($directions as $direction_index => $direction) {
            $square = $elephant_location;

            for ($i = 1; $i <= 3; $i++) {
                $square[0] += $direction[0];
                $square[1] += $direction[1];

                if ($square[0] < 1 || $square[0] > 8 || $square[1] < 1 || $square[1] > 8) {
                    break;
                }

                $attacking_move_squares[$direction_index] = $square;
            }
        }
        $attacking_move_squares = array_values($attacking_move_squares);
        return $attacking_move_squares;
    }

    // Return all squares attacked or semi-attacked by pieces of the color $player_color
    function getAllAttackedSquares($player_color, $all_piece_data, $board_state)
    {
        $attacked_squares = array();

        // Creates an 8x8 array of empty arrays
        for ($i = 1; $i <= 8; $i++) {
            $attacked_squares[$i] = array();

            for ($j = 1; $j <= 8; $j++) {
                $attacked_squares[$i][$j] = array();
            }
        }

        $semi_attacked_squares = $attacked_squares;

        // For all uncaptured pieces of this color
        foreach ($all_piece_data as $piece_id => $piece_data) {
            if ($piece_data['captured'] === "0" && $piece_data['piece_color'] === $player_color && $piece_data['piece_type'] != "reaper" && $piece_data['piece_type'] != "ghost") {
                $attacks = $this->getAttackingMoveSquares($piece_id, $piece_data['piece_type'], $all_piece_data, $board_state);
                $attacking_squares = $attacks['attacking_squares'];
                $attacking_squares_corresponding_captures = array();

                if ($piece_data['piece_type'] === "pawn" || $piece_data['piece_type'] === "nemesispawn") {
                    foreach ($attacking_squares as $attacking_square) {
                        $attacking_squares_corresponding_captures[] = array($attacking_square);
                    }
                } else {
                    $attacking_squares_corresponding_captures = $this->getCorrespondingCaptures($piece_id, $all_piece_data, $board_state, $attacking_squares);
                }

                $moves_and_captures = $this->removeIllegalCaptureMoves($piece_id, $all_piece_data, $board_state, $attacking_squares, $attacking_squares_corresponding_captures);
                $attacking_squares = $moves_and_captures['possible_moves'];
                $attacking_squares_corresponding_captures = $moves_and_captures['corresponding_captures'];

                $pieces_semi_attacking_squares = $attacks['semi_attacking_squares'];
                $semi_attacking_squares_corresponding_captures = $this->getCorrespondingCaptures($piece_id, $all_piece_data, $board_state, $pieces_semi_attacking_squares);

                // For each of the attacked squares
                foreach ($attacking_squares_corresponding_captures as $attacking_square_corresponding_captures) {
                    foreach ($attacking_square_corresponding_captures as $attacking_square_corresponding_capture) {
                        $attacked_squares[$attacking_square_corresponding_capture[0]][$attacking_square_corresponding_capture[1]][] = $piece_id;
                    }
                }
                // For each of the semi-attacked squares
                foreach ($semi_attacking_squares_corresponding_captures as $semi_attacking_square_corresponding_captures) {
                    foreach ($semi_attacking_square_corresponding_captures as $semi_attacking_square_corresponding_capture) {
                        $semi_attacked_squares[$semi_attacking_square_corresponding_capture[0]][$semi_attacking_square_corresponding_capture[1]][] = $piece_id;
                    }
                }
            }
        }

        return array("attacked_squares" => $attacked_squares, "semi_attacked_squares" => $semi_attacked_squares);
    }

    function getNonCapturingMoveSquares($piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares)
    {
        $move_squares = array();

        switch ($all_piece_data[$piece_id]['piece_type']) {
            case "pawn":
                foreach ($this->getAvailablePawnPushes($piece_id, $all_piece_data, $board_state) as $pawn_push) {
                    $move_squares[] = $pawn_push;
                }
                break;

            case "king":
                foreach ($this->getAvailableCastleMoves($piece_id, $all_piece_data, $board_state, $all_enemy_attacked_squares) as $castle_move) {
                    $move_squares[] = $castle_move;
                }
                break;

            case "nemesispawn":
                foreach ($this->getAvailableNemesisPawnPushes($piece_id, $all_piece_data, $board_state) as $pawn_push) {
                    $move_squares[] = $pawn_push;
                }
                break;

            case "ghost":
                for ($i = 1; $i <= 8; $i++) {
                    for ($j = 1; $j <= 8; $j++) {
                        if ($board_state[$i][$j]['defending_piece'] === null) {
                            $move_squares[] = array($i, $j);
                        }
                    }
                }
                break;

            case "elephant":
                $elephant_location = array((int) $all_piece_data[$piece_id]['board_file'], (int) $all_piece_data[$piece_id]['board_rank']);

                $directions = array(array(1, 0), array(0, 1), array(-1, 0), array(0, -1));

                foreach ($directions as $direction) {
                    $square = $elephant_location;

                    $change_axis = 0;
                    if ($direction[0] === 0) {
                        $change_axis = 1;
                    }

                    for ($i = 1; $i <= 2; $i++) {
                        $square[0] += $direction[0];
                        $square[1] += $direction[1];

                        if ($square[$change_axis] < 2 || $square[$change_axis] > 7) {
                            break;
                        }
                        if ($board_state[$square[0]][$square[1]]['defending_piece'] != null) {
                            break;
                        }

                        $move_squares[] = $square;
                    }
                }
                break;
        }

        return $move_squares;
    }

    function getAvailableCastleMoves($king_id, $all_piece_data, $board_state, $all_enemy_attacked_squares)
    {
        $castle_moves = array();

        // If the player is not using the classic army, the king cannot castle
        $all_players_data = $this->getAllPlayerData();

        foreach ($all_players_data as $player_data) {
            if ($player_data['color'] === $all_piece_data[$king_id]['piece_color']) {
                if ($player_data['army'] != "classic") {
                    return $castle_moves;
                }
                break;
            }
        }

        // If the king already moved it cannot castle
        if ($all_piece_data[$king_id]['moves_made'] != "0") {
            return $castle_moves;
        }

        // Store the king's location
        $king_file = (int) $all_piece_data[$king_id]['board_file'];
        $king_rank = (int) $all_piece_data[$king_id]['board_rank'];

        // If the king is in check right now it cannot castle
        if (count($all_enemy_attacked_squares['attacked_squares'][$king_file][$king_rank]) != 0) {
            return $castle_moves;
        }

        // Check both directions for possible castle
        foreach (array(-1, 1) as $direction) {
            $square = array($king_file + $direction, $king_rank);

            // If the next square along in this direction is attacked, the king cannot castle on this side
            if (count($all_enemy_attacked_squares['attacked_squares'][$square[0]][$square[1]]) != 0) {
                continue;
            }

            while ($square[0] > 0 && $square[0] < 9) {
                // If there is a piece on this square
                if ($board_state[$square[0]][$square[1]]['defending_piece'] != null) {
                    // Get the data for this encountered piece
                    $piece_on_square = $all_piece_data[$board_state[$square[0]][$square[1]]['defending_piece']];

                    // If it's a friendly unmoved rook, we can castle in this direction
                    if ($piece_on_square['piece_color'] === $all_piece_data[$king_id]['piece_color'] && $piece_on_square['piece_type'] === "rook" && $piece_on_square['moves_made'] === "0") {
                        $castle_moves[] = array($king_file + (2 * $direction), $king_rank);
                    }

                    break;
                }

                $square[0] += $direction;
            }
        }

        return $castle_moves;
    }

    function getAvailablePawnPushes($pawn_id, $all_piece_data, $board_state)
    {
        $pawn_pushes = array();

        $forward_direction = 1;
        if ($all_piece_data[$pawn_id]['piece_color'] === "000000") {
            $forward_direction = -1;
        }

        $pawn_location = array((int)$all_piece_data[$pawn_id]['board_file'], (int)$all_piece_data[$pawn_id]['board_rank']);

        // If one square forward is empty
        if ($board_state[$pawn_location[0]][$pawn_location[1] + $forward_direction]['defending_piece'] === null) {
            $pawn_pushes[] = array($pawn_location[0], $pawn_location[1] + $forward_direction);

            // If one and two squares forward are free and the pawn hasn't moved yet
            if ($all_piece_data[$pawn_id]['moves_made'] === "0" && $board_state[$pawn_location[0]][$pawn_location[1] + 2 * $forward_direction]['defending_piece'] === null) {
                $pawn_pushes[] = array($pawn_location[0], $pawn_location[1] + 2 * $forward_direction);
            }
        }

        return $pawn_pushes;
    }

    function getAvailableNemesisPawnPushes($nemesis_pawn_id, $all_piece_data, $board_state)
    {
        $nemesis_pawn_pushes = array();

        $nemesis_pawn_location = array((int)$all_piece_data[$nemesis_pawn_id]['board_file'], (int)$all_piece_data[$nemesis_pawn_id]['board_rank']);
        $nemesis_pawn_color = $all_piece_data[$nemesis_pawn_id]['piece_color'];

        $forward_direction = 1;
        if ($nemesis_pawn_color === "000000") {
            $forward_direction = -1;
        }

        $enemy_king_locations = array();

        // For each enemy king or warrior king
        foreach ($all_piece_data as $piece_data) {
            if ($piece_data['piece_color'] != $nemesis_pawn_color && ($piece_data['piece_type'] === "king" || $piece_data['piece_type'] === "warriorking")) {
                $enemy_king_locations[] = array((int) $piece_data['board_file'], (int) $piece_data['board_rank']);
            }
        }

        // If one square forward is empty
        if ($board_state[$nemesis_pawn_location[0]][$nemesis_pawn_location[1] + $forward_direction]['defending_piece'] === null) {
            $nemesis_pawn_pushes[] = array($nemesis_pawn_location[0], $nemesis_pawn_location[1] + $forward_direction);
        }

        $all_directions = array(array(1, 0), array(1, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -$forward_direction), array(1, -1));

        foreach ($all_directions as $direction) {
            $square_in_direction = array($nemesis_pawn_location[0] + $direction[0], $nemesis_pawn_location[1] + $direction[1]);

            if ($square_in_direction[0] < 1 || $square_in_direction[0] > 8 || $square_in_direction[1] < 1 || $square_in_direction[1] > 8) {
                continue;
            }

            if ($board_state[$square_in_direction[0]][$square_in_direction[1]]['defending_piece'] != null) {
                continue;
            }

            foreach ($enemy_king_locations as $enemy_king_location) {
                if (abs($square_in_direction[0] - $enemy_king_location[0]) < abs($nemesis_pawn_location[0] - $enemy_king_location[0])) {
                    if (!(abs($square_in_direction[1] - $enemy_king_location[1]) > abs($nemesis_pawn_location[1] - $enemy_king_location[1]))) {
                        $nemesis_pawn_pushes[] = array($square_in_direction[0], $square_in_direction[1]);
                    }
                } elseif (abs($square_in_direction[1] - $enemy_king_location[1]) < abs($nemesis_pawn_location[1] - $enemy_king_location[1])) {
                    if (!(abs($square_in_direction[0] - $enemy_king_location[0]) > abs($nemesis_pawn_location[0] - $enemy_king_location[0]))) {
                        $nemesis_pawn_pushes[] = array($square_in_direction[0], $square_in_direction[1]);
                    }
                }
            }
        }

        return $nemesis_pawn_pushes;
    }

    // Returns an array containing all capture squares for each possible move in $possible_moves
    function getCorrespondingCaptures($piece_id, $all_piece_data, $board_state, $possible_moves)
    {
        $corresponding_captures = array();

        $simple_piece = false;

        switch ($all_piece_data[$piece_id]['piece_type']) {
            case "pawn":
                foreach ($possible_moves as $possible_move) {
                    // If it's an attacking move
                    if (abs($possible_move[0] - $all_piece_data[$piece_id]['board_file']) === 1) {
                        // If it's an en passant move
                        if ($board_state[$possible_move[0]][$possible_move[1]]['defending_piece'] === null) {
                            $corresponding_captures[] = array(array($possible_move[0], (int)$all_piece_data[$piece_id]['board_rank']));
                        }
                        // If it's a normal attack
                        else {
                            $corresponding_captures[] = array($possible_move);
                        }
                    }
                    // If it's not an attacking move
                    else {
                        $corresponding_captures[] = array();
                    }
                }
                break;

            case "king":
                foreach ($possible_moves as $possible_move) {
                    // If it's a castle move
                    if (abs($possible_move[0] - $all_piece_data[$piece_id]['board_file']) === 2) {
                        $corresponding_captures[] = array();
                    } else {
                        $corresponding_captures[] = array($possible_move);
                    }
                }
                break;

            case "nemesispawn":
                $piece_color = $all_piece_data[$piece_id]['piece_color'];
                $forward_direction = 1;
                if ($piece_color === "000000") {
                    $forward_direction = -1;
                }

                foreach ($possible_moves as $possible_move) {
                    // If it's a diagonal forward move, this can be a capturing move
                    if (abs($possible_move[0] - $all_piece_data[$piece_id]['board_file']) === 1 && $possible_move[1] - $all_piece_data[$piece_id]['board_rank'] === $forward_direction) {
                        $piece_adjacent = $board_state[$possible_move[0]][$all_piece_data[$piece_id]['board_rank']]['defending_piece'];
                        // If the condition is met for an en passant move, the capture square is altered
                        if ($piece_adjacent != null && $all_piece_data[$piece_adjacent]['piece_color'] != $all_piece_data[$piece_id]['piece_color'] && $all_piece_data[$piece_adjacent]['en_passant_vulnerable'] != "0") {
                            $corresponding_captures[] = array(array($possible_move[0], (int)$all_piece_data[$piece_id]['board_rank']));
                        } else {
                            $corresponding_captures[] = array(array($possible_move[0], $possible_move[1]));
                        }
                    }
                    // If it's not a diagonal forward move, it cannot capture
                    else {
                        $corresponding_captures[] = array();
                    }
                }
                break;

            case "ghost":
                foreach ($possible_moves as $possible_move) {
                    $corresponding_captures[] = array();
                }
                break;

            case "warriorking":
                foreach ($possible_moves as $move_index => $possible_move) {
                    if ($possible_move[0] === (int) $all_piece_data[$piece_id]['board_file'] && $possible_move[1] === (int) $all_piece_data[$piece_id]['board_rank']) {
                        $corresponding_captures[$move_index] = array();

                        $directions = array(array(1, 0), array(1, 1), array(0, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -1), array(1, -1));

                        foreach ($directions as $direction) {
                            $square = array((int) $all_piece_data[$piece_id]['board_file'] + $direction[0], (int) $all_piece_data[$piece_id]['board_rank'] + $direction[1]);

                            if ($square[0] < 1 || $square[0] > 8 || $square[1] < 1 || $square[1] > 8) {
                                continue;
                            }

                            $corresponding_captures[$move_index][] = $square;
                        }
                    } else {
                        $corresponding_captures[$move_index] = array($possible_move);
                    }
                }
                break;

            case "elephant":
                $elephant_location = array((int) $all_piece_data[$piece_id]['board_file'], (int) $all_piece_data[$piece_id]['board_rank']);

                foreach ($possible_moves as $move_index => $possible_move) {
                    $corresponding_captures[$move_index] = array();

                    $difference = array($possible_move[0] - $elephant_location[0], $possible_move[1] - $elephant_location[1]);

                    $difference_magnitude = abs($difference[0]);
                    if ($difference_magnitude === 0) {
                        $difference_magnitude = abs($difference[1]);
                    }

                    $direction = array($difference[0] / $difference_magnitude, $difference[1] / $difference_magnitude);

                    $square = $elephant_location;

                    for ($i = 1; $i <= 3; $i++) {
                        $square[0] += $direction[0];
                        $square[1] += $direction[1];

                        $corresponding_captures[$move_index][] = $square;

                        if ($square === $possible_move) {
                            break;
                        }
                    }
                }
                break;

            default:
                $simple_piece = true;
                break;
        }

        if ($simple_piece) {
            foreach ($possible_moves as $possible_move) {
                $corresponding_captures[] = array($possible_move);
            }
        }

        //$this->printWithJavascript( "corresponding captures for moving piece ".$piece_id );
        //$this->printWithJavascript( $corresponding_captures );

        return $corresponding_captures;
    }

    // Seen square: If that square has a compatible defending piece then the relevant action can be taken
    // Semi-seen square: If that square has a compatible defending piece then the relevant action might be able to be taken if a piece along the path is removed
    function getSeenSquares($piece_id, $all_piece_data, $board_state, $steps, $range)
    {
        $seen_squares = array();
        $semi_seen_squares = array();

        $piece_file = $all_piece_data[$piece_id]['board_file'];
        $piece_rank = $all_piece_data[$piece_id]['board_rank'];

        foreach ($steps as $step) {
            $square_file = $piece_file;
            $square_rank = $piece_rank;

            $semi_seen = false;

            for ($i = 1; $i <= $range; $i++) {
                $square_file += $step[0];
                $square_rank += $step[1];

                // If the square is off the board, move to checking the next move step
                if ($square_file < 1 || $square_file > 8 || $square_rank < 1 || $square_rank > 8) {
                    break;
                }

                if ($semi_seen) {
                    $semi_seen_squares[] = array($square_file, $square_rank);
                } else {
                    $seen_squares[] = array($square_file, $square_rank);
                }

                $piece_on_square = $board_state[$square_file][$square_rank]['defending_piece'];
                if (!$semi_seen && $piece_on_square != null) {
                    $semi_seen = true;
                }
            }
        }

        return array($seen_squares, $semi_seen_squares);
    }

    function getEmpowerments($piece_id, $all_piece_data, $board_state)
    {
        $empowerments = array();

        $directions = array(array(1, 0), array(0, 1), array(-1, 0), array(0, -1));

        $piece_location = array((int) $all_piece_data[$piece_id]['board_file'], (int) $all_piece_data[$piece_id]['board_rank']);

        foreach ($directions as $direction) {
            $square = array($piece_location[0] + $direction[0], $piece_location[1] + $direction[1]);

            if ($square[0] < 1 || $square[0] > 8 || $square[1] < 1 || $square[1] > 8) {
                continue;
            }

            $piece_on_square = $board_state[$square[0]][$square[1]]['defending_piece'];

            if ($piece_on_square != null && $all_piece_data[$piece_on_square]['piece_color'] === $all_piece_data[$piece_id]['piece_color'] && $all_piece_data[$piece_on_square]['piece_type'] != $all_piece_data[$piece_id]['piece_type']) {
                switch ($all_piece_data[$piece_on_square]['piece_type']) {
                    case "empoweredknight":
                        $empowerments[] = "knight";
                        break;

                    case "empoweredbishop":
                        $empowerments[] = "bishop";
                        break;

                    case "empoweredrook":
                        $empowerments[] = "rook";
                        break;
                }
            }
        }

        return $empowerments;
    }

    function removeFriendlyOccupiedSquares($piece_id, $all_piece_data, $board_state, $squares_array)
    {
        // Loop through the provided array of squares
        foreach ($squares_array as $index => $square) {
            // If a square has a friendly piece on it, remove this square from the array
            $piece_on_square = $board_state[$square[0]][$square[1]]['defending_piece'];
            if ($piece_on_square != null && $all_piece_data[$piece_on_square]['piece_color'] === $all_piece_data[$piece_id]['piece_color'] && $all_piece_data[$piece_on_square]['piece_id'] != $piece_id) {
                unset($squares_array[$index]);
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    function removeEnemyOccupiedNonKingSquares($piece_id, $all_piece_data, $board_state, $squares_array)
    {
        // Loop through the provided array of squares
        foreach ($squares_array as $index => $square) {
            $piece_on_square = $board_state[$square[0]][$square[1]]['defending_piece'];

            // If there is an enemy piece on the square
            if ($piece_on_square != null && $all_piece_data[$piece_on_square]['piece_color'] != $all_piece_data[$piece_id]['piece_color']) {
                // And if that piece is not a king or a warrior king, remove the square from the array
                if ($all_piece_data[$piece_on_square]['piece_type'] != "king" && $all_piece_data[$piece_on_square]['piece_type'] != "warriorking") {
                    unset($squares_array[$index]);
                }
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    function removeEnemyOccupiedKingSquares($piece_id, $all_piece_data, $board_state, $squares_array)
    {
        // Loop through the provided array of squares
        foreach ($squares_array as $index => $square) {
            $piece_on_square = $board_state[$square[0]][$square[1]]['defending_piece'];

            // If there is an enemy piece on the square
            if ($piece_on_square != null && $all_piece_data[$piece_on_square]['piece_color'] != $all_piece_data[$piece_id]['piece_color']) {
                // And if that piece is a king or a warrior king, remove the square from the array
                if ($all_piece_data[$piece_on_square]['piece_type'] === "king" || $all_piece_data[$piece_on_square]['piece_type'] === "warriorking") {
                    unset($squares_array[$index]);
                }
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    function removeIllegalCaptureMoves($piece_id, $all_piece_data, $board_state, $possible_moves, $corresponding_captures)
    {
        foreach ($possible_moves as $move_index => $move_square) {
            foreach ($corresponding_captures[$move_index] as $capture_square) {
                $piece_on_square = $board_state[$capture_square[0]][$capture_square[1]]['defending_piece'];

                if ($piece_on_square != null) {
                    if (($all_piece_data[$piece_on_square]['piece_type'] === "king" || $all_piece_data[$piece_on_square]['piece_type'] === "warriorking") && $all_piece_data[$piece_on_square]['piece_color'] === $all_piece_data[$piece_id]['piece_color']) {
                        unset($possible_moves[$move_index]);
                        unset($corresponding_captures[$move_index]);
                        break;
                    }

                    switch ($all_piece_data[$piece_on_square]['piece_type']) {
                        case "ghost":
                            unset($possible_moves[$move_index]);
                            unset($corresponding_captures[$move_index]);
                            break (2);

                        case "nemesis":
                            if ($all_piece_data[$piece_id]['piece_type'] != "king" && $all_piece_data[$piece_id]['piece_type'] != "warriorking") {
                                unset($possible_moves[$move_index]);
                                unset($corresponding_captures[$move_index]);
                                break (2);
                            }
                            break;

                        case "elephant":
                            $capturing_piece_location = array((int) $all_piece_data[$piece_id]['board_file'], (int) $all_piece_data[$piece_id]['board_rank']);

                            if (abs($capturing_piece_location[0] - $capture_square[0]) > 2 || abs($capturing_piece_location[1] - $capture_square[1]) > 2) {
                                unset($possible_moves[$move_index]);
                                unset($corresponding_captures[$move_index]);
                                break (2);
                            }
                            break;
                    }
                }
            }
        }

        $possible_moves = array_values($possible_moves);
        $corresponding_captures = array_values($corresponding_captures);

        return array("possible_moves" => $possible_moves, "corresponding_captures" => $corresponding_captures);
    }

    // Takes an array of squares and removes any which do not correspond to a currently available capture for the pawn with id $piece_id
    function removeUnavailablePawnAttacks($piece_id, $all_piece_data, $board_state, $possible_attacks)
    {
        foreach ($possible_attacks as $index => $possible_attack) {
            $piece_on_square = $board_state[$possible_attack[0]][$possible_attack[1]]['defending_piece'];

            // If the square is empty and there is no en passant available there, remove this attack from the array
            if ($piece_on_square === null) {
                $piece_adjacent = $board_state[$possible_attack[0]][$all_piece_data[$piece_id]['board_rank']]['defending_piece'];

                if ($piece_adjacent === null || $all_piece_data[$piece_adjacent]['piece_color'] === $all_piece_data[$piece_id]['piece_color'] || $all_piece_data[$piece_adjacent]['en_passant_vulnerable'] === "0") {
                    unset($possible_attacks[$index]);
                }
            }
        }
        $possible_attacks = array_values($possible_attacks);

        return $possible_attacks;
    }

    // Takes an array of possible moves and returns the same array but with any options removed that would leave the player's own king in check 
    function removeSelfChecks($moving_piece_id, $all_piece_data, $board_state, $friendly_king_ids, $all_enemy_attacked_squares, $possible_moves, $corresponding_captures)
    {
        $move_piece_location = array((int)$all_piece_data[$moving_piece_id]['board_file'], (int)$all_piece_data[$moving_piece_id]['board_rank']);
        $move_piece_type = $all_piece_data[$moving_piece_id]['piece_type'];

        $enemies_attacking_move_piece_location = $all_enemy_attacked_squares['attacked_squares'][$move_piece_location[0]][$move_piece_location[1]];

        $king_locations = array();
        $enemies_attacking_king_locations = array();

        $attackers_to_recheck = array();

        foreach ($friendly_king_ids as $friendly_king_id) {
            $king_locations[$friendly_king_id] = array((int)$all_piece_data[$friendly_king_id]['board_file'], (int)$all_piece_data[$friendly_king_id]['board_rank']);
        }

        foreach ($king_locations as $king_id => $king_location) {
            $enemies_attacking_king_locations[$king_id]['attacking'] = $all_enemy_attacked_squares['attacked_squares'][$king_location[0]][$king_location[1]];
            $enemies_attacking_king_locations[$king_id]['semi_attacking'] = $all_enemy_attacked_squares['semi_attacked_squares'][$king_location[0]][$king_location[1]];

            if (count($enemies_attacking_king_locations[$king_id]['attacking']) != 0) {
                $attackers_to_recheck = array_merge($attackers_to_recheck, $enemies_attacking_king_locations[$king_id]['attacking']);
            }
        }

        // If the moving piece is a king or warriorking, it can't move onto an attacked square
        if ($move_piece_type === "king" || $move_piece_type === "warriorking") {
            foreach ($possible_moves as $move_index => $possible_move) {
                if (count($all_enemy_attacked_squares['attacked_squares'][$possible_move[0]][$possible_move[1]]) != 0) {
                    unset($possible_moves[$move_index]);
                    unset($corresponding_captures[$move_index]);
                    continue;
                }
            }
        }

        $need_to_check = false;
        foreach ($enemies_attacking_king_locations as $king_id => $enemies) {
            if (count($enemies['attacking']) != 0 || count($enemies['semi_attacking']) != 0) {
                $need_to_check = true;
            }
        }

        if ($need_to_check) {
            foreach ($possible_moves as $move_index => $possible_move) {
                //$this->printWithJavascript("Moving piece: ".$moving_piece_id.", Possible move: ".$possible_move[0].", ".$possible_move[1]);

                $attackers_to_recheck_copy = $attackers_to_recheck;

                // Any enemy pieces which are attacking the moving piece and semi-attacking a king should be rechecked
                foreach ($enemies_attacking_king_locations as $enemies) {
                    $attackers_to_recheck_copy = array_merge($attackers_to_recheck_copy, array_intersect($enemies_attacking_move_piece_location, $enemies['semi_attacking']));
                }

                if ($move_piece_type === "pawn" || $move_piece_type === "warriorking" || $move_piece_type === "tiger" || $move_piece_type === "elephant") {
                    foreach ($corresponding_captures[$move_index] as $capture_location) {
                        // If any pieces would be captured were this move made (by a piece which might not land where it captures)
                        if ($board_state[$capture_location[0]][$capture_location[1]]['defending_piece'] != null) {
                            // Any enemy pieces which are attacking the capture location and semi-attacking this king MIGHT be attacking the king if this move were made
                            $enemies_attacking_capture_location = $all_enemy_attacked_squares['attacked_squares'][$capture_location[0]][$capture_location[1]];

                            foreach ($enemies_attacking_king_locations as $enemies) {
                                $attackers_to_recheck_copy = array_merge($attackers_to_recheck_copy, array_intersect($enemies_attacking_capture_location, $enemies['semi_attacking']));
                            }
                        }
                    }
                }

                $attackers_to_recheck_copy = array_unique($attackers_to_recheck_copy);

                if (count($attackers_to_recheck_copy) === 0) {
                    continue;
                }

                $simulated_move = $this->simulatePossibleMove($moving_piece_id, $all_piece_data, $board_state, $possible_move, $corresponding_captures[$move_index]);
                $all_piece_data_sim = $simulated_move['all_piece_data_sim'];
                $board_state_sim = $simulated_move['board_state_sim'];

                foreach ($friendly_king_ids as $friendly_king_id) {
                    $king_location_sim = array((int)$all_piece_data_sim[$friendly_king_id]['board_file'], (int)$all_piece_data_sim[$friendly_king_id]['board_rank']);

                    if ($this->arePiecesAttackingSquare($all_piece_data_sim, $board_state_sim, $attackers_to_recheck_copy, $king_location_sim)) {
                        unset($possible_moves[$move_index]);
                        unset($corresponding_captures[$move_index]);
                        continue;
                    }
                }
            }
        }

        $possible_moves = array_values($possible_moves);
        $corresponding_captures = array_values($corresponding_captures);
        return array("possible_moves" => $possible_moves, "corresponding_captures" => $corresponding_captures);
    }

    function simulatePossibleMove($piece_id, $all_piece_data, $board_state, $possible_move, $capture_squares_for_this_move)
    {
        // Starting location of the moving piece
        $piece_starting_file = $all_piece_data[$piece_id]['board_file'];
        $piece_starting_rank = $all_piece_data[$piece_id]['board_rank'];

        // Remove the moving piece from its starting location
        $board_state[$piece_starting_file][$piece_starting_rank]['defending_piece'] = null;

        // Set as captured any pieces which would be captured in this move
        foreach ($capture_squares_for_this_move as $capture_square) {
            $piece_on_cap_square = $board_state[$capture_square[0]][$capture_square[1]]['defending_piece'];

            if ($piece_on_cap_square != null) {
                $board_state[$capture_square[0]][$capture_square[1]]['defending_piece'] = null;
                $all_piece_data[$piece_on_cap_square]['captured'] = "1";

                if ($all_piece_data[$piece_id]['piece_type'] === "tiger") {
                    $possible_move[0] = $piece_starting_file;
                    $possible_move[1] = $piece_starting_rank;
                }
            }
        }

        // Set the updated location of the moving piece
        $board_state[$possible_move[0]][$possible_move[1]]['defending_piece'] = $piece_id;
        $all_piece_data[$piece_id]['board_file'] = $possible_move[0];
        $all_piece_data[$piece_id]['board_rank'] = $possible_move[1];

        return array("all_piece_data_sim" => $all_piece_data, "board_state_sim" => $board_state);
    }

    // Returns true if any of the pieces specified in $piece_ids are attacking $square
    function arePiecesAttackingSquare($all_piece_data, $board_state, $piece_ids, $square)
    {
        //$this->printWithJavascript($square);

        foreach ($piece_ids as $piece_id) {
            if ($all_piece_data[$piece_id]['captured'] === "0") {
                $attacking_move_squares = $this->getAttackingMoveSquares($piece_id, $all_piece_data[$piece_id]['piece_type'], $all_piece_data, $board_state)['attacking_squares'];

                if ($all_piece_data[$piece_id]['piece_type'] === "pawn" || $all_piece_data[$piece_id]['piece_type'] === "nemesispawn") {
                    $attacking_move_squares = $this->removeUnavailablePawnAttacks($piece_id, $all_piece_data, $board_state, $attacking_move_squares);
                }

                $corresponding_captures = $this->getCorrespondingCaptures($piece_id, $all_piece_data, $board_state, $attacking_move_squares);
                $moves_and_captures = $this->removeIllegalCaptureMoves($piece_id, $all_piece_data, $board_state, $attacking_move_squares, $corresponding_captures);
                $attacking_move_squares = $moves_and_captures['possible_moves'];
                $corresponding_captures = $moves_and_captures['corresponding_captures'];

                //$this->printWithJavascript("arePiecesAttackingSquare, attacking_squares:");
                //$this->printWithJavascript($attacking_squares);

                foreach ($attacking_move_squares as $attacking_move_index => $attacking_move_square) {
                    foreach ($corresponding_captures[$attacking_move_index] as $corresponding_capture_square) {
                        if ($corresponding_capture_square === $square) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    // Replaces legal_moves db table with all legal king moves for given player. Returns true if there are any, else false
    function generateAllKingMovesForPlayer($all_piece_data, $active_player_id)
    {
        $board_state = $this->getBoardState($all_piece_data);

        self::DbQuery("DELETE FROM legal_moves");

        $all_legal_king_moves = array();

        $player_color = $this->getPlayerColorById($active_player_id);
        $enemy_player_color = ($player_color === "000000") ? "ffffff" : "000000";

        $friendly_king_ids = $this->getPlayerKingIds($active_player_id);

        $all_enemy_attacked_squares = $this->getAllAttackedSquares($enemy_player_color, $all_piece_data, $board_state);

        foreach ($friendly_king_ids as $friendly_king_id) {
            $possible_moves_and_corresponding_captures = $this->generateMoves($friendly_king_id, $all_piece_data, $board_state, $friendly_king_ids, $all_enemy_attacked_squares);
            $all_legal_king_moves[$friendly_king_id] = $possible_moves_and_corresponding_captures['possible_moves'];
        }

        $sql = "INSERT INTO legal_moves (move_id, moving_piece_id, board_file, board_rank) VALUES ";
        $moves = array();

        $has_legal_moves = false;

        $counter = 0;
        foreach ($all_legal_king_moves as $piece_id => $moves_for_piece) {
            foreach ($moves_for_piece as $move_square) {
                $moves[] = "('$counter','$piece_id','$move_square[0]','$move_square[1]')";
                $counter++;
            }

            if (!$has_legal_moves && count($moves_for_piece) != 0) {
                $has_legal_moves = true;
            }
        }

        if ($has_legal_moves) {
            $sql .= implode(',', $moves);
            self::DbQuery($sql);
        }

        self::notifyAllPlayers("updateLegalMovesTable", "", array("moves_added" => $all_legal_king_moves));

        return $has_legal_moves;
    }

    // Change player_stones by $amount for the player with color $player_color (max 6)
    function updateStones($player_color, $amount)
    {
        $new_stones = self::getUniqueValueFromDB("SELECT player_stones FROM player WHERE player_color = '$player_color'") + $amount;

        if ($new_stones == 7) {
            return;
        }

        self::DbQuery("UPDATE player SET player_stones = '$new_stones' WHERE player_color = '$player_color'");

        $player_id = self::getUniqueValueFromDB("SELECT player_id FROM player WHERE player_color = '$player_color'");

        self::notifyAllPlayers(
            "updatePlayerData",
            "",
            array(
                "player_id" => $player_id,
                "values_updated" => array("stones" => (string) $new_stones)
            )
        );
    }

    // TODO: Poorly designed and can be improved
    function resolveNextCapture($both_pieces_capture, $all_piece_data = null, $board_state = null, $capture_queue = null)
    {
        if ($all_piece_data === null) {
            $all_piece_data = self::getCollectionFromDB("SELECT * FROM pieces");
        }

        if ($board_state === null) {
            $board_state = $this->getBoardState($all_piece_data);
        }

        if ($capture_queue === null) {
            $capture_queue = self::getCollectionFromDB("SELECT * FROM capture_queue");
        }

        $capturing_piece_id = self::getUniqueValueFromDB("SELECT var_value FROM game_variables WHERE var_id = 'cap_id'");

        if ($both_pieces_capture) {
            $capture_ids = array();
            $capture_squares = array();
            for ($i = 1; $i <= 8; $i++) {
                if (array_key_exists($i, $capture_queue)) {
                    $capture_ids[] = $i;
                    $capture_squares[] = array((int) $capture_queue[$i]['board_file'], (int) $capture_queue[$i]['board_rank']);
                }
            }

            $defending_piece_id = $board_state[$capture_squares[0][0]][$capture_squares[0][1]]['defending_piece'];

            // Update piece data for defending piece
            self::DbQuery("UPDATE pieces SET captured = 1 WHERE piece_id = '$defending_piece_id'");

            self::notifyAllPlayers(
                "updateAllPieceData",
                "",
                array(
                    "piece_id" => $defending_piece_id,
                    "values_updated" => array("captured" => "1")
                )
            );

            if ($all_piece_data[$defending_piece_id]['piece_type'] === "pawn" || $all_piece_data[$defending_piece_id]['piece_type'] === "nemesispawn") {
                if ($all_piece_data[$defending_piece_id]['piece_color'] != $all_piece_data[$capturing_piece_id]['piece_color']) {
                    // Player with the color of the capturing piece gets a stone
                    $capturing_color = $all_piece_data[$capturing_piece_id]['piece_color'];
                    $this->updateStones($capturing_color, 1);
                }
            }

            // Update piece data for capturing piece
            self::DbQuery("UPDATE pieces SET captured = 1, capturing = 0 WHERE piece_id = '$capturing_piece_id'");

            self::DbQuery("UPDATE game_variables SET var_value = NULL WHERE var_id = 'cap_id'");

            self::notifyAllPlayers(
                "updateAllPieceData",
                "",
                array(
                    "piece_id" => $capturing_piece_id,
                    "values_updated" => array("captured" => "1", "capturing" => "0")
                )
            );

            if ($all_piece_data[$capturing_piece_id]['piece_type'] === "pawn" || $all_piece_data[$capturing_piece_id]['piece_type'] === "nemesispawn") {
                if ($all_piece_data[$defending_piece_id]['piece_color'] != $all_piece_data[$capturing_piece_id]['piece_color']) {
                    // Player with the color of the defending piece gets a stone
                    $defending_color = $all_piece_data[$defending_piece_id]['piece_color'];
                    $this->updateStones($defending_color, 1);
                }
            }

            // Clear entire capture queue
            foreach ($capture_ids as $capture_id) {
                self::DbQuery("DELETE FROM capture_queue WHERE capture_id = '$capture_id'");

                self::notifyAllPlayers("deleteFromCaptureQueue", "", array("capture_id" => $capture_id));
            }
        } else {
            $capture_id = 0;
            $capture_square = array();
            for ($i = 1; $i <= 8; $i++) {
                if (array_key_exists($i, $capture_queue)) {
                    $capture_id = $i;
                    $capture_square = array((int) $capture_queue[$i]['board_file'], (int) $capture_queue[$i]['board_rank']);
                    break;
                }
            }

            $defending_piece_id = $board_state[$capture_square[0]][$capture_square[1]]['defending_piece'];

            self::DbQuery("UPDATE pieces SET captured = 1 WHERE piece_id = '$defending_piece_id'");

            self::notifyAllPlayers(
                "updateAllPieceData",
                "",
                array(
                    "piece_id" => $defending_piece_id,
                    "values_updated" => array("captured" => "1")
                )
            );

            if ($all_piece_data[$defending_piece_id]['piece_type'] === "pawn" || $all_piece_data[$defending_piece_id]['piece_type'] === "nemesispawn") {
                if ($all_piece_data[$defending_piece_id]['piece_color'] != $all_piece_data[$capturing_piece_id]['piece_color']) {
                    // Player with the color of the capturing piece gets a stone
                    $capturing_color = $all_piece_data[$capturing_piece_id]['piece_color'];
                    $this->updateStones($capturing_color, 1);
                }
            }

            if (count($capture_queue) === 1) {
                self::DbQuery("UPDATE pieces SET capturing = 0 WHERE piece_id = '$capturing_piece_id'");

                self::DbQuery("UPDATE game_variables SET var_value = NULL WHERE var_id = 'cap_id'");

                self::notifyAllPlayers(
                    "updateAllPieceData",
                    "",
                    array(
                        "piece_id" => $capturing_piece_id,
                        "values_updated" => array("capturing" => "0")
                    )
                );
            }

            self::DbQuery("DELETE FROM capture_queue WHERE capture_id = '$capture_id'");

            self::notifyAllPlayers("deleteFromCaptureQueue", "", array("capture_id" => $capture_id));
        }
    }

    function resolveCastle($all_piece_data, $king_id)
    {
        $king_file = $all_piece_data[$king_id]['board_file'];
        $king_rank = $all_piece_data[$king_id]['board_rank'];
        $board_state = $this->getBoardState($all_piece_data);

        // Find the rook
        $rook_dir = ($king_file === "7") ? 1 : -1;
        $castling_rook_id = $board_state[$king_file + $rook_dir][$king_rank]['defending_piece'];
        $rook_destination_file = $king_file - $rook_dir;

        // Update the rook's position in the pieces database table
        self::DbQuery("UPDATE pieces SET board_file = '$rook_destination_file' WHERE piece_id = '$castling_rook_id'");

        // Update the game_variables database table to no longer have this piece castling
        self::DbQuery("UPDATE game_variables SET var_value = NULL WHERE var_id = 'cas_id'");

        self::notifyAllPlayers(
            "updateAllPieceData",
            "",
            array(
                "piece_id" => $castling_rook_id,
                "values_updated" => array("location" => array($rook_destination_file, $king_rank))
            )
        );

        $this->gamestate->nextState('whereNext');
    }

    // Returns true if active player has met the midline invasion win condition, else returns false
    function activePlayerInvaded($all_piece_data)
    {
        $king_ids = $this->getPlayerKingIds($this->getActivePlayerId());

        $invasion_direction = ($all_piece_data[$king_ids['player_king_id']]['piece_color'] === "000000") ? -1 : 1;

        foreach ($king_ids as $king_id) {
            if (($all_piece_data[$king_id]['board_rank'] - 4.5) * $invasion_direction < 0) {
                return false;
            }
        }
        return true;
    }

    function processCapture($all_piece_data, $capturing_piece_id)
    {
        $board_state = $this->getBoardState($all_piece_data);
        $capture_queue = self::getCollectionFromDB("SELECT * FROM capture_queue");

        // The first square in the capture queue
        $capture_square = array();
        for ($i = 1; $i <= 8; $i++) {
            if (array_key_exists($i, $capture_queue)) {
                $capture_square = array((int) $capture_queue[$i]['board_file'], (int) $capture_queue[$i]['board_rank']);
                break;
            }
        }

        $capturing_piece_data = $all_piece_data[$capturing_piece_id];
        $defending_piece_data = $all_piece_data[$board_state[$capture_square[0]][$capture_square[1]]['defending_piece']];

        $this->activeNextPlayer();
        $defender_player_id = $this->getActivePlayerId();
        $capturing_piece_rank = $this->piece_ranks[$capturing_piece_data['piece_type']];
        $defending_piece_rank = $this->piece_ranks[$defending_piece_data['piece_type']];

        $defender_stones = self::getUniqueValueFromDB("SELECT player_stones FROM player WHERE player_id = '$defender_player_id'");
        $cost_to_duel = ($capturing_piece_rank > $defending_piece_rank) ? 1 : 0;

        // If capturing (warrior)king or defending friendly or can't afford duel, capturing proceeds with no duel
        if (
            in_array($capturing_piece_data['piece_type'], ["king", "warriorking"])
            || $defending_piece_data['piece_color'] === $capturing_piece_data['piece_color']
            || $defender_stones <= $cost_to_duel
        ) {
            $this->activeNextPlayer();
            $this->resolveNextCapture(false, $all_piece_data, $board_state, $capture_queue);
            $this->gamestate->nextState('whereNext');
        } else {
            $this->gamestate->nextState('duelOffer');
        }
    }

    function activePlayerWins()
    {
        $active_player_id = $this->getActivePlayerId();
        self::DbQuery("UPDATE player SET player_score = 1 WHERE player_id = '$active_player_id'");

        $this->gamestate->nextState('gameEnd');
    }

    // Can be called anywhere in the game.php, just calls console.log on the client side with whatever argument you pass in
    function printWithJavascript($x)
    {
        //echo( $x );
        self::notifyAllPlayers("printWithJavascript", "", array('x' => $x));
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in chesssequel.action.php)
    */

    function confirmArmy($army_name)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('confirmArmy');

        // Check it's a valid army name according to the array in material.inc.php
        if (in_array($army_name, $this->all_army_names)) {
            // Get the id of the CURRENT player (there are multiple active players in armySelect)
            // In the BGA framework, the CURRENT player is the player who played the current player action (player who made the AJAX request)
            $player_id = $this->getCurrentPlayerId();

            // Updates the current player's army in the database
            self::DbQuery("UPDATE player SET player_army = '$army_name' WHERE player_id = '$player_id'");

            $opponent_active = self::getUniqueValueFromDB("SELECT player_is_multiactive FROM player WHERE player_id != '$player_id'");

            // If opponent is active, deactivate this player before the notification (status bar inconsistency otherwise)
            if ($opponent_active) {
                $this->gamestate->setPlayerNonMultiactive($player_id, 'boardSetup');
            }

            // Send notification
            self::notifyAllPlayers("confirmArmy", clienttranslate('${player_name} has confirmed their army choice'), array(
                'player_id' => $player_id,
                'player_name' => $this->getCurrentPlayerName()
            ));

            // If opponent not active, deactivate player after notification (also transitions to boardSetup state)
            if (!$opponent_active) {
                $this->gamestate->setPlayerNonMultiactive($player_id, 'boardSetup');
            }
        } else
            throw new BgaSystemException("Invalid army selection");
    }

    function movePiece($target_file, $target_rank, $moving_piece_id)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('movePiece');

        // Check for valid moving_piece_id
        if (!in_array($moving_piece_id, self::getObjectListFromDB("SELECT piece_id FROM pieces", true))) {
            return;
        }

        // Get some information
        $player_id = $this->getActivePlayerId();
        $player_color = $this->getPlayerColorById($player_id);
        $all_piece_data = self::getCollectionFromDB("SELECT * FROM pieces");

        // Check that the player is trying to move their own piece
        if ($all_piece_data[$moving_piece_id]['piece_color'] != $player_color) {
            return;
        }

        // More information
        $board_state = $this->getBoardState($all_piece_data);
        $target_location = array((int)$target_file, (int)$target_rank);
        $legal_moves = self::getObjectListFromDB("SELECT moving_piece_id, board_file, board_rank FROM legal_moves");

        foreach ($legal_moves as $move) {
            // If the attempted move is found in the array of possible moves
            if ($move['board_file'] === $target_file && $move['board_rank'] === $target_rank && $move['moving_piece_id'] === $moving_piece_id) {
                // $this->printWithJavascript("The target location IS in the array of legal moves");

                $moving_piece_starting_location = array($all_piece_data[$moving_piece_id]['board_file'], $all_piece_data[$moving_piece_id]['board_rank']);

                // If the moving piece is a pawn making its initial double move, set its en_passant_vulnerable value to 2
                if ($all_piece_data[$moving_piece_id]['piece_type'] === "pawn" && abs($moving_piece_starting_location[1] - $target_location[1]) === 2) {
                    //$this->printWithJavascript("double pawn push");
                    $pieces_values_to_set['en_passant_vulnerable'] = "2";
                }
                // If the moving piece is a castling king, set cas_id
                elseif ($all_piece_data[$moving_piece_id]['piece_type'] === "king" && abs($moving_piece_starting_location[0] - $target_location[0]) === 2) {
                    self::DbQuery("UPDATE game_variables SET var_value = '$moving_piece_id' WHERE var_id = 'cas_id'");
                }

                // If the moving piece is a pawn reaching the enemy backline, set pro_id
                if (in_array($all_piece_data[$moving_piece_id]['piece_type'],  ["pawn", "nemesispawn"])) {
                    $backline = ($all_piece_data[$moving_piece_id]['piece_color'] === "000000") ? "1" : "8";

                    if ($target_rank === $backline) {
                        self::DbQuery("UPDATE game_variables SET var_value = '$moving_piece_id' WHERE var_id = 'pro_id'");
                    }
                }

                $corresponding_captures = $this->getCorrespondingCaptures($moving_piece_id, $all_piece_data, $board_state, array($target_location))[0];

                $capture_queue = array();

                $counter = 0;
                foreach ($corresponding_captures as $corresponding_capture_square) {
                    if ($board_state[$corresponding_capture_square[0]][$corresponding_capture_square[1]]['defending_piece'] != null) {
                        if ($all_piece_data[$moving_piece_id]['piece_type'] === "tiger") {
                            $target_location[0] = $moving_piece_starting_location[0];
                            $target_location[1] = $moving_piece_starting_location[1];
                        }

                        $pieces_values_to_set['capturing'] = "1";

                        $counter++;
                        $capture_queue[] = "('$counter','$corresponding_capture_square[0]','$corresponding_capture_square[1]')";
                    }
                }

                $sql = "SELECT moves_made FROM pieces WHERE piece_id='$moving_piece_id'";
                $pieces_values_to_set['moves_made'] = (string) (self::getUniqueValueFromDB($sql) + 1);

                $pieces_values_to_set_notif = $pieces_values_to_set;
                $pieces_values_to_set_notif['location'] = $target_location;

                $pieces_values_to_set['board_file'] = (string) $target_location[0];
                $pieces_values_to_set['board_rank'] = (string) $target_location[1];

                if (count($capture_queue) != 0) {
                    $sql = "INSERT INTO capture_queue (capture_id,board_file,board_rank) VALUES ";
                    $sql .= implode(',', $capture_queue);
                    self::DbQuery($sql);

                    self::DbQuery("UPDATE game_variables SET var_value = '$moving_piece_id' WHERE var_id = 'cap_id'");
                }

                // Update pieces table for the moving piece
                $sql = "UPDATE pieces SET";
                foreach ($pieces_values_to_set as $column => $value) {
                    $sql .= " $column = '$value',";
                }
                $sql = rtrim($sql, ',');
                $sql .= " WHERE piece_id = '$moving_piece_id'";
                self::DbQuery($sql);

                // Send notifications
                if (count($capture_queue) != 0) {
                    self::notifyAllPlayers("fillCaptureQueue", "", array("capture_queue" => $capture_queue));
                }

                self::notifyAllPlayers(
                    "updateAllPieceData",
                    "",
                    array(
                        "piece_id" => $moving_piece_id,
                        "values_updated" => $pieces_values_to_set_notif
                    )
                );

                self::notifyAllPlayers("clearSelectedPiece", "", array());

                // Change player state
                $this->gamestate->nextState('whereNext');
                return;
            }
        }

        // $this->printWithJavascript("The target location is NOT in the array of possible moves");
    }

    function passKingMove()
    {
        // Check this action is allowed according to the game state
        $this->checkAction('passKingMove');

        $this->gamestate->nextState('whereNext');
        return;
    }

    function acceptDuel()
    {
        $this->checkAction('acceptDuel');

        // Pay the cost to duel
        $capture_queue = self::getCollectionFromDB("SELECT * FROM capture_queue");
        $all_piece_data = self::getCollectionFromDB("SELECT * FROM pieces");
        $board_state = $this->getBoardState($all_piece_data);

        $defending_piece_id = 0;
        for ($i = 1; $i <= 8; $i++) {
            if (array_key_exists($i, $capture_queue)) {
                $defending_piece_id = $board_state[(int) $capture_queue[$i]['board_file']][(int) $capture_queue[$i]['board_rank']]['defending_piece'];
                break;
            }
        }

        $capturing_piece_id = self::getUniqueValueFromDB("SELECT var_value FROM game_variables WHERE var_id = 'cap_id'");

        $capturing_piece_rank = $this->piece_ranks[$all_piece_data[$capturing_piece_id]['piece_type']];
        $defending_piece_rank = $this->piece_ranks[$all_piece_data[$defending_piece_id]['piece_type']];

        $cost_to_duel = ($capturing_piece_rank > $defending_piece_rank) ? 1 : 0;

        $this->updateStones($this->getCurrentPlayerColor(), $cost_to_duel * -1);

        $this->gamestate->nextState('duelBidding');
        return;
    }

    function rejectDuel()
    {
        $this->checkAction('rejectDuel');

        $this->resolveNextCapture(false);

        $this->gamestate->nextState('nextPlayer');
        return;
    }

    function pickBid($bid_amount)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('pickBid');

        // Get the id of the CURRENT player (there are multiple active players in armySelect)
        // In the BGA framework, the CURRENT player is the player who played the current player action (player who made the AJAX request)
        $player_id = $this->getCurrentPlayerId();

        $sql = "SELECT player_stones FROM player WHERE player_id = '$player_id'";
        $player_stones = self::getUniqueValueFromDB($sql);

        if (in_array($bid_amount, [0, 1, 2]) && $bid_amount <= $player_stones) {
            // Update the current player's bid in the database
            self::DbQuery("UPDATE player SET player_bid = '$bid_amount' WHERE player_id = '$player_id'");

            // Notify?

            // Deactivate player. If none left, transition to 'resolveDuel' state
            $this->gamestate->setPlayerNonMultiactive($player_id, 'resolveDuel');
        } else {
            throw new BgaSystemException("Invalid bid amount");
        }
    }

    function gainStone()
    {
        $this->checkAction('gainStone');

        $choosing_color = $this->getCurrentPlayerColor();
        $current_stones = self::getUniqueValueFromDB("SELECT player_stones FROM player WHERE player_color = '$choosing_color'");

        if ($current_stones == 6) {
            throw new BgaSystemException("Maximum stones reached");
        } else {
            $this->updateStones($choosing_color, 1);

            $this->gamestate->nextState('whereNext');
            return;
        }
    }

    function destroyStone()
    {
        $this->checkAction('destroyStone');

        $choosing_color = $this->getCurrentPlayerColor();
        $other_color = "000000";
        if ($choosing_color === "000000") {
            $other_color = "ffffff";
        }
        $current_stones = self::getUniqueValueFromDB("SELECT player_stones FROM player WHERE player_color = '$other_color'");

        if ($current_stones == 0) {
            throw new BgaSystemException("Minimum stones reached");
        } else {
            $this->updateStones($other_color, -1);

            $this->gamestate->nextState('whereNext');
            return;
        }
    }

    function promotePawn($chosen_promotion)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('promotePawn');

        // Check that the chosen promotion is valid for this player
        $player_id = $this->getActivePlayerId();
        $player_data = $this->getAllPlayerData();
        $player_army = $player_data[$player_id]['army'];

        if (!in_array($chosen_promotion, $this->all_armies_promote_options[$player_army])) {
            return;
        }

        $promoting_pawn_id = self::getUniqueValueFromDB("SELECT var_value FROM game_variables WHERE var_id = 'pro_id'");

        self::DbQuery("UPDATE pieces SET piece_type = '$chosen_promotion' WHERE piece_id = '$promoting_pawn_id'");

        self::DbQuery("UPDATE game_variables SET var_value = NULL WHERE var_id = 'pro_id'");

        self::notifyAllPlayers("updateAllPieceData", "", array(
            "piece_id" => $promoting_pawn_id,
            "values_updated" => array("piece_type" => $chosen_promotion)
        ));

        $this->gamestate->nextState('whereNext');
        return;
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

    function argPawnPromotion()
    {
        return array("promoteOptions" => $this->all_armies_promote_options);
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

        // Adding a row to the pieces database table for each piece in each player's starting layout
        $sql = "INSERT INTO pieces (piece_id,piece_color,piece_type,board_file,board_rank) VALUES ";
        $sql_values = array();

        $all_king_ids = array();

        // For each player
        foreach ($all_datas['players'] as $player_data) {
            // Get the starting layout for this player's chosen army, and their color
            $army_starting_layout = $all_datas['all_armies_starting_layout'][$player_data['army']];
            $player_color = $player_data['color'];

            // Add to $sql_values the information for a database entry for each piece in the starting layout
            foreach ($army_starting_layout as $piece_name => $piece_info) {
                // Adjust ranks for black player pieces
                $piece_rank = 0;
                if ($player_color === "000000") {
                    $piece_rank = 9 - $piece_info[1];
                } else {
                    $piece_rank = $piece_info[1];
                }

                $piece_id = $player_color . '_' . $piece_name;
                $sql_values[] = "('$piece_id','$player_color','$piece_info[2]','$piece_info[0]','$piece_rank')";
                $pieces_table_update_information[] = array($piece_id, $player_color, $piece_info[2], $piece_info[0], $piece_rank);

                if ($piece_info[2] === "king" || $piece_info[2] === "warriorking") {
                    $all_king_ids[$player_data['id']][] = $piece_id;
                }
            }
        }

        // Send the information to the pieces database table
        $sql .= implode(',', $sql_values);
        self::DbQuery($sql);

        foreach ($all_king_ids as $player_id => $king_ids) {
            self::DbQuery("UPDATE player SET player_king_id = '$king_ids[0]' WHERE player_id = '$player_id'");

            if (count($king_ids) === 2) {
                self::DbQuery("UPDATE player SET player_king_id_2 = '$king_ids[1]' WHERE player_id = '$player_id'");
            }
        }

        // Notifying players of the changes to gamedatas
        self::notifyAllPlayers("stBoardSetup", "", array(
            'pieces_table_update_information' => $pieces_table_update_information,
            'player_armies' => self::getCollectionFromDB("SELECT player_id, player_army FROM player", true)
        ));

        self::DbQuery("INSERT INTO game_variables (var_id) VALUES ('cap_id'), ('cas_id'), ('pro_id')");

        $this->activeNextPlayer();
        $this->activeNextPlayer();
        $this->gamestate->nextState('whereNext');
    }

    function stWhereNext()
    {
        $all_piece_data = self::getCollectionFromDB("SELECT * FROM pieces");

        // Check for midline invasion win condition
        if ($this->activePlayerInvaded($all_piece_data)) {
            $this->activePlayerWins();
            return;
        }

        $game_variables = self::getCollectionFromDB("SELECT * FROM game_variables", true);

        // Check for a pawn promoting
        if ($game_variables['pro_id']) {
            $this->gamestate->nextState('pawnPromotion');
            return;
        }

        // Check for a piece capturing
        if ($game_variables['cap_id']) {
            $this->processCapture($all_piece_data, $game_variables['cap_id']);
            return;
        }

        // Check for a king castling
        if ($game_variables['cas_id']) {
            $this->resolveCastle($all_piece_data, $game_variables['cas_id']);
            return;
        }

        // Now the board state is resolved

        // Give the active player a king turn if available
        $active_player_id = $this->getActivePlayerId();
        $if_king_move_available = self::getUniqueValueFromDB("SELECT player_king_move_available FROM player WHERE player_id = '$active_player_id'");
        if ($if_king_move_available) {
            self::DbQuery("UPDATE player SET player_king_move_available = 0 WHERE player_id = '$active_player_id'");

            if ($this->generateAllKingMovesForPlayer($all_piece_data, $active_player_id)) {
                $this->gamestate->nextState('playerKingMove');
                return;
            }
        }

        // Now the turn can pass to the other player
        
        // Tick down the en_passant_vulnerable value by 1 at the end of each player's turn so an en passant capture is only available for one turn
        foreach ($all_piece_data as $piece_id => $piece_data) {
            if ($piece_data['piece_type'] === "pawn" && $piece_data['en_passant_vulnerable'] != "0") {
                $en_passant_vulnerable = $piece_data['en_passant_vulnerable'] - 1;
                self::DbQuery("UPDATE pieces SET en_passant_vulnerable = '$en_passant_vulnerable' WHERE piece_id = '$piece_id'");

                self::notifyAllPlayers("updateAllPieceData", "", array(
                    "piece_id" => $piece_id,
                    "values_updated" => array("en_passant_vulnerable" => (string) $en_passant_vulnerable)
                ));
            }
        }

        // Activate the next player and generate their legal moves. If they have none, they lose.
        $this->activeNextPlayer();

        self::DbQuery("DELETE FROM legal_moves");

        $active_player_id = $this->getActivePlayerId();
        $board_state = $this->getBoardState($all_piece_data);
        $all_legal_moves = $this->generateAllMovesForPlayer($active_player_id, $all_piece_data, $board_state)['all_moves'];

        $sql = "INSERT INTO legal_moves (move_id, moving_piece_id, board_file, board_rank) VALUES ";
        $moves = array();

        $has_legal_moves = false;

        $counter = 0;
        foreach ($all_legal_moves as $piece_id => $moves_for_piece) {
            foreach ($moves_for_piece as $move_square) {
                $moves[] = "('$counter','$piece_id','$move_square[0]','$move_square[1]')";
                $counter++;
            }

            if (!$has_legal_moves && count($moves_for_piece) != 0) {
                $has_legal_moves = true;
            }
        }

        if ($has_legal_moves) {
            $sql .= implode(',', $moves);
            self::DbQuery($sql);

            self::notifyAllPlayers("updateLegalMovesTable", "", array("moves_added" => $all_legal_moves));

            $army_name = self::getUniqueValueFromDB("SELECT player_army FROM player WHERE player_id = '$active_player_id'");
            if ($army_name === "twokings") {
                self::DbQuery("UPDATE player SET player_king_move_available = 1 WHERE player_id = '$active_player_id'");
            }

            $this->gamestate->nextState('playerMove');
            return;
        }

        $this->activeNextPlayer();
        $this->activePlayerWins();
        return;

        /*

        // Check for threefold repetition, 50 turn rule(?), out of resources and resolve

        */
    }

    function stResolveDuel()
    {
        // Get bid amounts and the piece colors from the database
        $player_bids = self::getCollectionFromDB("SELECT player_color, player_bid FROM player", true);
        $capturing_piece_color = self::getUniqueValueFromDB("SELECT piece_color FROM pieces WHERE capturing='1'");
        $other_color = "000000";
        if ($capturing_piece_color === "000000") {
            $other_color = "ffffff";
        }

        // Determine outcome of duel and resolve capture
        $both_pieces_capture = false;

        if ($player_bids[$capturing_piece_color] < $player_bids[$other_color]) {
            $both_pieces_capture = true;
        }

        // Update bids database
        self::DbQuery("UPDATE player SET player_bid = null");

        // Update player stones in database and notify
        foreach ($player_bids as $player_color => $bid) {
            $this->updateStones($player_color, $bid * -1);
        }

        $this->resolveNextCapture($both_pieces_capture);

        $this->activeNextPlayer();

        // If both bid 0 stones, the attacker can choose to gain 1 stone or destroy 1 of the defender's stones
        if ($player_bids[$capturing_piece_color] == 0 && $player_bids[$other_color] == 0) {
            $this->gamestate->nextState('calledBluff');
            return;
        }

        // Transition to whereNext
        $this->gamestate->nextState('whereNext');
        return;
    }

    function stNextPlayer()
    {
        $this->activeNextPlayer();
        $this->gamestate->nextState('whereNext');
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

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');

            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
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

    function upgradeTableDb($from_version)
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
