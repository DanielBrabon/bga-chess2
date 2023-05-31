<?php

class CHSMoves
{
    public $game;

    public function __construct($game)
    {
        $this->game = $game;
    }

    private static $attack_steps = array(
        "wpawn" => array([1, 1], [-1, 1]),
        "bpawn" => array([1, -1], [-1, -1]),
        "knight" => array([2, 1], [1, 2], [2, -1], [1, -2], [-2, 1], [-1, 2], [-2, -1], [-1, -2]),
        "bishop" => array([1, 1], [-1, 1], [-1, -1], [1, -1]),
        "rook" => array([1, 0], [-1, 0], [0, 1], [0, -1]),
        "king" => array([1, 0], [1, 1], [0, 1], [-1, 1], [-1, 0], [-1, -1], [0, -1], [1, -1]),
        "tiger" => array([1, 1], [-1, 1], [-1, -1], [1, -1])
    );

    private static $attack_reps = array(
        "wpawn" => 1,
        "bpawn" => 1,
        "knight" => 1,
        "bishop" => 7,
        "rook" => 7,
        "king" => 1,
        "tiger" => 2
    );

    private static $effective_types = array(
        "wpawn" => array("wpawn"),
        "bpawn" => array("bpawn"),
        "knight" => array("knight"),
        "bishop" => array("bishop"),
        "rook" => array("rook"),
        "king" => array("king"),
        "tiger" => array("tiger"),
        "queen" => array("bishop", "rook"),
        "nemesis" => array("bishop", "rook"),
        "empoweredknight" => array("knight"),
        "empoweredbishop" => array("bishop"),
        "empoweredrook" => array("rook"),
        "elegantqueen" => array("king"),
        "warriorking" => array("king"),
        "wildhorse" => array("knight"),
        "junglequeen" => array("knight", "rook")
    );

    public function getUIData($current_player_color)
    {
        $current_player = $this->game->playerManager->getPlayerByColor($current_player_color);

        $state_name = $this->game->gamestate->state()['name'];

        if ($current_player->id == $this->game->getActivePlayerId()) {
            switch ($state_name) {
                case "playerMove":
                    return $this->getAllMovesForPlayer($current_player)['moves'];
                case "playerKingMove":
                    return $this->getKingMovesForPlayer($current_player)['moves'];
                default:
                    return null;
            }
        } else if ($state_name == "drawOffer") {
            return $this->getAllMovesForPlayer($current_player)['moves'];
        }

        return null;
    }

    // Note that $piece_ids must be an array of valid piece ids belonging to $player_id
    public function getAllMovesForPlayer($player)
    {
        $piece_ids = $this->game->pieceManager->getActivePieceIdsByColor($player->color);

        return $this->getAllMovesForPieces($piece_ids, $player->id);
    }

    public function getKingMovesForPlayer($player)
    {
        $king_ids = $this->game->pieceManager->getPlayerKingIds($player->id);

        return $this->getAllMovesForPieces($king_ids, $player->id);
    }

    public function getCaptureSquaresForMove($target_x, $target_y, $moving_piece)
    {
        $piece_player = $this->game->playerManager->getPlayerByColor($moving_piece->color);

        foreach ($this->getAllMovesForPieces([$moving_piece->id], $piece_player->id)['moves'][$moving_piece->id] as $move) {
            if ($move['x'] == $target_x && $move['y'] == $target_y) {
                return $move['cap_squares'];
            }
        }

        return null;
    }

    private function getAllMovesForPieces($piece_ids, $player_id)
    {
        $this->moves = [];

        $game_data = array(
            "pieces" => $this->game->pieceManager->getDataForMoveGen(),
            "squares" => $this->game->pieceManager->getSquaresData()
        );

        $enemy_color = ($this->game->getPlayerColorById($player_id) == "000000") ? "ffffff" : "000000";
        $game_data = $this->getChecksAndThreats($enemy_color, $game_data);
        $friendly_kings = $this->getFriendlyKingData($player_id, $game_data);

        // $this->game->notifyAllPlayers("highlightAttackedSquares", "", $game_data['squares']);

        foreach ($piece_ids as $piece_id) {
            $moves = $this->getAttackingMovesForPiece($piece_id, $game_data)['attacking_moves'];
            $this->moves[$piece_id] = $this->removeUnavailableAttackingMovesForPiece($piece_id, $moves, $game_data);
            $this->getAvailableExtraMovesForPiece($piece_id, $game_data);
        }

        $this->removeSelfChecks($friendly_kings, $game_data);

        $move_count = 0;
        foreach ($piece_ids as $piece_id) {
            $this->moves[$piece_id] = array_values($this->moves[$piece_id]);
            $move_count += count($this->moves[$piece_id]);
        }

        return array("moves" => $this->moves, "move_count" => $move_count, "friendly_kings" => $friendly_kings);
    }

    private function getChecksAndThreats($enemy_color, $game_data)
    {
        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 8; $j++) {
                $game_data['squares'][$i][$j]['checks'] = [];
                $game_data['squares'][$i][$j]['threats'] = [];
            }
        }

        foreach ($game_data['pieces'] as $piece_id => $piece_data) {
            if ($piece_data['color'] != $enemy_color || $piece_data['state'] == CAPTURED || in_array($piece_data['type'], ["reaper", "ghost"])) {
                continue;
            }

            $attacks = $this->getAttackingMovesForPiece($piece_id, $game_data);

            foreach ($attacks['attacking_moves'] as $move) {
                foreach ($move['cap_squares'] as $square) {
                    $game_data['squares'][$square['x']][$square['y']]['checks'][] = $piece_id;
                }
            }

            foreach ($attacks['semi_attacking_moves'] as $move) {
                foreach ($move['cap_squares'] as $square) {
                    $game_data['squares'][$square['x']][$square['y']]['threats'][] = $piece_id;
                }
            }
        }
        return $game_data;
    }

    private function getAttackingMovesForPiece($piece_id, $game_data)
    {
        $piece_data = $game_data['pieces'][$piece_id];

        $result = array("attacking_moves" => [], "semi_attacking_moves" => []);

        if ($piece_data['type'] == "ghost") {
            return $result;
        }

        if ($piece_data['type'] == "reaper") {
            $start_y = ($piece_data['color'] == "000000") ? 2 : 1;

            for ($i = 1; $i <= 8; $i++) {
                for ($j = $start_y; $j <= $start_y + 6; $j++) {
                    $result['attacking_moves'][] = $this->makeMove($i, $j, array([$i, $j]));
                }
            }

            return $result;
        }

        if ($piece_data['type'] == "elephant") {
            $result['attacking_moves'] = $this->getElephantAttackingMoves($piece_id, $game_data);
            return $result;
        }

        if (in_array($piece_data['type'], ["pawn", "nemesispawn"])) {
            $piece_data['type'] = ($piece_data['color'] == "000000") ? "bpawn" : "wpawn";
        }

        $ef_types = self::$effective_types[$piece_data['type']];

        if (in_array($piece_data['type'], ["empoweredknight", "empoweredbishop", "empoweredrook"])) {
            foreach ($this->getEmpowerments($piece_id, $game_data) as $empowerment) {
                array_push($ef_types, $empowerment);
            }
        }

        foreach ($ef_types as $type) {
            $seen_squares = $this->getSeenSquares($piece_id, self::$attack_steps[$type], self::$attack_reps[$type], $game_data);

            foreach (['', 'semi_'] as $prefix) {
                foreach ($seen_squares[$prefix . 'seen_squares'] as $square) {
                    $result[$prefix . 'attacking_moves'][] = $this->makeMove($square[0], $square[1], array($square));
                }
            }
        }

        return $result;
    }

    private function getFriendlyKingData($player_id, $game_data)
    {
        $friendly_kings = array();

        $friendly_king_ids = $this->game->pieceManager->getPlayerKingIds($player_id);

        foreach ($friendly_king_ids as $king_id) {
            $friendly_kings[$king_id] = array(
                "x" => $game_data['pieces'][$king_id]['x'],
                "y" => $game_data['pieces'][$king_id]['y'],
                "checked_by" => $game_data['squares'][$game_data['pieces'][$king_id]['x']][$game_data['pieces'][$king_id]['y']]['checks']
            );
        }

        return $friendly_kings;
    }

    private function makeMove($x, $y, $cap_squares)
    {
        $result = $this->makeSquare($x, $y);
        $result['cap_squares'] = array();

        foreach ($cap_squares as $square) {
            $result['cap_squares'][] = $this->makeSquare($square[0], $square[1]);
        }

        return $result;
    }

    private function makeSquare($x, $y)
    {
        return array("x" => $x, "y" => $y);
    }

    private function getElephantAttackingMoves($elephant_id, $game_data)
    {
        $attacking_moves = array();

        $x_i = $game_data['pieces'][$elephant_id]['x'];
        $y_i = $game_data['pieces'][$elephant_id]['y'];

        $directions = self::$attack_steps["rook"];

        foreach ($directions as $index => $dir) {
            $squares = array([$x_i, $y_i]);

            for ($i = 1; $i <= 3; $i++) {
                $squares[$i] = array($squares[$i - 1][0] + $dir[0], $squares[$i - 1][1] + $dir[1]);

                if ($squares[$i][0] < 1 || $squares[$i][0] > 8 || $squares[$i][1] < 1 || $squares[$i][1] > 8) {
                    break;
                }

                $attacking_moves[$index] = $this->makeMove($squares[$i][0], $squares[$i][1], array_slice($squares, 1));
            }
        }
        return $attacking_moves;
    }

    private function getEmpowerments($piece_id, $game_data)
    {
        $empowerments = array();

        $directions = array(array(1, 0), array(0, 1), array(-1, 0), array(0, -1));

        $x = $game_data['pieces'][$piece_id]['x'];
        $y = $game_data['pieces'][$piece_id]['y'];

        foreach ($directions as $dir) {
            $x_a = $x + $dir[0];
            $y_a = $y + $dir[1];

            if ($x_a < 1 || $x_a > 8 || $y_a < 1 || $y_a > 8) {
                continue;
            }

            $piece_on_square = $game_data['squares'][$x_a][$y_a]['def_piece'];

            if (
                $piece_on_square !== null
                && $game_data['pieces'][$piece_on_square]['color'] == $game_data['pieces'][$piece_id]['color']
                && $game_data['pieces'][$piece_on_square]['type'] != $game_data['pieces'][$piece_id]['type']
            ) {
                switch ($game_data['pieces'][$piece_on_square]['type']) {
                    case "empoweredknight":
                        $empowerments[] = "knight";
                        break;

                    case "empoweredbishop":
                        $empowerments[] = "bishop";
                        break;

                    case "empoweredrook":
                        $empowerments[] = "rook";
                        break;
                }
            }
        }

        return array_unique($empowerments);
    }

    // Seen square: If that square has a compatible defending piece then the relevant action can be taken
    // Semi-seen square: If that square has a compatible defending piece then the relevant action might be able to be taken if a piece along the path is removed
    private function getSeenSquares($piece_id, $steps, $range, $game_data)
    {
        $result = array("seen_squares" => [], "semi_seen_squares" => []);

        foreach ($steps as $step) {
            $x = $game_data['pieces'][$piece_id]['x'];
            $y = $game_data['pieces'][$piece_id]['y'];

            $semi_seen = false;

            for ($i = 0; $i < $range; $i++) {
                $x += $step[0];
                $y += $step[1];

                // If the square is off the board, move to checking the next move step
                if ($x < 1 || $x > 8 || $y < 1 || $y > 8) {
                    break;
                }

                if ($semi_seen) {
                    $result['semi_seen_squares'][] = array($x, $y);
                } else {
                    $result['seen_squares'][] = array($x, $y);
                }

                $piece_on_square = $game_data['squares'][$x][$y]['def_piece'];
                if (!$semi_seen && $piece_on_square !== null) {
                    $semi_seen = true;
                }
            }
        }

        return $result;
    }

    private function removeUnavailableAttackingMovesForPiece($piece_id, $possible_moves, $game_data)
    {
        $piece_type = $game_data['pieces'][$piece_id]['type'];

        if (!in_array($piece_type, ["elephant", "wildhorse"])) {
            $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $possible_moves, $game_data);
        }

        if (in_array($piece_type, ["pawn", "nemesispawn"])) {
            $possible_moves = $this->removeUnavailablePawnNormalAttacks($possible_moves, $game_data);
        }

        if ($piece_type == "nemesis") {
            $possible_moves = $this->removeOccupiedNonKingSquares($possible_moves, $game_data);
        }

        if ($piece_type == "reaper") {
            $possible_moves = $this->removeKingOccupiedSquares($possible_moves, $game_data);
        }

        $possible_moves = $this->removeIllegalCaptureMoves($piece_id, $possible_moves, $game_data);

        return $possible_moves;
    }

    private function removeFriendlyOccupiedSquares($piece_id, $moves_array, $game_data)
    {
        foreach ($moves_array as $index => $move) {
            $piece_on_square = $game_data['squares'][$move['x']][$move['y']]['def_piece'];

            // If there is a friendly piece on the square, remove the move from the array
            if (
                $piece_on_square !== null
                && $game_data['pieces'][$piece_on_square]['color'] == $game_data['pieces'][$piece_id]['color']
            ) {
                unset($moves_array[$index]);
            }
        }
        return $moves_array;
    }

    private function removeUnavailablePawnNormalAttacks($moves_array, $game_data)
    {
        foreach ($moves_array as $index => $move) {
            // If there is no piece on the square, remove the move from the array
            if ($game_data['squares'][$move['x']][$move['y']]['def_piece'] === null) {
                unset($moves_array[$index]);
            }
        }
        return $moves_array;
    }

    private function removeOccupiedNonKingSquares($moves_array, $game_data)
    {
        foreach ($moves_array as $index => $move) {
            $piece_on_square = $game_data['squares'][$move['x']][$move['y']]['def_piece'];

            // If there is a non (warrior)king on the square, remove the move from the array
            if (
                $piece_on_square !== null
                && !in_array($game_data['pieces'][$piece_on_square]['type'], ["king", "warriorking"])
            ) {
                unset($moves_array[$index]);
            }
        }
        return $moves_array;
    }

    private function removeKingOccupiedSquares($moves_array, $game_data)
    {
        foreach ($moves_array as $index => $move) {
            $piece_on_square = $game_data['squares'][$move['x']][$move['y']]['def_piece'];

            // If there is a (warrior)king on the square, remove the move from the array
            if (
                $piece_on_square !== null
                && in_array($game_data['pieces'][$piece_on_square]['type'], ["king", "warriorking"])
            ) {
                unset($moves_array[$index]);
            }
        }
        return $moves_array;
    }

    private function removeIllegalCaptureMoves($piece_id, $moves_array, $game_data)
    {
        foreach ($moves_array as $index => $move) {
            if (!$this->isCapturingMoveLegal($piece_id, $move['cap_squares'], $game_data)) {
                unset($moves_array[$index]);
            }
        }
        return $moves_array;
    }

    private function isCapturingMoveLegal($cap_id, $cap_squares, $game_data)
    {
        foreach ($cap_squares as $square) {
            $piece_on_square = $game_data['squares'][$square['x']][$square['y']]['def_piece'];

            if ($piece_on_square !== null) {
                if ($game_data['pieces'][$piece_on_square]['type'] == "ghost") {
                    return false;
                }

                if (
                    in_array($game_data['pieces'][$piece_on_square]['type'], ["king", "warriorking"])
                    && $game_data['pieces'][$piece_on_square]['color'] == $game_data['pieces'][$cap_id]['color']
                ) {
                    return false;
                }

                if (
                    $game_data['pieces'][$piece_on_square]['type'] == "nemesis"
                    && !in_array($game_data['pieces'][$cap_id]['type'], ["king", "warriorking"])
                ) {
                    return false;
                }

                if ($game_data['pieces'][$piece_on_square]['type'] == "elephant") {
                    if (
                        abs($game_data['pieces'][$cap_id]['x'] - $square['x']) > 2
                        || abs($game_data['pieces'][$cap_id]['y'] - $square['y']) > 2
                    ) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function getAvailableExtraMovesForPiece($piece_id, $game_data)
    {
        switch ($game_data['pieces'][$piece_id]['type']) {
            case "pawn":
                $this->moves[$piece_id] = array_merge($this->moves[$piece_id], $this->getAvailableEnPassants($piece_id, $game_data));
                $this->getAvailablePawnPushes($piece_id, $game_data);
                break;

            case "king":
                $this->moves[$piece_id] = array_merge($this->moves[$piece_id], $this->getAvailableCastleMoves($piece_id, $game_data));
                break;

            case "nemesispawn":
                $this->moves[$piece_id] = array_merge($this->moves[$piece_id], $this->getAvailableEnPassants($piece_id, $game_data));
                $this->getAvailableNemesisPawnPushes($piece_id, $game_data);
                break;

            case "ghost":
                $this->getAvailableGhostMoves($piece_id, $game_data);
                break;

            case "elephant":
                $this->getAvailableElephantNonAttackingMoves($piece_id, $game_data);
                break;

            case "warriorking":
                $this->getWarriorKingWhirlwind($piece_id, $game_data);
                break;
            default:
                break;
        }
    }

    private function getAvailablePawnPushes($pawn_id, $game_data)
    {
        $forward = ($game_data['pieces'][$pawn_id]['color'] == "000000") ? -1 : 1;

        $x_i = $game_data['pieces'][$pawn_id]['x'];
        $y_i = $game_data['pieces'][$pawn_id]['y'];

        $y_f = $y_i + $forward;

        // If one square forward is empty
        if ($game_data['squares'][$x_i][$y_f]['def_piece'] === null) {
            $this->moves[$pawn_id][] = $this->makeMove($x_i, $y_f, []);

            $y_2f = $y_f + $forward;

            // If one and two squares forward are free and the pawn hasn't moved yet
            if (
                $game_data['pieces'][$pawn_id]['moves_made'] == 0
                && $game_data['squares'][$x_i][$y_2f]['def_piece'] === null
            ) {
                $this->moves[$pawn_id][] = $this->makeMove($x_i, $y_2f, []);
            }
        }
    }

    public function getAvailableEnPassants($pawn_id, $game_data)
    {
        $enps = array();

        $x_i = $game_data['pieces'][$pawn_id]['x'];
        $y_i = $game_data['pieces'][$pawn_id]['y'];

        foreach ([1, -1] as $dir) {
            $x = $x_i + $dir;

            if ($x < 1 || $x > 8) {
                continue;
            }

            $adj_id = $game_data['squares'][$x][$y_i]['def_piece'];

            if (
                $adj_id !== null
                && $game_data['pieces'][$adj_id]['state'] == EN_PASSANT_VULNERABLE
            ) {
                $forward = ($game_data['pieces'][$pawn_id]['color'] == "000000") ? -1 : 1;

                $enps[] = $this->makeMove($x, $y_i + $forward, [[$x, $y_i]]);
            }
        }

        return $enps;
    }

    public function getAvailableCastleMoves($king_id, $game_data)
    {
        $castles = array();

        $x_i = $game_data['pieces'][$king_id]['x'];
        $y_i = $game_data['pieces'][$king_id]['y'];

        // The king cannot castle if it isn't classic, or it already moved, or it is in check
        if (
            $this->game->playerManager->getPlayerByColor($game_data['pieces'][$king_id]['color'])->army != "classic"
            || $game_data['pieces'][$king_id]['moves_made'] != 0
            || count($game_data['squares'][$x_i][$y_i]['checks']) != 0
        ) {
            return $castles;
        }

        // Check both directions for possible castle
        foreach (array(-1, 1) as $dir) {
            for ($x = $x_i + $dir; $x > 0 && $x < 9; $x += $dir) {
                $pid = $game_data['squares'][$x][$y_i]['def_piece'];

                if ($pid !== null) {
                    // If it's a friendly unmoved rook at least 3 squares away,
                    // and we wouldn't be moving through check, we can castle in this direction
                    if (
                        abs($x - $x_i) >= 3
                        && $game_data['pieces'][$pid]['color'] == $game_data['pieces'][$king_id]['color']
                        && $game_data['pieces'][$pid]['type'] == "rook"
                        && $game_data['pieces'][$pid]['moves_made'] == 0
                        && count($game_data['squares'][$x_i + $dir][$y_i]['checks']) == 0
                        && count($game_data['squares'][$x_i + (2 * $dir)][$y_i]['checks']) == 0
                    ) {
                        $castles[] = $this->makeMove($x_i + (2 * $dir), $y_i, []);
                    }

                    break;
                }
            }
        }

        return $castles;
    }

    private function getAvailableNemesisPawnPushes($nemesis_pawn_id, $game_data)
    {
        $x_i = $game_data['pieces'][$nemesis_pawn_id]['x'];
        $y_i = $game_data['pieces'][$nemesis_pawn_id]['y'];

        $forward = ($game_data['pieces'][$nemesis_pawn_id]['color'] == "000000") ? -1 : 1;

        $enemy_kings = array();

        // For each enemy (warrior)king, its position and distance from the npawn
        foreach ($game_data['pieces'] as $piece_id => $piece_data) {
            if (
                $piece_data['color'] != $game_data['pieces'][$nemesis_pawn_id]['color']
                && in_array($piece_data['type'], ["king", "warriorking"])
            ) {
                $enemy_kings[$piece_id]['x'] = $piece_data['x'];
                $enemy_kings[$piece_id]['y'] = $piece_data['y'];

                $enemy_kings[$piece_id]['dist_i'] = abs($enemy_kings[$piece_id]['x'] - $x_i) + abs($enemy_kings[$piece_id]['y'] - $y_i);
            }
        }

        // If one square forward is empty, it is a valid push
        $y_f = $y_i + $forward;
        if ($game_data['squares'][$x_i][$y_f]['def_piece'] === null) {
            $this->moves[$nemesis_pawn_id][] = $this->makeMove($x_i, $y_f, []);
        }

        $adj_squares = array(
            array($x_i + 1, $y_i),
            array($x_i - 1, $y_i),
            array($x_i + 1, $y_i + 1),
            array($x_i - 1, $y_i + 1),
            array($x_i + 1, $y_i - 1),
            array($x_i - 1, $y_i - 1),
            array($x_i, $y_i - $forward)
        );

        // If an adjacent empty square is closer to an enemy king, it is a valid push
        foreach ($adj_squares as list($x, $y)) {
            if (
                $x < 1 || $x > 8 || $y < 1 || $y > 8
                || $game_data['squares'][$x][$y]['def_piece'] !== null
            ) {
                continue;
            }

            foreach ($enemy_kings as $king) {
                $dist = abs($king['x'] - $x) + abs($king['y'] - $y);

                if ($dist < $king['dist_i'] && !$this->doesMoveAlreadyExist($nemesis_pawn_id, $x, $y)) {
                    $this->moves[$nemesis_pawn_id][] = $this->makeMove($x, $y, []);
                }
            }
        }
    }

    private function doesMoveAlreadyExist($piece_id, $x, $y)
    {
        foreach ($this->moves[$piece_id] as $move) {
            if ($move['x'] == $x && $move['y'] == $y) {
                return true;
            }
        }
        return false;
    }

    private function getAvailableGhostMoves($ghost_id, $game_data)
    {
        for ($i = 1; $i <= 8; $i++) {
            for ($j = 1; $j <= 8; $j++) {
                if ($game_data['squares'][$i][$j]['def_piece'] === null) {
                    $this->moves[$ghost_id][] = $this->makeMove($i, $j, []);
                }
            }
        }
    }

    private function getAvailableElephantNonAttackingMoves($ele_id, $game_data)
    {
        $x_i = $game_data['pieces'][$ele_id]['x'];
        $y_i = $game_data['pieces'][$ele_id]['y'];

        $directions = self::$attack_steps["rook"];

        foreach ($directions as $dir) {
            $sq = [$x_i, $y_i];

            $change_axis = ($dir[0] == 0) ? 1 : 0;

            for ($i = 0; $i < 2; $i++) {
                $sq = [$sq[0] + $dir[0], $sq[1] + $dir[1]];

                if (
                    $sq[$change_axis] < 2 || $sq[$change_axis] > 7
                    || $game_data['squares'][$sq[0]][$sq[1]]['def_piece'] !== null
                ) {
                    break;
                }

                $this->moves[$ele_id][] = $this->makeMove($sq[0], $sq[1], []);
            }
        }
    }

    private function getWarriorKingWhirlwind($wking_id, $game_data)
    {
        $x_i = $game_data['pieces'][$wking_id]['x'];
        $y_i = $game_data['pieces'][$wking_id]['y'];

        $cap_squares = array();

        $directions = self::$attack_steps['king'];

        foreach ($directions as $dir) {
            $x = $x_i + $dir[0];
            $y = $y_i + $dir[1];

            if ($x < 1 || $x > 8 || $y < 1 || $y > 8) {
                continue;
            }

            $cap_squares[] = [$x, $y];
        }

        $move = $this->makeMove($x_i, $y_i, $cap_squares);

        if ($this->isCapturingMoveLegal($wking_id, $move['cap_squares'], $game_data)) {
            $this->moves[$wking_id][] = $move;
        }
    }

    // Takes an array of possible moves and returns the same array but with any options removed that would leave the player's own king in check 
    private function removeSelfChecks($friendly_kings, $game_data)
    {
        $ids_to_recheck = array();

        // Recheck pieces which are checking a king
        foreach ($friendly_kings as $king_data) {
            $ids_to_recheck = array_merge($ids_to_recheck, $king_data['checked_by']);
        }

        foreach ($this->moves as $moving_id => $moves) {
            $x_i = $game_data['pieces'][$moving_id]['x'];
            $y_i = $game_data['pieces'][$moving_id]['y'];

            $moving_type = $game_data['pieces'][$moving_id]['type'];

            // If the moving piece is a king or warriorking, it can't move onto an attacked square
            if (in_array($moving_type, ["king", "warriorking"])) {
                foreach ($moves as $index => $move) {
                    if (
                        ($move['x'] != $x_i || $move['y'] != $y_i)
                        && count($game_data['squares'][$move['x']][$move['y']]['checks']) != 0
                    ) {
                        unset($this->moves[$moving_id][$index]);
                    }
                }
            }

            $enemies_attacking_start_square = $game_data['squares'][$x_i][$y_i]['checks'];
            $ids_piece = $ids_to_recheck;

            // Recheck pieces which are checking the moving piece and threatening a king
            foreach ($friendly_kings as $king_data) {
                $ids_piece = array_merge(
                    $ids_piece,
                    array_intersect(
                        $enemies_attacking_start_square,
                        $game_data['squares'][$king_data['x']][$king_data['y']]['threats']
                    )
                );
            }

            // Can capture and land on different squares
            $disjointed = (in_array($moving_type, ["pawn", "warriorking", "tiger", "elephant"])) ? true : false;

            foreach ($moves as $index => $move) {
                $ids_move = $ids_piece;

                if ($disjointed) {
                    foreach ($move['cap_squares'] as $square) {
                        if ($game_data['squares'][$square['x']][$square['y']]['def_piece'] !== null) {
                            // Recheck pieces which are checking a capture square and threatening a king
                            $enemies_attacking_square = $game_data['squares'][$square['x']][$square['y']]['checks'];

                            foreach ($friendly_kings as $king_data) {
                                $ids_move = array_merge(
                                    $ids_move,
                                    array_intersect(
                                        $enemies_attacking_square,
                                        $game_data['squares'][$king_data['x']][$king_data['y']]['threats']
                                    )
                                );
                            }
                        }
                    }
                }

                $ids_move = array_unique($ids_move);

                if (count($ids_move) == 0) {
                    continue;
                }

                $game_data_sim = $this->simulatePossibleMove($moving_id, $move, $game_data);

                if ($this->arePiecesAttackingKings($ids_move, $friendly_kings, $game_data_sim)) {
                    unset($this->moves[$moving_id][$index]);
                }
            }
        }
    }

    private function simulatePossibleMove($piece_id, $p_move, $game_data)
    {
        // Starting location of the moving piece
        $x_i = $game_data['pieces'][$piece_id]['x'];
        $y_i = $game_data['pieces'][$piece_id]['y'];

        // Remove the moving piece from its starting location
        $game_data['squares'][$x_i][$y_i]['def_piece'] = null;

        // Set as captured any pieces which would be captured in this move
        foreach ($p_move['cap_squares'] as $square) {
            $piece_on_cap_square = $game_data['squares'][$square['x']][$square['y']]['def_piece'];

            if ($piece_on_cap_square !== null) {
                $game_data['squares'][$square['x']][$square['y']]['def_piece'] = null;
                $game_data['pieces'][$piece_on_cap_square]['state'] = CAPTURED;

                if ($game_data['pieces'][$piece_id]['type'] == "tiger") {
                    $p_move['x'] = $x_i;
                    $p_move['y'] = $y_i;
                }
            }
        }

        // Set the updated location of the moving piece
        $game_data['squares'][$p_move['x']][$p_move['y']]['def_piece'] = $piece_id;
        $game_data['pieces'][$piece_id]['x'] = $p_move['x'];
        $game_data['pieces'][$piece_id]['y'] = $p_move['y'];

        return $game_data;
    }

    // Returns true if any of the pieces in $piece_ids are attacking any of the squares in $king_squares, else false
    private function arePiecesAttackingKings($piece_ids, $friendly_kings, $game_data)
    {
        foreach ($piece_ids as $piece_id) {
            if ($game_data['pieces'][$piece_id]['state'] == CAPTURED) {
                continue;
            }

            $attacking_moves = $this->getAttackingMovesForPiece($piece_id, $game_data)['attacking_moves'];
            $attacking_moves = $this->removeIllegalCaptureMoves($piece_id, $attacking_moves, $game_data);

            foreach ($attacking_moves as $move) {
                foreach ($move['cap_squares'] as $square) {
                    foreach (array_keys($friendly_kings) as $king) {
                        if (
                            $square['x'] == $game_data['pieces'][$king]['x']
                            && $square['y'] == $game_data['pieces'][$king]['y']
                        ) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
