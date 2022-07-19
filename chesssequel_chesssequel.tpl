{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- ChessSequel implementation : © <Daniel Brabon> <dev.d8dms@simplelogin.co>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    chesssequel_chesssequel.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<div id="main">

    <div id="board">

        <!-- BEGIN square -->
        <div id="square_{X}_{Y}" class="square" style="left: {LEFT}px; top: {TOP}px;"></div>
        <!-- END square -->

        <div id="pieces">
        </div>

    </div>

    <div id="info_panel">

        <div id="duel_offer_btn_container" class="btn_container">
            <button id="btn_start_duel" class="bgabutton bgabutton_beddac_temp" type="button">Start Duel</button>
            <button id="btn_do_not_start_duel" class="bgabutton bgabutton_beddac_temp" type="button">Do Not Start Duel</button>
            <button id="btn_confirm_duel_offer" class="bgabutton bgabutton_red" type="button">Confirm Choice</button>
        </div>

        <div id="duel_bid_btn_container" class="btn_container">
            <button id="btn_bid_zero" class="bgabutton bgabutton_green" type="button">Bid 0</button>
            <button id="btn_bid_one" class="bgabutton bgabutton_green" type="button">Bid 1</button>
            <button id="btn_bid_two" class="bgabutton bgabutton_green" type="button">Bid 2</button>
            <button id="btn_confirm_duel_bid" class="bgabutton bgabutton_red" type="button">Confirm Bid</button>
        </div>

    </div>

</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

/*
Can be used in .js file to create a piece element of the chosen colour and type with the chosen id.
For example:

dojo.place( this.format_block( 'jstpl_piece', {
    color: this.gamedatas.players[ player ].color,
    type: type
    piece_id: piece_id
} ), 'pieces' );

*/
var jstpl_piece='<div class="piece piececolor_${color} piecetype_${type}" id="${piece_id}"></div>';

</script>  

{OVERALL_GAME_FOOTER}
