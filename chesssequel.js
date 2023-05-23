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

                this.duel_styling_applied = false;
                this.own_bid_shown = false;

                this.selected_piece_id = null;
                this.cap_id = null;
                this.def_id = null;
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

                // Set up player boards
                if (this.gamedatas.ruleset_version == this.gamedatas.constants['RULESET_TWO_POINT_FOUR']) {
                    for (var player_id in gamedatas.players) {
                        var player = gamedatas.players[player_id];

                        dojo.place(`player_stones_wrap_${player_id}`, `player_board_${player_id}`);

                        for (let i = 0; i < player.stones; i++) {
                            dojo.place(this.format_block('jstpl_stone', player), `${player_id}_stone_slot_${i}`);
                        }

                        this.addTooltip(`player_stones_wrap_${player_id}`, _('Stones'), _('The resource used in duels'));

                        let player_name = this.getPlayerColorText(player['color'], player['name']);
                        dojo.place(player_name, `duel_board_name_${player_id}`);
                    }

                    dojo.style('player_board_duel', 'display', 'none');
                } else {
                    for (let player_id in gamedatas.players) {
                        dojo.destroy(`player_stones_wrap_${player_id}`);
                    }
                    dojo.destroy(`player_board_duel`);
                }

                // Place pieces on the game board
                this.populateBoard();

                // Highlight last move
                for (let key in this.gamedatas.last_move_piece_ids) {
                    let piece_id = this.gamedatas.last_move_piece_ids[key];
                    if (piece_id != 0) {
                        this.highlightLastMove(piece_id);
                    }
                }

                // Flip the board for the black player
                if (gamedatas.players[this.player_id].color == "000000") {
                    dojo.addClass('board', 'flipped');

                    dojo.style('files', 'flex-direction', 'row-reverse');
                    dojo.style('ranks', 'flex-direction', 'column');

                    dojo.query('.coordtype_0').style('color', 'var(--dark-square-color)');
                    dojo.query('.coordtype_1').style('color', 'var(--light-square-color)');
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                // Connect events
                dojo.query('.square').connect('onclick', this, 'squareClicked');
                dojo.query('.square').connect('onmouseover', this, 'mouseOverSquare');
                dojo.query('.square').connect('onmouseout', this, 'mouseOutSquare');

                dojo.query('#main').connect('onclick', this, 'mainClicked');

                dojo.query('#btn_draw').connect('onclick', this, 'offerDraw');

                // Hide state-specific player boards
                dojo.style('player_board_buttons', 'display', 'none');

                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);

                switch (stateName) {
                    case 'armySelect':
                        if (this.isCurrentPlayerActive()) {
                            this.updateArmySelectTitleText("Classic");
                        }
                        break;

                    case 'playerMove':
                        if (this.isCurrentPlayerActive()) {
                            dojo.style('player_board_buttons', 'display', 'block');
                        }
                        break;

                    case 'duelOffer':
                        this.duelStyling();
                        break;

                    case 'duelBidding':
                        if (!this.duel_styling_applied) {
                            this.duelStyling();
                        }

                        this.setupDuelBoard();

                        // For the case of a player reloading the page after placing a bid
                        if (this.gamedatas.players[this.player_id].bid !== null) {
                            this.placeBidStones(this.player_id, this.gamedatas.players[this.player_id].bid);
                        }

                        break;

                    case 'processDuelOutcome':
                        // For the case of duelling a player with 0 stones
                        if ($('player_board_duel').style.display == 'none') {
                            this.setupDuelBoard();
                        }
                        break;

                    case 'calledBluff':
                        // For the case of calling bluff in the middle of an elephant rampage
                        if (this.gamedatas.capture_queue.length != 0) {
                            this.duelStyling();
                        }
                        break;

                    /* Example:
                    
                    case 'myGameState':
                    
                        // Show some HTML block at this game state
                        dojo.style( 'my_html_block_id', 'display', 'block' );
                        
                        break;
                    */
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {
                    case 'playerMove':
                        dojo.style('player_board_buttons', 'display', 'none');
                        break;

                    case 'processDuelRejected':
                        this.removeDuelStyling();
                        break;

                    case 'processDuelOutcome':
                        this.removeDuelStyling();

                        this.clearDuelBoard();

                        this.gamedatas.players[this.player_id].bid = null;

                        this.own_bid_shown = false;

                        break;

                    case 'processBluffChoice':
                        if (this.duel_styling_applied) {
                            this.removeDuelStyling();
                        }
                        break;

                    /* Example:
                    
                    case 'myGameState':
                    
                        // Hide the HTML block we are displaying only during this game state
                        dojo.style( 'my_html_block_id', 'display', 'none' );
                        
                        break;
                   */
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
                            for (let army of this.gamedatas.all_army_names) {
                                let button_id = `btn_${army}`;

                                let button_piece = this.format_block(
                                    'jstpl_buttonpiece',
                                    {
                                        color: this.gamedatas.players[this.player_id].color,
                                        type: this.gamedatas.all_armies_layouts[army][3]
                                    }
                                );

                                this.addActionButton(button_id, button_piece + this.gamedatas.button_labels[army], 'pickArmy');

                                this.addTooltip(button_id, "", this.gamedatas.army_tooltips[army]);
                            }

                            this.addActionButton('btn_confirm_army', _('Confirm Army'), 'confirmArmy', null, false, 'red');
                            break;

                        case 'playerKingMove':
                            this.addActionButton('btn_pass_king_move', _('Pass King Move'), 'passKingMove', null, false, 'red');
                            break;

                        case 'duelOffer':
                            let accept_label = "Accept Duel";

                            if (args.duel_cost == 1) {
                                accept_label += " (Pay 1 Stone)";
                            }

                            if (args.cap_stones == 0) {
                                accept_label += " (Bid 1 and Win)";
                            }

                            this.addActionButton('btn_accept_duel', accept_label, 'acceptDuel');
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
                            this.addActionButton('btn_gain_stone', _('Gain 1 Stone'), 'gainStone');
                            this.addActionButton('btn_destroy_stone', _('Destroy 1 Enemy Stone'), 'destroyStone');
                            break;

                        case 'pawnPromotion':
                            for (let piece_type of args.promote_options) {
                                let button_piece = this.format_block(
                                    'jstpl_buttonpiece',
                                    {
                                        color: this.gamedatas.players[this.player_id].color,
                                        type: piece_type
                                    }
                                );

                                this.addActionButton(`btn_promote_${piece_type}`, button_piece + this.gamedatas.button_labels[piece_type], 'choosePromotion');
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
                    dojo.query('.possible_move_oc').removeClass('possible_move_oc');
                    dojo.query('.hover_possible_move').removeClass('hover_possible_move');
                    dojo.query('.capture_square').removeClass('capture_square');
                    this.selected_piece_id = null;
                }
            },

            populateBoard: function () {
                if (this.gamedatas.pieces.length == 0) { // We're in armySelect and pieces haven't been added to the database yet
                    for (var player_id in this.gamedatas.players) {
                        let army = (player_id == this.player_id) ? this.gamedatas.players[this.player_id].army : "empty";
                        this.placeStartingPiecesOnBoard(army, this.gamedatas.players[player_id].color);
                    }
                }
                else { // Players have confirmed their armies and their pieces have been added to the database
                    for (var piece_id in this.gamedatas.pieces) {
                        var piece_info = this.gamedatas.pieces[piece_id];

                        if (piece_info['state'] != this.gamedatas.constants['CAPTURED']) {
                            // Insert the HTML element for the piece as a child of the square it's on
                            dojo.place(this.format_block('jstpl_boardpiece', {
                                color: piece_info['color'],
                                type: piece_info['type'],
                                piece_id: piece_info['id']
                            }), 'square_' + piece_info['x'] + '_' + piece_info['y']);

                            this.addTooltip(
                                piece_info['id'],
                                this.gamedatas.piece_tooltips[piece_info['type']].help_string,
                                this.gamedatas.piece_tooltips[piece_info['type']].action_string
                            );
                        }
                    }

                    // Flip the pieces for the black player
                    if (this.gamedatas.players[this.player_id].color == "000000") {
                        dojo.query('.boardpiece').addClass('flipped');
                    }
                }
            },

            placeStartingPiecesOnBoard: function (army_name, player_color) {
                // Gets the array of piece types in this army's layout
                var types = this.gamedatas.all_armies_layouts[army_name];

                var piece_id_offset = 1;
                var y_values = [1, 2];
                // If this is for black, change the y values and ids to be correct for this player
                if (player_color == "000000") {
                    piece_id_offset = 17;
                    y_values = [8, 7];
                }

                // If pieces have already been placed for that color, remove those HTML elements
                dojo.query('.boardpiece.piececolor_' + player_color).forEach(dojo.destroy);

                // For each piece in the layout
                for (let piece_index in types) {
                    let piece_id = piece_id_offset + piece_index;
                    let x = (piece_index % 8) + 1;
                    let y = y_values[Math.floor(piece_index / 8)];

                    // Insert the HTML element for the piece as a child of the square it's on
                    dojo.place(this.format_block('jstpl_boardpiece', {
                        color: player_color,
                        type: types[piece_index],
                        piece_id: piece_id
                    }), 'square_' + x + '_' + y);

                    this.addTooltip(
                        piece_id,
                        this.gamedatas.piece_tooltips[types[piece_index]].help_string,
                        this.gamedatas.piece_tooltips[types[piece_index]].action_string
                    );
                }

                // Flip the pieces for the black player
                if (this.gamedatas.players[this.player_id].color == "000000") {
                    dojo.query('.boardpiece').addClass('flipped');
                }
            },

            highlightLastMove: function (piece_id) {
                let piece = this.gamedatas.pieces[piece_id];
                dojo.addClass(`square_${piece['last_x']}_${piece['last_y']}`, 'last_move');
                dojo.addClass(`square_${piece['x']}_${piece['y']}`, 'last_move');
            },

            updateArmySelectTitleText: function (army) {
                $('pagemaintitletext').innerHTML = this.format_block(
                    'jstpl_army_select_title_text',
                    {
                        you: this.getPlayerColorText(this.gamedatas.players[this.player_id].color, "You"),
                        army: army
                    }
                );
            },

            format_string_recursive: function format_string_recursive(log, args) {
                try {
                    if (log && args) {
                        for (var key in args) {
                            let key_split = key.split("_");

                            switch (key_split[0]) {
                                case 'logpiece':
                                    let value_split = args[key].split("_");
                                    args[key] = this.format_block('jstpl_logpiece', { color: value_split[0], type: value_split[1] });
                                    break;
                                case 'army':
                                    args[key] = this.getPlayerColorText(key_split[1], args[key]);
                                    break;
                            }
                        }
                    }
                } catch (e) {
                    console.error(log, args, "Exception thrown", e.stack);
                }
                return this.inherited({ callee: format_string_recursive }, arguments);
            },

            getPlayerColorText: function (color, text) {
                let bg_color = (color == "ffffff") ? "#bbbbbb" : "transparent";
                return this.format_block('jstpl_player_color_text', { color: color, bg_color: bg_color, text: text });
            },

            updatePlayerOrdering() {
                this.inherited(arguments);

                if (this.gamedatas.ruleset_version == this.gamedatas.constants['RULESET_TWO_POINT_FOUR']) {
                    dojo.place('player_board_duel', 'player_boards', 'last');
                }

                dojo.place('player_board_buttons', 'player_boards', 'last');
            },

            duelStyling: function () {
                // Set this.cap_id and this.def_id
                for (let piece_id in this.gamedatas.pieces) {
                    if (
                        this.gamedatas.pieces[piece_id].state == this.gamedatas.constants['CAPTURING']
                        || this.gamedatas.pieces[piece_id].state == this.gamedatas.constants['CAPTURING_AND_PROMOTING']
                    ) {
                        this.cap_id = piece_id;
                        break;
                    }
                }

                this.def_id = this.gamedatas.capture_queue[0];

                // Place capturing piece on current capture square
                dojo.place(this.cap_id, $(this.gamedatas.capture_queue[0]).parentNode);

                // Offset pieces
                dx = this.gamedatas.pieces[this.cap_id]['last_x'] - this.gamedatas.pieces[this.def_id]['x'];
                dy = this.gamedatas.pieces[this.def_id]['y'] - this.gamedatas.pieces[this.cap_id]['last_y'];

                x_offset = (dx == 0) ? 0 : dx / Math.abs(dx);
                y_offset = (dy == 0) ? 0 : dy / Math.abs(dy);

                let offset_unit = (x_offset + y_offset == 2) ? 25 : 35;

                dojo.style(this.cap_id, 'translate', `${x_offset * offset_unit}% ${y_offset * offset_unit}%`);
                dojo.style(this.def_id, 'translate', `${x_offset * -1 * offset_unit}% ${y_offset * -1 * offset_unit}%`);

                // Highlight capture squares
                for (let piece_id of this.gamedatas.capture_queue) {
                    dojo.addClass($(piece_id).parentNode, 'capture_square');
                }

                this.duel_styling_applied = true;
            },

            removeDuelStyling: function () {
                // Place capturing piece back on its own square and remove offsets
                if (this.gamedatas.pieces[this.cap_id].state != this.gamedatas.constants['CAPTURED']) {
                    dojo.place(
                        this.cap_id,
                        'square_' + this.gamedatas.pieces[this.cap_id]['x'] + '_' + this.gamedatas.pieces[this.cap_id]['y']
                    );

                    dojo.removeAttr(this.cap_id, 'style');
                }

                dojo.query('.capture_square').removeClass('capture_square');

                this.cap_id = null;
                this.def_id = null;

                this.duel_styling_applied = false;
            },

            setupDuelBoard: function () {
                for (let player_id in this.gamedatas.players) {
                    $(`duel_board_piece_${player_id}`).className = 'piece logpiece';

                    let duel_piece_id = this.def_id;
                    let status = _('Defender');

                    if (this.gamedatas.pieces[this.cap_id]['color'] == this.gamedatas.players[player_id]['color']) {
                        duel_piece_id = this.cap_id;
                        status = _('Attacker');
                    }

                    dojo.addClass(`duel_board_piece_${player_id}`,
                        [`piececolor_${this.gamedatas.pieces[duel_piece_id]['color']}`,
                        `piecetype_${this.gamedatas.pieces[duel_piece_id]['type']}`]
                    );

                    $(`duel_board_status_${player_id}`).innerHTML = status;
                }

                dojo.style('player_board_duel', 'display', 'block');
            },

            clearDuelBoard: function () {
                dojo.style('player_board_duel', 'display', 'none');

                dojo.query('.bid_slot').forEach(dojo.empty);

                dojo.query('.inactive_slot').removeClass('inactive_slot');
            },

            placeBidStones: function (player_id, bid_amount) {
                if (bid_amount == 0) {
                    for (let i = 0; i < 2; i++) {
                        dojo.addClass(`${player_id}_bid_slot_${i}`, 'inactive_slot');
                    }
                }

                for (let i = 0; i < bid_amount; i++) {
                    dojo.place($(`${player_id}_stone_slot_${this.gamedatas.players[player_id]['stones'] - 1}`).children[0], `${player_id}_bid_slot_${i}`);
                    this.gamedatas.players[player_id]['stones'] -= 1;
                }

                this.own_bid_shown = true;
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
                if (dojo.hasClass(evt.currentTarget, 'possible_move') || dojo.hasClass(evt.currentTarget, 'possible_move_oc')) {
                    let id_split = evt.currentTarget.id.split('_');

                    this.ajaxcallWrapper("movePiece", {
                        target_file: id_split[1],
                        target_rank: id_split[2],
                        moving_piece_id: this.selected_piece_id
                    });

                    return;
                }

                // If the player has a piece selected, deselect it
                this.clearSelectedPiece();

                // If the player clicked a square with a friendly piece, select it
                let children = evt.currentTarget.children;

                if (children.length != 0 && this.gamedatas.pieces[children[0].id].color == this.gamedatas.players[this.player_id].color) {
                    this.selected_piece_id = children[0].id;

                    dojo.addClass(evt.currentTarget, 'selected_piece');

                    if (typeof this.gamedatas.legal_moves[this.selected_piece_id] == 'undefined') {
                        return;
                    }

                    for (let move of this.gamedatas.legal_moves[this.selected_piece_id]) {
                        if ($(`square_${move['x']}_${move['y']}`).children.length != 0) {
                            dojo.addClass(`square_${move['x']}_${move['y']}`, 'possible_move_oc');
                        } else {
                            dojo.addClass(`square_${move['x']}_${move['y']}`, 'possible_move');
                        }

                        if (
                            move['x'] == this.gamedatas.pieces[this.selected_piece_id]['x']
                            && move['y'] == this.gamedatas.pieces[this.selected_piece_id]['y']
                        ) {
                            for (let square of move['cap_squares']) {
                                if ($(`square_${square['x']}_${square['y']}`).children.length != 0) {
                                    dojo.addClass(`square_${square['x']}_${square['y']}`, 'capture_square');
                                }
                            }
                        }
                    }
                }
            },

            mouseOverSquare: function (evt) {
                dojo.stopEvent(evt);

                if (!this.checkAction('movePiece', true)) {
                    return;
                }

                if (dojo.hasClass(evt.currentTarget, 'possible_move') || dojo.hasClass(evt.currentTarget, 'possible_move_oc')) {
                    dojo.addClass(evt.currentTarget, 'hover_possible_move');

                    let id_split = evt.currentTarget.id.split('_');

                    for (let move of this.gamedatas.legal_moves[this.selected_piece_id]) {
                        if (move['x'] == id_split[1] && move['y'] == id_split[2]) {
                            for (let square of move['cap_squares']) {
                                if ($(`square_${square['x']}_${square['y']}`).children.length != 0) {
                                    dojo.addClass(`square_${square['x']}_${square['y']}`, 'capture_square');
                                }
                            }

                            break;
                        }
                    }
                } else if (
                    !dojo.hasClass(evt.currentTarget, 'selected_piece')
                    && evt.currentTarget.children.length != 0
                    && this.gamedatas.pieces[evt.currentTarget.children[0].id].color == this.gamedatas.players[this.player_id].color
                ) {
                    dojo.addClass(evt.currentTarget, 'hover_friendly');
                }
            },

            mouseOutSquare: function (evt) {
                dojo.stopEvent(evt);

                if (!this.checkAction('movePiece', true)) {
                    return;
                }

                dojo.query('.hover_friendly').removeClass('hover_friendly');
                dojo.query('.hover_possible_move').removeClass('hover_possible_move');
                dojo.query('.capture_square').removeClass('capture_square');
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

                dojo.subscribe('stProcessArmySelection', this, "notif_stProcessArmySelection");

                dojo.subscribe('showBacklineRandomization', this, "notif_showBacklineRandomization");
                this.notifqueue.setSynchronous('showBacklineRandomization', 3000);

                dojo.subscribe('updateLegalMoves', this, "notif_updateLegalMoves");

                dojo.subscribe('updatePieces', this, "notif_updatePieces");

                dojo.subscribe('updateCaptureQueue', this, "notif_updateCaptureQueue");

                dojo.subscribe('gainOneStone', this, "notif_gainOneStone");

                dojo.subscribe('loseOneStone', this, "notif_loseOneStone");

                dojo.subscribe('bidStones', this, "notif_bidStones");
                this.notifqueue.setSynchronous('bidStones', 750);
                this.notifqueue.setIgnoreNotificationCheck('bidStones', (notif) => (notif.args.player_id == this.player_id && this.own_bid_shown));

                dojo.subscribe('showDuelOutcome', this, "notif_showDuelOutcome");
                this.notifqueue.setSynchronous('showDuelOutcome', 4000);

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

            notif_stProcessArmySelection: function (notif) {
                this.gamedatas.pieces = notif.args.pieces;

                dojo.query('.boardpiece').forEach(dojo.destroy);
                this.populateBoard();
            },

            notif_showBacklineRandomization: function () {
                $('pagemaintitletext').innerHTML = _('Randomizing backline positions');

                if (this.gamedatas.players[this.player_id].color == "000000") {
                    dojo.query('.flipped').removeClass('flipped');
                }

                for (let piece_id in this.gamedatas.pieces) {
                    if ((piece_id > 8 && piece_id < 17) || piece_id > 24) {
                        continue;
                    }

                    let x = ((piece_id - 1) % 8) + 1;
                    let y = (this.gamedatas.pieces[piece_id].color == "000000") ? 8 : 1;

                    this.placeOnObject(piece_id, `square_${x}_${y}`);

                    this.slideToObject(piece_id, $(piece_id).parentNode, 2000).play();
                }

                if (this.gamedatas.players[this.player_id].color == "000000") {
                    dojo.addClass('board', 'flipped');
                    dojo.query('.boardpiece').addClass('flipped');
                }
            },

            notif_updateLegalMoves: function (notif) {
                this.gamedatas.legal_moves = notif.args.legal_moves;
            },

            notif_updatePieces: function (notif) {
                for (var field in notif.args.values_updated) {
                    switch (field) {
                        case "location":
                            this.gamedatas.pieces[notif.args.piece_id]['x'] = notif.args.values_updated[field][0];
                            this.gamedatas.pieces[notif.args.piece_id]['y'] = notif.args.values_updated[field][1];

                            dojo.place(String(notif.args.piece_id), 'square_' + notif.args.values_updated[field][0] + '_' + notif.args.values_updated[field][1]);
                            break;

                        case "state":
                            if (notif.args.values_updated[field] == this.gamedatas.constants['CAPTURED']) {
                                dojo.destroy(String(notif.args.piece_id));
                            }
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

                if (typeof notif.args.state_name !== 'undefined') {
                    if (notif.args.state_name == "playerMove") {
                        this.gamedatas.last_move_piece_ids['player_move'] = notif.args.piece_id;
                        this.gamedatas.last_move_piece_ids['king_move'] = 0;

                        dojo.query('.last_move').removeClass('last_move');
                    } else {
                        this.gamedatas.last_move_piece_ids['king_move'] = notif.args.piece_id;

                        if (this.gamedatas.last_move_piece_ids['player_move'] == notif.args.piece_id) {
                            dojo.query('.last_move').removeClass('last_move');
                        }
                    }

                    this.highlightLastMove(notif.args.piece_id);
                }

                //console.log(this.gamedatas.pieces);
            },

            notif_updateCaptureQueue: function (notif) {
                this.gamedatas.capture_queue = notif.args.capture_queue;
            },

            notif_gainOneStone: function (notif) {
                let new_stone_div = this.format_block('jstpl_stone', { 'color': this.gamedatas.players[notif.args.player_id]['color'] });

                let first_empty_slot_number = this.gamedatas.players[notif.args.player_id]['stones'];

                let stone_slot_div = $(`${notif.args.player_id}_stone_slot_${first_empty_slot_number}`);

                dojo.place(new_stone_div, stone_slot_div);

                this.placeOnObject(stone_slot_div.children[0], notif.args.source);

                this.slideToObject(stone_slot_div.children[0], stone_slot_div, 750).play();

                this.gamedatas.players[notif.args.player_id]['stones'] = Number(this.gamedatas.players[notif.args.player_id]['stones']) + 1;
            },

            notif_loseOneStone: function (notif) {
                let last_filled_slot_number = this.gamedatas.players[notif.args.player_id]['stones'] - 1;

                let stone_slot_div = $(`${notif.args.player_id}_stone_slot_${last_filled_slot_number}`);

                this.slideToObject(stone_slot_div.children[0], "board", 750).play();

                this.fadeOutAndDestroy(stone_slot_div.children[0], 750);

                this.gamedatas.players[notif.args.player_id]['stones'] -= 1;
            },

            notif_bidStones: function (notif) {
                if (notif.args.bid_amount == 0) {
                    for (let i = 0; i < 2; i++) {
                        dojo.addClass(`${notif.args.player_id}_bid_slot_${i}`, 'inactive_slot');
                    }
                }

                for (let i = 0; i < notif.args.bid_amount; i++) {
                    let slot_number = this.gamedatas.players[notif.args.player_id]['stones'] - notif.args.bid_amount + i;

                    let stone_slot_div = $(`${notif.args.player_id}_stone_slot_${slot_number}`);

                    this.attachToNewParent(stone_slot_div.children[0], `${notif.args.player_id}_bid_slot_${i}`);

                    this.slideToObject($(`${notif.args.player_id}_bid_slot_${i}`).children[0], `${notif.args.player_id}_bid_slot_${i}`, 750).play();
                }

                if (notif.args.player_id == this.player_id) {
                    this.own_bid_shown = true;
                }

                this.gamedatas.players[notif.args.player_id]['stones'] -= notif.args.bid_amount;
            },

            notif_showDuelOutcome: function (notif) {
                $('pagemaintitletext').innerHTML = notif.args.outcome_message;
            },

            notif_clearSelectedPiece: function () {
                this.clearSelectedPiece();
            },

            notif_highlightAttackedSquares: function (notif) {
                dojo.query('.check').removeClass('check');
                dojo.query('.threat').removeClass('threat');

                for (var i = 1; i <= 8; i++) {
                    for (var j = 1; j <= 8; j++) {
                        if (notif.args[i][j].checks.length != 0) {
                            dojo.addClass('square_' + i + '_' + j, 'check');
                        }
                        else if (notif.args[i][j].threats.length != 0) {
                            dojo.addClass('square_' + i + '_' + j, 'threat');
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
