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
 * states.inc.php
 *
 * ChessSequel game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


$machinestates = array(

    // The initial state. Please do not modify.
    ST_GAME_SETUP => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array(
            "" => ST_ARMY_SELECT
        )
    ),

    // Both players simultaneously pick their armies from the 6 options
    ST_ARMY_SELECT => array(
        "name" => "armySelect",
        "description" => clienttranslate('${actplayer} must select an army'),
        "descriptionmyturn" => clienttranslate('${you} must select an army'),
        "type" => "multipleactiveplayer",
        "action" => "stMakeEveryoneActive",
        "possibleactions" => array("confirmArmy"),
        "transitions" => array(
            "processArmySelection" => ST_PROCESS_ARMY_SELECTION
        )
    ),

    // The board is initialised with pieces
    ST_PROCESS_ARMY_SELECTION => array(
        "name" => "processArmySelection",
        "type" => "game",
        "action" => "stProcessArmySelection",
        "transitions" => array(
            "playerMove" => ST_PLAYER_MOVE
        )
    ),

    // A regular turn for a player
    ST_PLAYER_MOVE => array(
        "name" => "playerMove",
        "type" => "activeplayer",
        "description" => clienttranslate('${actplayer} must choose a move'),
        "descriptionmyturn" => clienttranslate('${you} must choose a move'),
        "possibleactions" => array("movePiece", "offerDraw", "concedeGame"),
        "transitions" => array(
            "processMove" => ST_PROCESS_MOVE,
            "offerDraw" => ST_OFFER_DRAW,
            "gameEnd" => ST_GAME_END
        ),
        "updateGameProgression" => true
    ),

    // The game decides which state to transition to next and calcualtes legal moves for the next turn
    ST_PROCESS_MOVE => array(
        "name" => "processMove",
        "type" => "game",
        "action" => "stProcessMove",
        "transitions" => array(
            "playerMove" => ST_PLAYER_MOVE,
            "pawnPromotion" => ST_PAWN_PROMOTION,
            "duelOffer" => ST_DUEL_OFFER,
            "playerKingMove" => ST_PLAYER_KING_MOVE,
            "gameEnd" => ST_GAME_END
        )
    ),

    // A player choses the promotion for their pawn
    ST_PAWN_PROMOTION => array(
        "name" => "pawnPromotion",
        "type" => "activeplayer",
        "description" => clienttranslate('${actplayer} must choose the pawn promotion'),
        "descriptionmyturn" => clienttranslate('${you} must choose the pawn promotion'),
        "possibleactions" => array("promotePawn"),
        "args" => "argPawnPromotion",
        "transitions" => array(
            "processPromotion" => ST_PROCESS_PROMOTION
        )
    ),

    ST_PROCESS_PROMOTION => array(
        "name" => "processPromotion",
        "type" => "game",
        "action" => "stProcessPromotion",
        "transitions" => array(
            "duelOffer" => ST_DUEL_OFFER,
            "playerKingMove" => ST_PLAYER_KING_MOVE,
            "gameEnd" => ST_GAME_END,
            "playerMove" => ST_PLAYER_MOVE
        )
    ),

    // A player choses whether to initialise a duel
    ST_DUEL_OFFER => array(
        "name" => "duelOffer",
        "type" => "activeplayer",
        "description" => clienttranslate('${actplayer} must choose whether to duel'),
        "descriptionmyturn" => clienttranslate('${you} must choose whether to duel'),
        "possibleactions" => array("rejectDuel", "acceptDuel"),
        "args" => "argDuelOffer",
        "transitions" => array(
            "processDuelRejected" => ST_PROCESS_DUEL_REJECTED,
            "duelBidding" => ST_DUEL_BIDDING,
            "processDuelOutcome" => ST_PROCESS_DUEL_OUTCOME
        )
    ),

    ST_PROCESS_DUEL_REJECTED => array(
        "name" => "processDuelRejected",
        "type" => "game",
        "action" => "stProcessDuelRejected",
        "transitions" => array(
            "playerMove" => ST_PLAYER_MOVE,
            "duelOffer" => ST_DUEL_OFFER,
            "playerKingMove" => ST_PLAYER_KING_MOVE,
            "gameEnd" => ST_GAME_END
        )
    ),

    // Both players simultaneously choose how many stones to bid
    ST_DUEL_BIDDING => array(
        "name" => "duelBidding",
        "type" => "multipleactiveplayer",
        "description" => clienttranslate('${actplayer} must choose how many stones to bid'),
        "descriptionmyturn" => clienttranslate('${you} must choose how many stones to bid'),
        "action" => "stMakeEveryoneActive",
        "possibleactions" => array("pickBid"),
        "args" => "argDuelBidding",
        "transitions" => array(
            "processDuelOutcome" => ST_PROCESS_DUEL_OUTCOME
        )
    ),

    ST_PROCESS_DUEL_OUTCOME => array(
        "name" => "processDuelOutcome",
        "type" => "game",
        "action" => "stProcessDuelOutcome",
        "transitions" => array(
            "playerMove" => ST_PLAYER_MOVE,
            "playerKingMove" => ST_PLAYER_KING_MOVE,
            "calledBluff" => ST_CALLED_BLUFF,
            "duelOffer" => ST_DUEL_OFFER,
            "gameEnd" => ST_GAME_END
        )
    ),

    ST_CALLED_BLUFF => array(
        "name" => "calledBluff",
        "type" => "activeplayer",
        "description" => clienttranslate('${actplayer} must choose between gaining a stone and destroying an enemy stone'),
        "descriptionmyturn" => clienttranslate('${you} must choose between gaining a stone and destroying an enemy stone'),
        "possibleactions" => array("gainStone", "destroyStone"),
        "transitions" => array(
            "processBluffChoice" => ST_PROCESS_BLUFF_CHOICE
        )
    ),

    ST_PROCESS_BLUFF_CHOICE => array(
        "name" => "processBluffChoice",
        "type" => "game",
        "action" => "stProcessBluffChoice",
        "transitions" => array(
            "playerMove" => ST_PLAYER_MOVE,
            "playerKingMove" => ST_PLAYER_KING_MOVE,
            "calledBluff" => ST_CALLED_BLUFF,
            "duelOffer" => ST_DUEL_OFFER,
            "gameEnd" => ST_GAME_END
        )
    ),

    // A two kings player can move with a warrior king or pass
    ST_PLAYER_KING_MOVE => array(
        "name" => "playerKingMove",
        "type" => "activeplayer",
        "description" => clienttranslate('${actplayer} must choose a king move or pass'),
        "descriptionmyturn" => clienttranslate('${you} must choose a king move or pass'),
        "possibleactions" => array("movePiece", "passKingMove"),
        "transitions" => array(
            "processMove" => ST_PROCESS_MOVE,
            "processPass" => ST_PROCESS_PASS
        )
    ),

    ST_PROCESS_PASS => array(
        "name" => "processPass",
        "type" => "game",
        "action" => "stProcessPass",
        "transitions" => array(
            "playerMove" => ST_PLAYER_MOVE,
            "gameEnd" => ST_GAME_END
        )
    ),

    ST_OFFER_DRAW => array(
        "name" => "offerDraw",
        "type" => "game",
        "action" => "stOfferDraw",
        "transitions" => array(
            "drawOffer" => ST_DRAW_OFFER
        )
    ),

    ST_DRAW_OFFER => array(
        "name" => "drawOffer",
        "type" => "activeplayer",
        "description" => clienttranslate('${actplayer} must choose whether to accept draw'),
        "descriptionmyturn" => clienttranslate('Your opponent has offered a draw'),
        "possibleactions" => array("acceptDraw", "rejectDraw"),
        "transitions" => array(
            "gameEnd" => ST_GAME_END,
            "processDrawRejected" => ST_PROCESS_DRAW_REJECTED
        )
    ),

    ST_PROCESS_DRAW_REJECTED => array(
        "name" => "processDrawRejected",
        "type" => "game",
        "action" => "stProcessDrawRejected",
        "transitions" => array(
            "playerMove" => ST_PLAYER_MOVE
        )
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    ST_GAME_END => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);
