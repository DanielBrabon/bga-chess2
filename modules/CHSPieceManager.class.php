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

    public function insertPieces()
    {
        // Randomized backline positions in ruleset 3.0
        $x_offsets = ($this->game->getGameStateValue('ruleset_version') == RULESET_THREE_POINT_ZERO) ? $this->rollXOffsets() : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        $sql_values = [];

        $piece_rows = [];

        $counter = 1;

        foreach ($this->game->playerManager->getPlayers() as $player) {
            // Correct y positions for this color
            $y_values = ($player->color == "000000") ? [8, 7] : [1, 2];

            // For each piece in the chosen army
            foreach ($this->game->all_armies_layouts[$player->army] as $piece_index => $piece_type) {
                $x = ($piece_index % 8) + 1 + $x_offsets[$piece_index];
                $y = $y_values[floor($piece_index / 8)];

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

    // TODO: Improve?
    private function rollXOffsets()
    {
        $x_offsets = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        $squares = [1, 2, 3, 4, 5, 6, 7, 8];

        // The 5 needed rolls
        $rolls = array();
        foreach ([4, 4, 6, 5, 4] as $max_roll) {
            $rolls[] = bga_rand(1, $max_roll);
        }

        // Move the black square bishop
        $bb_square_index = ($rolls[0] - 1) * 2;
        $x_offsets[2] = $squares[$bb_square_index] - 3;

        // Move the white square bishop
        $wb_square_index = ($rolls[1] * 2) - 1;
        $x_offsets[5] = $squares[$wb_square_index] - 6;

        unset($squares[$bb_square_index]);
        unset($squares[$wb_square_index]);
        $squares = array_values($squares);

        // Move the queen
        $square_index = $rolls[2] - 1;
        $x_offsets[3] = $squares[$square_index] - 4;
        unset($squares[$square_index]);
        $squares = array_values($squares);

        // Move the first knight
        $square_index = $rolls[3] - 1;
        $x_offsets[1] = $squares[$square_index] - 2;
        unset($squares[$square_index]);
        $squares = array_values($squares);

        // Move the second knight
        $square_index = $rolls[4] - 1;
        $x_offsets[6] = $squares[$square_index] - 7;
        unset($squares[$square_index]);
        $squares = array_values($squares);

        // Move the remaining pieces
        $x_offsets[0] = $squares[0] - 1;
        $x_offsets[4] = $squares[1] - 5;
        $x_offsets[7] = $squares[2] - 8;

        return $x_offsets;
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

    // Replaces content of legal_moves and capture_squares db tables with data provided. Returns number of legal moves
    function insertLegalMoves($all_legal_moves)
    {
        self::DbQuery("DELETE FROM capture_squares");
        self::DbQuery("DELETE FROM legal_moves");

        $legal_moves = array();
        $capture_squares = array();

        $move_counter = 0;
        foreach ($all_legal_moves as $piece_id => $moves_for_piece) {
            foreach ($moves_for_piece as $move) {
                $legal_moves[] = "($move_counter, $piece_id, {$move['x']}, {$move['y']})";

                foreach ($move['cap_squares'] as $cap_square) {
                    $capture_squares[] = "($move_counter, {$cap_square['x']}, {$cap_square['y']})";
                }

                $move_counter++;
            }
        }

        if ($move_counter > 0) {
            self::DbQuery("INSERT INTO legal_moves (move_id, piece_id, x, y) VALUES " . implode(',', $legal_moves));

            if (count($capture_squares) > 0) {
                self::DbQuery("INSERT INTO capture_squares (move_id, x, y) VALUES " . implode(',', $capture_squares));
            }
        }

        $this->game->notifyAllPlayers("updateLegalMovesTable", "", array("moves_added" => $all_legal_moves));

        return $move_counter;
    }

    // TODO: Make this collection
    public function getLegalMoves()
    {
        return self::getObjectListFromDB("SELECT piece_id, x, y FROM legal_moves");
    }

    public function getLegalMoveId($x, $y, $piece_id)
    {
        return self::getUniqueValueFromDB(
            "SELECT move_id FROM legal_moves
            WHERE piece_id = $piece_id
            AND x = $x
            AND y = $y"
        );
    }

    public function getCaptureSquares()
    {
        return self::getCollectionFromDB("SELECT move_id, x, y FROM capture_squares");
    }

    public function getCaptureSquaresForMove($move_id)
    {
        return self::getObjectListFromDB("SELECT x, y FROM capture_squares WHERE move_id = $move_id");
    }
}
