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
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
    function (dojo, declare) {
        return declare("bgagame.chesssequel", ebg.core.gamegui, {
            constructor: function () {
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

            setup: function (gamedatas) {
                console.log("Starting game setup");

                // Setting up player boards
                // for (var player_id in gamedatas.players) {
                //     var player = gamedatas.players[player_id];
                // }

                // Placing pieces on the board
                if (gamedatas.board_state.length === 0) // We're in armySelect and pieces haven't been added to the database yet
                {
                    for (var player_id in gamedatas.players) {
                        if (player_id == this.player_id) {
                            var army = "classic";
                        }
                        else {
                            var army = "empty";
                        }
                        this.placeStartingPiecesOnBoard(army, gamedatas.players[player_id].color);
                    }
                }
                else // Players have confirmed their armies and their pieces have been added to the database
                {
                    for (var piece_id in gamedatas.pieces) {
                        var piece_info = gamedatas.pieces[piece_id];

                        if (piece_info['if_captured'] === "0") {
                            this.addPieceOnSquare(piece_info['piece_color'], piece_info['piece_type'], piece_info['piece_id'], piece_info['board_file'], piece_info['board_rank']);

                            if (piece_info['if_capturing'] === "1") {
                                this.pieceCapturing(piece_info['piece_id'], piece_info['board_file'], piece_info['board_rank']);
                            }
                        }
                    }
                }

                if (gamedatas.players[this.player_id].color === "000000") {
                    dojo.addClass('board', 'flipped');
                    dojo.query('.piece').addClass('flipped');
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                // Any time an empty square is clicked, call squareClicked
                dojo.query('.square').connect('onclick', this, 'squareClicked');

                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'armySelect':
                            $('pagemaintitletext').innerHTML = "<span class='playername' style='color:#" + this.gamedatas.players[this.player_id].color +
                                ";'>You</span> must choose your army<br>Current selection: Classic<br>";
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
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {

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
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'armySelect':
                            const army_names = this.gamedatas.all_army_names;

                            for (var army_index in army_names) {
                                var army_name = army_names[army_index];
                                this.addActionButton('btn_' + army_name, this.gamedatas.button_labels[army_name], 'pickArmy');
                            }

                            this.addActionButton('btn_confirm_army', _('Confirm Army'), 'confirmArmy', null, false, 'red');

                            break;

                        case 'playerMove':
                            if (this.gamedatas.players[this.getActivePlayerId()].army === "twokings") {
                                this.addActionButton('btn_whirlwind', _('Whirlwind Attack'), 'whirlwindClicked');
                            }

                            break;

                        case 'playerKingMove':
                            this.addActionButton('btn_whirlwind', _('Whirlwind Attack'), 'whirlwindClicked');
                            this.addActionButton('btn_pass_king_move', _('Pass King Move'), 'passKingMove', null, false, 'red');

                            break;

                        case 'duelOffer':
                            this.addActionButton('btn_accept_duel', _('Accept Duel'), 'acceptDuel');
                            this.addActionButton('btn_reject_duel', _('Reject Duel'), 'rejectDuel');
                            break;

                        case 'duelBidding':
                            this.addActionButton('btn_bid_zero', _('Bid 0 Stones'), 'pickBid');
                            this.addActionButton('btn_bid_one', _('Bid 1 Stone'), 'pickBid');
                            this.addActionButton('btn_bid_two', _('Bid 2 Stones'), 'pickBid');
                            break;

                        case 'pawnPromotion':
                            var player_army = this.gamedatas.players[this.getActivePlayerId()]['army'];

                            for (var piece_type_index in args.promoteOptions[player_army]) {
                                var piece_type = args.promoteOptions[player_army][piece_type_index];
                                this.addActionButton('btn_promote_' + piece_type, _(piece_type), 'choosePromotion');
                            }

                            break;
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

            addPieceOnSquare: function (color, type, piece_id, file, rank) {
                // The color argument is as a hex code, either 000000 or ffffff
                // The type argument is like "queen" or "nemesispawn"
                // The piece_id argument will be the id field of the created HTML div

                // Insert the HTML for a piece as a child of pieces
                dojo.place(this.format_block('jstpl_piece', {
                    color: color,
                    type: type,
                    piece_id: piece_id
                }), 'pieces');

                dojo.query('#' + piece_id).connect('onclick', this, 'pieceClicked');

                // With BGA "this.placeOnObject" method, place this element over the right square
                this.placeOnObject(piece_id, 'square_' + file + '_' + rank);
            },

            placeStartingPiecesOnBoard: function (army_name, player_color) {
                dojo.query('.flipped').removeClass('flipped');

                // Gets an array of starting pieces for that army type where:
                // Keys are a name for the piece e.g. "pawn_1"
                // Values are the array [ file, rank, type ] for that piece (on white side)
                var army_starting_layout = JSON.parse(JSON.stringify(this.gamedatas.all_armies_starting_layout[army_name]));

                // If this is for black, change the ranks to be correct for this player
                if (player_color === "000000") {
                    for (var piece_name in army_starting_layout) {
                        army_starting_layout[piece_name][1] = 9 - army_starting_layout[piece_name][1];
                    }
                }

                // If pieces have already been placed for that color, remove those HTML elements
                var dojo_query = dojo.query('.piececolor_' + player_color);
                if (dojo_query.length != 0) {
                    // Should I use for-of instead of for-in? Would that mean I don't need the if statement?
                    for (var piece in dojo_query) {
                        if (dojo_query[piece].id != undefined) {
                            var piece_id = dojo_query[piece].id;
                            this.disconnect($(piece_id), 'onclick');
                        }
                    }

                    dojo.query('.piececolor_' + player_color).forEach(dojo.destroy);
                }

                // Create an HTML element on the page for each piece in the starting layout
                for (var piece_name in army_starting_layout) {
                    var piece_info = army_starting_layout[piece_name];
                    this.addPieceOnSquare(player_color, piece_info[2], player_color + '_' + piece_name, piece_info[0], piece_info[1]);
                }
            },

            pieceCapturing: function (capturing_piece_id, location) {
                console.log("pieceCapturing called");
                // Move the attacking piece off centre on the square so both can be seen, and highlight the square
            },

            pieceNoLongerCapturing: function (capturing_piece_id, location) {
                console.log("pieceNoLongerCapturing called");
                // Undo the effect of pieceCapturing
            },

            playerConfirmedArmy: function (player_id, player_name) {
                // Could hightlight the board somehow to show that the choice was confirmed
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

            pickArmy: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                // Gets the army name, e.g. 'classic'
                var army_name = evt.currentTarget.id.split('_')[1];

                // Gets the array of valid army names that started in material.inc.php and was returned by getAllDatas
                var all_army_names = this.gamedatas.all_army_names;

                // Check client side that the army name is valid
                if (all_army_names.indexOf(army_name) >= 0) {
                    // Place the starting pieces for that army_name on the board for this player
                    dojo.query('.flipped').removeClass('flipped');

                    this.placeStartingPiecesOnBoard(army_name, this.gamedatas.players[this.player_id].color);
                    this.gamedatas.players[this.player_id].army = army_name;

                    if (this.gamedatas.players[this.player_id].color === "000000") {
                        dojo.addClass('board', 'flipped');
                        dojo.query('.piece').addClass('flipped');
                    }

                    $('pagemaintitletext').innerHTML = "<span class='playername' style='color:#" + this.gamedatas.players[this.player_id].color +
                        ";'>You</span> must choose your army<br>Current selection: " + this.gamedatas.button_labels[army_name] + "<br>";
                }
            },

            confirmArmy: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                // Check that "confirmArmy" action is possible, according to current game state
                if (this.checkAction('confirmArmy')) {
                    // Make a call to the server using BGA "ajaxcall" method
                    this.ajaxcall("/chesssequel/chesssequel/confirmArmy.html", {
                        army_name: this.gamedatas.players[this.player_id].army
                    }, this, function (result) { });
                }
            },

            pieceClicked: function (evt) {
                // We stop the propagation of the Javascript "onclick" event to avoid random behaviour
                dojo.stopEvent(evt);

                var player_id = this.getActivePlayerId();

                // If the player doesn't have a highlighted piece, find the valid moves for that piece and highlight those moves and the piece
                if (dojo.query('.highlight_piece').length === 0) {
                    // If this player is active, and the action is allowed in the current game state, and they click on one of their own pieces
                    if (this.checkAction('displayAvailableMoves', true) && evt.currentTarget.id.split('_')[0] === this.gamedatas.players[player_id].color) {
                        this.gamedatas.players[player_id].piece_clicked = evt.currentTarget.id;
                        dojo.addClass(evt.currentTarget.id, 'highlight_piece');

                        for (var move_index in this.gamedatas.legal_moves) {
                            var move_object = this.gamedatas.legal_moves[move_index];

                            if (move_object['moving_piece_id'] === evt.currentTarget.id) {
                                dojo.addClass('square_' + move_object['board_file'] + '_' + move_object['board_rank'], 'possible_move');
                            }
                        }
                    }
                }
                // If the player clicks the same friendly piece again, deselect it
                else if (dojo.query('.highlight_piece')[0]['id'] === evt.currentTarget.id) {
                    dojo.query('.highlight_piece').removeClass('highlight_piece');
                    dojo.query('.possible_move').removeClass('possible_move');
                }
                // If the player has already clicked a friendly piece and then clicks another piece, try to move the first piece to the second
                else {
                    if (this.checkAction('movePiece')) {
                        // Find the location of the piece being clicked on
                        var target_piece_file = this.gamedatas.pieces[evt.currentTarget.id]['board_file'];
                        var target_piece_rank = this.gamedatas.pieces[evt.currentTarget.id]['board_rank'];

                        console.log("clicked on a piece at: " + target_piece_file + ", " + target_piece_rank);

                        // Make a call to the server using BGA "ajaxcall" method
                        this.ajaxcall("/chesssequel/chesssequel/movePiece.html", {
                            target_file: target_piece_file,
                            target_rank: target_piece_rank,
                            moving_piece_id: this.gamedatas.players[player_id].piece_clicked
                        }, this, function (result) { });
                    }
                }
            },

            squareClicked: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                // If there is a highlighted friendly piece to move (from pieceClicked), and this player is active, and this move is allowed in this game state
                if (dojo.query('.highlight_piece').length != 0 && this.checkAction('movePiece')) {
                    // The location of the square being clicked on
                    var target_square_file = evt.currentTarget.id.split('_')[1];
                    var target_square_rank = evt.currentTarget.id.split('_')[2];

                    console.log("clicked on a square at: " + target_square_file + ", " + target_square_rank);

                    // Make a call to the server using BGA "ajaxcall" method
                    this.ajaxcall("/chesssequel/chesssequel/movePiece.html", {
                        target_file: target_square_file,
                        target_rank: target_square_rank,
                        moving_piece_id: this.gamedatas.players[this.getActivePlayerId()].piece_clicked
                    }, this, function (result) { });
                }
            },

            whirlwindClicked: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                var player_id = this.getActivePlayerId();

                if (dojo.query('.highlight_piece').length != 0 && this.gamedatas.pieces[this.gamedatas.players[player_id].piece_clicked]['piece_type'] === "warriorking" && this.checkAction('movePiece')) {
                    // Find the location of the piece being clicked on
                    var target_piece_file = this.gamedatas.pieces[this.gamedatas.players[player_id].piece_clicked]['board_file'];
                    var target_piece_rank = this.gamedatas.pieces[this.gamedatas.players[player_id].piece_clicked]['board_rank'];

                    // Make a call to the server using BGA "ajaxcall" method
                    this.ajaxcall("/chesssequel/chesssequel/movePiece.html", {
                        target_file: target_piece_file,
                        target_rank: target_piece_rank,
                        moving_piece_id: this.gamedatas.players[player_id].piece_clicked
                    }, this, function (result) { });
                }
            },

            passKingMove: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('passKingMove')) {
                    // Make a call to the server using BGA "ajaxcall" method
                    this.ajaxcall("/chesssequel/chesssequel/passKingMove.html", {}, this, function (result) { });
                }
            },

            acceptDuel: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('acceptDuel')) {
                    // Make a call to the server using BGA "ajaxcall" method
                    this.ajaxcall("/chesssequel/chesssequel/acceptDuel.html", {
                    }, this, function (result) { });
                }
            },

            rejectDuel: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('rejectDuel')) {
                    // Make a call to the server using BGA "ajaxcall" method
                    this.ajaxcall("/chesssequel/chesssequel/rejectDuel.html", {
                    }, this, function (result) { });
                }
            },

            pickBid: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                console.log("bid chosen: " + evt.currentTarget.id.split('_')[2]);
                var bid_amount = 0;

                switch (evt.currentTarget.id.split('_')[2]) {
                    case 'zero':
                        break;

                    case 'one':
                        bid_amount = 1;
                        break;

                    case 'two':
                        bid_amount = 2;
                        break;

                    default:
                        return;
                }

                if (this.checkAction('pickBid')) {
                    // Make a call to the server using BGA "ajaxcall" method with argument army_name.
                    this.ajaxcall("/chesssequel/chesssequel/pickBid.html", {
                        bid_amount: bid_amount
                    }, this, function (result) { });
                }
            },

            choosePromotion: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('promotePawn')) {
                    var chosen_promotion = evt.currentTarget.id.split('_')[2];

                    // Make a call to the server using BGA "ajaxcall" method
                    this.ajaxcall("/chesssequel/chesssequel/promotePawn.html", {
                        chosen_promotion: chosen_promotion
                    }, this, function (result) { });
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
            setupNotifications: function () {
                console.log('notifications subscriptions setup');

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

                dojo.subscribe('confirmArmy', this, "notif_confirmArmy");

                dojo.subscribe('stBoardSetup', this, "notif_stBoardSetup");

                dojo.subscribe('updateLegalMovesTable', this, "notif_updateLegalMovesTable");

                dojo.subscribe('fillCaptureQueue', this, "notif_fillCaptureQueue");

                dojo.subscribe('updateAllPieceData', this, "notif_updateAllPieceData");

                dojo.subscribe('updateBoardState', this, "notif_updateBoardState");

                dojo.subscribe('updatePlayerData', this, "notif_updatePlayerData")

                dojo.subscribe('deleteFromCaptureQueue', this, "notif_deleteFromCaptureQueue");

                dojo.subscribe('clearHighlights', this, "notif_clearHighlights");

                dojo.subscribe('highlightAttackedSquares', this, "notif_highlightAttackedSquares");

                dojo.subscribe('printWithJavascript', this, "notif_printWithJavascript");
            },

            // TODO: from this point and below, you can write your game notifications handling methods

            notif_confirmArmy: function (notif) {
                this.playerConfirmedArmy(notif.args.player_id, notif.args.player_name);
            },

            notif_stBoardSetup: function (notif) {
                // Updates gamedatas for all players with the new information added to the database during boardSetup

                // Update the board state information in gamedatas to reflect the changes made during boardSetup
                var board_info = notif.args.board_table_update_information;
                var board_state_object = {};

                for (let i = 1; i <= 8; i++) {
                    board_state_object[i] = {};

                    for (let j = 1; j <= 8; j++) {
                        board_state_object[i][j] = { defending_piece: null, capturing_piece: null };
                        board_state_object[i][j].board_file = String(i);
                        board_state_object[i][j].board_rank = String(j);
                    }
                }
                for (var entry in board_info) {
                    board_state_object[board_info[entry][0]][board_info[entry][1]].defending_piece = board_info[entry][2];
                }
                this.gamedatas.board_state = board_state_object;

                // Update the pieces information in gamedatas to reflect the changes made during boardSetup          
                var pieces_info = notif.args.pieces_table_update_information;
                var pieces_object = {};

                for (var piece in pieces_info) {
                    pieces_object[pieces_info[piece][0]] = {};
                    pieces_object[pieces_info[piece][0]].piece_id = pieces_info[piece][0];
                    pieces_object[pieces_info[piece][0]].piece_color = pieces_info[piece][1];
                    pieces_object[pieces_info[piece][0]].piece_type = pieces_info[piece][2];
                    pieces_object[pieces_info[piece][0]].board_file = String(pieces_info[piece][3]);
                    pieces_object[pieces_info[piece][0]].board_rank = String(pieces_info[piece][4]);
                    pieces_object[pieces_info[piece][0]].if_capturing = String(0);
                    pieces_object[pieces_info[piece][0]].if_captured = String(0);
                    pieces_object[pieces_info[piece][0]].moves_made = String(0);
                }
                this.gamedatas.pieces = pieces_object;
            },

            notif_updateLegalMovesTable: function (notif) {
                this.gamedatas.legal_moves = [];

                for (var piece_id in notif.args.moves_added) {
                    var moves_for_piece = notif.args.moves_added[piece_id];

                    for (move_index in moves_for_piece) {
                        move = moves_for_piece[move_index];

                        this.gamedatas.legal_moves.push({ 'moving_piece_id': piece_id, 'board_file': String(move[0]), 'board_rank': String(move[1]) });
                    }
                }

                //console.log(this.gamedatas.legal_moves);
            },

            notif_fillCaptureQueue: function (notif) {
                this.gamedatas.capture_queue = {};

                var capture_queue = notif.args.capture_queue;
                for (var i = 0; i < capture_queue.length; i++) {
                    this.gamedatas.capture_queue[capture_queue[i].substring(2, 3)] = { "capture_id": capture_queue[i].substring(2, 3) };
                    this.gamedatas.capture_queue[capture_queue[i].substring(2, 3)].board_file = capture_queue[i].substring(6, 7);
                    this.gamedatas.capture_queue[capture_queue[i].substring(2, 3)].board_rank = capture_queue[i].substring(10, 11);
                }

                //console.log(this.gamedatas.capture_queue);
            },

            notif_updateAllPieceData: function (notif) {
                for (var field in notif.args.values_updated) {
                    switch (field) {
                        case "location":
                            this.gamedatas.pieces[notif.args.piece_id]['board_file'] = String(notif.args.values_updated[field][0]);
                            this.gamedatas.pieces[notif.args.piece_id]['board_rank'] = String(notif.args.values_updated[field][1]);

                            dojo.query('.flipped').removeClass('flipped');

                            this.slideToObject(notif.args.piece_id, 'square_' + notif.args.values_updated[field][0] + '_' + notif.args.values_updated[field][1]).play();

                            if (this.gamedatas.players[this.player_id].color === "000000") {
                                dojo.addClass('board', 'flipped');
                                dojo.query('.piece').addClass('flipped');
                            }
                            break;

                        case "if_captured":
                            this.disconnect($(notif.args.piece_id), 'onclick');
                            dojo.query('#' + notif.args.piece_id).forEach(dojo.destroy);
                            break;

                        case "if_capturing":
                            if (notif.args.values_updated[field] === "1") {
                                this.pieceCapturing(notif.args.piece_id, notif.args.location);
                            }
                            else {
                                this.pieceNoLongerCapturing(notif.args.piece_id, notif.args.location);
                            }
                            break;

                        case "piece_type":
                            var previous_type = this.gamedatas.pieces[notif.args.piece_id]['piece_type'];

                            dojo.query('#' + notif.args.piece_id).removeClass('piecetype_' + previous_type);
                            dojo.query('#' + notif.args.piece_id).addClass('piecetype_' + notif.args.values_updated['piece_type']);
                            break;
                    }

                    if (field != "location") {
                        this.gamedatas.pieces[notif.args.piece_id][field] = notif.args.values_updated[field];
                    }
                }

                //console.log(this.gamedatas.pieces);
            },

            notif_updateBoardState: function (notif) {
                for (var field in notif.args.values_updated) {
                    this.gamedatas.board_state[notif.args.square[0]][notif.args.square[1]][field] = notif.args.values_updated[field];
                }

                //console.log(this.gamedatas.board_state);
            },

            notif_updatePlayerData: function (notif) {
                for (var field in notif.args.values_updated) {
                    this.gamedatas.players[notif.args.player_id][field] = notif.args.values_updated[field];
                }

                console.log(this.gamedatas.players);
            },

            notif_deleteFromCaptureQueue: function (notif) {
                delete this.gamedatas.capture_queue[notif.args.capture_id];
                //console.log(this.gamedatas.capture_queue);
            },

            notif_clearHighlights: function (notif) {
                dojo.query('.highlight_piece').removeClass('highlight_piece');
                dojo.query('.possible_move').removeClass('possible_move');
            },

            notif_highlightAttackedSquares: function (notif) {
                dojo.query('.attacked_square').removeClass('attacked_square');
                dojo.query('.semi_attacked_square').removeClass('semi_attacked_square');

                for (var i = 1; i <= 8; i++) {
                    for (var j = 1; j <= 8; j++) {
                        if (notif.args.attacked_squares[i][j].length != 0) {
                            dojo.addClass('square_' + i + '_' + j, 'attacked_square');
                        }
                        else if (notif.args.semi_attacked_squares[i][j].length != 0) {
                            dojo.addClass('square_' + i + '_' + j, 'semi_attacked_square');
                        }
                    }
                }
            },

            notif_printWithJavascript: function (notif) {
                console.log(notif.args.x);
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
