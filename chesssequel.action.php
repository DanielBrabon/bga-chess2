<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * ChessSequel implementation : © <Daniel Brabon> <dev.d8dms@simplelogin.co>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * chesssequel.action.php
 *
 * ChessSequel main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/chesssequel/chesssequel/myAction.html", ...)
 *
 */


class action_chesssequel extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if (self::isArg('notifwindow')) {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
    } else {
      $this->view = "chesssequel_chesssequel";
      self::trace("Complete reinitialization of board game");
    }
  }

  // TODO: defines your action entry points there

  // Manage the "confirmArmy" action on the server side
  public function confirmArmy()
  {
    // We call a corresponding "confirmArmy" method in game.php
    self::setAjaxMode();
    $army_name = self::getArg("army_name", AT_alphanum, true);
    $result = $this->game->confirmArmy($army_name);
    self::ajaxResponse();
  }

  public function movePiece()
  {
    // We call a corresponding "movePiece" method in game.php
    self::setAjaxMode();
    $target_file = self::getArg("target_file", AT_posint, true);
    $target_rank = self::getArg("target_rank", AT_posint, true);
    $moving_piece_id = self::getArg("moving_piece_id", AT_posint, true);
    $result = $this->game->movePiece($target_file, $target_rank, $moving_piece_id);
    self::ajaxResponse();
  }

  public function passKingMove()
  {
    // We call a corresponding "passKingMove" method in game.php
    self::setAjaxMode();
    $result = $this->game->passKingMove();
    self::ajaxResponse();
  }

  public function acceptDuel()
  {
    // We call a corresponding "acceptDuel" method in game.php
    self::setAjaxMode();
    $result = $this->game->acceptDuel();
    self::ajaxResponse();
  }

  public function rejectDuel()
  {
    // We call a corresponding "rejectDuel" method in game.php
    self::setAjaxMode();
    $result = $this->game->rejectDuel();
    self::ajaxResponse();
  }

  public function pickBid()
  {
    // We call a corresponding "pickBid" method in game.php
    self::setAjaxMode();
    $bid_amount = self::getArg("bid_amount", AT_alphanum, true);
    $result = $this->game->pickBid($bid_amount);
    self::ajaxResponse();
  }

  public function gainStone()
  {
    // We call a corresponding "gainStone" method in game.php
    self::setAjaxMode();
    $result = $this->game->gainStone();
    self::ajaxResponse();
  }

  public function destroyStone()
  {
    // We call a corresponding "destroyStone" method in game.php
    self::setAjaxMode();
    $result = $this->game->destroyStone();
    self::ajaxResponse();
  }

  public function promotePawn()
  {
    // We call a corresponding "promotePawn" method in game.php
    self::setAjaxMode();
    $chosen_promotion = self::getArg("chosen_promotion", AT_alphanum, true);
    $result = $this->game->promotePawn($chosen_promotion);
    self::ajaxResponse();
  }
  
  public function offerDraw()
  {
    // We call a corresponding "offerDraw" method in game.php
    self::setAjaxMode();
    $result = $this->game->offerDraw();
    self::ajaxResponse();
  }

  public function acceptDraw()
  {
    // We call a corresponding "acceptDraw" method in game.php
    self::setAjaxMode();
    $result = $this->game->acceptDraw();
    self::ajaxResponse();
  }

  public function rejectDraw()
  {
    // We call a corresponding "rejectDraw" method in game.php
    self::setAjaxMode();
    $result = $this->game->rejectDraw();
    self::ajaxResponse();
  }

  public function concedeGame()
  {
    // We call a corresponding "concedeGame" method in game.php
    self::setAjaxMode();
    $result = $this->game->concedeGame();
    self::ajaxResponse();
  }



  /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */
}
