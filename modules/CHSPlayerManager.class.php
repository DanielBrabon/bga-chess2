<?php

require_once('CHSPlayer.class.php');

class CHSPlayerManager extends APP_GameClass
{
    private $game;

    private $players = null;

    public function __construct($game)
    {
        $this->game = $game;
    }

    private function selectPlayers()
    {
        $this->players = [];

        $player_rows = self::getObjectListFromDB(
            "SELECT player_id,
            player_name,
            player_color,
            player_is_multiactive,
            player_army,
            player_stones,
            player_bid,
            player_king_move_available
            FROM player"
        );

        foreach ($player_rows as $player_data) {
            $player = new CHSPlayer($this->game, $player_data);

            $this->players[$player_data['player_color']] = $player;
        }
    }

    public function getUIData($current_player_id)
    {
        $collection = self::getCollectionFromDB(
            "SELECT player_id id, player_color color, player_score score, player_army army, player_stones stones, player_bid bid FROM player"
        );

        foreach ($collection as $player_data) {
            if ($player_data['id'] != $current_player_id) {
                unset($collection[$player_data['id']]['army']);
                unset($collection[$player_data['id']]['bid']);
            }
        }

        return $collection;
    }

    public function getPlayers()
    {
        if ($this->players === null) {
            $this->selectPlayers();
        }

        return $this->players;
    }

    public function getPlayerByColor($color)
    {
        if ($this->players === null) {
            $this->selectPlayers();
        }

        return $this->players[$color];
    }

    public function getOtherPlayerByColor($color)
    {
        if ($this->players === null) {
            $this->selectPlayers();
        }

        $other_color = ($color == "000000") ? "ffffff" : "000000";

        return $this->players[$other_color];
    }

    public function getActivePlayer()
    {
        if ($this->players === null) {
            $this->selectPlayers();
        }

        $active_color = $this->getActivePlayerColor();

        return $this->players[$active_color];
    }

    public function getInactivePlayer()
    {
        if ($this->players === null) {
            $this->selectPlayers();
        }

        $active_color = $this->getActivePlayerColor();

        return ($active_color == "000000") ? $this->players["ffffff"] : $this->players["000000"];
    }

    public function getActivePlayerColor()
    {
        $active_player_id = $this->game->getActivePlayerId();

        $player_infos = $this->game->loadPlayersBasicInfos();

        return $player_infos[$active_player_id]['player_color'];
    }

    public function setRemainingReflexionTime($seconds)
    {
        self::DbQuery("UPDATE player SET player_remaining_reflexion_time = $seconds");
    }
}
