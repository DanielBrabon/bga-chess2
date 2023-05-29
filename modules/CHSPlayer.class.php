<?php

class CHSPlayer extends APP_GameClass
{
    private $game;

    public $id;
    public $name;
    public $color;
    public $is_multiactive;
    public $army;
    public $stones;
    public $bid;
    public $king_move_available;

    public function __construct($game, $player_data)
    {
        $this->game = $game;

        $this->id = $player_data['player_id'];
        $this->name = $player_data['player_name'];
        $this->color = $player_data['player_color'];
        $this->is_multiactive = $player_data['player_is_multiactive'];
        $this->army = $player_data['player_army'];
        $this->stones = $player_data['player_stones'];
        $this->bid = $player_data['player_bid'];
        $this->king_move_available = $player_data['player_king_move_available'];
    }

    public function setArmy($army)
    {
        $this->army = $army;

        self::DbQuery("UPDATE player SET player_army = '$army' WHERE player_id = $this->id");

        $this->setStat(array_search($army, $this->game->all_army_names), "army");

        // Translate
        $this->game->notifyAllPlayers("confirmArmy", clienttranslate('${player_name} selects an army'), array(
            'player_id' => $this->id,
            'player_name' => $this->name
        ));
    }

    public function incrementStones($increment)
    {
        $this->stones += $increment;
        self::DbQuery("UPDATE player SET player_stones = player_stones + $increment WHERE player_id = $this->id");
    }

    public function gainOneStone($source)
    {
        if ($this->stones == 6) {
            return;
        }

        $this->incrementStones(1);

        $this->game->notifyAllPlayers("gainOneStone", "", array("player_id" => $this->id, "source" => $source));
    }

    public function loseOneStone()
    {
        $this->incrementStones(-1);

        $this->game->notifyAllPlayers("loseOneStone", "", array("player_id" => $this->id));
    }

    public function bidStones()
    {
        $this->incrementStones($this->bid * -1);

        $this->incStat($this->bid, "stones_bid");

        $this->game->notifyAllPlayers("bidStones", "", array("player_id" => $this->id, "bid_amount" => $this->bid));

        $this->setBid(null);
    }

    public function setBid($bid_amount)
    {
        $this->bid = $bid_amount;

        if ($bid_amount === null) {
            $sql = "UPDATE player SET player_bid = null WHERE player_id = $this->id";
        } else {
            $sql = "UPDATE player SET player_bid = $bid_amount WHERE player_id = $this->id";
        }
        self::DbQuery($sql);
    }

    public function setKingMoveAvailable()
    {
        $this->king_move_available = 1;
        self::DbQuery("UPDATE player SET player_king_move_available = 1 WHERE player_id = $this->id");
    }

    public function setKingMoveUnavailable()
    {
        $this->king_move_available = 0;
        self::DbQuery("UPDATE player SET player_king_move_available = 0 WHERE player_id = $this->id");
    }

    public function setAsWinner()
    {
        self::DbQuery("UPDATE player SET player_score = 1 WHERE player_id = $this->id");
    }

    public function incStat($increment, $stat_name)
    {
        $this->game->incStat($increment, $stat_name, $this->id);
    }

    public function setStat($value, $stat_name)
    {
        $this->game->setStat($value, $stat_name, $this->id);
    }
}
