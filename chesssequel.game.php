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
            "cap_id" => 10,
            "cas_id" => 11,
            "pro_id" => 12,
            "fifty_counter" => 13
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
        self::setGameStateInitialValue('cap_id', 0);
        self::setGameStateInitialValue('cas_id', 0);
        self::setGameStateInitialValue('pro_id', 0);
        self::setGameStateInitialValue('fifty_counter', 51);

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
        $result['legal_moves'] = self::getObjectListFromDB("SELECT moving_piece_id, x, y FROM legal_moves");

        // Gathering variables from material.inc.php
        $result['all_army_names'] = $this->all_army_names;
        $result['all_armies_layouts'] = $this->all_armies_layouts;
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
        $king_ids = self::getObjectFromDB("SELECT player_king_id, player_king_id_2 FROM player WHERE player_id = '$player_id'");

        if ($king_ids['player_king_id_2'] === null) {
            unset($king_ids['player_king_id_2']);
        }

        return $king_ids;
    }

    function getSquaresData($pieces)
    {
        $squares = [];

        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 8; $j++) {
                $squares[$i][$j] = array('def_piece' => null, 'cap_piece' => null);
            }
        }

        foreach ($pieces as $piece_id => $piece_data) {
            if ($piece_data['captured']) {
                continue;
            }

            if ($piece_data['capturing']) {
                $squares[$piece_data['x']][$piece_data['y']]['cap_piece'] = $piece_id;
            } else {
                $squares[$piece_data['x']][$piece_data['y']]['def_piece'] = $piece_id;
            }
        }

        return $squares;
    }

    // Note that $piece_ids must be an array of valid piece ids belonging to $player_id
    function getAllMovesForPieces($piece_ids, $player_id, $game_data)
    {
        $enemy_player_color = ($this->getPlayerColorById($player_id) === "000000") ? "ffffff" : "000000";
        $friendly_king_ids = $this->getPlayerKingIds($player_id);
        $enemy_attacks = $this->getSquaresAttackedByColor($enemy_player_color, $game_data);

        //$this->printWithJavascript($enemy_attacks);
        /*self::notifyPlayer( $active_player_id, "highlightAttackedSquares", "", array( 
            'attacked_squares' => $enemy_attacks[0], 
            'semi_attacked_squares' => $enemy_attacks[1] )
        );*/

        $all_moves = array();

        foreach ($piece_ids as $piece_id) {
            $all_moves[$piece_id] = $this->getMovesForPiece($piece_id, $friendly_king_ids, $enemy_attacks, $game_data);
        }

        return $all_moves;
    }

    // Returns an array of the squares of all current possible moves for this piece (the squares the player can click on to make the move)
    function getMovesForPiece($piece_id, $friendly_king_ids, $enemy_attacks, $game_data)
    {
        $piece_type = $game_data['pieces'][$piece_id]['type'];

        $possible_moves = $this->getAttackingMoveSquares($piece_id, $game_data)['attacking_squares'];

        if (!in_array($piece_type, ["elephant", "wildhorse"])) {
            $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $possible_moves, $game_data);
        }

        if (in_array($piece_type, ["pawn", "nemesispawn"])) {
            $possible_moves = $this->removeUnavailablePawnAttacks($piece_id, $possible_moves, $game_data);
        }

        if (in_array($piece_type, ["pawn", "king", "nemesispawn", "ghost", "elephant"])) {
            $non_cap_moves = $this->getNonCapturingMoveSquares($piece_id, $enemy_attacks, $game_data);
            $possible_moves = array_merge($possible_moves, $non_cap_moves);
        }

        if ($piece_type === "nemesis") {
            $possible_moves = $this->removeEnemyOccupiedNonKingSquares($piece_id, $possible_moves, $game_data);
        }

        if ($piece_type === "reaper") {
            $possible_moves = $this->removeEnemyOccupiedKingSquares($piece_id, $possible_moves, $game_data);
        }

        $corresponding_captures = $this->getCorrespondingCaptures($piece_id, $possible_moves, $game_data);
        $moves_and_captures = $this->removeIllegalCaptureMoves($piece_id, $possible_moves, $corresponding_captures, $game_data);

        $possible_moves = $this->removeSelfChecks($piece_id, $friendly_king_ids, $enemy_attacks, $moves_and_captures, $game_data);

        return $possible_moves;
    }

    function getAttackingMoveSquares($piece_id, $game_data)
    {
        $piece_data = $game_data['pieces'][$piece_id];

        $result = array("attacking_squares" => [], "semi_attacking_squares" => []);

        if ($piece_data['type'] === "ghost") {
            return $result;
        }

        if ($piece_data['type'] === "reaper") {
            $start_y = ($piece_data['color'] === "000000") ? 2 : 1;

            for ($i = 1; $i <= 8; $i++) {
                for ($j = $start_y; $j <= $start_y + 6; $j++) {
                    $result['attacking_squares'][] = array($i, $j);
                }
            }

            return $result;
        }

        if ($piece_data['type'] === "elephant") {
            $result['attacking_squares'] = $this->getElephantAttackingMoveSquares($piece_id, $game_data);
            return $result;
        }

        if (in_array($piece_data['type'], ["pawn", "nemesispawn"])) {
            $piece_data['type'] = ($piece_data['color'] === "000000") ? "bpawn" : "wpawn";
        }

        $ef_types = $this->effective_types[$piece_data['type']];

        if (in_array($piece_data['type'], ["empoweredknight", "empoweredbishop", "empoweredrook"])) {
            foreach ($this->getEmpowerments($piece_id, $game_data) as $empowerment) {
                array_push($ef_types, $empowerment);
            }
        }

        foreach ($ef_types as $type) {
            $seen_squares = $this->getSeenSquares($piece_id, $this->attack_steps[$type], $this->attack_reps[$type], $game_data);
            $result['attacking_squares'] = array_merge($result['attacking_squares'], $seen_squares['seen_squares']);
            $result['semi_attacking_squares'] = array_merge($result['semi_attacking_squares'], $seen_squares['semi_seen_squares']);
        }

        if ($piece_data['type'] === "warriorking") {
            $result['attacking_squares'][] = array((int) $piece_data['x'], (int) $piece_data['y']);
        }

        return $result;
    }

    function getElephantAttackingMoveSquares($elephant_id, $game_data)
    {
        $attacking_move_squares = array();

        $elephant_location = array((int) $game_data['pieces'][$elephant_id]['x'], (int) $game_data['pieces'][$elephant_id]['y']);

        $directions = $this->attack_steps["rook"];

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
    function getSquaresAttackedByColor($player_color, $game_data)
    {
        $result = array("attacked_squares" => [], "semi_attacked_squares" => []);

        // Creates an 8x8 array of empty arrays
        for ($i = 1; $i <= 8; $i++) {
            $result['attacked_squares'][$i] = array();

            for ($j = 1; $j <= 8; $j++) {
                $result['attacked_squares'][$i][$j] = array();
            }
        }

        $result['semi_attacked_squares'] = $result['attacked_squares'];

        // For all uncaptured pieces of this color
        foreach ($game_data['pieces'] as $piece_id => $piece_data) {
            if ($piece_data['captured'] === "1" || $piece_data['color'] != $player_color || in_array($piece_data['type'], ["reaper", "ghost"])) {
                continue;
            }

            $attacks = $this->getAttackingMoveSquares($piece_id, $game_data);

            $att_squares = $attacks['attacking_squares'];
            $att_squares_cc = array();

            if (in_array($piece_data['type'], ["pawn", "nemesispawn"])) {
                foreach ($att_squares as $square) {
                    $att_squares_cc[] = array($square);
                }
            } else {
                $att_squares_cc = $this->getCorrespondingCaptures($piece_id, $att_squares, $game_data);
            }

            $moves_and_captures = $this->removeIllegalCaptureMoves($piece_id, $att_squares, $att_squares_cc, $game_data);
            $att_squares = $moves_and_captures['possible_moves'];
            $att_squares_cc = $moves_and_captures['corresponding_captures'];

            $semi_att_squares = $attacks['semi_attacking_squares'];
            $semi_att_squares_cc = $this->getCorrespondingCaptures($piece_id, $semi_att_squares, $game_data);

            // For each of the attacked squares
            foreach ($att_squares_cc as $cap_list) {
                foreach ($cap_list as $cap) {
                    $result['attacked_squares'][$cap[0]][$cap[1]][] = $piece_id;
                }
            }
            // For each of the semi-attacked squares
            foreach ($semi_att_squares_cc as $cap_list) {
                foreach ($cap_list as $cap) {
                    $result['semi_attacked_squares'][$cap[0]][$cap[1]][] = $piece_id;
                }
            }
        }

        return $result;
    }

    function getNonCapturingMoveSquares($piece_id, $enemy_attacks, $game_data)
    {
        $move_squares = array();

        switch ($game_data['pieces'][$piece_id]['type']) {
            case "pawn":
                foreach ($this->getAvailablePawnPushes($piece_id, $game_data) as $pawn_push) {
                    $move_squares[] = $pawn_push;
                }
                break;

            case "king":
                foreach ($this->getAvailableCastleMoves($piece_id, $enemy_attacks, $game_data) as $castle_move) {
                    $move_squares[] = $castle_move;
                }
                break;

            case "nemesispawn":
                foreach ($this->getAvailableNemesisPawnPushes($piece_id, $game_data) as $pawn_push) {
                    $move_squares[] = $pawn_push;
                }
                break;

            case "ghost":
                for ($i = 1; $i <= 8; $i++) {
                    for ($j = 1; $j <= 8; $j++) {
                        if ($game_data['squares'][$i][$j]['def_piece'] === null) {
                            $move_squares[] = array($i, $j);
                        }
                    }
                }
                break;

            case "elephant":
                $elephant_location = array((int) $game_data['pieces'][$piece_id]['x'], (int) $game_data['pieces'][$piece_id]['y']);

                $directions = $this->attack_steps["rook"];

                foreach ($directions as $direction) {
                    $square = $elephant_location;

                    $change_axis = ($direction[0] === 0) ? 1 : 0;

                    for ($i = 1; $i <= 2; $i++) {
                        $square[0] += $direction[0];
                        $square[1] += $direction[1];

                        if ($square[$change_axis] < 2 || $square[$change_axis] > 7) {
                            break;
                        }
                        if ($game_data['squares'][$square[0]][$square[1]]['def_piece'] != null) {
                            break;
                        }

                        $move_squares[] = $square;
                    }
                }
                break;
        }

        return $move_squares;
    }

    function getAvailableCastleMoves($king_id, $enemy_attacks, $game_data)
    {
        $castle_moves = array();

        // If the player is not using the classic army, the king cannot castle
        $all_players_data = $this->getAllPlayerData();

        foreach ($all_players_data as $player_data) {
            if ($player_data['color'] === $game_data['pieces'][$king_id]['color']) {
                if ($player_data['army'] != "classic") {
                    return $castle_moves;
                }
                break;
            }
        }

        // If the king already moved it cannot castle
        if ($game_data['pieces'][$king_id]['moves_made'] != "0") {
            return $castle_moves;
        }

        // Store the king's location
        $king_x = (int) $game_data['pieces'][$king_id]['x'];
        $king_y = (int) $game_data['pieces'][$king_id]['y'];

        // If the king is in check right now it cannot castle
        if (count($enemy_attacks['attacked_squares'][$king_x][$king_y]) != 0) {
            return $castle_moves;
        }

        // Check both directions for possible castle
        foreach (array(-1, 1) as $direction) {
            $square = array($king_x + $direction, $king_y);

            // If the next square along in this direction is attacked, the king cannot castle on this side
            if (count($enemy_attacks['attacked_squares'][$square[0]][$square[1]]) != 0) {
                continue;
            }

            while ($square[0] > 0 && $square[0] < 9) {
                // If there is a piece on this square
                if ($game_data['squares'][$square[0]][$square[1]]['def_piece'] != null) {
                    // Get the data for this encountered piece
                    $piece_on_square = $game_data['pieces'][$game_data['squares'][$square[0]][$square[1]]['def_piece']];

                    // If it's a friendly unmoved rook, we can castle in this direction
                    if (
                        $piece_on_square['color'] === $game_data['pieces'][$king_id]['color']
                        && $piece_on_square['type'] === "rook" && $piece_on_square['moves_made'] === "0"
                    ) {
                        $castle_moves[] = array($king_x + (2 * $direction), $king_y);
                    }

                    break;
                }

                $square[0] += $direction;
            }
        }

        return $castle_moves;
    }

    function getAvailablePawnPushes($pawn_id, $game_data)
    {
        $pawn_pushes = array();

        $forward_direction = ($game_data['pieces'][$pawn_id]['color'] === "000000") ? -1 : 1;

        $pawn_location = array((int)$game_data['pieces'][$pawn_id]['x'], (int)$game_data['pieces'][$pawn_id]['y']);

        // If one square forward is empty
        if ($game_data['squares'][$pawn_location[0]][$pawn_location[1] + $forward_direction]['def_piece'] === null) {
            $pawn_pushes[] = array($pawn_location[0], $pawn_location[1] + $forward_direction);

            // If one and two squares forward are free and the pawn hasn't moved yet
            if (
                $game_data['pieces'][$pawn_id]['moves_made'] === "0"
                && $game_data['squares'][$pawn_location[0]][$pawn_location[1] + 2 * $forward_direction]['def_piece'] === null
            ) {
                $pawn_pushes[] = array($pawn_location[0], $pawn_location[1] + 2 * $forward_direction);
            }
        }

        return $pawn_pushes;
    }

    function getAvailableNemesisPawnPushes($nemesis_pawn_id, $game_data)
    {
        $nemesis_pawn_pushes = array();

        $nemesis_pawn_location = array((int)$game_data['pieces'][$nemesis_pawn_id]['x'], (int)$game_data['pieces'][$nemesis_pawn_id]['y']);
        $nemesis_pawn_color = $game_data['pieces'][$nemesis_pawn_id]['color'];

        $forward_direction = ($nemesis_pawn_color === "000000") ? -1 : 1;

        $enemy_king_locations = array();

        // For each enemy king or warrior king
        foreach ($game_data['pieces'] as $piece_data) {
            if ($piece_data['color'] != $nemesis_pawn_color && in_array($piece_data['type'], ["king", "warriorking"])) {
                $enemy_king_locations[] = array((int) $piece_data['x'], (int) $piece_data['y']);
            }
        }

        // If one square forward is empty
        if ($game_data['squares'][$nemesis_pawn_location[0]][$nemesis_pawn_location[1] + $forward_direction]['def_piece'] === null) {
            $nemesis_pawn_pushes[] = array($nemesis_pawn_location[0], $nemesis_pawn_location[1] + $forward_direction);
        }

        $all_directions = array(array(1, 0), array(1, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -$forward_direction), array(1, -1));

        foreach ($all_directions as $direction) {
            $square_in_direction = array($nemesis_pawn_location[0] + $direction[0], $nemesis_pawn_location[1] + $direction[1]);

            if ($square_in_direction[0] < 1 || $square_in_direction[0] > 8 || $square_in_direction[1] < 1 || $square_in_direction[1] > 8) {
                continue;
            }

            if ($game_data['squares'][$square_in_direction[0]][$square_in_direction[1]]['def_piece'] != null) {
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
    function getCorrespondingCaptures($piece_id, $possible_moves, $game_data)
    {
        $corresponding_captures = array();

        $simple_piece = false;

        switch ($game_data['pieces'][$piece_id]['type']) {
            case "pawn":
                foreach ($possible_moves as $possible_move) {
                    // If it's an attacking move
                    if (abs($possible_move[0] - $game_data['pieces'][$piece_id]['x']) === 1) {
                        // If it's an en passant move
                        if ($game_data['squares'][$possible_move[0]][$possible_move[1]]['def_piece'] === null) {
                            $corresponding_captures[] = array(array($possible_move[0], (int)$game_data['pieces'][$piece_id]['y']));
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
                    if (abs($possible_move[0] - $game_data['pieces'][$piece_id]['x']) === 2) {
                        $corresponding_captures[] = array();
                    } else {
                        $corresponding_captures[] = array($possible_move);
                    }
                }
                break;

            case "nemesispawn":
                $piece_color = $game_data['pieces'][$piece_id]['color'];
                $forward_direction = ($piece_color === "000000") ? -1 : 1;

                foreach ($possible_moves as $possible_move) {
                    // If it's a diagonal forward move, this can be a capturing move
                    if (
                        abs($possible_move[0] - $game_data['pieces'][$piece_id]['x']) === 1
                        && $possible_move[1] - $game_data['pieces'][$piece_id]['y'] === $forward_direction
                    ) {
                        $piece_adjacent = $game_data['squares'][$possible_move[0]][$game_data['pieces'][$piece_id]['y']]['def_piece'];
                        // If the condition is met for an en passant move, the capture square is altered
                        if (
                            $piece_adjacent != null
                            && $game_data['pieces'][$piece_adjacent]['color'] != $game_data['pieces'][$piece_id]['color']
                            && $game_data['pieces'][$piece_adjacent]['en_passant_vulnerable'] != "0"
                        ) {
                            $corresponding_captures[] = array(array($possible_move[0], (int)$game_data['pieces'][$piece_id]['y']));
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
                    if ($possible_move === [(int)$game_data['pieces'][$piece_id]['x'], (int)$game_data['pieces'][$piece_id]['y']]) {
                        $corresponding_captures[$move_index] = array();

                        $directions = $this->attack_steps['king'];

                        foreach ($directions as $direction) {
                            $square = array((int) $game_data['pieces'][$piece_id]['x'] + $direction[0], (int) $game_data['pieces'][$piece_id]['y'] + $direction[1]);

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
                $elephant_location = array((int) $game_data['pieces'][$piece_id]['x'], (int) $game_data['pieces'][$piece_id]['y']);

                foreach ($possible_moves as $move_index => $possible_move) {
                    $corresponding_captures[$move_index] = array();

                    $difference = array($possible_move[0] - $elephant_location[0], $possible_move[1] - $elephant_location[1]);
                    $difference_magnitude = (abs($difference[0]) === 0) ? abs($difference[1]) : abs($difference[0]);
                    $direction = array($difference[0] / $difference_magnitude, $difference[1] / $difference_magnitude);

                    if (
                        $difference_magnitude != 3
                        && in_array($possible_move[0] + $direction[0], [0, 9])
                        && in_array($possible_move[1] + $direction[1], [0, 9])
                    ) {
                        continue;
                    }

                    $square = $elephant_location;

                    for ($i = 0; $i < 3; $i++) {
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

        return $corresponding_captures;
    }

    // Seen square: If that square has a compatible defending piece then the relevant action can be taken
    // Semi-seen square: If that square has a compatible defending piece then the relevant action might be able to be taken if a piece along the path is removed
    function getSeenSquares($piece_id, $steps, $range, $game_data)
    {
        $result = array("seen_squares" => [], "semi_seen_squares" => []);

        $piece_x = $game_data['pieces'][$piece_id]['x'];
        $piece_y = $game_data['pieces'][$piece_id]['y'];

        foreach ($steps as $step) {
            $square_x = $piece_x;
            $square_y = $piece_y;

            $semi_seen = false;

            for ($i = 1; $i <= $range; $i++) {
                $square_x += $step[0];
                $square_y += $step[1];

                // If the square is off the board, move to checking the next move step
                if ($square_x < 1 || $square_x > 8 || $square_y < 1 || $square_y > 8) {
                    break;
                }

                if ($semi_seen) {
                    $result['semi_seen_squares'][] = array($square_x, $square_y);
                } else {
                    $result['seen_squares'][] = array($square_x, $square_y);
                }

                $piece_on_square = $game_data['squares'][$square_x][$square_y]['def_piece'];
                if (!$semi_seen && $piece_on_square != null) {
                    $semi_seen = true;
                }
            }
        }

        return $result;
    }

    function getEmpowerments($piece_id, $game_data)
    {
        $empowerments = array();

        $directions = array(array(1, 0), array(0, 1), array(-1, 0), array(0, -1));

        $piece_location = array((int) $game_data['pieces'][$piece_id]['x'], (int) $game_data['pieces'][$piece_id]['y']);

        foreach ($directions as $direction) {
            $square = array($piece_location[0] + $direction[0], $piece_location[1] + $direction[1]);

            if ($square[0] < 1 || $square[0] > 8 || $square[1] < 1 || $square[1] > 8) {
                continue;
            }

            $piece_on_square = $game_data['squares'][$square[0]][$square[1]]['def_piece'];

            if (
                $piece_on_square != null
                && $game_data['pieces'][$piece_on_square]['color'] === $game_data['pieces'][$piece_id]['color']
                && $game_data['pieces'][$piece_on_square]['type'] != $game_data['pieces'][$piece_id]['type']
            ) {
                switch ($game_data['pieces'][$piece_on_square]['type']) {
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

    function removeFriendlyOccupiedSquares($piece_id, $squares_array, $game_data)
    {
        // Loop through the provided array of squares
        foreach ($squares_array as $index => $square) {
            // If a square has a friendly piece on it, remove this square from the array
            $piece_on_square = $game_data['squares'][$square[0]][$square[1]]['def_piece'];
            if (
                $piece_on_square != null
                && $game_data['pieces'][$piece_on_square]['color'] === $game_data['pieces'][$piece_id]['color']
                && $game_data['pieces'][$piece_on_square]['piece_id'] != $piece_id
            ) {
                unset($squares_array[$index]);
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    function removeEnemyOccupiedNonKingSquares($piece_id, $squares_array, $game_data)
    {
        // Loop through the provided array of squares
        foreach ($squares_array as $index => $square) {
            $piece_on_square = $game_data['squares'][$square[0]][$square[1]]['def_piece'];

            // If there is an enemy non (warrior)king on the square, remove the square from the array
            if (
                $piece_on_square != null
                && $game_data['pieces'][$piece_on_square]['color'] != $game_data['pieces'][$piece_id]['color']
                && !in_array($game_data['pieces'][$piece_on_square]['type'], ["king", "warriorking"])
            ) {
                unset($squares_array[$index]);
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    function removeEnemyOccupiedKingSquares($piece_id, $squares_array, $game_data)
    {
        // Loop through the provided array of squares
        foreach ($squares_array as $index => $square) {
            $piece_on_square = $game_data['squares'][$square[0]][$square[1]]['def_piece'];

            // If there is an enemy (warrior)king on the square, remove the square from the array
            if (
                $piece_on_square != null
                && $game_data['pieces'][$piece_on_square]['color'] != $game_data['pieces'][$piece_id]['color']
                && in_array($game_data['pieces'][$piece_on_square]['type'], ["king", "warriorking"])
            ) {
                unset($squares_array[$index]);
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    function removeIllegalCaptureMoves($piece_id, $possible_moves, $corresponding_captures, $game_data)
    {
        $result = array("possible_moves" => [], "corresponding_captures" => []);

        $move_count = count($possible_moves);

        for ($i = 0; $i < $move_count; $i++) {
            if ($this->isCapturingMoveLegal($piece_id, $corresponding_captures[$i], $game_data)) {
                $result['possible_moves'][] = $possible_moves[$i];
                $result['corresponding_captures'][] = $corresponding_captures[$i];
            }
        }

        return $result;
    }

    function isCapturingMoveLegal($cap_id, $capture_squares, $game_data)
    {
        foreach ($capture_squares as $capture_square) {
            $piece_on_square = $game_data['squares'][$capture_square[0]][$capture_square[1]]['def_piece'];

            if ($piece_on_square != null) {
                if ($game_data['pieces'][$piece_on_square]['type'] === "ghost") {
                    return false;
                }

                if (
                    in_array($game_data['pieces'][$piece_on_square]['type'], ["king", "warriorking"])
                    && $game_data['pieces'][$piece_on_square]['color'] === $game_data['pieces'][$cap_id]['color']
                ) {
                    return false;
                }

                if (
                    $game_data['pieces'][$piece_on_square]['type'] === "nemesis"
                    && !in_array($game_data['pieces'][$cap_id]['type'], ["king", "warriorking"])
                ) {
                    return false;
                }

                if ($game_data['pieces'][$piece_on_square]['type'] === "elephant") {
                    $capturing_piece_location = array((int) $game_data['pieces'][$cap_id]['x'], (int) $game_data['pieces'][$cap_id]['y']);

                    if (abs($capturing_piece_location[0] - $capture_square[0]) > 2 || abs($capturing_piece_location[1] - $capture_square[1]) > 2) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    // Takes an array of squares and removes any which do not correspond to a currently available capture for the pawn with id $piece_id
    function removeUnavailablePawnAttacks($piece_id, $possible_attacks, $game_data)
    {
        foreach ($possible_attacks as $index => $possible_attack) {
            $piece_on_square = $game_data['squares'][$possible_attack[0]][$possible_attack[1]]['def_piece'];

            // If the square is empty and there is no en passant available there, remove this attack from the array
            if ($piece_on_square === null) {
                $piece_adjacent = $game_data['squares'][$possible_attack[0]][$game_data['pieces'][$piece_id]['y']]['def_piece'];

                if (
                    $piece_adjacent === null
                    || $game_data['pieces'][$piece_adjacent]['color'] === $game_data['pieces'][$piece_id]['color']
                    || $game_data['pieces'][$piece_adjacent]['en_passant_vulnerable'] === "0"
                ) {
                    unset($possible_attacks[$index]);
                }
            }
        }
        $possible_attacks = array_values($possible_attacks);

        return $possible_attacks;
    }

    // Takes an array of possible moves and returns the same array but with any options removed that would leave the player's own king in check 
    function removeSelfChecks($moving_piece_id, $friendly_king_ids, $enemy_attacks, $moves_and_captures, $game_data)
    {
        $possible_moves = $moves_and_captures['possible_moves'];
        $corresponding_captures = $moves_and_captures['corresponding_captures'];

        $move_piece_location = array((int)$game_data['pieces'][$moving_piece_id]['x'], (int)$game_data['pieces'][$moving_piece_id]['y']);
        $move_piece_type = $game_data['pieces'][$moving_piece_id]['type'];

        $enemies_attacking_move_piece_location = $enemy_attacks['attacked_squares'][$move_piece_location[0]][$move_piece_location[1]];

        $king_locations = array();
        $enemies_attacking_king_locations = array();

        $attackers_to_recheck = array();

        foreach ($friendly_king_ids as $friendly_king_id) {
            $king_locations[$friendly_king_id] = array((int)$game_data['pieces'][$friendly_king_id]['x'], (int)$game_data['pieces'][$friendly_king_id]['y']);
        }

        foreach ($king_locations as $king_id => $king_location) {
            $enemies_attacking_king_locations[$king_id]['attacking'] = $enemy_attacks['attacked_squares'][$king_location[0]][$king_location[1]];
            $enemies_attacking_king_locations[$king_id]['semi_attacking'] = $enemy_attacks['semi_attacked_squares'][$king_location[0]][$king_location[1]];

            if (count($enemies_attacking_king_locations[$king_id]['attacking']) != 0) {
                $attackers_to_recheck = array_merge($attackers_to_recheck, $enemies_attacking_king_locations[$king_id]['attacking']);
            }
        }

        // If the moving piece is a king or warriorking, it can't move onto an attacked square
        if (in_array($move_piece_type, ["king", "warriorking"])) {
            foreach ($possible_moves as $move_index => $possible_move) {
                if (
                    $possible_move != $move_piece_location
                    && count($enemy_attacks['attacked_squares'][$possible_move[0]][$possible_move[1]]) != 0
                ) {
                    unset($possible_moves[$move_index]);
                    unset($corresponding_captures[$move_index]);
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

                if (in_array($move_piece_type, ["pawn", "warriorking", "tiger", "elephant"])) {
                    foreach ($corresponding_captures[$move_index] as $capture_location) {
                        // If any pieces would be captured were this move made (by a piece which might not land where it captures)
                        if ($game_data['squares'][$capture_location[0]][$capture_location[1]]['def_piece'] != null) {
                            // Any enemy pieces which are attacking the capture location and semi-attacking this king MIGHT be attacking the king if this move were made
                            $enemies_attacking_capture_location = $enemy_attacks['attacked_squares'][$capture_location[0]][$capture_location[1]];

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

                $simulated_move = $this->simulatePossibleMove($moving_piece_id, $possible_move, $corresponding_captures[$move_index], $game_data);
                $all_piece_data_sim = $simulated_move['all_piece_data_sim'];
                $board_state_sim = $simulated_move['board_state_sim'];

                foreach ($friendly_king_ids as $friendly_king_id) {
                    $king_location_sim = array((int)$all_piece_data_sim[$friendly_king_id]['x'], (int)$all_piece_data_sim[$friendly_king_id]['y']);

                    if ($this->arePiecesAttackingSquare($attackers_to_recheck_copy, $king_location_sim, array("pieces" => $all_piece_data_sim, "squares" => $board_state_sim))) {
                        unset($possible_moves[$move_index]);
                        unset($corresponding_captures[$move_index]);
                        break;
                    }
                }
            }
        }

        return array_values($possible_moves);
    }

    function simulatePossibleMove($piece_id, $possible_move, $capture_squares_for_this_move, $game_data)
    {
        // Starting location of the moving piece
        $piece_starting_x = $game_data['pieces'][$piece_id]['x'];
        $piece_starting_y = $game_data['pieces'][$piece_id]['y'];

        // Remove the moving piece from its starting location
        $game_data['squares'][$piece_starting_x][$piece_starting_y]['def_piece'] = null;

        // Set as captured any pieces which would be captured in this move
        foreach ($capture_squares_for_this_move as $capture_square) {
            $piece_on_cap_square = $game_data['squares'][$capture_square[0]][$capture_square[1]]['def_piece'];

            if ($piece_on_cap_square != null) {
                $game_data['squares'][$capture_square[0]][$capture_square[1]]['def_piece'] = null;
                $game_data['pieces'][$piece_on_cap_square]['captured'] = "1";

                if ($game_data['pieces'][$piece_id]['type'] === "tiger") {
                    $possible_move[0] = $piece_starting_x;
                    $possible_move[1] = $piece_starting_y;
                }
            }
        }

        // Set the updated location of the moving piece
        $game_data['squares'][$possible_move[0]][$possible_move[1]]['def_piece'] = $piece_id;
        $game_data['pieces'][$piece_id]['x'] = $possible_move[0];
        $game_data['pieces'][$piece_id]['y'] = $possible_move[1];

        return array("all_piece_data_sim" => $game_data['pieces'], "board_state_sim" => $game_data['squares']);
    }

    // Returns true if any of the pieces specified in $piece_ids are attacking $square, else false
    function arePiecesAttackingSquare($piece_ids, $square, $game_data)
    {
        foreach ($piece_ids as $piece_id) {
            if ($game_data['pieces'][$piece_id]['captured'] === "0") {
                $attacking_move_squares = $this->getAttackingMoveSquares($piece_id, $game_data)['attacking_squares'];

                if (in_array($game_data['pieces'][$piece_id]['type'], ["pawn", "nemesispawn"])) {
                    $attacking_move_squares = $this->removeUnavailablePawnAttacks($piece_id, $attacking_move_squares, $game_data);
                }

                $corresponding_captures = $this->getCorrespondingCaptures($piece_id, $attacking_move_squares, $game_data);
                $moves_and_captures = $this->removeIllegalCaptureMoves($piece_id, $attacking_move_squares, $corresponding_captures, $game_data);
                $attacking_move_squares = $moves_and_captures['possible_moves'];
                $corresponding_captures = $moves_and_captures['corresponding_captures'];

                foreach (array_keys($attacking_move_squares) as $attacking_move_index) {
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

    // Replaces content of legal_moves db table with data provided. Returns number of legal moves
    function replaceLegalMoves($all_legal_moves)
    {
        self::DbQuery("DELETE FROM legal_moves");

        $moves = array();

        $counter = 0;
        foreach ($all_legal_moves as $piece_id => $moves_for_piece) {
            foreach ($moves_for_piece as $move_square) {
                $moves[] = "('$counter','$piece_id','$move_square[0]','$move_square[1]')";
                $counter++;
            }
        }

        if ($counter > 0) {
            self::DbQuery("INSERT INTO legal_moves (move_id, moving_piece_id, x, y) VALUES " . implode(',', $moves));
        }

        self::notifyAllPlayers("updateLegalMovesTable", "", array("moves_added" => $all_legal_moves));

        return $counter;
    }

    function getCostToDuel($cap_id, $def_id, $pieces)
    {
        $cap_piece_rank = $this->piece_ranks[$pieces[$cap_id]['type']];
        $def_piece_rank = $this->piece_ranks[$pieces[$def_id]['type']];
        return ($cap_piece_rank > $def_piece_rank) ? 1 : 0;
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

    // TODO: Look to improve further
    function resolveNextCapture($both_cap, $game_data = [])
    {
        if (count($game_data) === 0) {
            $game_data['pieces'] = self::getCollectionFromDB("SELECT * FROM pieces");
            $game_data['squares'] = $this->getSquaresData($game_data['pieces']);
            $game_data['cap_q'] = self::getCollectionFromDB("SELECT * FROM capture_queue");
        }

        $min_cq_id = min(array_keys($game_data['cap_q']));

        $cap_id = $this->getGameStateValue('cap_id');
        $def_id = $game_data['squares'][(int) $game_data['cap_q'][$min_cq_id]['x']][(int) $game_data['cap_q'][$min_cq_id]['y']]['def_piece'];

        $same_color = ($game_data['pieces'][$def_id]['color'] === $game_data['pieces'][$cap_id]['color']);

        $pieces_to_cap = array($def_id);

        if ($both_cap) {
            $pieces_to_cap[] = $cap_id;
            $this->setGameStateValue('cap_id', 0);
            self::DbQuery("DELETE FROM capture_queue");
        } else {
            self::DbQuery("DELETE FROM capture_queue WHERE cq_id = '$min_cq_id'");

            if (count($game_data['cap_q']) === 1) {
                self::DbQuery("UPDATE pieces SET capturing = 0 WHERE piece_id = '$cap_id'");

                $this->setGameStateValue('cap_id', 0);

                self::notifyAllPlayers(
                    "updateAllPieceData",
                    "",
                    array(
                        "piece_id" => $cap_id,
                        "values_updated" => array("capturing" => "0")
                    )
                );
            }
        }

        foreach ($pieces_to_cap as $id) {
            self::DbQuery("UPDATE pieces SET captured = 1, capturing = 0 WHERE piece_id = '$id'");

            self::notifyAllPlayers(
                "updateAllPieceData",
                "",
                array(
                    "piece_id" => $id,
                    "values_updated" => array("captured" => "1", "capturing" => 0)
                )
            );

            if (in_array($game_data['pieces'][$id]['type'], ["pawn", "nemesispawn"]) && !$same_color) {
                // Player with the other color gets a stone
                $other_color = ($game_data['pieces'][$id]['color'] === "000000") ? "ffffff" : "000000";
                $this->updateStones($other_color, 1);
            }
        }
    }

    function resolveCastle($king_id, $pieces)
    {
        $king_x = $pieces[$king_id]['x'];
        $king_y = $pieces[$king_id]['y'];
        $squares = $this->getSquaresData($pieces);

        // Find the rook
        $rook_x = 8;
        $rook_dest_x = 6;
        if ($king_x == 3) {
            $rook_x = 1;
            $rook_dest_x = 4;
        }

        $castling_rook_id = $squares[$rook_x][$king_y]['def_piece'];

        // Update the rook's position in the pieces database table
        self::DbQuery("UPDATE pieces SET x = '$rook_dest_x' WHERE piece_id = '$castling_rook_id'");

        // Update the global variable to no longer have this piece castling
        $this->setGameStateValue('cas_id', 0);

        self::notifyAllPlayers(
            "updateAllPieceData",
            "",
            array(
                "piece_id" => $castling_rook_id,
                "values_updated" => array("location" => array($rook_dest_x, $king_y))
            )
        );

        $this->gamestate->nextState('whereNext');
    }

    // Returns true if active player has met the midline invasion win condition, else returns false
    function hasActivePlayerInvaded($pieces)
    {
        $king_ids = $this->getPlayerKingIds($this->getActivePlayerId());

        $invasion_direction = ($pieces[$king_ids['player_king_id']]['color'] === "000000") ? -1 : 1;

        foreach ($king_ids as $king_id) {
            if (($pieces[$king_id]['y'] - 4.5) * $invasion_direction < 0) {
                return false;
            }
        }
        return true;
    }

    function processCapture($cap_id, $pieces)
    {
        // 50 move rule
        $this->setGameStateValue('fifty_counter', 51);

        $squares = $this->getSquaresData($pieces);
        $cap_q = self::getCollectionFromDB("SELECT * FROM capture_queue");

        // The first square in the capture queue
        $min_cq_id = min(array_keys($cap_q));
        $cap_square = array((int) $cap_q[$min_cq_id]['x'], (int) $cap_q[$min_cq_id]['y']);

        $def_id = $squares[$cap_square[0]][$cap_square[1]]['def_piece'];

        $this->activeNextPlayer();

        $def_player_id = $this->getActivePlayerId();
        $defender_stones = self::getUniqueValueFromDB("SELECT player_stones FROM player WHERE player_id = '$def_player_id'");

        // If capturing (warrior)king or defending friendly or can't afford duel, capturing proceeds with no duel
        if (
            in_array($pieces[$cap_id]['type'], ["king", "warriorking"])
            || $pieces[$def_id]['color'] === $pieces[$cap_id]['color']
            || $defender_stones <= $this->getCostToDuel($cap_id, $def_id, $pieces)
        ) {
            $this->activeNextPlayer();
            $this->resolveNextCapture(false, array("pieces" => $pieces, "squares" => $squares, "cap_q" => $cap_q));
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

    function getDuelData()
    {
        $pieces = self::getCollectionFromDB("SELECT * FROM pieces");
        $squares = $this->getSquaresData($pieces);
        $cap_q = self::getCollectionFromDB("SELECT * FROM capture_queue");

        $min_cq_id = min(array_keys($cap_q));

        $cap_id = $this->getGameStateValue('cap_id');
        $def_id = $squares[(int) $cap_q[$min_cq_id]['x']][(int) $cap_q[$min_cq_id]['y']]['def_piece'];

        return array(
            "capID" => (string) $cap_id,
            "defID" => (string) $def_id,
            "costToDuel" => $this->getCostToDuel($cap_id, $def_id, $pieces)
        );
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

    function movePiece($target_x, $target_y, $moving_piece_id)
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
        $pieces = self::getCollectionFromDB("SELECT * FROM pieces");

        // Check that the player is trying to move their own piece
        if ($pieces[$moving_piece_id]['color'] != $player_color) {
            return;
        }

        $move_match = self::getUniqueValueFromDB(
            "SELECT COUNT(*) FROM legal_moves 
            WHERE moving_piece_id = '$moving_piece_id'
            AND x = '$target_x'
            AND y = '$target_y'"
        );

        // If the attempted move is found in legal_moves table
        if ($move_match) {
            // More information
            $squares = $this->getSquaresData($pieces);

            // If the moving piece is a castling king, set cas_id
            if ($pieces[$moving_piece_id]['type'] === "king" && abs($pieces[$moving_piece_id]['x'] - $target_x) === 2) {
                $this->setGameStateValue('cas_id', $moving_piece_id);
            } else if (in_array($pieces[$moving_piece_id]['type'],  ["pawn", "nemesispawn"])) {
                // 50 move rule
                $this->setGameStateValue('fifty_counter', 51);

                // If the moving piece is a pawn reaching the enemy backline, set pro_id
                $backline = ($pieces[$moving_piece_id]['color'] === "000000") ? "1" : "8";
                if ($target_y === $backline) {
                    $this->setGameStateValue('pro_id', $moving_piece_id);
                }

                // If the moving piece is a pawn making its initial double move, set its en_passant_vulnerable value to 2
                if (abs($pieces[$moving_piece_id]['y'] - $target_y) === 2) {
                    $pieces_values_to_set['en_passant_vulnerable'] = "2";
                }
            }

            $corresponding_captures = $this->getCorrespondingCaptures(
                $moving_piece_id,
                [[(int)$target_x, (int)$target_y]],
                array("pieces" => $pieces, "squares" => $squares)
            )[0];

            $capture_queue = array();

            $counter = 0;
            foreach ($corresponding_captures as $corresponding_capture_square) {
                if ($squares[$corresponding_capture_square[0]][$corresponding_capture_square[1]]['def_piece'] != null) {
                    if ($pieces[$moving_piece_id]['type'] === "tiger") {
                        $target_x = $pieces[$moving_piece_id]['x'];
                        $target_y = $pieces[$moving_piece_id]['y'];
                    }

                    $pieces_values_to_set['capturing'] = "1";

                    $counter++;
                    $capture_queue[] = "('$counter','$corresponding_capture_square[0]','$corresponding_capture_square[1]')";
                }
            }

            $sql = "SELECT moves_made FROM pieces WHERE piece_id = '$moving_piece_id'";
            $pieces_values_to_set['moves_made'] = (string) (self::getUniqueValueFromDB($sql) + 1);

            $pieces_values_to_set_notif = $pieces_values_to_set;
            $pieces_values_to_set_notif['location'] = array((int)$target_x, (int)$target_y);

            $pieces_values_to_set['x'] = $target_x;
            $pieces_values_to_set['y'] = $target_y;

            if (count($capture_queue) != 0) {
                $sql = "INSERT INTO capture_queue (cq_id,x,y) VALUES ";
                $sql .= implode(',', $capture_queue);
                self::DbQuery($sql);

                $this->setGameStateValue('cap_id', $moving_piece_id);
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
        } else
            throw new BgaSystemException("Illegal move");
    }

    function passKingMove()
    {
        // Check this action is allowed according to the game state
        $this->checkAction('passKingMove');

        $this->gamestate->nextState('whereNext');
    }

    function acceptDuel()
    {
        $this->checkAction('acceptDuel');

        // Pay the cost to duel
        $cost_to_duel = $this->getDuelData()['costToDuel'];
        $this->updateStones($this->getCurrentPlayerColor(), $cost_to_duel * -1);

        $this->gamestate->nextState('duelBidding');
    }

    function rejectDuel()
    {
        $this->checkAction('rejectDuel');

        $this->resolveNextCapture(false);

        $this->gamestate->nextState('nextPlayer');
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
        }
    }

    function destroyStone()
    {
        $this->checkAction('destroyStone');

        $choosing_color = $this->getCurrentPlayerColor();
        $other_color = ($choosing_color === "000000") ? "ffffff" : "000000";
        $current_stones = self::getUniqueValueFromDB("SELECT player_stones FROM player WHERE player_color = '$other_color'");

        if ($current_stones == 0) {
            throw new BgaSystemException("Minimum stones reached");
        } else {
            $this->updateStones($other_color, -1);

            $this->gamestate->nextState('whereNext');
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

        $promoting_pawn_id = $this->getGameStateValue('pro_id');

        self::DbQuery("UPDATE pieces SET type = '$chosen_promotion' WHERE piece_id = '$promoting_pawn_id'");

        $this->setGameStateValue('pro_id', 0);

        self::notifyAllPlayers("updateAllPieceData", "", array(
            "piece_id" => $promoting_pawn_id,
            "values_updated" => array("type" => $chosen_promotion)
        ));

        $this->gamestate->nextState('whereNext');
    }

    function offerDraw()
    {
        $this->checkAction('offerDraw');
        $this->gamestate->nextState('offerDraw');
    }

    function acceptDraw()
    {
        $this->checkAction('acceptDraw');
        $this->gamestate->nextState('gameEnd');
    }

    function rejectDraw()
    {
        $this->checkAction('rejectDraw');
        $this->gamestate->nextState('drawRejected');
    }

    function concedeGame()
    {
        $this->checkAction('concedeGame');
        $this->gamestate->nextState('concedeGame');
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

    function argDuelOffer()
    {
        return $this->getDuelData();
    }

    function argDuelBidding()
    {
        return $this->getDuelData();
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
        $sql = "INSERT INTO pieces (piece_id,color,type,x,y) VALUES ";
        $sql_values = array();

        $all_king_ids = array();

        // For each player
        foreach ($all_datas['players'] as $player_data) {
            $player_color = $player_data['color'];

            $piece_id_offset = 1;
            $y_values = [1, 2];
            // If this is for black, change the y values and ids to be correct for this player
            if ($player_color === "000000") {
                $piece_id_offset = 17;
                $y_values = [8, 7];
            }

            // For each piece in their army's layout
            foreach ($all_datas['all_armies_layouts'][$player_data['army']] as $piece_index => $piece_type) {
                $x = ($piece_index % 8) + 1;
                $y = $y_values[floor($piece_index / 8)];
                $piece_id = $piece_id_offset + $piece_index;

                // Add the piece's data to be sent to the database and in a notification
                $sql_values[] = "('$piece_id','$player_color','$piece_type','$x','$y')";
                $pieces_table_update_information[] = array($piece_id, $player_color, $piece_type, $x, $y);

                if (in_array($piece_type, ["king", "warriorking"])) {
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

        $this->activeNextPlayer();
        $this->activeNextPlayer();
        $this->gamestate->nextState('whereNext');
    }

    function stWhereNext()
    {
        $pieces = self::getCollectionFromDB("SELECT * FROM pieces");

        // Check for midline invasion win condition
        if ($this->hasActivePlayerInvaded($pieces)) {
            $this->activePlayerWins();
            return;
        }

        // Check for a pawn promoting
        if ($this->getGameStateValue('pro_id') != 0) {
            $this->gamestate->nextState('pawnPromotion');
            return;
        }

        // Check for a piece capturing
        $cap_id = $this->getGameStateValue('cap_id');
        if ($cap_id != 0) {
            $this->processCapture($cap_id, $pieces);
            return;
        }

        // Check for a king castling
        $cas_id = $this->getGameStateValue('cas_id');
        if ($cas_id != 0) {
            $this->resolveCastle($cas_id, $pieces);
            return;
        }

        // Now the state is resolved

        $squares = $this->getSquaresData($pieces);

        // Give the active player a king turn if available
        $active_player_id = $this->getActivePlayerId();

        if (self::getUniqueValueFromDB("SELECT player_king_move_available FROM player WHERE player_id = '$active_player_id'")) {
            self::DbQuery("UPDATE player SET player_king_move_available = 0 WHERE player_id = '$active_player_id'");

            $kings = array_values($this->getPlayerKingIds($active_player_id));
            $king_moves = $this->getAllMovesForPieces($kings, $active_player_id, array("pieces" => $pieces, "squares" => $squares));

            if ($this->replaceLegalMoves($king_moves)) {
                $this->gamestate->nextState('playerKingMove');
                return;
            }
        }

        // Now the turn can pass to the other player

        // 50 move rule
        // Reduce fifty_counter by 1 at the end of each black player's turn. Reset to 51 when moving a pawn or capturing. If it reaches 0, draw
        if ($this->getPlayerColorById($active_player_id) === "000000") {
            $fifty_counter = $this->getGameStateValue('fifty_counter') - 1;
            if ($fifty_counter === 0) {
                $this->gamestate->nextState('gameEnd');
            }
            $this->setGameStateValue('fifty_counter', $fifty_counter);
        }

        // Tick down the en_passant_vulnerable value by 1 at the end of each player's turn so an en passant capture is only available for one turn
        foreach ($pieces as $piece_id => $piece_data) {
            if ($piece_data['type'] === "pawn" && $piece_data['en_passant_vulnerable'] != "0") {
                $piece_data['en_passant_vulnerable'] -= 1;
                self::DbQuery("UPDATE pieces SET en_passant_vulnerable = {$piece_data['en_passant_vulnerable']} WHERE piece_id = '$piece_id'");

                self::notifyAllPlayers("updateAllPieceData", "", array(
                    "piece_id" => $piece_id,
                    "values_updated" => array("en_passant_vulnerable" => (string) $piece_data['en_passant_vulnerable'])
                ));
            }
        }

        // Activate the next player and generate their legal moves. If they have none, they lose.
        $this->activeNextPlayer();

        $active_player_id = $this->getActivePlayerId();
        $active_player_color = $this->getPlayerColorById($active_player_id);
        $act_pieces = self::getObjectListFromDB("SELECT piece_id FROM pieces WHERE color = '$active_player_color' AND captured = 0", true);

        $all_legal_moves = $this->getAllMovesForPieces($act_pieces, $active_player_id, array("pieces" => $pieces, "squares" => $squares));

        if (!$this->replaceLegalMoves($all_legal_moves)) {
            $this->activeNextPlayer();
            $this->activePlayerWins();
            return;
        }

        $army_name = self::getUniqueValueFromDB("SELECT player_army FROM player WHERE player_id = '$active_player_id'");

        if ($army_name === "twokings") {
            self::DbQuery("UPDATE player SET player_king_move_available = 1 WHERE player_id = '$active_player_id'");
        }

        // String describing the current position
        $pos_string = $active_player_color[0];
        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 8; $j++) {
                $pid = $squares[$i][$j]['def_piece'];

                if ($pid === null) {
                    $pos_string .= "-";
                    continue;
                }

                $pos_string .= $this->type_code[$pieces[$pid]['type']];
                $pos_string .= $pieces[$pid]['color'][0];

                if (
                    $pieces[$pid]['type'] === "pawn"
                    || ($pieces[$pid]['type'] === "king"
                        && self::getUniqueValueFromDB("SELECT player_army FROM player WHERE player_color = '{$pieces[$pid]['color']}'") === "classic")
                ) {
                    $owner_id = self::getUniqueValueFromDB("SELECT player_id FROM player WHERE player_color = '{$pieces[$pid]['color']}'");

                    $piece_moves = $this->getAllMovesForPieces(array($pid), $owner_id, array("pieces" => $pieces, "squares" => $squares))[$pid];
                    $pos_string .= count($piece_moves);
                }
            }
        }

        // Check for threefold repetition
        self::DbQuery("INSERT INTO pos_history (pos_string) VALUES ('$pos_string')");
        $pos_reps = self::getUniqueValueFromDB("SELECT COUNT(*) FROM pos_history WHERE pos_string = '$pos_string'");
        if ($pos_reps == 3) {
            $this->gamestate->nextState('gameEnd');
        }

        $this->gamestate->nextState('playerMove');
    }

    function stResolveDuel()
    {
        // Get bid amounts and the piece colors from the database
        $player_bids = self::getCollectionFromDB("SELECT player_color, player_bid FROM player", true);
        $cap_piece_color = self::getUniqueValueFromDB("SELECT color FROM pieces WHERE capturing = '1'");
        $other_color = ($cap_piece_color === "000000") ? "ffffff" : "000000";

        // Determine outcome of duel and resolve capture
        $both_cap = ($player_bids[$cap_piece_color] < $player_bids[$other_color]) ? true : false;

        // Update bids database
        self::DbQuery("UPDATE player SET player_bid = null");

        // Update player stones in database and notify
        foreach ($player_bids as $player_color => $bid) {
            $this->updateStones($player_color, $bid * -1);
        }

        $this->resolveNextCapture($both_cap);

        $this->activeNextPlayer();

        // If both bid 0 stones, the attacker can choose to gain 1 stone or destroy 1 of the defender's stones
        if ($player_bids[$cap_piece_color] == 0 && $player_bids[$other_color] == 0) {
            $this->gamestate->nextState('calledBluff');
            return;
        }

        // Transition to whereNext
        $this->gamestate->nextState('whereNext');
    }

    function stNextPlayer()
    {
        $this->activeNextPlayer();
        $this->gamestate->nextState('whereNext');
    }

    function stOfferDraw()
    {
        $this->activeNextPlayer();
        $this->gamestate->nextState('drawOffer');
    }

    function stDrawRejected()
    {
        $this->activeNextPlayer();
        $this->gamestate->nextState('playerMove');
    }

    function stConcedeGame()
    {
        $this->activeNextPlayer();
        $this->activePlayerWins();
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
