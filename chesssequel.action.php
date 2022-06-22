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
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "chesssequel_chesssequel";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// TODO: defines your action entry points there

    // Manage the "pickArmy" action on the server side
    public function pickArmy()
    {
      // We get the arguments from the javascript call and call a corresponding "pickArmy" method in game.php
      self::setAjaxMode();     
      $army_name = self::getArg( "army_name", AT_alphanum, true );
      $result = $this->game->pickArmy( $army_name );
      self::ajaxResponse( );
    }

    // Manage the "confirmArmy" action on the server side
    public function confirmArmy()
    {
      // We call a corresponding "confirmArmy" method in game.php
      self::setAjaxMode();     
      $result = $this->game->confirmArmy();
      self::ajaxResponse( );
    }

    public function findValidMoves()
    {
      // We call a corresponding "findValidMoves" method in game.php
      self::setAjaxMode();
      $piece_id = self::getArg( "piece_id", AT_alphanum, true );     
      $result = $this->game->findValidMoves( $piece_id );
      self::ajaxResponse( );
    }

    public function movePiece()
    {
      // We call a corresponding "movePiece" method in game.php
      self::setAjaxMode();
      $target_file = self::getArg( "target_file", AT_posint, true ); 
      $target_rank = self::getArg( "target_rank", AT_posint, true );    
      $result = $this->game->movePiece( $target_file, $target_rank );
      self::ajaxResponse( );
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
  

