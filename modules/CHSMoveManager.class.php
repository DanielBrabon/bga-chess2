<?php

class CHSMoveManager extends APP_GameClass
{
    private $game;

    public function __construct($game)
    {
        $this->game = $game;
    }

    // Replaces content of legal_moves and capture_squares db tables with data provided. Returns number of legal moves
    function insertLegalMoves($all_legal_moves, $player)
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

        $this->game->notifyPlayer($player->id, "updateLegalMoves", "", ["legal_moves" => $all_legal_moves]);

        $this->game->notifyPlayer(
            $this->game->playerManager->getOtherPlayerByColor($player->color)->id,
            "updateLegalMoves",
            "",
            ["legal_moves" => null]
        );

        return $move_counter;
    }

    public function getLegalMoves($current_player_color)
    {
        $legal_moves = self::getObjectListFromDB("SELECT move_id, piece_id, x, y FROM legal_moves");

        if (count($legal_moves) == 0) {
            return null;
        }

        if ($this->game->pieceManager->getPiece($legal_moves[0]['piece_id'])->color != $current_player_color) {
            return null;
        }

        $capture_squares = self::getObjectListFromDB("SELECT move_id, x, y FROM capture_squares");

        $result = [];

        foreach ($legal_moves as $move) {
            $result_move = array(
                "x" => $move['x'],
                "y" => $move['y'],
                "cap_squares" => []
            );

            foreach ($capture_squares as $square) {
                if ($square['move_id'] == $move['move_id']) {
                    $result_move['cap_squares'][] = array(
                        "x" => $square['x'],
                        "y" => $square['y']
                    );
                }
            }

            $result[$move['piece_id']][] = $result_move;
        }

        return $result;
    }

    public function getFirstLegalMoveFromDB()
    {
        return self::getObjectFromDB("SELECT piece_id, x, y FROM legal_moves LIMIT 1");
    }

    public function getCaptureSquaresForMove($x, $y, $piece_id)
    {
        $move_id = self::getUniqueValueFromDB(
            "SELECT move_id FROM legal_moves
                WHERE piece_id = $piece_id
                AND x = $x
                AND y = $y"
        );

        if ($move_id === null) {
            return null;
        }

        return self::getObjectListFromDB("SELECT x, y FROM capture_squares WHERE move_id = $move_id");
    }
}
