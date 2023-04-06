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
                for (var player_id in gamedatas.players) {
                    var player = gamedatas.players[player_id];

                    var player_board_div = $('player_board_' + player_id);
                    dojo.place(this.format_block('jstpl_player_stones', player), player_board_div);
                }

                // Placing pieces on the board
                this.populateBoard();

                // Flip the board for the black player
                if (gamedatas.players[this.player_id].color === "000000") {
                    dojo.addClass('board', 'flipped');
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                // Connect onclick events
                dojo.query('.square').connect('onclick', this, 'squareClicked');
                dojo.query('#main').connect('onclick', this, 'mainClicked');

                dojo.query('#btn_conc').connect('onclick', this, 'concedeGame');
                dojo.query('#btn_draw').connect('onclick', this, 'offerDraw');

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
                            this.updateArmySelectTitleText("Classic");
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

                if (stateName == 'duelOffer' || stateName == 'duelBidding') {
                    this.gamedatas.cap_id = args.args.capID;
                    this.gamedatas.def_id = args.args.defID;

                    // Place capturing piece on current capture square
                    dojo.place(args.args.capID,
                        'square_' + this.gamedatas.pieces[args.args.defID]['x'] + '_' + this.gamedatas.pieces[args.args.defID]['y']
                    );

                    // Offset pieces
                    dojo.addClass(args.args.capID, 'cap_piece');
                    dojo.addClass(args.args.defID, 'def_piece');

                    // Maybe highlight/outline cap piece
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                if (stateName == 'duelOffer' || stateName == 'duelBidding') {
                    // Place capturing piece back on its own square
                    dojo.place(
                        this.gamedatas.cap_id,
                        'square_' + this.gamedatas.pieces[this.gamedatas.cap_id]['x'] + '_' + this.gamedatas.pieces[this.gamedatas.cap_id]['y']
                    );

                    // Remove offset
                    dojo.query('.cap_piece').removeClass('cap_piece');
                    dojo.query('.def_piece').removeClass('def_piece');
                }

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

                        case 'playerKingMove':
                            this.addActionButton('btn_pass_king_move', _('Pass King Move'), 'passKingMove', null, false, 'red');
                            break;

                        case 'duelOffer':
                            this.addActionButton('btn_accept_duel', _('Accept Duel'), 'acceptDuel');
                            this.addActionButton('btn_reject_duel', _('Reject Duel'), 'rejectDuel');
                            break;

                        case 'duelBidding':
                            let stones = this.gamedatas.players[this.player_id]['stones'];
                            let max_bid = (stones > 2) ? 2 : stones;

                            for (let i = 0; i <= max_bid; i++) {
                                this.addActionButton('btn_bid_' + i, this.gamedatas.button_labels["bid_" + i], 'pickBid');
                            }
                            break;

                        case 'calledBluff':
                            if (this.gamedatas.players[this.player_id]['stones'] != 6) {
                                this.addActionButton('btn_gain_stone', _('Gain 1 Stone'), 'gainStone');
                            }
                            this.addActionButton('btn_destroy_stone', _('Destroy 1 Enemy Stone'), 'destroyStone');
                            break;

                        case 'pawnPromotion':
                            var player_army = this.gamedatas.players[this.getActivePlayerId()]['army'];

                            for (var piece_type_index in args.promoteOptions[player_army]) {
                                var piece_type = args.promoteOptions[player_army][piece_type_index];
                                this.addActionButton('btn_promote_' + piece_type, this.gamedatas.button_labels[piece_type], 'choosePromotion');
                            }
                            break;

                        case 'drawOffer':
                            this.addActionButton('btn_accept_draw', _('Accept Draw'), 'acceptDraw');
                            this.addActionButton('btn_reject_draw', _('Reject Draw'), 'rejectDraw');
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

            ajaxcallWrapper: function (action, args, handler) {
                if (!args) {
                    args = {};
                }
                args.lock = true;

                // Make a call to the server using BGA "ajaxcall" method
                this.ajaxcall("/chesssequel/chesssequel/" + action + ".html", args, this, (result) => { }, handler);
            },

            clearSelectedPiece: function () {
                var selected_pieces = dojo.query('.selected_piece');
                if (selected_pieces.length != 0) {
                    selected_pieces.removeClass('selected_piece');
                    dojo.query('.possible_move').removeClass('possible_move');
                    this.gamedatas.players[this.player_id].piece_selected = null;
                }
            },

            populateBoard: function () {
                if (this.gamedatas.pieces.length === 0) { // We're in armySelect and pieces haven't been added to the database yet
                    for (var player_id in this.gamedatas.players) {
                        let army = (player_id == this.player_id) ? "classic" : "empty";
                        this.placeStartingPiecesOnBoard(army, this.gamedatas.players[player_id].color);
                    }
                }
                else { // Players have confirmed their armies and their pieces have been added to the database
                    for (var piece_id in this.gamedatas.pieces) {
                        var piece_info = this.gamedatas.pieces[piece_id];

                        if (piece_info['captured'] === "0") {
                            // Insert the HTML element for the piece as a child of the square it's on
                            dojo.place(this.format_block('jstpl_piece', {
                                color: piece_info['color'],
                                type: piece_info['type'],
                                piece_id: piece_info['piece_id']
                            }), 'square_' + piece_info['x'] + '_' + piece_info['y']);
                        }
                    }

                    // Flip the pieces for the black player
                    if (this.gamedatas.players[this.player_id].color === "000000") {
                        dojo.query('.piece').addClass('flipped');
                    }
                }
            },

            placeStartingPiecesOnBoard: function (army_name, player_color) {
                // Gets the array of piece types in this army's layout
                var types = this.gamedatas.all_armies_layouts[army_name];

                var piece_id_offset = 1;
                var y_values = [1, 2];
                // If this is for black, change the y values and ids to be correct for this player
                if (player_color === "000000") {
                    piece_id_offset = 17;
                    y_values = [8, 7];
                }

                // If pieces have already been placed for that color, remove those HTML elements
                dojo.query('.piececolor_' + player_color).forEach(dojo.destroy);

                // For each piece in the layout
                for (let piece_index in types) {
                    let x = (piece_index % 8) + 1;
                    let y = y_values[Math.floor(piece_index / 8)];

                    // Insert the HTML element for the piece as a child of the square it's on
                    dojo.place(this.format_block('jstpl_piece', {
                        color: player_color,
                        type: types[piece_index],
                        piece_id: piece_id_offset + Number(piece_index)
                    }), 'square_' + x + '_' + y);
                }

                // Flip the pieces for the black player
                if (this.gamedatas.players[this.player_id].color === "000000") {
                    dojo.query('.piece').addClass('flipped');
                }
            },

            updateArmySelectTitleText: function (army) {
                if (this.gamedatas.players[this.player_id].color === "ffffff") {
                    var you = '<span style="font-weight:bold;color:#ffffff;background-color:#bbbbbb;">You</span>';
                }
                else {
                    var you = '<span style="font-weight:bold;color:#000000;">You</span>';
                }
                $('pagemaintitletext').innerHTML = you + ' must select an army<br>Current selection: ' + army + '<br>';
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
                    this.placeStartingPiecesOnBoard(army_name, this.gamedatas.players[this.player_id].color);
                    this.gamedatas.players[this.player_id].army = army_name;
                    this.updateArmySelectTitleText(this.gamedatas.button_labels[army_name]);
                }
            },

            confirmArmy: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                // Check that "confirmArmy" action is possible, according to current game state
                if (this.checkAction('confirmArmy')) {
                    this.ajaxcallWrapper("confirmArmy", { army_name: this.gamedatas.players[this.player_id].army });
                }
            },

            mainClicked: function (evt) {
                // We stop the propagation of the Javascript "onclick" event.
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                this.clearSelectedPiece();
            },

            squareClicked: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                // If this player is not active or movePiece is not allowed in the current game state or the interface is locked, do nothing
                if (!this.checkAction('movePiece', true)) {
                    return;
                }

                // If the player clicked a highlighted possible move square, call the server to make the move
                if (dojo.hasClass(evt.currentTarget, 'possible_move')) {
                    this.ajaxcallWrapper("movePiece", {
                        target_file: evt.currentTarget.id.split('_')[1],
                        target_rank: evt.currentTarget.id.split('_')[2],
                        moving_piece_id: this.gamedatas.players[this.player_id].piece_selected
                    });
                }
                else {
                    // If the player has a piece selected, deselect it
                    this.clearSelectedPiece();

                    // If the player clicked a square with a friendly piece, select it
                    const children = evt.currentTarget.children;
                    if (children.length != 0 && this.gamedatas.pieces[children[0].id].color === this.gamedatas.players[this.player_id].color) {
                        this.gamedatas.players[this.player_id].piece_selected = children[0].id;
                        dojo.addClass(children[0].id, 'selected_piece');

                        for (var move_index in this.gamedatas.legal_moves) {
                            var move_object = this.gamedatas.legal_moves[move_index];

                            if (move_object['moving_piece_id'] === children[0].id) {
                                dojo.addClass('square_' + move_object['x'] + '_' + move_object['y'], 'possible_move');
                            }
                        }
                    }
                }
            },

            passKingMove: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                this.clearSelectedPiece();

                if (this.checkAction('passKingMove')) {
                    this.ajaxcallWrapper("passKingMove");
                }
            },

            acceptDuel: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('acceptDuel')) {
                    this.ajaxcallWrapper("acceptDuel");
                }
            },

            rejectDuel: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('rejectDuel')) {
                    this.ajaxcallWrapper("rejectDuel");
                }
            },

            pickBid: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('pickBid')) {
                    this.ajaxcallWrapper("pickBid", {
                        bid_amount: evt.currentTarget.id.split('_')[2]
                    });
                }
            },

            gainStone: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('gainStone')) {
                    this.ajaxcallWrapper("gainStone");
                }
            },

            destroyStone: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('destroyStone')) {
                    this.ajaxcallWrapper("destroyStone");
                }
            },

            choosePromotion: function (evt) {
                // We stop the propagation of the Javascript "onclick" event. 
                // Otherwise, it can lead to random behavior so it's always a good idea.
                dojo.stopEvent(evt);

                if (this.checkAction('promotePawn')) {
                    var chosen_promotion = evt.currentTarget.id.split('_')[2];

                    this.ajaxcallWrapper("promotePawn", {
                        chosen_promotion: chosen_promotion
                    });
                }
            },

            offerDraw: function (evt) {
                dojo.stopEvent(evt);

                if (this.checkAction('offerDraw')) {
                    this.ajaxcallWrapper("offerDraw");
                }
            },

            acceptDraw: function (evt) {
                dojo.stopEvent(evt);

                if (this.checkAction('acceptDraw')) {
                    this.ajaxcallWrapper("acceptDraw");
                }
            },

            rejectDraw: function (evt) {
                dojo.stopEvent(evt);

                if (this.checkAction('rejectDraw')) {
                    this.ajaxcallWrapper("rejectDraw");
                }
            },

            concedeGame: function (evt) {
                dojo.stopEvent(evt);

                if (this.checkAction('concedeGame')) {
                    this.ajaxcallWrapper("concedeGame");
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

                dojo.subscribe('updateAllPieceData', this, "notif_updateAllPieceData");

                dojo.subscribe('updatePlayerData', this, "notif_updatePlayerData");

                dojo.subscribe('clearSelectedPiece', this, "notif_clearSelectedPiece");

                dojo.subscribe('highlightAttackedSquares', this, "notif_highlightAttackedSquares");

                dojo.subscribe('printWithJavascript', this, "notif_printWithJavascript");
            },

            // TODO: from this point and below, you can write your game notifications handling methods

            notif_confirmArmy: function (notif) {
                if (this.player_id != notif.args.player_id) {
                    this.updateArmySelectTitleText(this.gamedatas.button_labels[this.gamedatas.players[this.player_id].army]);
                }
            },

            notif_stBoardSetup: function (notif) {
                // Updates gamedatas for all players with the new information added to the database during boardSetup

                // Update the pieces information in gamedatas to reflect the changes made during boardSetup          
                var pieces_info = notif.args.pieces_table_update_information;
                var pieces_object = {};

                for (var piece in pieces_info) {
                    pieces_object[pieces_info[piece][0]] = {};
                    pieces_object[pieces_info[piece][0]].piece_id = pieces_info[piece][0];
                    pieces_object[pieces_info[piece][0]].color = pieces_info[piece][1];
                    pieces_object[pieces_info[piece][0]].type = pieces_info[piece][2];
                    pieces_object[pieces_info[piece][0]].x = String(pieces_info[piece][3]);
                    pieces_object[pieces_info[piece][0]].y = String(pieces_info[piece][4]);
                    pieces_object[pieces_info[piece][0]].capturing = String(0);
                    pieces_object[pieces_info[piece][0]].captured = String(0);
                    pieces_object[pieces_info[piece][0]].moves_made = String(0);
                }
                this.gamedatas.pieces = pieces_object;

                for (var player_id in notif.args.player_armies) {
                    this.gamedatas.players[player_id].army = notif.args.player_armies[player_id];
                    this.placeStartingPiecesOnBoard(this.gamedatas.players[player_id].army, this.gamedatas.players[player_id].color);
                }
            },

            notif_updateLegalMovesTable: function (notif) {
                this.gamedatas.legal_moves = [];

                for (var piece_id in notif.args.moves_added) {
                    var moves_for_piece = notif.args.moves_added[piece_id];

                    for (move_index in moves_for_piece) {
                        move = moves_for_piece[move_index];

                        this.gamedatas.legal_moves.push({ 'moving_piece_id': piece_id, 'x': String(move[0]), 'y': String(move[1]) });
                    }
                }

                //console.log(this.gamedatas.legal_moves);
            },

            notif_updateAllPieceData: function (notif) {
                for (var field in notif.args.values_updated) {
                    switch (field) {
                        case "location":
                            this.gamedatas.pieces[notif.args.piece_id]['x'] = String(notif.args.values_updated[field][0]);
                            this.gamedatas.pieces[notif.args.piece_id]['y'] = String(notif.args.values_updated[field][1]);

                            dojo.place(String(notif.args.piece_id), 'square_' + notif.args.values_updated[field][0] + '_' + notif.args.values_updated[field][1]);
                            break;

                        case "captured":
                            dojo.destroy(String(notif.args.piece_id));
                            break;

                        case "type":
                            var previous_type = this.gamedatas.pieces[notif.args.piece_id]['type'];

                            dojo.query('#' + notif.args.piece_id).removeClass('piecetype_' + previous_type);
                            dojo.query('#' + notif.args.piece_id).addClass('piecetype_' + notif.args.values_updated['type']);
                            break;
                    }

                    if (field != "location") {
                        this.gamedatas.pieces[notif.args.piece_id][field] = notif.args.values_updated[field];
                    }
                }

                //console.log(this.gamedatas.pieces);
            },

            notif_updatePlayerData: function (notif) {
                for (var field in notif.args.values_updated) {
                    this.gamedatas.players[notif.args.player_id][field] = notif.args.values_updated[field];

                    if (field == "stones") {
                        $('player_stones_' + notif.args.player_id).innerHTML = "Stones: " + notif.args.values_updated[field];
                    }
                }

                // console.log(this.gamedatas.players);
            },

            notif_clearSelectedPiece: function () {
                this.clearSelectedPiece();
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
