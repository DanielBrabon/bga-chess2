/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * ChessSequel implementation : © <Daniel Brabon> <dev.d8dms@simplelogin.co>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * chesssequel.js
 *
 * ChessSequel user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.chesssequel", ebg.core.gamegui, {
        constructor: function(){
            console.log('chesssequel constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {                
                // Setting up players boards
                var player = gamedatas.players[player_id];
            }

            // Placing pieces on the board
            if ( gamedatas.board_state.length === 0) // We're in armySelect and pieces haven't been added to the database yet
            {
                for( var player_id in gamedatas.players )
                {                
                    var player = gamedatas.players[player_id];

                    this.placeStartingPiecesOnBoard(player.army, player.color);
                    // TO DO: Call an animation to put a pulsating glow on their side of the board, later removed when confirming army
                }
            }
            else // Players have confirmed their armies and their pieces have been added to the database
            {
                // TO DO: gamedatas.pieces example
                
                for ( var piece_id in gamedatas.pieces )
                {
                    var piece_info = gamedatas.pieces[piece_id];

                    if ( piece_info['if_captured'] === "0" )
                    {
                        this.addPieceOnSquare( piece_info['piece_color'], piece_info['piece_type'], piece_info['piece_id'], piece_info['board_file'], piece_info['board_rank'] );

                        if ( piece_info['if_attacking'] === "1" )
                        {
                            this.pieceAttacking( piece_info['piece_id'], piece_info['board_file'], piece_info['board_rank'] );
                        }
                    }
                }
            }

            // TODO: Set up your game interface here, according to "gamedatas"

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            // When an element with the army_button class is clicked, call pickArmy
            dojo.query( '.army_button' ).connect( 'onclick', this, 'pickArmy' );

            // When the confirm army button is clicked, call confirmArmy
            dojo.query( '#btn_confirm_army' ).connect( 'onclick', this, 'confirmArmy' );

            // Any time an empty square is clicked, call squareClicked
            dojo.query( '.square' ).connect( 'onclick', this, 'squareClicked' );

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
                case 'playerMove':
                    this.updateAllLegalMoves( args.args.allLegalMoves, args.args.allCorrespondingCaptures );
                    break;
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        addPieceOnSquare: function( color, type, piece_id, file, rank )
        {
            // The color argument is as a hex code, either 000000 or ffffff
            // The type argument is like "queen" or "nemesispawn"
            // The piece_id argument will be the id field of the created HTML div

            // Insert the HTML for a piece as a child of pieces
            dojo.place( this.format_block( 'jstpl_piece', {
                color: color,
                type: type,
                piece_id: piece_id
            } ), 'pieces' );

            dojo.query( '#'+piece_id ).connect( 'onclick', this, 'pieceClicked' );

            // With BGA "this.placeOnObject" method, place this element over the right square
            this.placeOnObject( piece_id, 'square_'+file+'_'+rank );
        },

        placePiecesOnBoard: function( pieces )
        {
            // pieces should be an array of all pieces to place on the board
            // Format: { piece_id: { file: file, rank: rank, type: type, color: color }, ... }
        },

        placeStartingPiecesOnBoard: function( army_name, player_color )
        {
            // Gets an array of starting pieces for that army type where:
            // Keys are a name for the piece e.g. "pawn_1"
            // Values are the array [ file, rank, type ] for that piece (on white side)
            var army_starting_layout = JSON.parse(JSON.stringify(this.gamedatas.all_armies_starting_layout[army_name]));

            // If this is for black, change the ranks to be correct for this player
            if ( player_color === "000000" )
            {
                for ( var piece_name in army_starting_layout )
                {
                    army_starting_layout[piece_name][1] = 9 - army_starting_layout[piece_name][1];
                }
            }

            // If pieces have already been placed for that color, remove those HTML elements
            var dojo_query = dojo.query( '.piececolor_'+player_color );
            if ( dojo_query.length != 0 )
            {
                // Should I use for-of instead of for-in? Would that mean I don't need the if statement?
                for ( var piece in dojo_query )
                {
                    if ( dojo_query[piece].id != undefined )
                    {                  
                        var piece_id = dojo_query[piece].id;
                        this.disconnect( $(piece_id), 'onclick');
                    }
                }

                dojo.query( '.piececolor_'+player_color ).forEach(dojo.destroy);
            }

            // Create an HTML element on the page for each piece in the starting layout
            for ( var piece_name in army_starting_layout )
            {
                var piece_info = army_starting_layout[piece_name];
                this.addPieceOnSquare( player_color, piece_info[2], player_color+'_'+piece_name, piece_info[0], piece_info[1]);
            }
        },

        pieceAttacking: function( capturing_piece_id, square_file, square_rank )
        {
            console.log( "pieceAttacking called" );
            // Move the attacking piece off centre on the square so both can be seen, and highlight the square
        },

        pieceNoLongerAttacking: function( capturing_piece_id, square_file, square_rank )
        {
            console.log( "pieceNoLongerAttacking called" );
            // Undo the effect of pieceAttacking
        },

        playerConfirmedArmy: function( player_id, player_name )
        {
            // Could hightlight the board somehow to show that the choice was confirmed
        },

        updateAllLegalMoves: function( all_legal_moves, all_corresponding_captures )
        {
            if ( this.isCurrentPlayerActive() )
            {
                this.gamedatas.all_legal_moves = all_legal_moves;
                this.gamedatas.all_corresponding_captures = all_corresponding_captures;
            }
            else
            {
                this.gamedatas.all_legal_moves = [];
                this.gamedatas.all_corresponding_captures = [];
            }

            console.log( this.gamedatas.all_legal_moves );
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

        pickArmy: function( evt )
        {
            // We stop the propagation of the Javascript "onclick" event. 
            // Otherwise, it can lead to random behavior so it's always a good idea.
            dojo.stopEvent( evt );

            // Gets the army name, e.g. 'classic'
            var army_name = evt.currentTarget.id.split('_')[1];

            // Gets the array of valid army names that started in material.inc.php and was returned by getAllDatas
            var all_army_names = this.gamedatas.all_army_names;

            // Check client side that the army name is valid
            if (all_army_names.indexOf(army_name) >= 0)
            {
                // Check that "pickArmy" action is possible, according to current game state
                if ( this.checkAction( 'pickArmy' ) )
                {       
                    // Make a call to the server using BGA "ajaxcall" method with argument army_name.
                    this.ajaxcall( "/chesssequel/chesssequel/pickArmy.html", {
                        army_name:army_name
                    }, this, function( result ) {} );
                }   
            }
        },

        confirmArmy: function( evt )
        {
            // We stop the propagation of the Javascript "onclick" event. 
            // Otherwise, it can lead to random behavior so it's always a good idea.
            dojo.stopEvent( evt );

            // Check that "confirmArmy" action is possible, according to current game state
            if ( this.checkAction( 'confirmArmy' ) )
            {            
                // Make a call to the server using BGA "ajaxcall" method with no argument
                this.ajaxcall( "/chesssequel/chesssequel/confirmArmy.html", {
                }, this, function( result ) {} );
            }  
        },

        pieceClicked: function ( evt )
        {            
            // We stop the propagation of the Javascript "onclick" event. 
            // Otherwise, it can lead to random behavior so it's always a good idea.
            dojo.stopEvent( evt );

            var player_id = this.getActivePlayerId();

            // If the player doesn't have a highlighted piece, find the valid moves for that piece and highlight those moves and the piece
            if ( dojo.query( '.highlight_piece' ).length === 0 )
            {
                // If this player is active, and the action is allowed in the current game state, and they click on one of their own pieces
                if ( this.checkAction( 'displayAvailableMoves', true ) && evt.currentTarget.id.split('_')[0] === this.gamedatas.players[ player_id ].color )
                {
                    this.gamedatas.players[ player_id ].piece_clicked = evt.currentTarget.id;
                    dojo.addClass( evt.currentTarget.id, 'highlight_piece' );
        
                    var valid_moves = this.gamedatas.all_legal_moves[ evt.currentTarget.id ]
                    var valid_moves_length = valid_moves.length;
        
                    for ( var i = 0; i < valid_moves_length; i++ )
                    {
                        dojo.addClass( 'square_'+valid_moves[i][0]+'_'+valid_moves[i][1], 'possible_move' );
                        // I would also like to make it so that if a player hovers over one of these possible move squares, the corresponding capture squares and indicated
                    }
                }
            }
            // If the player clicks the same friendly piece again, deselect it
            else if ( dojo.query( '.highlight_piece' )[0]['id'] === evt.currentTarget.id )
            {
                dojo.query( '.highlight_piece' ).removeClass( 'highlight_piece' );
                dojo.query( '.possible_move' ).removeClass( 'possible_move' );
            }
            // If the player has already clicked a friendly piece and then clicks another piece, try to move the first piece to the second
            else
            {
                if ( this.checkAction( 'movePiece' ) )
                {
                    // Find the location of the piece being clicked on
                    var target_piece_file = this.gamedatas.pieces[evt.currentTarget.id]['board_file'];
                    var target_piece_rank = this.gamedatas.pieces[evt.currentTarget.id]['board_rank'];

                    console.log("clicked on a piece at: "+target_piece_file+", "+target_piece_rank);

                    // Make a call to the server using BGA "ajaxcall" method
                    this.ajaxcall( "/chesssequel/chesssequel/movePiece.html", {
                        target_file:target_piece_file,
                        target_rank:target_piece_rank,
                        moving_piece_id:this.gamedatas.players[ player_id ].piece_clicked
                    }, this, function( result ) {} );
                }
            }
        },

        squareClicked: function ( evt )
        {
            // We stop the propagation of the Javascript "onclick" event. 
            // Otherwise, it can lead to random behavior so it's always a good idea.
            dojo.stopEvent( evt );

            // If there is a highlighted friendly piece to move (from pieceClicked), and this player is active, and this move is allowed in this game state
            if ( dojo.query( '.highlight_piece' ).length != 0  && this.checkAction( 'movePiece' ) )
            {
                // The location of the square being clicked on
                var target_square_file = evt.currentTarget.id.split('_')[1];
                var target_square_rank = evt.currentTarget.id.split('_')[2];

                console.log( "clicked on a square at: "+target_square_file+", "+target_square_rank );

                // Make a call to the server using BGA "ajaxcall" method
                this.ajaxcall( "/chesssequel/chesssequel/movePiece.html", {
                    target_file:target_square_file,
                    target_rank:target_square_rank,
                    moving_piece_id:this.gamedatas.players[ this.getActivePlayerId() ].piece_clicked
                }, this, function( result ) {} );
            }
        },
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/chesssequel/chesssequel/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your chesssequel.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 

            // Associate each notification with a method prefixed with "notif_"
            dojo.subscribe( 'pickArmy', this, "notif_pickArmy" );

            dojo.subscribe( 'confirmArmy', this, "notif_confirmArmy" );

            dojo.subscribe( 'movePiece', this, "notif_movePiece" );

            dojo.subscribe( 'stBoardSetup', this, "notif_stBoardSetup" );
            
            dojo.subscribe( 'resolveAttack', this, "notif_resolveAttack" );

            dojo.subscribe( 'highlightAttackedSquares', this, "notif_highlightAttackedSquares" );
            
            dojo.subscribe( 'printWithJavascript', this, "notif_printWithJavascript" );
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        notif_pickArmy: function( notif )
        {   
            // Call a function that places the starting pieces for that army_name on the board for that player_id
            this.placeStartingPiecesOnBoard( notif.args.army_name, notif.args.player_color );
        },

        notif_confirmArmy: function( notif )
        {
            this.playerConfirmedArmy( notif.args.player_id, notif.args.player_name );
        },

        notif_stBoardSetup: function( notif )
        {
            // Updates gamedatas for all players with the new information added to the database during boardSetup

            // Update the board state information in gamedatas to reflect the changes made during boardSetup
            var board_info = notif.args.board_table_update_information;
            var board_state_object = {};

            for ( let i = 1; i <= 8; i++ )
            {
                board_state_object[i] = {};

                for ( let j = 1; j <= 8; j++ )
                {
                    board_state_object[i][j] = {defending_piece: null, capturing_piece: null};
                    board_state_object[i][j].board_file = String(i);
                    board_state_object[i][j].board_rank = String(j);
                }
            }
            for ( var entry in board_info )
            {
                board_state_object[ board_info[entry][0] ][ board_info[entry][1] ].defending_piece = board_info[entry][2];
            }
            this.gamedatas.board_state = board_state_object;

            // Update the pieces information in gamedatas to reflect the changes made during boardSetup          
            var pieces_info = notif.args.pieces_table_update_information;
            var pieces_object = {};

            for ( var piece in pieces_info )
            {
                pieces_object[ pieces_info[piece][0] ] = {};
                pieces_object[ pieces_info[piece][0] ].piece_id = pieces_info[piece][0];
                pieces_object[ pieces_info[piece][0] ].piece_color = pieces_info[piece][1];
                pieces_object[ pieces_info[piece][0] ].piece_type = pieces_info[piece][2];
                pieces_object[ pieces_info[piece][0] ].board_file = String(pieces_info[piece][3]);
                pieces_object[ pieces_info[piece][0] ].board_rank = String(pieces_info[piece][4]);
                pieces_object[ pieces_info[piece][0] ].if_attacking = String(0);
                pieces_object[ pieces_info[piece][0] ].if_captured = String(0);
                pieces_object[ pieces_info[piece][0] ].moves_made = String(0);
            }
            this.gamedatas.pieces = pieces_object;
        },

        notif_movePiece: function( notif )
        {
            var moving_piece_id = notif.args.moving_piece_id;
            
            // Update pieces info in gamedatas
            this.gamedatas.pieces[ moving_piece_id ].board_file = notif.args.target_file;
            this.gamedatas.pieces[ moving_piece_id ].board_rank = notif.args.target_rank;
            
            // Update board_state info in gamedatas
            this.gamedatas.board_state[notif.args.moving_piece_starting_location_file][notif.args.moving_piece_starting_location_rank].defending_piece = null;
            if ( notif.args.if_attacking === "1" )
            {
                this.gamedatas.pieces[ moving_piece_id ].if_attacking = "1";
                this.gamedatas.board_state[notif.args.target_file][notif.args.target_rank].capturing_piece = moving_piece_id;
            }
            else
            {
                this.gamedatas.board_state[notif.args.target_file][notif.args.target_rank].defending_piece = moving_piece_id;
            }

            // Animate the piece moving
            this.slideToObject( moving_piece_id, 'square_'+notif.args.target_file+'_'+notif.args.target_rank ).play();

            // Applies some extra visual changes if the piece is attacking
            if ( this.gamedatas.pieces[moving_piece_id].if_attacking === "1" )
            {
                this.pieceAttacking( notif.args.moving_piece_id, notif.args.target_file, notif.args.target_file );
            }

            // Removes the highlights on the board
            dojo.query( '.highlight_piece' ).removeClass( 'highlight_piece' );
            dojo.query( '.possible_move' ).removeClass( 'possible_move' );
        },

        notif_resolveAttack: function( notif )
        {
            // Update the gamedatas for pieces and board state
            this.gamedatas.pieces[notif.args.defending_piece_id].if_captured = "1";
            this.gamedatas.pieces[notif.args.capturing_piece_id].if_attacking = "0";
            this.gamedatas.board_state[notif.args.board_file][notif.args.board_rank].defending_piece = notif.args.capturing_piece_id;
            this.gamedatas.board_state[notif.args.board_file][notif.args.board_rank].capturing_piece = null;

            // Undo the attacking visual effect
            this.pieceNoLongerAttacking( notif.args.capturing_piece_id, notif.args.board_file, notif.args.board_rank );

            // Disconnect the onclick and remove the piece that got captured
            this.disconnect( $(notif.args.defending_piece_id), 'onclick');
            dojo.query( '#'+notif.args.defending_piece_id ).forEach(dojo.destroy);

            // If the attack was an en passant, move the attacking pawn to the correct location
            if ( notif.args.if_en_passant === "1" )
            {
                var rank_to_use = notif.args.board_rank + 1;
                if ( notif.args.piece_color === "000000" )
                {
                    rank_to_use -= 2;
                }

                this.gamedatas.pieces[notif.args.capturing_piece_id].board_rank = rank_to_use;
                this.gamedatas.pieces[notif.args.capturing_piece_id].if_performing_en_passant = "0";
                this.gamedatas.board_state[notif.args.board_file][notif.args.board_rank].defending_piece = null;
                this.gamedatas.board_state[notif.args.board_file][rank_to_use].defending_piece = notif.args.capturing_piece_id;

                this.slideToObject( notif.args.capturing_piece_id, 'square_'+notif.args.board_file+'_'+rank_to_use ).play();
            }
        },

        notif_highlightAttackedSquares: function( notif )
        {
            dojo.query( '.attacked_square' ).removeClass( 'attacked_square' );
            dojo.query( '.semi_attacked_square' ).removeClass( 'semi_attacked_square' );

            for ( var i = 1; i <= 8; i++ )
            {
                for ( var j = 1; j <= 8; j++ )
                {
                    if ( notif.args.attacked_squares[i][j].length != 0 )
                    {
                        dojo.addClass( 'square_'+i+'_'+j, 'attacked_square' );
                    }
                    else if ( notif.args.semi_attacked_squares[i][j].length != 0 )
                    {
                        dojo.addClass( 'square_'+i+'_'+j, 'semi_attacked_square' );
                    }
                }
            }
        },

        notif_printWithJavascript: function( notif )
        {
            console.log( notif.args.x );
        }

        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });             
});
