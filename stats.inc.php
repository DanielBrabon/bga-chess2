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

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "end_condition" => array(
            "id" => 10,
            "name" => totranslate("Game end condition"),
            "type" => "int"
        ),

        "moves_number" => array(
            "id" => 11,
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
            "id" => 12,
            "name" => totranslate("Army"),
            "type" => "int"
        ),

        "enemies_captured" => array(
            "id" => 13,
            "name" => totranslate("Enemy pieces captured"),
            "type" => "int"
        ),

        "friendlies_captured" => array(
            "id" => 14,
            "name" => totranslate("Friendly pieces captured"),
            "type" => "int"
        ),

        "duels_initiated" => array(
            "id" => 15,
            "name" => totranslate("Duels initiated"),
            "type" => "int"
        ),

        "stones_bid" => array(
            "id" => 16,
            "name" => totranslate("Total stones bid"),
            "type" => "int"
        ),

        "duel_captures" => array(
            "id" => 17,
            "name" => totranslate("Captures by duel"),
            "type" => "int"
        ),

        "bluffs_called" => array(
            "id" => 18,
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

        10 => array(
            0 => totranslate("Checkmate"),
            1 => totranslate("Stalemate"),
            2 => totranslate("Midline invasion"),
            3 => totranslate("Threefold repetition"),
            4 => totranslate("50 move rule"),
            5 => totranslate("Agreed to draw"),
            6 => totranslate("Concession")
        ),

        12 => array(
            0 => totranslate("Classic"),
            1 => totranslate("Nemesis"),
            2 => totranslate("Empowered"),
            3 => totranslate("Reaper"),
            4 => totranslate("Two Kings"),
            5 => totranslate("Animal")
        )

    )

);
