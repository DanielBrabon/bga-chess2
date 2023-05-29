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
require_once('modules/CHSPlayerManager.class.php');
require_once('modules/CHSPieceManager.class.php');
require_once('modules/CHSMoveManager.class.php');
require_once('modules/CHSCaptureManager.class.php');
require_once('modules/CHSMoves.class.php');
require_once('modules/constants.inc.php');

class ChessSequel extends Table
{
    public $playerManager;
    public $pieceManager;
    private $moveManager;
    private $captureManager;
    private $moves;

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
            "ruleset_version" => OPTION_RULESET
        ));

        $this->playerManager = new CHSPlayerManager($this);
        $this->pieceManager = new CHSPieceManager($this);
        $this->moveManager = new CHSMoveManager($this);
        $this->captureManager = new CHSCaptureManager($this);
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
        self::setGameStateInitialValue('fifty_counter', 50);
        self::setGameStateInitialValue('last_player_move_piece_id', 0);
        self::setGameStateInitialValue('last_king_move_piece_id', 0);

        // Init game statistics
        self::initStat("table", "end_condition", CONCESSION);
        self::initStat("table", "moves_number", 0);

        self::initStat("player", "army", CLASSIC);
        self::initStat("player", "enemies_captured", 0);
        self::initStat("player", "friendlies_captured", 0);

        if ($this->getGameStateValue('ruleset_version') == RULESET_TWO_POINT_FOUR) {
            self::DbQuery("UPDATE player SET player_stones = 3");

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
        $current_player_color = $this->getCurrentPlayerColor();    // !! We must only return informations visible by this player !!

        $result = array(
            // From database
            "players" => $this->playerManager->getUIData($current_player_color),
            "pieces" => $this->pieceManager->getPieces(),
            "legal_moves" => $this->moveManager->getLegalMoves($current_player_color),
            "capture_queue" => $this->captureManager->getCaptureQueue(),

            // From material.inc.php
            "all_army_names" => $this->all_army_names,
            "all_armies_layouts" => $this->all_armies_layouts,
            "layout_x" => $this->layout_x,
            "layout_y" => $this->layout_y,
            "button_labels" => $this->button_labels,
            "piece_tooltips" => $this->piece_tooltips,
            "army_tooltips" => $this->army_tooltips,

            // Globals
            "last_move_piece_ids" => array(
                "player_move" => $this->getGameStateValue('last_player_move_piece_id'),
                "king_move" => $this->getGameStateValue('last_king_move_piece_id')
            ),
            "ruleset_version" => $this->getGameStateValue('ruleset_version'),

            // From modules/constants.inc.php
            "constants" => get_defined_constants(true)['user']
        );

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
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function processAction($invaded = null, $duelling = null, $promoting = null, $giving_king_move = null)
    {
        $active_player = $this->playerManager->getActivePlayer();

        if ($invaded === null) {
            $invaded = $this->pieceManager->hasPlayerInvaded($active_player);

            // If the action invaded the midline, the player wins
            if ($invaded) {
                $this->endGame(MIDLINE_INVASION, $active_player);
                return;
            }
        }

        if ($duelling === null) {
            $duelling = $this->captureManager->processFrontOfCaptureQueue();

            // If the action enabled a duel, go to duelOffer state
            if ($duelling) {
                $this->activeNextPlayer();
                $this->gamestate->nextState('duelOffer');
                return;
            }
        }

        if ($promoting === null) {
            $promoting = $this->pieceManager->isPromotionAvailable();

            // If the action enabled a pawn promotion, go to pawnPromotion state
            if ($promoting) {
                $this->gamestate->nextState('pawnPromotion');
                return;
            }
        }

        if ($giving_king_move === null) {
            $giving_king_move = $this->processPendingKingMove($active_player);

            // If a king move is now available, go to playerKingMove state
            if ($giving_king_move) {
                $this->gamestate->nextState('playerKingMove');
                return;
            }
        }

        // Process fifty move rule and en passant vulnerability
        $triggered_fifty_move_rule = $this->processEndOfTurn($active_player);

        // If the action triggered the fifty move rule, the game is a draw
        if ($triggered_fifty_move_rule) {
            $this->endGame(FIFTY_MOVE_RULE);
            return;
        }

        // Activate the next player and calculate available moves
        $this->processNextPlayerMoves();
    }

    function processPendingKingMove($player)
    {
        if (!$player->king_move_available) {
            return false;
        }

        $player->setKingMoveUnavailable();

        $king_moves = $this->moves->getAllKingMovesForPlayer($player)['moves'];

        $amount_of_legal_moves = $this->moveManager->insertLegalMoves($king_moves, $player);

        if ($amount_of_legal_moves == 0) {
            return false;
        }

        // Giving king move
        return true;
    }

    function processEndOfTurn($player)
    {
        // Reduce fifty_counter by 1 at the end of each black player's turn. Reset to 51 when moving a pawn or capturing. If it reaches 0, draw
        if (
            $player->color == "000000"
            && $this->incGameStateValue('fifty_counter', -1) == 0
        ) {
            return true;
        }

        // Remove en passant vulnerability from piece where applicable
        $enpassant_pieces = $this->pieceManager->getPiecesInStates([EN_PASSANT_VULNERABLE]);
        $last_move_piece = $this->getGameStateValue('last_player_move_piece_id');

        foreach ($enpassant_pieces as $piece) {
            if ($piece->id != $last_move_piece) {
                $piece->setState(NEUTRAL);
            }
        }

        return false;
    }

    function processNextPlayerMoves()
    {
        $this->activeNextPlayer();

        $active_player = $this->playerManager->getActivePlayer();

        $moves = $this->moves->getAllMovesForPlayer($active_player);

        $amount_of_legal_moves = $this->moveManager->insertLegalMoves($moves['moves'], $active_player);

        // If the next player has no available moves, they lose
        if ($amount_of_legal_moves == 0) {
            $condition = ($this->kingIsInCheck($moves['friendly_kings'])) ? CHECKMATE : STALEMATE;

            $this->endGame($condition, $this->playerManager->getInactivePlayer());
            return;
        }

        foreach ($moves['friendly_kings'] as $king_id => $king) {
            if (count($king['checked_by']) != 0) {
                $this->pieceManager->getPiece($king_id)->setState(IN_CHECK);
            }
        }

        if ($active_player->army == "twokings") {
            $active_player->setKingMoveAvailable();
        }

        $position_reps = $this->processPositionRepetition($moves['moves']);

        // If this exact position has occurred three times, the game is a draw
        if ($position_reps == 3) {
            $this->endGame(THREEFOLD_REPETITION);
            return true;
        }

        // If none of the above occurred, go to playerMove state
        self::incStat(1, "moves_number");
        $this->gamestate->nextState('playerMove');
    }

    function kingIsInCheck($king_data)
    {
        foreach ($king_data as $king) {
            if (count($king['checked_by']) != 0) {
                return true;
            }
        }

        return false;
    }

    function processPositionRepetition($all_legal_moves)
    {
        $pos_string = $this->getPositionString($all_legal_moves);

        self::DbQuery("INSERT INTO pos_history (pos_string) VALUES ('$pos_string')");

        return self::getUniqueValueFromDB("SELECT COUNT(*) FROM pos_history WHERE pos_string = '$pos_string'");
    }

    function getPositionString($all_legal_moves)
    {
        $active_color = $this->playerManager->getActivePlayer()->color;
        $pieces = $this->pieceManager->getDataForMoveGen();
        $squares = $this->pieceManager->getSquaresData();

        $pos_string = $active_color[0];

        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 8; $j++) {
                $pid = $squares[$i][$j]['def_piece'];

                if ($pid === null) {
                    $pos_string .= "-";
                    continue;
                }

                $piece = $this->pieceManager->getPiece($pid);

                $pos_string .= $this->type_code[$piece->type];
                $pos_string .= $piece->color[0];

                if (in_array($piece->type, ["pawn", "nemesispawn"])) {
                    if ($piece->color == $active_color) {
                        $pos_string .= count($all_legal_moves[$pid]);
                    } else {
                        $pos_string .= count($this->moves->getAvailableEnPassants($pid, array("pieces" => $pieces, "squares" => $squares)));
                    }
                } else if (
                    $piece->type == "king"
                    && $this->playerManager->getPlayerByColor($piece->color)->army == "classic"
                ) {
                    if ($piece->color == $active_color) {
                        $pos_string .= count($all_legal_moves[$pid]);
                    } else {
                        foreach ([-2, -1, 0, 1, 2] as $dx) {
                            $squares[$piece->x + $dx][$piece->y]['checks'] = [];
                        }
                        $pos_string .= count($this->moves->getAvailableCastleMoves($pid, array("pieces" => $pieces, "squares" => $squares)));
                    }
                }
            }
        }

        return $pos_string;
    }

    function getCostToDuel($cap_piece, $def_piece)
    {
        $cap_piece_rank = $this->piece_ranks[$cap_piece->type];
        $def_piece_rank = $this->piece_ranks[$def_piece->type];
        return ($cap_piece_rank > $def_piece_rank) ? 1 : 0;
    }

    function endGame($condition, $winner = null)
    {
        self::setStat($condition, "end_condition");

        $args = array("i18n" => ["condition"], "condition" => $this->end_conditions[$condition]);

        if ($winner !== null) {
            $winner->setAsWinner();

            $msg = clienttranslate('${player_name} wins by ${condition}');
            $args['player_name'] = $winner->name;
        } else {
            $msg = clienttranslate('The game is a draw by ${condition}');
        }

        // Translate
        self::notifyAllPlayers("message", $msg, $args);

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

    function confirmArmy($army_name, $current_player = null)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('confirmArmy');

        // Check it's a valid army name according to the array in material.inc.php
        if (!in_array($army_name, $this->all_army_names)) {
            throw new BgaSystemException("Invalid army selection");
        }

        // Get the color of the CURRENT player (there are multiple active players in armySelect)
        // In the BGA framework, the CURRENT player is the player who played the current player action (player who made the AJAX request)
        if ($current_player === null) {
            $current_player = $this->playerManager->getPlayerByColor($this->getCurrentPlayerColor());
        }

        $opponent = $this->playerManager->getOtherPlayerByColor($current_player->color);

        // If opponent is active, deactivate this player before setting army (status bar inconsistency otherwise)
        if ($opponent->is_multiactive) {
            $this->gamestate->setPlayerNonMultiactive($current_player->id, 'processArmySelection');
            $current_player->setArmy($army_name);
        } else {
            $current_player->setArmy($army_name);
            $this->gamestate->setPlayerNonMultiactive($current_player->id, 'processArmySelection');
        }
    }

    function movePiece($target_x, $target_y, $moving_piece_id)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('movePiece');

        $valid_coords = [1, 2, 3, 4, 5, 6, 7, 8];

        // Check inputs are all valid
        if (
            !$this->pieceManager->pieceIdExists($moving_piece_id)
            || !in_array($target_x, $valid_coords)
            || !in_array($target_y, $valid_coords)
        ) {
            throw new BgaSystemException("Invalid inputs");
        }

        // Get some information
        $player_id = $this->getActivePlayerId();
        $player_color = $this->getPlayerColorById($player_id);

        $moving_piece = $this->pieceManager->getPiece($moving_piece_id);

        // Check that the player is trying to move their own piece
        if ($moving_piece->color != $player_color) {
            throw new BgaSystemException("Invalid target");
        }

        $cap_squares = $this->moveManager->getCaptureSquaresForMove($target_x, $target_y, $moving_piece_id);

        // If the attempted move is not found in legal_moves table, throw an error
        if ($cap_squares === null) {
            throw new BgaSystemException("Illegal move");
        }

        foreach ($this->pieceManager->getPiecesInStates([IN_CHECK]) as $piece) {
            $piece->setState(NEUTRAL);
        }

        $squares = $this->pieceManager->getSquaresData();

        $capture_queue = array();

        // Add all occupied capture squares to the capture queue
        foreach ($cap_squares as $square) {
            $piece_on_square = $squares[$square['x']][$square['y']]['def_piece'];

            if ($piece_on_square !== null) {
                $capture_queue[] = $piece_on_square;

                if ($moving_piece->type == "tiger") {
                    $target_x = $moving_piece->x;
                    $target_y = $moving_piece->y;
                }
            }
        }

        // Array of values to set for the moving piece
        $values_updated = array(
            "state" => NEUTRAL,
            "location" => [$target_x, $target_y],
            "last_x" => $moving_piece->x,
            "last_y" => $moving_piece->y
        );

        if (count($capture_queue) != 0) {
            $this->captureManager->insertCaptureQueue($moving_piece, $capture_queue);
            $values_updated['state'] = CAPTURING;
        }

        // Special conditions for pawns
        if (in_array($moving_piece->type, ["pawn", "nemesispawn"])) {
            // 50 move rule
            $this->setGameStateValue('fifty_counter', 51);

            // If the moving piece is a pawn reaching the enemy backline, set its state to promoting
            $backline = ($moving_piece->color == "000000") ? 1 : 8;
            if ($target_y == $backline) {
                if ($values_updated['state'] == CAPTURING) {
                    $values_updated['state'] = CAPTURING_AND_PROMOTING;
                } else {
                    $values_updated['state'] = PROMOTING;
                }
            }

            // If the moving piece is a pawn making its initial double move, set it as en passant vulnerable
            else if (abs($moving_piece->y - $target_y) == 2) {
                $values_updated['state'] = EN_PASSANT_VULNERABLE;
            }
        }

        $state_name = $this->gamestate->state()['name'];

        if ($state_name == "playerMove") {
            $this->setGameStateValue("last_player_move_piece_id", $moving_piece_id);
            $this->setGameStateValue("last_king_move_piece_id", 0);
        } else {
            $this->setGameStateValue("last_king_move_piece_id", $moving_piece_id);
        }

        // This happens when a two kings player moves the same warrior king in playerMove and playerKingMove
        // and means that the last move highlight will display the warrior king's combined movement over the two actions
        if ($this->getGameStateValue("last_player_move_piece_id") == $this->getGameStateValue("last_king_move_piece_id")) {
            $values_updated['last_x'] = $moving_piece->last_x;
            $values_updated['last_y'] = $moving_piece->last_y;
        }

        $msg = '${player_name}: ${logpiece}${square}';
        if (
            $moving_piece->type == "warriorking"
            && $target_x == $moving_piece->x
            && $target_y == $moving_piece->y
        ) {
            $msg = clienttranslate('${player_name}: ${logpiece} whirlwinds');
        }

        // Send notifications
        // Translate
        self::notifyAllPlayers(
            "message",
            $msg,
            array(
                "player_name" => self::getActivePlayerName(),
                "logpiece" => $moving_piece->color . "_" . $moving_piece->type,
                "square" => $this->files[$target_x] . $target_y
            )
        );

        // If the moving piece is a castling king, resolve the castle
        if ($moving_piece->type == "king" && abs($moving_piece->x - $target_x) == 2) {
            $dir = ($target_x - $moving_piece->x) / 2;

            for ($i = 1; $i < 5; $i++) {
                $x = $target_x + ($dir * $i);

                if ($squares[$x][$target_y]['def_piece'] !== null) {
                    $castling_rook = $this->pieceManager->getPiece($squares[$x][$target_y]['def_piece']);

                    $rook_dest_x = $target_x - $dir;

                    $castling_rook->setNewLocation($rook_dest_x, $target_y);

                    // Translate
                    self::notifyAllPlayers(
                        "message",
                        clienttranslate('${player_name} castles: ${logpiece}${square}'),
                        array(
                            "player_name" => self::getActivePlayerName(),
                            "logpiece" => $castling_rook->color . "_" . $castling_rook->type,
                            "square" => $this->files[$rook_dest_x] . $castling_rook->y
                        )
                    );

                    break;
                }
            }
        }

        $moving_piece->movePiece($values_updated, $state_name);

        // Change player state
        $this->gamestate->nextState('processMove');
    }

    function promotePawn($chosen_promotion)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('promotePawn');

        $active_player = $this->playerManager->getActivePlayer();

        // Check that the chosen promotion is valid for this player
        if (!in_array($chosen_promotion, $this->all_armies_promote_options[$active_player->army])) {
            throw new BgaSystemException("Invalid promotion");
        }

        $promoting_pawn = $this->pieceManager->getPiecesInStates([PROMOTING])[0];

        $promoting_pawn->promote($chosen_promotion);

        $promoting_pawn_type = ($active_player->army == "nemesis") ? "nemesispawn" : "pawn";

        // Translate
        self::notifyAllPlayers(
            "message",
            clienttranslate('${player_name} promotes ${logpiece_before} to ${logpiece_after}'),
            array(
                "player_name" => $active_player->name,
                "logpiece_before" => $active_player->color . "_" . $promoting_pawn_type,
                "logpiece_after" => $active_player->color . "_" . $chosen_promotion
            )
        );

        $this->gamestate->nextState('processPromotion');
    }

    function acceptDuel()
    {
        $this->checkAction('acceptDuel');

        $cap_piece = $this->pieceManager->getPiecesInStates([CAPTURING, CAPTURING_AND_PROMOTING])[0];
        $def_piece = $this->pieceManager->getPiece($this->captureManager->getCurrentDefenderId());

        $active_player = $this->playerManager->getActivePlayer();
        $inactive_player = $this->playerManager->getInactivePlayer();

        $active_player->incStat(1, "duels_initiated");

        $msg = clienttranslate('${player_name}: ${logpiece_def} duels ${logpiece_cap}');

        if ($this->getCostToDuel($cap_piece, $def_piece) == 1) {
            // Pay the cost to duel
            $active_player->loseOneStone();
            $msg .= clienttranslate(' (Pays 1 stone)');
        }

        // Translate
        self::notifyAllPlayers(
            "message",
            $msg,
            array(
                "player_name" => $active_player->name,
                "logpiece_def" => $def_piece->color . "_" . $def_piece->type,
                "logpiece_cap" => $cap_piece->color . "_" . $cap_piece->type
            )
        );

        $enemy_stones = $inactive_player->stones;

        if ($enemy_stones == 0) {
            $active_player->setBid(1);
            $inactive_player->setBid(0);

            $this->gamestate->nextState('processDuelOutcome');
            return;
        }

        $this->gamestate->nextState('duelBidding');
    }

    function rejectDuel()
    {
        $this->checkAction('rejectDuel');

        $this->gamestate->nextState('processDuelRejected');
    }

    function pickBid($bid_amount, $current_player = null)
    {
        // Check this action is allowed according to the game state
        $this->checkAction('pickBid');

        // Get the CURRENT player (there are multiple active players in duelBidding)
        // In the BGA framework, the CURRENT player is the player who played the current player action (player who made the AJAX request)
        if ($current_player === null) {
            $current_player = $this->playerManager->getPlayerByColor($this->getCurrentPlayerColor());
        }

        if (!in_array($bid_amount, [0, 1, 2]) || $bid_amount > $current_player->stones) {
            throw new BgaSystemException("Invalid bid amount");
        }

        // Update the current player's bid in the database
        $current_player->setBid($bid_amount);

        // Display the stones being bid to the current player only
        self::notifyPlayer(
            $current_player->id,
            "bidStones",
            "",
            array(
                "player_id" => $current_player->id,
                "bid_amount" => $bid_amount
            )
        );

        // Deactivate player. If none left, transition to 'processDuelOutcome' state
        $this->gamestate->setPlayerNonMultiactive($current_player->id, 'processDuelOutcome');
    }

    function gainStone()
    {
        $this->checkAction('gainStone');

        $active_player = $this->playerManager->getActivePlayer();

        $active_player->gainOneStone("board");

        // Translate
        self::notifyAllPlayers(
            "message",
            clienttranslate('${player_name} gains a stone'),
            array(
                "player_name" => $active_player->name
            )
        );

        $this->gamestate->nextState('processBluffChoice');
    }

    function destroyStone()
    {
        $this->checkAction('destroyStone');

        $this->playerManager->getInactivePlayer()->loseOneStone();

        // Translate
        self::notifyAllPlayers(
            "message",
            clienttranslate('${player_name} destroys an enemy stone'),
            array(
                "player_name" => self::getActivePlayerName()
            )
        );

        $this->gamestate->nextState('processBluffChoice');
    }

    function passKingMove()
    {
        $this->checkAction('passKingMove');

        $this->gamestate->nextState('processPass');
    }

    function offerDraw()
    {
        $this->checkAction('offerDraw');

        // Translate
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
        $this->endGame(AGREED_TO_DRAW);
    }

    function rejectDraw()
    {
        $this->checkAction('rejectDraw');

        // Translate
        self::notifyAllPlayers(
            "message",
            clienttranslate('${player_name} rejects the draw'),
            array(
                "player_name" => self::getActivePlayerName()
            )
        );

        $this->gamestate->nextState('processDrawRejected');
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
        $army = $this->playerManager->getActivePlayer()->army;
        return array("promote_options" => $this->all_armies_promote_options[$army]);
    }

    function argDuelOffer()
    {
        $cap_piece = $this->pieceManager->getPiecesInStates([CAPTURING, CAPTURING_AND_PROMOTING])[0];
        $capture_queue = $this->captureManager->getCaptureQueue();
        $def_piece = $this->pieceManager->getPiece($capture_queue[0]);
        $duel_cost = $this->getCostToDuel($cap_piece, $def_piece);
        $cap_stones = $this->playerManager->getPlayerByColor($cap_piece->color)->stones;

        return array(
            "duel_cost" => $duel_cost,
            "cap_stones" => $cap_stones
        );
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
    function stProcessArmySelection()
    {
        if ($this->getGameStateValue('ruleset_version') == RULESET_THREE_POINT_ZERO) {
            $x_positions = $this->pieceManager->rollBacklinePositions();

            $this->pieceManager->insertPieces($x_positions);

            // Translate
            self::notifyAllPlayers("showBacklineRandomization", clienttranslate('Backline positions are randomized'), ["x_positions" => $x_positions]);
        } else {
            $this->pieceManager->insertPieces();
        }

        $this->playerManager->setRemainingReflexionTime(1800);

        // Translate
        self::notifyAllPlayers(
            "message",
            clienttranslate('Game begins: ${army_ffffff} vs ${army_000000}'),
            array(
                "army_ffffff" => $this->button_labels[$this->playerManager->getPlayerByColor("ffffff")->army],
                "army_000000" => $this->button_labels[$this->playerManager->getPlayerByColor("000000")->army]
            )
        );

        $this->processNextPlayerMoves();
    }

    function stProcessMove()
    {
        $this->processAction();
    }

    function stProcessPromotion()
    {
        $this->processAction(false, false, false);
    }

    function stProcessDuelRejected()
    {
        $def_piece = $this->pieceManager->getPiece($this->captureManager->getCurrentDefenderId());

        // Single capture the first piece in the capture queue
        $this->captureManager->singleCapture($def_piece);

        // Then process the front of the remaining queue
        $duelling = $this->captureManager->processFrontOfCaptureQueue();

        // If another duel is available, go to duelOffer state
        if ($duelling) {
            $this->gamestate->nextState('duelOffer');
            return;
        }

        $this->activeNextPlayer();
        $this->processAction(false, false);
    }

    function stProcessDuelOutcome()
    {
        $cap_piece = $this->pieceManager->getPiecesInStates([CAPTURING, CAPTURING_AND_PROMOTING])[0];
        $def_piece = $this->pieceManager->getPiece($this->captureManager->getCurrentDefenderId());

        $cap_player = $this->playerManager->getPlayerByColor($cap_piece->color);
        $def_player = $this->playerManager->getPlayerByColor($def_piece->color);

        $cap_player_bid = $cap_player->bid;
        $def_player_bid = $def_player->bid;

        $cap_player->bidStones();
        $def_player->bidStones();

        $msg = clienttranslate('Duel outcome: normal capture');
        if ($cap_player_bid < $def_player_bid) {
            $msg = clienttranslate('Duel outcome: both pieces capture');
        } else if ($cap_player_bid == 0 && $def_player_bid == 0) {
            $msg = clienttranslate('Duel outcome: called bluff');
        }

        // Translate
        self::notifyAllPlayers(
            "showDuelOutcome",
            clienttranslate('Bids: ${logpiece_def} ${bid_def} - ${bid_cap} ${logpiece_cap}'),
            array(
                "outcome_message" => $msg,
                "logpiece_def" => $def_player->color . "_" . $def_piece->type,
                "bid_def" => $def_player_bid,
                "bid_cap" => $cap_player_bid,
                "logpiece_cap" => $cap_player->color . "_" . $cap_piece->type
            )
        );

        // Determine outcome of duel and resolve capture
        if ($cap_player_bid < $def_player_bid) {
            $this->captureManager->doubleCapture($def_piece);

            $this->activeNextPlayer();
            $this->processAction(false, false);
            return;
        }

        $this->captureManager->singleCapture($def_piece);

        // If both bid 0 stones, the attacker can choose to gain 1 stone or destroy 1 of the defender's stones
        if ($cap_player_bid == 0 && $def_player_bid == 0) {
            $cap_player->incStat(1, "bluffs_called");

            if ($cap_player->stones == 6) {
                $def_player->loseOneStone();

                // Translate
                self::notifyAllPlayers(
                    "message",
                    clienttranslate('${player_name} destroys an enemy stone'),
                    array(
                        "player_name" => $cap_player->name
                    )
                );
            } else {
                $this->activeNextPlayer();
                $this->gamestate->nextState('calledBluff');
                return;
            }
        }

        $duelling = $this->captureManager->processFrontOfCaptureQueue();

        // If another duel is available, go to duelOffer state
        if ($duelling) {
            $this->gamestate->nextState('duelOffer');
            return;
        }

        $this->activeNextPlayer();
        $this->processAction(false, false);
    }

    function stProcessBluffChoice()
    {
        $this->processAction(false);
    }

    function stProcessPass()
    {
        $this->processAction(false, false, false, false);
    }

    function stOfferDraw()
    {
        $this->activeNextPlayer();
        $this->gamestate->nextState('drawOffer');
    }

    function stProcessDrawRejected()
    {
        $this->activeNextPlayer();
        $this->gamestate->nextState('playerMove');
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

    function zombieTurn($state, $active_player_id)
    {
        switch ($state['name']) {
            case 'playerMove':
                $all_legal_moves = self::getObjectListFromDB("SELECT piece_id, x, y FROM legal_moves");

                $roll = bga_rand(0, count($all_legal_moves) - 1);

                $move = $all_legal_moves[$roll];

                $this->movePiece($move['x'], $move['y'], $move['piece_id']);

                return;

            case 'playerKingMove':
                $all_legal_moves = self::getObjectListFromDB("SELECT piece_id, x, y FROM legal_moves");

                $roll = bga_rand(0, count($all_legal_moves));

                if ($roll == count($all_legal_moves)) {
                    $this->passKingMove();
                    return;
                }

                $move = $all_legal_moves[$roll];

                $this->movePiece($move['x'], $move['y'], $move['piece_id']);

                return;

            case 'pawnPromotion':
                $promote_options = $this->all_armies_promote_options[$this->playerManager->getPlayerById($active_player_id)->army];

                $roll = bga_rand(0, count($promote_options) - 1);

                $this->promotePawn($promote_options[$roll]);

                return;

            case 'duelOffer':
                $roll = bga_rand(0, 1);

                if ($roll == 0) {
                    $this->rejectDuel();
                } else {
                    $this->acceptDuel();
                }

                return;

            case 'calledBluff':
                $roll = bga_rand(0, 1);

                if ($roll == 0) {
                    $this->destroyStone();
                } else {
                    $this->gainStone();
                }

                return;

            case 'drawOffer':
                $this->rejectDraw();
                return;

            case 'armySelect':
                $roll = bga_rand(0, count($this->all_army_names) - 1);

                $this->confirmArmy($this->all_army_names[$roll], $this->playerManager->getPlayerById($active_player_id));

                return;

            case 'duelBidding':
                $active_player = $this->playerManager->getPlayerById($active_player_id);

                $roll = bga_rand(0, $active_player->stones);

                $this->pickBid($roll, $active_player);

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
