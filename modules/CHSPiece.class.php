<?php

class CHSPiece extends APP_GameClass
{
    public $id;
    public $color;
    public $type;
    public $x;
    public $y;
    public $last_x;
    public $last_y;
    public $moves_made;
    public $state;

    public function __construct($piece_data)
    {
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
    }

    public function movePiece($values)
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
    }

    public function setNewLocation($x, $y)
    {
        self::DbQuery("UPDATE pieces SET x = $x, y = $y, last_x = $this->x, last_y = $this->y WHERE piece_id = $this->id");

        $this->x = $x;
        $this->y = $y;
        $this->last_x = $this->x;
        $this->last_y = $this->y;
    }

    public function promote($promotion_type, $new_state)
    {
        $this->type = $promotion_type;
        $this->state = $new_state;
        self::DbQuery("UPDATE pieces SET type = '$promotion_type', state = $new_state WHERE piece_id = $this->id");
    }
}
