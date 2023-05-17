<?php

class CHSCaptureManager extends APP_GameClass
{
    private $game;

    private $cap_piece = null;

    private $capture_queue = null;

    public function __construct($game)
    {
        $this->game = $game;
    }

    private function selectCaptureQueue()
    {
        $this->cap_piece = $this->game->pieceManager->getPiecesInStates([CAPTURING, CAPTURING_AND_PROMOTING])[0] ?? null;

        $this->capture_queue = self::getObjectListFromDB("SELECT piece_id FROM capture_queue ORDER BY cq_id", true);
    }

    public function insertCaptureQueue($cap_piece, $capture_queue)
    {
        $this->cap_piece = $cap_piece;

        $this->capture_queue = $capture_queue;

        $sql_values = [];

        foreach ($capture_queue as $piece_id) {
            $sql_values[] = "($piece_id)";
        }

        self::DbQuery("INSERT INTO capture_queue (piece_id) VALUES " . implode(',', $sql_values));
    }

    public function getCaptureQueue()
    {
        if ($this->capture_queue === null) {
            $this->selectCaptureQueue();
        }

        return $this->capture_queue;
    }

    public function getCurrentDefenderId()
    {
        if ($this->capture_queue === null) {
            $this->selectCaptureQueue();
        }

        return $this->capture_queue[0] ?? null;
    }

    // Process all captures from the front of the capture queue which can be processed without offering a duel
    // Return true if there are any pieces left in the queue
    public function processFrontOfCaptureQueue()
    {
        if (!$this->game->pieceManager->isPieceInStates([CAPTURING, CAPTURING_AND_PROMOTING])) {
            return false;
        }

        if ($this->capture_queue === null) {
            $this->selectCaptureQueue();
        }

        // A capture is occurring so the fify move rule counter is reset
        $this->game->setGameStateValue('fifty_counter', 51);

        // If we are not using the v2 ruleset or a (warrior)king is capturing, there is no duelling at all
        if (
            $this->game->getGameStateValue('ruleset_version') != RULESET_TWO_POINT_FOUR
            || in_array($this->cap_piece->type, ["king", "warriorking"])
        ) {
            $this->captureEntireQueue();
            return false;
        }

        // Else we will need to check each capture individually 
        foreach ($this->capture_queue as $def_id) {
            $def_piece = $this->game->pieceManager->getPiece($def_id);

            if (
                $def_piece->color != $this->cap_piece->color
                && $this->game->playerManager->getPlayerByColor($def_piece->color)->stones > $this->game->getCostToDuel($this->cap_piece, $def_piece)
            ) {
                return true;
            }

            $this->singleCapture($def_piece);
        }

        return false;
    }

    public function captureEntireQueue()
    {
        foreach ($this->capture_queue as $def_id) {
            $def_piece = $this->game->pieceManager->getPiece($def_id);
            $this->singleCapture($def_piece);
        }
    }

    public function singleCapture($def_piece)
    {
        $def_piece->setState(CAPTURED);

        $same_color = ($def_piece->color == $this->cap_piece->color);
        $stat = ($same_color) ? "friendlies_captured" : "enemies_captured";

        $cap_player = $this->game->playerManager->getPlayerByColor($this->cap_piece->color);

        $cap_player->incStat(1, $stat);

        if (
            $this->game->getGameStateValue('ruleset_version') == RULESET_TWO_POINT_FOUR
            && in_array($def_piece->type, ["pawn", "nemesispawn"])
            && !$same_color
        ) {
            // Player with the other color gets a stone
            $cap_player->gainOneStone($def_piece->id);
        }

        $this->game->notifyAllPlayers(
            "updateAllPieceData",
            clienttranslate('${logpiece_cap} captures ${logpiece_def}'),
            array(
                "piece_id" => $def_piece->id,
                "values_updated" => array("state" => CAPTURED),
                "logpiece_cap" => $this->cap_piece->color . "_" . $this->cap_piece->type,
                "logpiece_def" => $def_piece->color . "_" . $def_piece->type
            )
        );

        array_splice($this->capture_queue, 0, 1);
        self::DbQuery("DELETE FROM capture_queue WHERE piece_id = $def_piece->id");

        if (count($this->capture_queue) == 0) {
            $new_state = ($this->cap_piece->state == CAPTURING_AND_PROMOTING) ? PROMOTING : NEUTRAL;

            $this->cap_piece->setState($new_state);

            $this->game->notifyAllPlayers(
                "updateAllPieceData",
                "",
                array(
                    "piece_id" => $this->cap_piece->id,
                    "values_updated" => array("state" => $new_state)
                )
            );

            $this->cap_piece = null;
        }
    }

    public function doubleCapture($def_piece)
    {
        $def_player = $this->game->playerManager->getPlayerByColor($def_piece->color);
        $cap_player = $this->game->playerManager->getPlayerByColor($this->cap_piece->color);

        $def_player->incStat(1, "duel_captures");
        $def_player->incStat(1, "enemies_captured");
        $cap_player->incStat(1, "enemies_captured");

        if ($this->game->getGameStateValue('ruleset_version') == RULESET_TWO_POINT_FOUR) {
            if (in_array($this->cap_piece->type, ["pawn", "nemesispawn"])) {
                $def_player->gainOneStone($this->cap_piece->id);
            }

            if (in_array($def_piece->type, ["pawn", "nemesispawn"])) {
                $cap_player->gainOneStone($def_piece->id);
            }
        }

        $this->cap_piece->setState(CAPTURED);
        $def_piece->setState(CAPTURED);

        $this->game->notifyAllPlayers(
            "updateAllPieceData",
            clienttranslate('${logpiece_cap} captures ${logpiece_def}'),
            array(
                "piece_id" => $def_piece->id,
                "values_updated" => array("state" => CAPTURED),
                "logpiece_cap" => $this->cap_piece->color . "_" . $this->cap_piece->type,
                "logpiece_def" => $def_piece->color . "_" . $def_piece->type
            )
        );

        $this->game->notifyAllPlayers(
            "updateAllPieceData",
            clienttranslate('${logpiece_def} captures ${logpiece_cap}'),
            array(
                "piece_id" => $this->cap_piece->id,
                "values_updated" => array("state" => CAPTURED),
                "logpiece_def" => $def_piece->color . "_" . $def_piece->type,
                "logpiece_cap" => $this->cap_piece->color . "_" . $this->cap_piece->type
            )
        );

        $this->capture_queue = [];
        self::DbQuery("DELETE FROM capture_queue");

        $this->cap_piece = null;
    }
}
