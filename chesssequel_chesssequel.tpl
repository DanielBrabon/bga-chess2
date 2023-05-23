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

<div id="player_board_buttons" class="player-board">

    <button id="btn_draw" class="bgabutton bgabutton_blue">Offer Draw</button>

</div>

<!-- BEGIN player_stones -->
<div id="player_stones_wrap_{ID}" class="stones_wrap">

    <div id="{ID}_stone_slot_0" class="stone_slot"></div>
    <div id="{ID}_stone_slot_1" class="stone_slot"></div>
    <div id="{ID}_stone_slot_2" class="stone_slot"></div>
    <div id="{ID}_stone_slot_3" class="stone_slot"></div>
    <div id="{ID}_stone_slot_4" class="stone_slot"></div>
    <div id="{ID}_stone_slot_5" class="stone_slot"></div>

</div>
<!-- END player_stones -->

<div id="player_board_duel" class="player-board">

    <div class="duel_board_title">Bids:</div>

    <!-- BEGIN player_bids -->
    <div id="duel_board_{ID}" class="duel_board_player_section">

        <div id="duel_board_name_{ID}"></div>

        <div id="duel_board_piece_{ID}" class="logpiece"></div>

        <div id="duel_board_bids_{ID}" class="stones_wrap">
            <div id="{ID}_bid_slot_0" class="stone_slot bid_slot"></div>
            <div id="{ID}_bid_slot_1" class="stone_slot bid_slot"></div>
        </div>

        <div id="duel_board_status_{ID}" class="duel_board_status"></div>

    </div>
    <!-- END player_bids -->

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

    var jstpl_boardpiece = '<div id="${piece_id}" class="piece boardpiece piececolor_${color} piecetype_${type}"></div>';

    var jstpl_logpiece = '<div class="piece logpiece piececolor_${color} piecetype_${type}"></div>';

    var jstpl_buttonpiece = '<div class="piece buttonpiece piececolor_${color} piecetype_${type}"></div>';

    var jstpl_player_color_text = '<span style="font-weight: bold; color: #${color}; background-color: ${bg_color};">${text}</span>';

    var jstpl_army_select_title_text = '${you} must select an army<br>Current selection: ${army}<br>';

    var jstpl_stone = '<div class="stone stonecolor_${color}"></div>';
</script>

{OVERALL_GAME_FOOTER}