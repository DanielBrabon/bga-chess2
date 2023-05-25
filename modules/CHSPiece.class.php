<?php

class CHSPiece extends APP_GameClass
{
    private $game;

    public $id;
    public $color;
    public $type;
    public $x;
    public $y;
    public $last_x;
    public $last_y;
    public $moves_made;
    public $state;

    public function __construct($game, $piece_data)
    {
        $this->game = $game;

        $this->id = $piece_data['piece_id'];
        $this->color = $piece_data['color'];
        $this->type = $piece_data['type'];
        $this->x = $piece_data['x'];
        $this->y = $piece_data['y'];
        $this->last_x = $piece_data['last_x'];
        $this->last_y = $piece_data['last_y'];
        $this->moves_made = $piece_data['moves_made'];
        $this->state = $piece_data['state'];
    }

    public function setState($state)
    {
        $this->state = $state;

        self::DbQuery("UPDATE pieces SET state = $state WHERE piece_id = $this->id");

        $this->game->notifyAllPlayers("updatePieces", "", ["piece_id" => $this->id, "values_updated" => ["state" => $state]]);
    }

    public function movePiece($values, $state_name)
    {
        $this->state = $values['state'];
        $this->x = $values['location'][0];
        $this->y = $values['location'][1];
        $this->last_x = $values['last_x'];
        $this->last_y = $values['last_y'];
        $this->moves_made += 1;

        self::DbQuery(
            "UPDATE pieces SET
            state = $this->state,
            x = $this->x,
            y = $this->y,
            last_x = $this->last_x,
            last_y = $this->last_y,
            moves_made = moves_made + 1
            WHERE piece_id = $this->id"
        );

        $this->game->notifyAllPlayers(
            "updatePieces",
            "",
            array(
                "piece_id" => $this->id,
                "values_updated" => $values,
                "state_name" => $state_name
            )
        );
    }

    public function setNewLocation($x, $y)
    {
        $this->game->notifyAllPlayers(
            "updatePieces",
            "",
            array(
                "piece_id" => $this->id,
                "values_updated" => array(
                    "location" => [$x, $y],
                    "last_x" => $this->x,
                    "last_y" => $this->y
                )
            )
        );

        self::DbQuery("UPDATE pieces SET x = $x, y = $y, last_x = $this->x, last_y = $this->y WHERE piece_id = $this->id");

        $this->last_x = $this->x;
        $this->last_y = $this->y;
        $this->x = $x;
        $this->y = $y;
    }

    public function promote($promotion_type)
    {
        $this->type = $promotion_type;
        $this->state = NEUTRAL;

        self::DbQuery("UPDATE pieces SET type = '$promotion_type', state = " . NEUTRAL . " WHERE piece_id = $this->id");

        $this->game->notifyAllPlayers(
            "updatePieces",
            "",
            array(
                "piece_id" => $this->id,
                "values_updated" => array(
                    "type" => $promotion_type,
                    "state" => NEUTRAL
                )
            )
        );
    }
}
