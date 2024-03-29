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
 * chesssequel.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in chesssequel_chesssequel.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_chesssequel_chesssequel extends game_view
{
  function getGameName()
  {
    return "chesssequel";
  }
  function build_page($viewArgs)
  {
    // Get players & players number
    $players = $this->game->loadPlayersBasicInfos();
    $players_nbr = count($players);

    /*********** Place your code below:  ************/

    $this->page->begin_block("chesssequel_chesssequel", "square");

    // Files = columns, ranks = rows
    $square_size = 12.5;

    // For each column/file/x
    for ($file = 1; $file <= 8; $file++) {
      // For each row/rank/y in that column
      for ($rank = 1; $rank <= 8; $rank++) {
        // Make that element (a square on the board) in .tpl
        $this->page->insert_block("square", array(
          'X' => $file,
          'Y' => $rank,
          'LEFT' => ($file - 1) * $square_size,
          'TOP' => (8 - $rank) * $square_size
        ));
      }
    }

    $this->page->begin_block("chesssequel_chesssequel", "ranks");
    $this->page->begin_block("chesssequel_chesssequel", "files");

    for ($i = 1; $i <= 8; $i++) {
      $this->page->insert_block("ranks", array(
        'TYPE' => $i % 2,
        'RANK' => $i
      ));

      $this->page->insert_block("files", array(
        'TYPE' => ($i + 1) % 2,
        'FILE' => $this->game->files[$i]
      ));
    }

    $this->page->begin_block("chesssequel_chesssequel", "player_stones");
    $this->page->begin_block("chesssequel_chesssequel", "player_bids");

    foreach (array_keys($players) as $player_id) {
      $this->page->insert_block("player_stones", array('ID' => $player_id));
      $this->page->insert_block("player_bids", array('ID' => $player_id));
    }

    /*
        
        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
        
        */

    /*
        
        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 
        

        $this->page->begin_block( "chesssequel_chesssequel", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array( 
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }
        
        */

    /*********** Do not change anything below this line  ************/
  }
}
