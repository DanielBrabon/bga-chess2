<?php

require_once('CHSPiece.class.php');

class CHSPieceManager extends APP_GameClass
{
    private $game;

    private $pieces = null;

    public function __construct($game)
    {
        $this->game = $game;
    }

    private function selectPieces($piece_rows = null)
    {
        $this->pieces = [];

        if ($piece_rows === null) {
            $piece_rows = self::getObjectListFromDB("SELECT * FROM pieces");
        }

        foreach ($piece_rows as $piece_data) {
            $piece = new CHSPiece($piece_data);
            $this->pieces[$piece_data['piece_id']] = $piece;
        }
    }

    public function insertPieces($x_positions = null)
    {
        $sql_values = [];

        $piece_rows = [];

        $counter = 1;

        foreach ($this->game->playerManager->getPlayers() as $player) {
            // For each piece in the chosen army
            foreach ($this->game->all_armies_layouts[$player->army] as $layout_index => $piece_type) {
                $x = $x_positions[$layout_index] ?? $this->game->layout_x[$layout_index];
                $y = $this->game->layout_y[$layout_index][$player->color];

                // Piece data for the insert query
                $sql_values[] = "('$player->color', '$piece_type', $x, $y)";

                // The information for selectPieces without needing to select it from the database
                $piece_rows[] = array(
                    "piece_id" => (string) $counter,
                    "color" => $player->color,
                    "type" => $piece_type,
                    "x" => (string) $x,
                    "y" => (string) $y,
                    "last_x" => null,
                    "last_y" => null,
                    "moves_made" => (string) 0,
                    "state" => (string) 0
                );

                $counter++;
            }
        }

        self::DbQuery("INSERT INTO pieces (color, type, x, y) VALUES " . implode(',', $sql_values));

        $this->selectPieces($piece_rows);
    }

    public function rollBacklinePositions()
    {
        $available_x = [1, 2, 3, 4, 5, 6, 7, 8];

        $x_positions = [];

        $x_positions[LAYOUT_BISHOPA] = (bga_rand(1, 4) * 2) - 1; // Random black square
        $x_positions[LAYOUT_BISHOPB] = bga_rand(1, 4) * 2; // Random white square

        unset($available_x[$x_positions[LAYOUT_BISHOPA] - 1]);
        unset($available_x[$x_positions[LAYOUT_BISHOPB] - 1]);
        $available_x = array_values($available_x);

        $x_positions[LAYOUT_QUEEN] = array_splice($available_x, bga_rand(0, 5), 1)[0]; // Random remaining square
        $x_positions[LAYOUT_KNIGHTA] = array_splice($available_x, bga_rand(0, 4), 1)[0]; // Random remaining square
        $x_positions[LAYOUT_KNIGHTB] = array_splice($available_x, bga_rand(0, 3), 1)[0]; // Random remaining square

        // King between the rooks in the remaining squares
        $x_positions[LAYOUT_ROOKA] = $available_x[0];
        $x_positions[LAYOUT_KING] = $available_x[1];
        $x_positions[LAYOUT_ROOKB] = $available_x[2];

        return $x_positions;
    }

    public function getPieces()
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        return $this->pieces;
    }

    public function getPiece($piece_id)
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        return $this->pieces[$piece_id];
    }

    public function getPiecesInStates($states)
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        $pieces = [];

        foreach ($this->pieces as $piece) {
            foreach ($states as $state) {
                if ($piece->state == $state) {
                    $pieces[] = $piece;
                }
            }
        }

        return $pieces;
    }

    public function isPieceInStates($states)
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        foreach ($this->pieces as $piece) {
            foreach ($states as $state) {
                if ($piece->state == $state) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getPlayerKingIds($player_id)
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        $player_color = $this->game->getPlayerColorById($player_id);

        $piece_ids = [];

        foreach ($this->pieces as $piece) {
            if ($piece->color == $player_color && in_array($piece->type, ["king", "warriorking"])) {
                $piece_ids[] = $piece->id;
            }
        }

        return $piece_ids;
    }

    public function getActivePieceIdsByColor($color)
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        $piece_ids = [];

        foreach ($this->pieces as $piece) {
            if ($piece->color == $color && $piece->state != CAPTURED) {
                $piece_ids[] = $piece->id;
            }
        }

        return $piece_ids;
    }

    public function pieceIdExists($piece_id)
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        foreach (array_keys($this->pieces) as $id) {
            if ($id == $piece_id) {
                return true;
            }
        }

        return false;
    }

    public function getDataForMoveGen()
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        $result = [];

        foreach ($this->pieces as $piece) {
            $result[$piece->id] = array(
                "piece_id" => $piece->id,
                "color" => $piece->color,
                "type" => $piece->type,
                "x" => $piece->x,
                "y" => $piece->y,
                "last_x" => $piece->last_x,
                "last_y" => $piece->last_y,
                "moves_made" => $piece->moves_made,
                "state" => $piece->state
            );
        }

        return $result;
    }

    public function getSquaresData()
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        $squares = [];

        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 8; $j++) {
                $squares[$i][$j]['def_piece'] = null;
            }
        }

        foreach ($this->pieces as $piece) {
            if (!in_array($piece->state, [CAPTURED, CAPTURING])) {
                $squares[$piece->x][$piece->y]['def_piece'] = $piece->id;
            }
        }

        return $squares;
    }

    // Returns true if active player has met the midline invasion win condition, else returns false
    public function hasPlayerInvaded($player)
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        $king_ids = $this->getPlayerKingIds($player->id);

        $invasion_direction = ($player->color == "000000") ? -1 : 1;

        foreach ($king_ids as $king_id) {
            if (($this->pieces[$king_id]->y - 4.5) * $invasion_direction < 0) {
                return false;
            }
        }

        return true;
    }

    public function isPromotionAvailable()
    {
        if ($this->pieces === null) {
            $this->selectPieces();
        }

        foreach ($this->pieces as $piece) {
            if (in_array($piece->state, [PROMOTING])) {
                return true;
            }
        }

        return false;
    }
}
