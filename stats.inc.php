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
 * stats.inc.php
 *
 * ChessSequel game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

require_once('modules/constants.inc.php');

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "end_condition" => array(
            "id" => STAT_END_CONDITION,
            "name" => totranslate("Game end condition"),
            "type" => "int"
        ),

        "moves_number" => array(
            "id" => STAT_MOVES_NUMBER,
            "name" => totranslate("Number of moves"),
            "type" => "int"
        )

        /*
        Examples:


        "table_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("table test stat 1"), 
                                "type" => "int" ),
                                
        "table_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("table test stat 2"), 
                                "type" => "float" )
*/
    ),

    // Statistics existing for each player
    "player" => array(

        "army" => array(
            "id" => STAT_ARMY,
            "name" => totranslate("Army"),
            "type" => "int"
        ),

        "enemies_captured" => array(
            "id" => STAT_ENEMIES_CAPTURED,
            "name" => totranslate("Enemy pieces captured"),
            "type" => "int"
        ),

        "friendlies_captured" => array(
            "id" => STAT_FRIENDLIES_CAPTURED,
            "name" => totranslate("Friendly pieces captured"),
            "type" => "int"
        ),

        "duels_initiated" => array(
            "id" => STAT_DUELS_INITIATED,
            "name" => totranslate("Duels initiated"),
            "type" => "int"
        ),

        "stones_bid" => array(
            "id" => STAT_STONES_BID,
            "name" => totranslate("Total stones bid"),
            "type" => "int"
        ),

        "duel_captures" => array(
            "id" => STAT_DUEL_CAPTURES,
            "name" => totranslate("Captures by duel"),
            "type" => "int"
        ),

        "bluffs_called" => array(
            "id" => STAT_BLUFFS_CALLED,
            "name" => totranslate("Bluffs called"),
            "type" => "int"
        )

        /*
        Examples:    
        
        
        "player_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("player test stat 1"), 
                                "type" => "int" ),
                                
        "player_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("player test stat 2"), 
                                "type" => "float" )

*/
    ),

    "value_labels" => array(

        STAT_END_CONDITION => array(
            CHECKMATE => totranslate("Checkmate"),
            STALEMATE => totranslate("Stalemate"),
            MIDLINE_INVASION => totranslate("Midline invasion"),
            THREEFOLD_REPETITION => totranslate("Threefold repetition"),
            FIFTY_MOVE_RULE => totranslate("50 move rule"),
            AGREED_TO_DRAW => totranslate("Agreed to draw"),
            CONCESSION => totranslate("Concession")
        ),

        STAT_ARMY => array(
            ARMY_CLASSIC => totranslate("Classic"),
            ARMY_NEMESIS => totranslate("Nemesis"),
            ARMY_EMPOWERED => totranslate("Empowered"),
            ARMY_REAPER => totranslate("Reaper"),
            ARMY_TWOKINGS => totranslate("Two Kings"),
            ARMY_ANIMAL => totranslate("Animal")
        )

    )

);
