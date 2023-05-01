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
require_once('modules/CHSMoves.php');
require_once('modules/constants.inc.php');

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
            "fifty_counter" => 13,
            "last_player_move_piece_id" => 14,
            "last_king_move_piece_id" => 15,
            "ruleset_version" => 100
        ));

        $this->moves = new CHSMoves($this);
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
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue('fifty_counter', 51);
        self::setGameStateInitialValue('last_player_move_piece_id', 0);
        self::setGameStateInitialValue('last_king_move_piece_id', 0);

        // Init game statistics
        self::initStat("table", "end_condition", 0);
        self::initStat("table", "moves_number", 0);

        self::initStat("player", "army", 0);
        self::initStat("player", "enemies_captured", 0);
        self::initStat("player", "friendlies_captured", 0);

        if ($this->getGameStateValue('ruleset_version') == 2) {
            self::initStat("player", "duels_initiated", 0);
            self::initStat("player", "stones_bid", 0);
            self::initStat("player", "duel_captures", 0);
            self::initStat("player", "bluffs_called", 0);
        }

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
        $result['piece_tooltips'] = $this->piece_tooltips;
        $result['army_tooltips'] = $this->army_tooltips;

        $result['last_move_piece_ids'] = array(
            "player_move" => $this->getGameStateValue('last_player_move_piece_id'),
            "king_move" => $this->getGameStateValue('last_king_move_piece_id')
        );

        $result['ruleset_version'] = $this->getGameStateValue('ruleset_version');

        $result['constants'] = get_defined_constants(true)['user'];

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
        $moves_number = self::getStat("moves_number");
        return $moves_number * 3;
        // $data = self::getObjectFromDB("SELECT SUM(moves_made) AS moves, SUM(captured) AS caps FROM pieces");
        // return $data['moves'] + $data['caps'];
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
        player_king_move_available king_move_available, player_army army FROM player";
        return self::getCollectionFromDb($sql);
    }

    function getPlayerKingIds($player_id)
    {
        $player_color = $this->getPlayerColorById($player_id);
        return self::getObjectListFromDB("SELECT piece_id FROM pieces WHERE color = '$player_color' AND type IN ('king', 'warriorking')", true);
    }

    function getPlayerArmy($player_color)
    {
        return self::getUniqueValueFromDB("SELECT player_army FROM player WHERE player_color = '$player_color'");
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
            if ($piece_data['state'] == CAPTURED) {
                continue;
            }

            if ($piece_data['state'] == CAPTURING) {
                $squares[$piece_data['x']][$piece_data['y']]['cap_piece'] = $piece_id;
            } else {
                $squares[$piece_data['x']][$piece_data['y']]['def_piece'] = $piece_id;
            }
        }

        return $squares;
    }

    // Replaces content of legal_moves db table with data provided. Returns number of legal moves
    function replaceLegalMoves($all_legal_moves)
    {
        self::DbQuery("DELETE FROM legal_moves");

        $moves = array();

        $counter = 0;
        foreach ($all_legal_moves as $piece_id => $moves_for_piece) {
            foreach ($moves_for_piece as $move) {
                $cap_squares = json_encode($move['cap_squares']);
                $moves[] = "('$counter','$piece_id','{$move['x']}','{$move['y']}','$cap_squares')";
                $counter++;
            }
        }

        if ($counter > 0) {
            self::DbQuery("INSERT INTO legal_moves (move_id, moving_piece_id, x, y, cap_squares) VALUES " . implode(',', $moves));
        }

        self::notifyAllPlayers("updateLegalMovesTable", "", array("moves_added" => $all_legal_moves));

        return $counter;
    }

    function getPositionString($active_color, $armies, $all_legal_moves, $game_data)
    {
        $pos_string = $active_color[0];

        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 8; $j++) {
                $pid = $game_data['squares'][$i][$j]['def_piece'];

                if ($pid === null) {
                    $pos_string .= "-";
                    continue;
                }

                $pos_string .= $this->type_code[$game_data['pieces'][$pid]['type']];
                $pos_string .= $game_data['pieces'][$pid]['color'][0];

                if (in_array($game_data['pieces'][$pid]['type'], ["pawn", "nemesispawn"])) {
                    if ($game_data['pieces'][$pid]['color'] == $active_color) {
                        $pos_string .= count($all_legal_moves[$pid]);
                    } else {
                        $pos_string .= count($this->moves->getAvailableEnPassants($pid, $game_data));
                    }
                } else if (
                    $game_data['pieces'][$pid]['type'] == "king"
                    && $armies[$game_data['pieces'][$pid]['color']] == "classic"
                ) {
                    if ($game_data['pieces'][$pid]['color'] == $active_color) {
                        $pos_string .= count($all_legal_moves[$pid]);
                    } else {
                        foreach ([-2, -1, 0, 1, 2] as $dx) {
                            $game_data['squares'][$game_data['pieces'][$pid]['x'] + $dx][$game_data['pieces'][$pid]['y']]['checks'] = [];
                        }
                        $pos_string .= count($this->moves->getAvailableCastleMoves($pid, $game_data));
                    }
                }
            }
        }

        return $pos_string;
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
                "values_updated" => array("stones" => $new_stones)
            )
        );
    }

    // TODO: Look to improve further
    function resolveNextCapture($both_cap, $game_data = [])
    {
        if (count($game_data) == 0) {
            $game_data['pieces'] = self::getCollectionFromDB("SELECT * FROM pieces");
            $game_data['squares'] = $this->getSquaresData($game_data['pieces']);
            $game_data['cap_q'] = self::getCollectionFromDB("SELECT * FROM capture_queue");
        }

        $player_ids = self::getCollectionFromDB("SELECT player_color, player_id FROM player", true);

        $min_cq_id = min(array_keys($game_data['cap_q']));

        $cap_id = self::getUniqueValueFromDB("SELECT piece_id FROM pieces WHERE state = " . CAPTURING, true);
        $def_id = $game_data['squares'][$game_data['cap_q'][$min_cq_id]['x']][$game_data['cap_q'][$min_cq_id]['y']]['def_piece'];

        $same_color = ($game_data['pieces'][$def_id]['color'] == $game_data['pieces'][$cap_id]['color']);
        $stat = ($same_color) ? "friendlies_captured" : "enemies_captured";

        $pieces_to_cap = array($def_id);

        if ($both_cap) {
            $pieces_to_cap[] = $cap_id;
            self::DbQuery("DELETE FROM capture_queue");
            self::incStat(1, "duel_captures", $player_ids[$game_data['pieces'][$def_id]['color']]);
        } else {
            self::DbQuery("DELETE FROM capture_queue WHERE cq_id = '$min_cq_id'");

            if (count($game_data['cap_q']) == 1) {
                self::DbQuery("UPDATE pieces SET state = " . NORMAL . " WHERE piece_id = '$cap_id'");

                self::notifyAllPlayers(
                    "updateAllPieceData",
                    "",
                    array(
                        "piece_id" => $cap_id,
                        "values_updated" => array("state" => NORMAL)
                    )
                );
            }
        }

        foreach ($pieces_to_cap as $id) {
            self::DbQuery("UPDATE pieces SET state = " . CAPTURED . " WHERE piece_id = '$id'");

            $capping_id = ($id == $cap_id) ? $def_id : $cap_id;

            self::incStat(1, $stat, $player_ids[$game_data['pieces'][$capping_id]['color']]);

            self::notifyAllPlayers(
                "updateAllPieceData",
                clienttranslate('${logpiece_cap} captures ${logpiece_def}'),
                array(
                    "piece_id" => $id,
                    "values_updated" => array("state" => CAPTURED),
                    "logpiece_cap" => $game_data['pieces'][$capping_id]['color'] . "_" . $game_data['pieces'][$capping_id]['type'],
                    "logpiece_def" => $game_data['pieces'][$id]['color'] . "_" . $game_data['pieces'][$id]['type']
                )
            );

            if (in_array($game_data['pieces'][$id]['type'], ["pawn", "nemesispawn"]) && !$same_color) {
                // Player with the other color gets a stone
                $other_color = ($game_data['pieces'][$id]['color'] == "000000") ? "ffffff" : "000000";
                $this->updateStones($other_color, 1);
            }
        }
    }

    // Returns true if active player has met the midline invasion win condition, else returns false
    function hasActivePlayerInvaded($pieces)
    {
        $king_ids = $this->getPlayerKingIds($this->getActivePlayerId());

        $invasion_direction = ($pieces[$king_ids[0]]['color'] == "000000") ? -1 : 1;

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
        $cap_square = array($cap_q[$min_cq_id]['x'], $cap_q[$min_cq_id]['y']);

        $def_id = $squares[$cap_square[0]][$cap_square[1]]['def_piece'];

        $this->activeNextPlayer();

        $def_player_id = $this->getActivePlayerId();
        $defender_stones = self::getUniqueValueFromDB("SELECT player_stones FROM player WHERE player_id = '$def_player_id'");

        // If not ruleset version 2/capturing (warrior)king/defending friendly/can't afford duel: capturing proceeds with no duel
        if (
            $this->getGameStateValue('ruleset_version') != 2
            || in_array($pieces[$cap_id]['type'], ["king", "warriorking"])
            || $pieces[$def_id]['color'] == $pieces[$cap_id]['color']
            || $defender_stones <= $this->getCostToDuel($cap_id, $def_id, $pieces)
        ) {
            $this->activeNextPlayer();
            $this->resolveNextCapture(false, array("pieces" => $pieces, "squares" => $squares, "cap_q" => $cap_q));
            $this->gamestate->nextState('whereNext');
        } else {
            $this->gamestate->nextState('duelOffer');
        }
    }

    function activePlayerWins($condition)
    {
        $active_player_id = $this->getActivePlayerId();
        self::DbQuery("UPDATE player SET player_score = 1 WHERE player_id = '$active_player_id'");

        self::setStat($condition, "end_condition");

        self::notifyAllPlayers(
            "message",
            clienttranslate('${player_name} wins by ${condition}'),
            array(
                "player_name" => self::getActivePlayerName(),
                "condition" => $this->end_conditions[$condition]
            )
        );

        $this->gamestate->nextState('gameEnd');
    }

    function getDuelData($pieces = null)
    {
        if ($pieces == null) {
            $pieces = self::getCollectionFromDB("SELECT * FROM pieces");
        }
        $squares = $this->getSquaresData($pieces);
        $cap_q = self::getCollectionFromDB("SELECT * FROM capture_queue");

        $min_cq_id = min(array_keys($cap_q));

        $cap_id = self::getUniqueValueFromDB("SELECT piece_id FROM pieces WHERE state = " . CAPTURING, true);
        $def_id = $squares[$cap_q[$min_cq_id]['x']][$cap_q[$min_cq_id]['y']]['def_piece'];

        return array(
            "capID" => $cap_id,
            "defID" => $def_id,
            "costToDuel" => $this->getCostToDuel($cap_id, $def_id, $pieces)
        );
    }

    // TODO: Optimise
    function rollXOffsets()
    {
        $x_offsets = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        $squares = [1, 2, 3, 4, 5, 6, 7, 8];

        // The 5 needed rolls
        $rolls = array();
        foreach ([4, 4, 6, 5, 4] as $max_roll) {
            $rolls[] = bga_rand(1, $max_roll);
        }

        // Move the black square bishop
        $bb_square_index = ($rolls[0] - 1) * 2;
        $x_offsets[2] = $squares[$bb_square_index] - 3;

        // Move the white square bishop
        $wb_square_index = ($rolls[1] * 2) - 1;
        $x_offsets[5] = $squares[$wb_square_index] - 6;

        unset($squares[$bb_square_index]);
        unset($squares[$wb_square_index]);
        $squares = array_values($squares);

        // Move the queen
        $square_index = $rolls[2] - 1;
        $x_offsets[3] = $squares[$square_index] - 4;
        unset($squares[$square_index]);
        $squares = array_values($squares);

        // Move the first knight
        $square_index = $rolls[3] - 1;
        $x_offsets[1] = $squares[$square_index] - 2;
        unset($squares[$square_index]);
        $squares = array_values($squares);

        // Move the second knight
        $square_index = $rolls[4] - 1;
        $x_offsets[6] = $squares[$square_index] - 7;
        unset($squares[$square_index]);
        $squares = array_values($squares);

        // Move the remaining pieces
        $x_offsets[0] = $squares[0] - 1;
        $x_offsets[4] = $squares[1] - 5;
        $x_offsets[7] = $squares[2] - 8;

        return $x_offsets;
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

    function confirmArmy($army_name, $player_id = null)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('confirmArmy');

        // Check it's a valid army name according to the array in material.inc.php
        if (in_array($army_name, $this->all_army_names)) {
            // Get the id of the CURRENT player (there are multiple active players in armySelect)
            // In the BGA framework, the CURRENT player is the player who played the current player action (player who made the AJAX request)
            if ($player_id === null) {
                $player_id = $this->getCurrentPlayerId();
            }

            // Updates the current player's army in the database
            self::DbQuery("UPDATE player SET player_army = '$army_name' WHERE player_id = '$player_id'");
            self::setStat(array_search($army_name, $this->all_army_names), "army", $player_id);

            $opponent_active = self::getUniqueValueFromDB("SELECT player_is_multiactive FROM player WHERE player_id != '$player_id'");

            // If opponent is active, deactivate this player before the notification (status bar inconsistency otherwise)
            if ($opponent_active) {
                $this->gamestate->setPlayerNonMultiactive($player_id, 'boardSetup');
            }

            // Send notification
            self::notifyAllPlayers("confirmArmy", clienttranslate('${player_name} selects an army'), array(
                'player_id' => $player_id,
                'player_name' => $this->getPlayerNameById($player_id)
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

        // Get the capture squares for the attempted move
        $cap_squares = self::getUniqueValueFromDB(
            "SELECT cap_squares FROM legal_moves 
            WHERE moving_piece_id = '$moving_piece_id'
            AND x = '$target_x'
            AND y = '$target_y'"
        );

        // If the attempted move is not found in legal_moves table, throw an error
        if ($cap_squares == null) {
            throw new BgaSystemException("Illegal move");
            return;
        }

        // Array of values to set for the moving piece
        $pieces_values_to_set = array();

        // Special conditions for pawns
        if (in_array($pieces[$moving_piece_id]['type'], ["pawn", "nemesispawn"])) {
            // 50 move rule
            $this->setGameStateValue('fifty_counter', 51);

            // If the moving piece is a pawn reaching the enemy backline, set its state to promoting
            $backline = ($pieces[$moving_piece_id]['color'] == "000000") ? 1 : 8;
            if ($target_y == $backline) {
                $pieces_values_to_set['state'] = PROMOTING;
            }

            // If the moving piece is a pawn making its initial double move, set it as en passant vulnerable
            else if (abs($pieces[$moving_piece_id]['y'] - $target_y) == 2) {
                $pieces_values_to_set['state'] = EN_PASSANT_VULNERABLE;
            }
        }

        $squares = $this->getSquaresData($pieces);
        $cap_squares = json_decode($cap_squares);
        $capture_queue = array();

        // Add all occupied capture squares to the capture queue
        $counter = 0;
        foreach ($cap_squares as $square) {
            if ($squares[$square[0]][$square[1]]['def_piece'] !== null) {
                if ($pieces[$moving_piece_id]['type'] == "tiger") {
                    $target_x = $pieces[$moving_piece_id]['x'];
                    $target_y = $pieces[$moving_piece_id]['y'];
                }

                $counter++;
                $capture_queue[] = "('$counter','$square[0]','$square[1]')";
            }
        }

        if (count($capture_queue) != 0) {
            $sql = "INSERT INTO capture_queue (cq_id,x,y) VALUES ";
            $sql .= implode(',', $capture_queue);
            self::DbQuery($sql);

            // Only true if the piece is promoting
            if (isset($pieces_values_to_set['state'])) {
                $pieces_values_to_set['state'] = CAPTURING_AND_PROMOTING;
            } else {
                $pieces_values_to_set['state'] = CAPTURING;
            }
        }

        $sql = "SELECT moves_made FROM pieces WHERE piece_id = '$moving_piece_id'";
        $pieces_values_to_set['moves_made'] = self::getUniqueValueFromDB($sql) + 1;

        $state_name = $this->gamestate->state()['name'];

        if ($state_name == "playerMove") {
            $this->setGameStateValue("last_player_move_piece_id", $moving_piece_id);
            $this->setGameStateValue("last_king_move_piece_id", 0);
        } else {
            $this->setGameStateValue("last_king_move_piece_id", $moving_piece_id);
        }

        if ($this->getGameStateValue("last_player_move_piece_id") != $this->getGameStateValue("last_king_move_piece_id")) {
            $pieces_values_to_set['last_x'] = $pieces[$moving_piece_id]['x'];
            $pieces_values_to_set['last_y'] = $pieces[$moving_piece_id]['y'];
        }

        $pieces_values_to_set_notif = $pieces_values_to_set;
        $pieces_values_to_set_notif['location'] = array($target_x, $target_y);

        $pieces_values_to_set['x'] = $target_x;
        $pieces_values_to_set['y'] = $target_y;

        // Update pieces table for the moving piece
        $sql = "UPDATE pieces SET";
        foreach ($pieces_values_to_set as $column => $value) {
            $sql .= " $column = '$value',";
        }
        $sql = rtrim($sql, ',');
        $sql .= " WHERE piece_id = '$moving_piece_id'";
        self::DbQuery($sql);

        $msg = clienttranslate('${player_name}: ${logpiece}${square}');
        if (
            $target_x == $pieces[$moving_piece_id]['x']
            && $target_y == $pieces[$moving_piece_id]['y']
        ) {
            $msg = clienttranslate('${player_name}: ${logpiece} whirlwinds');
        }

        // Send notifications
        self::notifyAllPlayers(
            "updateAllPieceData",
            $msg,
            array(
                "piece_id" => $moving_piece_id,
                "values_updated" => $pieces_values_to_set_notif,
                "state_name" => $state_name,
                "player_name" => self::getActivePlayerName(),
                "logpiece" => $pieces[$moving_piece_id]['color'] . "_" . $pieces[$moving_piece_id]['type'],
                "square" => $this->files[$target_x] . $target_y
            )
        );

        self::notifyAllPlayers("clearSelectedPiece", "", array());

        // If the moving piece is a castling king, resolve the castle
        if ($pieces[$moving_piece_id]['type'] == "king" && abs($pieces[$moving_piece_id]['x'] - $target_x) == 2) {
            $dir = ($target_x - $pieces[$moving_piece_id]['x']) / abs($pieces[$moving_piece_id]['x'] - $target_x);

            for ($i = 1; $i < 5; $i++) {
                $x = $target_x + ($dir * $i);

                if ($squares[$x][$target_y]['def_piece'] !== null) {
                    $castling_rook_id = $squares[$x][$target_y]['def_piece'];

                    $rook_dest_x = $target_x - $dir;

                    self::DbQuery("UPDATE pieces SET x = '$rook_dest_x' WHERE piece_id = '$castling_rook_id'");

                    $rook_values_updated = array(
                        "location" => array($rook_dest_x, $target_y),
                        "last_x" => $x,
                        "last_y" => $target_y
                    );

                    self::notifyAllPlayers(
                        "updateAllPieceData",
                        clienttranslate('${player_name} castles: ${logpiece}${square}'),
                        array(
                            "piece_id" => $castling_rook_id,
                            "values_updated" => $rook_values_updated,
                            "player_name" => self::getActivePlayerName(),
                            "logpiece" => $pieces[$castling_rook_id]['color'] . "_" . $pieces[$castling_rook_id]['type'],
                            "square" => $this->files[$rook_dest_x] . $pieces[$castling_rook_id]['y']
                        )
                    );

                    break;
                }
            }
        }

        // Change player state
        $this->gamestate->nextState('whereNext');
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

        $pieces = self::getCollectionFromDB("SELECT * FROM pieces");
        $duel_data = $this->getDuelData($pieces);

        self::incStat(1, "duels_initiated", $this->getActivePlayerId());

        $msg = clienttranslate('${player_name}: ${logpiece_def} duels ${logpiece_cap}');

        if ($duel_data['costToDuel'] == 1) {
            // Pay the cost to duel
            $this->updateStones($this->getCurrentPlayerColor(), -1);
            $msg = clienttranslate('${player_name}: ${logpiece_def} duels ${logpiece_cap} (Pays 1 stone)');
        }

        self::notifyAllPlayers(
            "message",
            $msg,
            array(
                "player_name" => self::getActivePlayerName(),
                "logpiece_def" => $pieces[$duel_data['defID']]['color'] . "_" . $pieces[$duel_data['defID']]['type'],
                "logpiece_cap" => $pieces[$duel_data['capID']]['color'] . "_" . $pieces[$duel_data['capID']]['type']
            )
        );

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

            self::incStat($bid_amount, "stones_bid", $player_id);

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

            self::notifyAllPlayers(
                "message",
                clienttranslate('${player_name} gains a stone'),
                array(
                    "player_name" => self::getActivePlayerName()
                )
            );

            $this->gamestate->nextState('whereNext');
        }
    }

    function destroyStone()
    {
        $this->checkAction('destroyStone');

        $player_id = $this->getActivePlayerId();
        $choosing_color = $this->getPlayerColorById($player_id);
        $other_color = ($choosing_color == "000000") ? "ffffff" : "000000";
        $current_stones = self::getUniqueValueFromDB("SELECT player_stones FROM player WHERE player_color = '$other_color'");

        if ($current_stones == 0) {
            throw new BgaSystemException("Minimum stones reached");
        } else {
            $this->updateStones($other_color, -1);

            self::notifyAllPlayers(
                "message",
                clienttranslate('${player_name} destroys an enemy stone'),
                array(
                    "player_name" => self::getActivePlayerName()
                )
            );

            $this->gamestate->nextState('whereNext');
        }
    }

    function promotePawn($chosen_promotion)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('promotePawn');

        $player_id = $this->getActivePlayerId();
        $player_color = $this->getPlayerColorById($player_id);
        $player_army = $this->getPlayerArmy($player_color);

        // Check that the chosen promotion is valid for this player
        if (!in_array($chosen_promotion, $this->all_armies_promote_options[$player_army])) {
            return;
        }

        $promoting_pawn_data = self::getObjectFromDB("SELECT piece_id, state FROM pieces WHERE state IN (" . PROMOTING . ", " . CAPTURING_AND_PROMOTING . ")");
        $promoting_pawn_id = $promoting_pawn_data['piece_id'];
        $new_state = ($promoting_pawn_data['state'] == CAPTURING_AND_PROMOTING) ? CAPTURING : NORMAL;
        
        self::DbQuery("UPDATE pieces SET type = '$chosen_promotion', state = '$new_state' WHERE piece_id = '$promoting_pawn_id'");
        
        $promoting_pawn_type = ($player_army == "nemesis") ? "nemesispawn" : "pawn";

        self::notifyAllPlayers(
            "updateAllPieceData",
            clienttranslate('${player_name} promotes ${logpiece_before} to ${logpiece_after}'),
            array(
                "piece_id" => $promoting_pawn_id,
                "values_updated" => array("type" => $chosen_promotion),
                "player_name" => self::getActivePlayerName(),
                "logpiece_before" => $player_color . "_" . $promoting_pawn_type,
                "logpiece_after" => $player_color . "_" . $chosen_promotion
            )
        );

        $this->gamestate->nextState('whereNext');
    }

    function offerDraw()
    {
        $this->checkAction('offerDraw');

        self::notifyAllPlayers(
            "message",
            clienttranslate('${player_name} offers a draw'),
            array(
                "player_name" => self::getActivePlayerName()
            )
        );

        $this->gamestate->nextState('offerDraw');
    }

    function acceptDraw()
    {
        $this->checkAction('acceptDraw');

        self::setStat(AGREED_TO_DRAW, "end_condition");

        self::notifyAllPlayers(
            "message",
            clienttranslate('${player_name} accepts the draw'),
            array(
                "player_name" => self::getActivePlayerName()
            )
        );

        $this->gamestate->nextState('gameEnd');
    }

    function rejectDraw()
    {
        $this->checkAction('rejectDraw');

        self::notifyAllPlayers(
            "message",
            clienttranslate('${player_name} rejects the draw'),
            array(
                "player_name" => self::getActivePlayerName()
            )
        );

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
        $pieces_table_update_information = array();
        $armies = array();

        // Adding a row to the pieces database table for each piece in each player's starting layout
        $sql = "INSERT INTO pieces (piece_id,color,type,x,y) VALUES ";
        $sql_values = array();

        $x_offsets = ($this->getGameStateValue('ruleset_version') == 3) ? $this->rollXOffsets() : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        // For each player
        foreach ($this->getAllPlayerData() as $player_id => $player_data) {
            $player_color = $player_data['color'];
            $armies[$player_color] = array("player_id" => $player_id, "army" => $player_data['army']);

            $piece_id_offset = 1;
            $y_values = [1, 2];
            // If this is for black, change the y values and ids to be correct for this player
            if ($player_color == "000000") {
                $piece_id_offset = 17;
                $y_values = [8, 7];
            }

            // For each piece in their army's layout
            foreach ($this->all_armies_layouts[$player_data['army']] as $piece_index => $piece_type) {
                $x = ($piece_index % 8) + 1 + $x_offsets[$piece_index];
                $y = $y_values[floor($piece_index / 8)];
                $piece_id = $piece_id_offset + $piece_index;

                // Add the piece's data to be sent to the database and in a notification
                $sql_values[] = "('$piece_id','$player_color','$piece_type','$x','$y')";
                $pieces_table_update_information[] = array($piece_id, $player_color, $piece_type, $x, $y);
            }
        }

        // Send the information to the pieces database table
        $sql .= implode(',', $sql_values);
        self::DbQuery($sql);

        self::DbQuery("UPDATE player SET player_remaining_reflexion_time = 1800");

        // Notifying players of the changes to gamedatas
        self::notifyAllPlayers(
            "stBoardSetup",
            clienttranslate('Game begins: ${army_ffffff} vs ${army_000000}'),
            array(
                "pieces_table_update_information" => $pieces_table_update_information,
                "player_armies" => $armies,
                "army_ffffff" => $this->button_labels[$armies['ffffff']['army']],
                "army_000000" => $this->button_labels[$armies['000000']['army']]
            )
        );

        $this->activeNextPlayer();
        $this->activeNextPlayer();
        $this->gamestate->nextState('whereNext');
    }

    function stWhereNext()
    {
        $pieces = self::getCollectionFromDB("SELECT * FROM pieces");

        // Check for midline invasion win condition
        if ($this->hasActivePlayerInvaded($pieces)) {
            $this->activePlayerWins(MIDLINE_INVASION);
            return;
        }

        // Check for a pawn promoting
        $pro_id = self::getUniqueValueFromDB("SELECT piece_id FROM pieces WHERE state IN (" . PROMOTING . ", " . CAPTURING_AND_PROMOTING . ")");
        if ($pro_id !== null) {
            $this->gamestate->nextState('pawnPromotion');
            return;
        }

        // Check for a piece capturing
        $cap_id = self::getUniqueValueFromDB("SELECT piece_id FROM pieces WHERE state = " . CAPTURING);
        if ($cap_id !== null) {
            $this->processCapture($cap_id, $pieces);
            return;
        }

        // Now the state is resolved

        $squares = $this->getSquaresData($pieces);

        // Give the active player a king turn if available
        $resolved_player_id = $this->getActivePlayerId();

        if (self::getUniqueValueFromDB("SELECT player_king_move_available FROM player WHERE player_id = '$resolved_player_id'")) {
            self::DbQuery("UPDATE player SET player_king_move_available = 0 WHERE player_id = '$resolved_player_id'");

            $kings = $this->getPlayerKingIds($resolved_player_id);
            $king_moves = $this->moves->getAllMovesForPieces($kings, $resolved_player_id, array("pieces" => $pieces, "squares" => $squares))['moves'];

            if ($this->replaceLegalMoves($king_moves)) {
                $this->gamestate->nextState('playerKingMove');
                return;
            }
        }

        // Now the turn can pass to the other player

        // 50 move rule
        // Reduce fifty_counter by 1 at the end of each black player's turn. Reset to 51 when moving a pawn or capturing. If it reaches 0, draw
        if ($this->getPlayerColorById($resolved_player_id) == "000000") {
            $fifty_counter = $this->getGameStateValue('fifty_counter') - 1;
            if ($fifty_counter == 0) {
                self::setStat(FIFTY_MOVE_RULE, "end_condition");
                self::notifyAllPlayers("message", clienttranslate('The game is a draw by the 50 move rule'), array());
                $this->gamestate->nextState('gameEnd');
                return;
            }
            $this->setGameStateValue('fifty_counter', $fifty_counter);
        }

        foreach ($pieces as $piece_id => $piece_data) {
            if (
                $piece_data['state'] == EN_PASSANT_VULNERABLE
                && $this->getGameStateValue('last_player_move_piece_id') != $piece_id
            ) {
                $piece_data['state'] = NORMAL;
                self::DbQuery("UPDATE pieces SET state = " . NORMAL . " WHERE piece_id = '$piece_id'");

                self::notifyAllPlayers("updateAllPieceData", "", array(
                    "piece_id" => $piece_id,
                    "values_updated" => array("state" => NORMAL)
                ));
            }
        }

        // Activate the next player and generate their legal moves. If they have none, they lose.
        $this->activeNextPlayer();

        $active_player_id = $this->getActivePlayerId();
        $active_color = $this->getPlayerColorById($active_player_id);
        $act_pieces = self::getObjectListFromDB("SELECT piece_id FROM pieces WHERE color = '$active_color' AND state != " . CAPTURED, true);

        $moves = $this->moves->getAllMovesForPieces($act_pieces, $active_player_id, array("pieces" => $pieces, "squares" => $squares));
        $all_legal_moves = $moves['moves'];

        if (!$this->replaceLegalMoves($all_legal_moves)) {
            // Determine whether it is checkmate or stalemate
            $condition = STALEMATE;
            foreach ($moves['friendly_kings'] as $king_data) {
                if (count($king_data['checked_by']) != 0) {
                    $condition = CHECKMATE;
                    break;
                }
            }

            $this->activeNextPlayer();
            $this->activePlayerWins($condition);
            return;
        }

        $armies = self::getCollectionFromDB("SELECT player_color, player_army FROM player", true);

        if ($armies[$active_color] == "twokings") {
            self::DbQuery("UPDATE player SET player_king_move_available = 1 WHERE player_id = '$active_player_id'");
        }

        // String describing the current position
        $pos_string = $this->getPositionString(
            $active_color,
            $armies,
            $all_legal_moves,
            array("pieces" => $pieces, "squares" => $squares)
        );

        // Check for threefold repetition
        self::DbQuery("INSERT INTO pos_history (pos_string) VALUES ('$pos_string')");
        $pos_reps = self::getUniqueValueFromDB("SELECT COUNT(*) FROM pos_history WHERE pos_string = '$pos_string'");
        if ($pos_reps == 3) {
            self::setStat(THREEFOLD_REPETITION, "end_condition");
            self::notifyAllPlayers("message", clienttranslate('The game is a draw by threefold repetition'), array());
            $this->gamestate->nextState('gameEnd');
            return;
        }

        self::incStat(1, "moves_number");

        $this->gamestate->nextState('playerMove');
    }

    function stResolveDuel()
    {
        $pieces = self::getCollectionFromDB("SELECT * FROM pieces");
        $duel_data = $this->getDuelData($pieces);
        $player_bids = self::getCollectionFromDB("SELECT player_color, player_bid FROM player", true);
        $cap_color = $pieces[$duel_data['capID']]['color'];
        $def_color = $pieces[$duel_data['defID']]['color'];

        self::DbQuery("UPDATE player SET player_bid = null");

        // Update player stones in database and notify
        foreach ($player_bids as $player_color => $bid) {
            $this->updateStones($player_color, $bid * -1);
        }

        self::notifyAllPlayers(
            "message",
            clienttranslate('Bids: ${logpiece_def} ${bid_def} - ${bid_cap} ${logpiece_cap}'),
            array(
                "logpiece_def" => $def_color . "_" . $pieces[$duel_data['defID']]['type'],
                "bid_def" => $player_bids[$def_color],
                "bid_cap" => $player_bids[$cap_color],
                "logpiece_cap" => $cap_color . "_" . $pieces[$duel_data['capID']]['type']
            )
        );

        // Determine outcome of duel and resolve capture
        $both_cap = ($player_bids[$cap_color] < $player_bids[$def_color]) ? true : false;
        $this->resolveNextCapture($both_cap);

        $this->activeNextPlayer();

        // If both bid 0 stones, the attacker can choose to gain 1 stone or destroy 1 of the defender's stones
        if ($player_bids[$cap_color] == 0 && $player_bids[$def_color] == 0) {
            self::incStat(1, "bluffs_called", self::getUniqueValueFromDB("SELECT player_id FROM player WHERE player_color = '$cap_color'"));

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
        $this->activePlayerWins(CONCESSION);
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
        switch ($state['name']) {
            case 'playerMove':
                $move = self::getObjectFromDB("SELECT moving_piece_id, x, y FROM legal_moves LIMIT 1");
                $this->movePiece($move['x'], $move['y'], $move['moving_piece_id']);
                return;

            case 'playerKingMove':
                $this->gamestate->nextState('whereNext');
                return;

            case 'pawnPromotion':
                $army = self::getUniqueValueFromDB("SELECT player_army FROM player WHERE player_id = '$active_player'");
                $this->promotePawn($this->all_armies_promote_options[$army][0]);
                return;

            case 'duelOffer':
                $this->rejectDuel();
                return;

            case 'calledBluff':
                $this->destroyStone();
                return;

            case 'drawOffer':
                $this->rejectDraw();
                return;

            case 'armySelect':
                $this->confirmArmy("classic", $active_player);
                return;

            case 'duelBidding':
                $this->pickBid(0);
                return;

            default:
                throw new feException("Zombie mode not supported at this game state: " . $state['name']);
        }
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
