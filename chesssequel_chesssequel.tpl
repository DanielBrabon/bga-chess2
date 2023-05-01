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

    <div id="board_wrap">

        <div id="board">

            <!-- BEGIN square -->
            <div id="square_{X}_{Y}" class="square" style="left: {LEFT}%; top: {TOP}%;"></div>
            <!-- END square -->

        </div>

        <div id="ranks" class="coords">

            <!-- BEGIN ranks -->
            <div class="coord coordtype_{TYPE}">{RANK}</div>
            <!-- END ranks -->

        </div>

        <div id="files" class="coords">

            <!-- BEGIN files -->
            <div class="coord coordtype_{TYPE}">{FILE}</div>
            <!-- END files -->

        </div>

    </div>

</div>

<div class='player-board' id="player_board_buttons">

    <button id="btn_draw" class="bgabutton bgabutton_blue">Offer Draw</button>
    <button id="btn_conc" class="bgabutton bgabutton_blue">Concede Game</button>

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

    var jstpl_piece = '<div class="piece piececolor_${color} piecetype_${type}" id="${piece_id}"></div>';

    var jstpl_player_stones = '\<div class="player_stones">\<span id="player_stones_${id}">Stones: ${stones}</span>\</div>';

    var jstpl_logpiece = '<div class="logpiece piececolor_${color} piecetype_${type}"></div>';

    var jstpl_player_text = '<span class="playername" style="color:#${color};background-color:#${bg_color};">${text}</span>';
</script>

{OVERALL_GAME_FOOTER}