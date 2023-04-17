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

    // Note that $piece_ids must be an array of valid piece ids belonging to $player_id
    public function getAllMovesForPieces($piece_ids, $player_id, $game_data)
    {
        $enemy_player_color = ($this->game->getPlayerColorById($player_id) === "000000") ? "ffffff" : "000000";
        $friendly_king_ids = $this->game->getPlayerKingIds($player_id);
        $enemy_attacks = $this->getSquaresAttackedByColor($enemy_player_color, $game_data);

        //$this->printWithJavascript($enemy_attacks);
        /*self::notifyPlayer( $active_player_id, "highlightAttackedSquares", "", array( 
            'attacked_squares' => $enemy_attacks[0], 
            'semi_attacked_squares' => $enemy_attacks[1] )
        );*/

        $all_moves = array();

        foreach ($piece_ids as $piece_id) {
            $all_moves[$piece_id] = $this->getMovesForPiece($piece_id, $friendly_king_ids, $enemy_attacks, $game_data);
        }

        return $all_moves;
    }

    // Returns an array of the squares of all current possible moves for this piece (the squares the player can click on to make the move)
    private function getMovesForPiece($piece_id, $friendly_king_ids, $enemy_attacks, $game_data)
    {
        $piece_type = $game_data['pieces'][$piece_id]['type'];

        $possible_moves = $this->getAttackingMoveSquares($piece_id, $game_data)['attacking_squares'];

        if (!in_array($piece_type, ["elephant", "wildhorse"])) {
            $possible_moves = $this->removeFriendlyOccupiedSquares($piece_id, $possible_moves, $game_data);
        }

        if (in_array($piece_type, ["pawn", "nemesispawn"])) {
            $possible_moves = $this->removeUnavailablePawnAttacks($piece_id, $possible_moves, $game_data);
        }

        if (in_array($piece_type, ["pawn", "king", "nemesispawn", "ghost", "elephant"])) {
            $non_cap_moves = $this->getNonCapturingMoveSquares($piece_id, $enemy_attacks, $game_data);
            $possible_moves = array_merge($possible_moves, $non_cap_moves);
        }

        if ($piece_type === "nemesis") {
            $possible_moves = $this->removeEnemyOccupiedNonKingSquares($piece_id, $possible_moves, $game_data);
        }

        if ($piece_type === "reaper") {
            $possible_moves = $this->removeEnemyOccupiedKingSquares($piece_id, $possible_moves, $game_data);
        }

        $corresponding_captures = $this->getCorrespondingCaptures($piece_id, $possible_moves, $game_data);
        $moves_and_captures = $this->removeIllegalCaptureMoves($piece_id, $possible_moves, $corresponding_captures, $game_data);

        $possible_moves = $this->removeSelfChecks($piece_id, $friendly_king_ids, $enemy_attacks, $moves_and_captures, $game_data);

        return $possible_moves;
    }

    private function getAttackingMoveSquares($piece_id, $game_data)
    {
        $piece_data = $game_data['pieces'][$piece_id];

        $result = array("attacking_squares" => [], "semi_attacking_squares" => []);

        if ($piece_data['type'] === "ghost") {
            return $result;
        }

        if ($piece_data['type'] === "reaper") {
            $start_y = ($piece_data['color'] === "000000") ? 2 : 1;

            for ($i = 1; $i <= 8; $i++) {
                for ($j = $start_y; $j <= $start_y + 6; $j++) {
                    $result['attacking_squares'][] = array($i, $j);
                }
            }

            return $result;
        }

        if ($piece_data['type'] === "elephant") {
            $result['attacking_squares'] = $this->getElephantAttackingMoveSquares($piece_id, $game_data);
            return $result;
        }

        if (in_array($piece_data['type'], ["pawn", "nemesispawn"])) {
            $piece_data['type'] = ($piece_data['color'] === "000000") ? "bpawn" : "wpawn";
        }

        $ef_types = self::$effective_types[$piece_data['type']];

        if (in_array($piece_data['type'], ["empoweredknight", "empoweredbishop", "empoweredrook"])) {
            foreach ($this->getEmpowerments($piece_id, $game_data) as $empowerment) {
                array_push($ef_types, $empowerment);
            }
        }

        foreach ($ef_types as $type) {
            $seen_squares = $this->getSeenSquares($piece_id, self::$attack_steps[$type], self::$attack_reps[$type], $game_data);
            $result['attacking_squares'] = array_merge($result['attacking_squares'], $seen_squares['seen_squares']);
            $result['semi_attacking_squares'] = array_merge($result['semi_attacking_squares'], $seen_squares['semi_seen_squares']);
        }

        if ($piece_data['type'] === "warriorking") {
            $result['attacking_squares'][] = array((int) $piece_data['x'], (int) $piece_data['y']);
        }

        return $result;
    }

    private function getElephantAttackingMoveSquares($elephant_id, $game_data)
    {
        $attacking_move_squares = array();

        $elephant_location = array((int) $game_data['pieces'][$elephant_id]['x'], (int) $game_data['pieces'][$elephant_id]['y']);

        $directions = self::$attack_steps["rook"];

        foreach ($directions as $direction_index => $direction) {
            $square = $elephant_location;

            for ($i = 1; $i <= 3; $i++) {
                $square[0] += $direction[0];
                $square[1] += $direction[1];

                if ($square[0] < 1 || $square[0] > 8 || $square[1] < 1 || $square[1] > 8) {
                    break;
                }

                $attacking_move_squares[$direction_index] = $square;
            }
        }
        $attacking_move_squares = array_values($attacking_move_squares);
        return $attacking_move_squares;
    }

    // Return all squares attacked or semi-attacked by pieces of the color $player_color
    private function getSquaresAttackedByColor($player_color, $game_data)
    {
        $result = array("attacked_squares" => [], "semi_attacked_squares" => []);

        // Creates an 8x8 array of empty arrays
        for ($i = 1; $i <= 8; $i++) {
            $result['attacked_squares'][$i] = array();

            for ($j = 1; $j <= 8; $j++) {
                $result['attacked_squares'][$i][$j] = array();
            }
        }

        $result['semi_attacked_squares'] = $result['attacked_squares'];

        // For all uncaptured pieces of this color
        foreach ($game_data['pieces'] as $piece_id => $piece_data) {
            if ($piece_data['captured'] === "1" || $piece_data['color'] != $player_color || in_array($piece_data['type'], ["reaper", "ghost"])) {
                continue;
            }

            $attacks = $this->getAttackingMoveSquares($piece_id, $game_data);

            $att_squares = $attacks['attacking_squares'];
            $att_squares_cc = array();

            if (in_array($piece_data['type'], ["pawn", "nemesispawn"])) {
                foreach ($att_squares as $square) {
                    $att_squares_cc[] = array($square);
                }
            } else {
                $att_squares_cc = $this->getCorrespondingCaptures($piece_id, $att_squares, $game_data);
            }

            $moves_and_captures = $this->removeIllegalCaptureMoves($piece_id, $att_squares, $att_squares_cc, $game_data);
            $att_squares = $moves_and_captures['possible_moves'];
            $att_squares_cc = $moves_and_captures['corresponding_captures'];

            $semi_att_squares = $attacks['semi_attacking_squares'];
            $semi_att_squares_cc = $this->getCorrespondingCaptures($piece_id, $semi_att_squares, $game_data);

            // For each of the attacked squares
            foreach ($att_squares_cc as $cap_list) {
                foreach ($cap_list as $cap) {
                    $result['attacked_squares'][$cap[0]][$cap[1]][] = $piece_id;
                }
            }
            // For each of the semi-attacked squares
            foreach ($semi_att_squares_cc as $cap_list) {
                foreach ($cap_list as $cap) {
                    $result['semi_attacked_squares'][$cap[0]][$cap[1]][] = $piece_id;
                }
            }
        }

        return $result;
    }

    private function getNonCapturingMoveSquares($piece_id, $enemy_attacks, $game_data)
    {
        $move_squares = array();

        switch ($game_data['pieces'][$piece_id]['type']) {
            case "pawn":
                foreach ($this->getAvailablePawnPushes($piece_id, $game_data) as $pawn_push) {
                    $move_squares[] = $pawn_push;
                }
                break;

            case "king":
                foreach ($this->getAvailableCastleMoves($piece_id, $enemy_attacks, $game_data) as $castle_move) {
                    $move_squares[] = $castle_move;
                }
                break;

            case "nemesispawn":
                foreach ($this->getAvailableNemesisPawnPushes($piece_id, $game_data) as $pawn_push) {
                    $move_squares[] = $pawn_push;
                }
                break;

            case "ghost":
                for ($i = 1; $i <= 8; $i++) {
                    for ($j = 1; $j <= 8; $j++) {
                        if ($game_data['squares'][$i][$j]['def_piece'] === null) {
                            $move_squares[] = array($i, $j);
                        }
                    }
                }
                break;

            case "elephant":
                $elephant_location = array((int) $game_data['pieces'][$piece_id]['x'], (int) $game_data['pieces'][$piece_id]['y']);

                $directions = self::$attack_steps["rook"];

                foreach ($directions as $direction) {
                    $square = $elephant_location;

                    $change_axis = ($direction[0] === 0) ? 1 : 0;

                    for ($i = 1; $i <= 2; $i++) {
                        $square[0] += $direction[0];
                        $square[1] += $direction[1];

                        if ($square[$change_axis] < 2 || $square[$change_axis] > 7) {
                            break;
                        }
                        if ($game_data['squares'][$square[0]][$square[1]]['def_piece'] != null) {
                            break;
                        }

                        $move_squares[] = $square;
                    }
                }
                break;
        }

        return $move_squares;
    }

    private function getAvailableCastleMoves($king_id, $enemy_attacks, $game_data)
    {
        $castle_moves = array();

        // If the player is not using the classic army, the king cannot castle
        $all_players_data = $this->game->getAllPlayerData();

        foreach ($all_players_data as $player_data) {
            if ($player_data['color'] === $game_data['pieces'][$king_id]['color']) {
                if ($player_data['army'] != "classic") {
                    return $castle_moves;
                }
                break;
            }
        }

        // If the king already moved it cannot castle
        if ($game_data['pieces'][$king_id]['moves_made'] != "0") {
            return $castle_moves;
        }

        // Store the king's location
        $king_x = (int) $game_data['pieces'][$king_id]['x'];
        $king_y = (int) $game_data['pieces'][$king_id]['y'];

        // If the king is in check right now it cannot castle
        if (count($enemy_attacks['attacked_squares'][$king_x][$king_y]) != 0) {
            return $castle_moves;
        }

        // Check both directions for possible castle
        foreach (array(-1, 1) as $direction) {
            $square = array($king_x + $direction, $king_y);

            // If the king would be moving through or into check, it cannot castle on this side
            if (
                count($enemy_attacks['attacked_squares'][$square[0]][$square[1]]) != 0
                || count($enemy_attacks['attacked_squares'][$square[0] + $direction][$square[1]]) != 0
            ) {
                continue;
            }

            while ($square[0] > 0 && $square[0] < 9) {
                // If there is a piece on this square
                if ($game_data['squares'][$square[0]][$square[1]]['def_piece'] != null) {
                    // Get the data for this encountered piece
                    $piece_on_square = $game_data['pieces'][$game_data['squares'][$square[0]][$square[1]]['def_piece']];

                    // If it's a friendly unmoved rook at least 3 squares away, we can castle in this direction
                    if (
                        abs($square[0] - $king_x) >= 3
                        && $piece_on_square['color'] === $game_data['pieces'][$king_id]['color']
                        && $piece_on_square['type'] === "rook"
                        && $piece_on_square['moves_made'] === "0"
                    ) {
                        $castle_moves[] = array($king_x + (2 * $direction), $king_y);
                    }

                    break;
                }

                $square[0] += $direction;
            }
        }

        return $castle_moves;
    }

    private function getAvailablePawnPushes($pawn_id, $game_data)
    {
        $pawn_pushes = array();

        $forward_direction = ($game_data['pieces'][$pawn_id]['color'] === "000000") ? -1 : 1;

        $pawn_location = array((int)$game_data['pieces'][$pawn_id]['x'], (int)$game_data['pieces'][$pawn_id]['y']);

        // If one square forward is empty
        if ($game_data['squares'][$pawn_location[0]][$pawn_location[1] + $forward_direction]['def_piece'] === null) {
            $pawn_pushes[] = array($pawn_location[0], $pawn_location[1] + $forward_direction);

            // If one and two squares forward are free and the pawn hasn't moved yet
            if (
                $game_data['pieces'][$pawn_id]['moves_made'] === "0"
                && $game_data['squares'][$pawn_location[0]][$pawn_location[1] + 2 * $forward_direction]['def_piece'] === null
            ) {
                $pawn_pushes[] = array($pawn_location[0], $pawn_location[1] + 2 * $forward_direction);
            }
        }

        return $pawn_pushes;
    }

    private function getAvailableNemesisPawnPushes($nemesis_pawn_id, $game_data)
    {
        $nemesis_pawn_pushes = array();

        $nemesis_pawn_location = array((int)$game_data['pieces'][$nemesis_pawn_id]['x'], (int)$game_data['pieces'][$nemesis_pawn_id]['y']);
        $nemesis_pawn_color = $game_data['pieces'][$nemesis_pawn_id]['color'];

        $forward_direction = ($nemesis_pawn_color === "000000") ? -1 : 1;

        $enemy_king_locations = array();

        // For each enemy king or warrior king
        foreach ($game_data['pieces'] as $piece_data) {
            if ($piece_data['color'] != $nemesis_pawn_color && in_array($piece_data['type'], ["king", "warriorking"])) {
                $enemy_king_locations[] = array((int) $piece_data['x'], (int) $piece_data['y']);
            }
        }

        // If one square forward is empty
        if ($game_data['squares'][$nemesis_pawn_location[0]][$nemesis_pawn_location[1] + $forward_direction]['def_piece'] === null) {
            $nemesis_pawn_pushes[] = array($nemesis_pawn_location[0], $nemesis_pawn_location[1] + $forward_direction);
        }

        $all_directions = array(array(1, 0), array(1, 1), array(-1, 1), array(-1, 0), array(-1, -1), array(0, -$forward_direction), array(1, -1));

        foreach ($all_directions as $direction) {
            $square_in_direction = array($nemesis_pawn_location[0] + $direction[0], $nemesis_pawn_location[1] + $direction[1]);

            if ($square_in_direction[0] < 1 || $square_in_direction[0] > 8 || $square_in_direction[1] < 1 || $square_in_direction[1] > 8) {
                continue;
            }

            if ($game_data['squares'][$square_in_direction[0]][$square_in_direction[1]]['def_piece'] != null) {
                continue;
            }

            foreach ($enemy_king_locations as $enemy_king_location) {
                if (abs($square_in_direction[0] - $enemy_king_location[0]) < abs($nemesis_pawn_location[0] - $enemy_king_location[0])) {
                    if (!(abs($square_in_direction[1] - $enemy_king_location[1]) > abs($nemesis_pawn_location[1] - $enemy_king_location[1]))) {
                        $nemesis_pawn_pushes[] = array($square_in_direction[0], $square_in_direction[1]);
                    }
                } elseif (abs($square_in_direction[1] - $enemy_king_location[1]) < abs($nemesis_pawn_location[1] - $enemy_king_location[1])) {
                    if (!(abs($square_in_direction[0] - $enemy_king_location[0]) > abs($nemesis_pawn_location[0] - $enemy_king_location[0]))) {
                        $nemesis_pawn_pushes[] = array($square_in_direction[0], $square_in_direction[1]);
                    }
                }
            }
        }

        return $nemesis_pawn_pushes;
    }

    // Returns an array containing all capture squares for each possible move in $possible_moves
    public function getCorrespondingCaptures($piece_id, $possible_moves, $game_data)
    {
        $corresponding_captures = array();

        $simple_piece = false;

        switch ($game_data['pieces'][$piece_id]['type']) {
            case "pawn":
                foreach ($possible_moves as $possible_move) {
                    // If it's an attacking move
                    if (abs($possible_move[0] - $game_data['pieces'][$piece_id]['x']) === 1) {
                        // If it's an en passant move
                        if ($game_data['squares'][$possible_move[0]][$possible_move[1]]['def_piece'] === null) {
                            $corresponding_captures[] = array(array($possible_move[0], (int)$game_data['pieces'][$piece_id]['y']));
                        }
                        // If it's a normal attack
                        else {
                            $corresponding_captures[] = array($possible_move);
                        }
                    }
                    // If it's not an attacking move
                    else {
                        $corresponding_captures[] = array();
                    }
                }
                break;

            case "king":
                foreach ($possible_moves as $possible_move) {
                    // If it's a castle move
                    if (abs($possible_move[0] - $game_data['pieces'][$piece_id]['x']) === 2) {
                        $corresponding_captures[] = array();
                    } else {
                        $corresponding_captures[] = array($possible_move);
                    }
                }
                break;

            case "nemesispawn":
                $piece_color = $game_data['pieces'][$piece_id]['color'];
                $forward_direction = ($piece_color === "000000") ? -1 : 1;

                foreach ($possible_moves as $possible_move) {
                    // If it's a diagonal forward move, this can be a capturing move
                    if (
                        abs($possible_move[0] - $game_data['pieces'][$piece_id]['x']) === 1
                        && $possible_move[1] - $game_data['pieces'][$piece_id]['y'] === $forward_direction
                    ) {
                        $piece_adjacent = $game_data['squares'][$possible_move[0]][$game_data['pieces'][$piece_id]['y']]['def_piece'];
                        // If the condition is met for an en passant move, the capture square is altered
                        if (
                            $piece_adjacent != null
                            && $game_data['pieces'][$piece_adjacent]['color'] != $game_data['pieces'][$piece_id]['color']
                            && $game_data['pieces'][$piece_adjacent]['en_passant_vulnerable'] != "0"
                        ) {
                            $corresponding_captures[] = array(array($possible_move[0], (int)$game_data['pieces'][$piece_id]['y']));
                        } else {
                            $corresponding_captures[] = array(array($possible_move[0], $possible_move[1]));
                        }
                    }
                    // If it's not a diagonal forward move, it cannot capture
                    else {
                        $corresponding_captures[] = array();
                    }
                }
                break;

            case "ghost":
                foreach ($possible_moves as $possible_move) {
                    $corresponding_captures[] = array();
                }
                break;

            case "warriorking":
                foreach ($possible_moves as $move_index => $possible_move) {
                    if ($possible_move === [(int)$game_data['pieces'][$piece_id]['x'], (int)$game_data['pieces'][$piece_id]['y']]) {
                        $corresponding_captures[$move_index] = array();

                        $directions = self::$attack_steps['king'];

                        foreach ($directions as $direction) {
                            $square = array((int) $game_data['pieces'][$piece_id]['x'] + $direction[0], (int) $game_data['pieces'][$piece_id]['y'] + $direction[1]);

                            if ($square[0] < 1 || $square[0] > 8 || $square[1] < 1 || $square[1] > 8) {
                                continue;
                            }

                            $corresponding_captures[$move_index][] = $square;
                        }
                    } else {
                        $corresponding_captures[$move_index] = array($possible_move);
                    }
                }
                break;

            case "elephant":
                $elephant_location = array((int) $game_data['pieces'][$piece_id]['x'], (int) $game_data['pieces'][$piece_id]['y']);

                foreach ($possible_moves as $move_index => $possible_move) {
                    $corresponding_captures[$move_index] = array();

                    $difference = array($possible_move[0] - $elephant_location[0], $possible_move[1] - $elephant_location[1]);
                    $difference_magnitude = (abs($difference[0]) === 0) ? abs($difference[1]) : abs($difference[0]);
                    $direction = array($difference[0] / $difference_magnitude, $difference[1] / $difference_magnitude);

                    if (
                        $difference_magnitude != 3
                        && in_array($possible_move[0] + $direction[0], [0, 9])
                        && in_array($possible_move[1] + $direction[1], [0, 9])
                    ) {
                        continue;
                    }

                    $square = $elephant_location;

                    for ($i = 0; $i < 3; $i++) {
                        $square[0] += $direction[0];
                        $square[1] += $direction[1];

                        $corresponding_captures[$move_index][] = $square;

                        if ($square === $possible_move) {
                            break;
                        }
                    }
                }
                break;

            default:
                $simple_piece = true;
                break;
        }

        if ($simple_piece) {
            foreach ($possible_moves as $possible_move) {
                $corresponding_captures[] = array($possible_move);
            }
        }

        return $corresponding_captures;
    }

    // Seen square: If that square has a compatible defending piece then the relevant action can be taken
    // Semi-seen square: If that square has a compatible defending piece then the relevant action might be able to be taken if a piece along the path is removed
    private function getSeenSquares($piece_id, $steps, $range, $game_data)
    {
        $result = array("seen_squares" => [], "semi_seen_squares" => []);

        $piece_x = $game_data['pieces'][$piece_id]['x'];
        $piece_y = $game_data['pieces'][$piece_id]['y'];

        foreach ($steps as $step) {
            $square_x = $piece_x;
            $square_y = $piece_y;

            $semi_seen = false;

            for ($i = 1; $i <= $range; $i++) {
                $square_x += $step[0];
                $square_y += $step[1];

                // If the square is off the board, move to checking the next move step
                if ($square_x < 1 || $square_x > 8 || $square_y < 1 || $square_y > 8) {
                    break;
                }

                if ($semi_seen) {
                    $result['semi_seen_squares'][] = array($square_x, $square_y);
                } else {
                    $result['seen_squares'][] = array($square_x, $square_y);
                }

                $piece_on_square = $game_data['squares'][$square_x][$square_y]['def_piece'];
                if (!$semi_seen && $piece_on_square != null) {
                    $semi_seen = true;
                }
            }
        }

        return $result;
    }

    private function getEmpowerments($piece_id, $game_data)
    {
        $empowerments = array();

        $directions = array(array(1, 0), array(0, 1), array(-1, 0), array(0, -1));

        $piece_location = array((int) $game_data['pieces'][$piece_id]['x'], (int) $game_data['pieces'][$piece_id]['y']);

        foreach ($directions as $direction) {
            $square = array($piece_location[0] + $direction[0], $piece_location[1] + $direction[1]);

            if ($square[0] < 1 || $square[0] > 8 || $square[1] < 1 || $square[1] > 8) {
                continue;
            }

            $piece_on_square = $game_data['squares'][$square[0]][$square[1]]['def_piece'];

            if (
                $piece_on_square != null
                && $game_data['pieces'][$piece_on_square]['color'] === $game_data['pieces'][$piece_id]['color']
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

        return $empowerments;
    }

    private function removeFriendlyOccupiedSquares($piece_id, $squares_array, $game_data)
    {
        // Loop through the provided array of squares
        foreach ($squares_array as $index => $square) {
            // If a square has a friendly piece on it, remove this square from the array
            $piece_on_square = $game_data['squares'][$square[0]][$square[1]]['def_piece'];
            if (
                $piece_on_square != null
                && $game_data['pieces'][$piece_on_square]['color'] === $game_data['pieces'][$piece_id]['color']
                && $game_data['pieces'][$piece_on_square]['piece_id'] != $piece_id
            ) {
                unset($squares_array[$index]);
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    private function removeEnemyOccupiedNonKingSquares($piece_id, $squares_array, $game_data)
    {
        // Loop through the provided array of squares
        foreach ($squares_array as $index => $square) {
            $piece_on_square = $game_data['squares'][$square[0]][$square[1]]['def_piece'];

            // If there is an enemy non (warrior)king on the square, remove the square from the array
            if (
                $piece_on_square != null
                && $game_data['pieces'][$piece_on_square]['color'] != $game_data['pieces'][$piece_id]['color']
                && !in_array($game_data['pieces'][$piece_on_square]['type'], ["king", "warriorking"])
            ) {
                unset($squares_array[$index]);
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    private function removeEnemyOccupiedKingSquares($piece_id, $squares_array, $game_data)
    {
        // Loop through the provided array of squares
        foreach ($squares_array as $index => $square) {
            $piece_on_square = $game_data['squares'][$square[0]][$square[1]]['def_piece'];

            // If there is an enemy (warrior)king on the square, remove the square from the array
            if (
                $piece_on_square != null
                && $game_data['pieces'][$piece_on_square]['color'] != $game_data['pieces'][$piece_id]['color']
                && in_array($game_data['pieces'][$piece_on_square]['type'], ["king", "warriorking"])
            ) {
                unset($squares_array[$index]);
            }
        }
        $squares_array = array_values($squares_array);

        return $squares_array;
    }

    private function removeIllegalCaptureMoves($piece_id, $possible_moves, $corresponding_captures, $game_data)
    {
        $result = array("possible_moves" => [], "corresponding_captures" => []);

        $move_count = count($possible_moves);

        for ($i = 0; $i < $move_count; $i++) {
            if ($this->isCapturingMoveLegal($piece_id, $corresponding_captures[$i], $game_data)) {
                $result['possible_moves'][] = $possible_moves[$i];
                $result['corresponding_captures'][] = $corresponding_captures[$i];
            }
        }

        return $result;
    }

    private function isCapturingMoveLegal($cap_id, $capture_squares, $game_data)
    {
        foreach ($capture_squares as $capture_square) {
            $piece_on_square = $game_data['squares'][$capture_square[0]][$capture_square[1]]['def_piece'];

            if ($piece_on_square != null) {
                if ($game_data['pieces'][$piece_on_square]['type'] === "ghost") {
                    return false;
                }

                if (
                    in_array($game_data['pieces'][$piece_on_square]['type'], ["king", "warriorking"])
                    && $game_data['pieces'][$piece_on_square]['color'] === $game_data['pieces'][$cap_id]['color']
                ) {
                    return false;
                }

                if (
                    $game_data['pieces'][$piece_on_square]['type'] === "nemesis"
                    && !in_array($game_data['pieces'][$cap_id]['type'], ["king", "warriorking"])
                ) {
                    return false;
                }

                if ($game_data['pieces'][$piece_on_square]['type'] === "elephant") {
                    $capturing_piece_location = array((int) $game_data['pieces'][$cap_id]['x'], (int) $game_data['pieces'][$cap_id]['y']);

                    if (abs($capturing_piece_location[0] - $capture_square[0]) > 2 || abs($capturing_piece_location[1] - $capture_square[1]) > 2) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    // Takes an array of squares and removes any which do not correspond to a currently available capture for the pawn with id $piece_id
    private function removeUnavailablePawnAttacks($piece_id, $possible_attacks, $game_data)
    {
        foreach ($possible_attacks as $index => $possible_attack) {
            $piece_on_square = $game_data['squares'][$possible_attack[0]][$possible_attack[1]]['def_piece'];

            // If the square is empty and there is no en passant available there, remove this attack from the array
            if ($piece_on_square === null) {
                $piece_adjacent = $game_data['squares'][$possible_attack[0]][$game_data['pieces'][$piece_id]['y']]['def_piece'];

                if (
                    $piece_adjacent === null
                    || $game_data['pieces'][$piece_adjacent]['color'] === $game_data['pieces'][$piece_id]['color']
                    || $game_data['pieces'][$piece_adjacent]['en_passant_vulnerable'] === "0"
                ) {
                    unset($possible_attacks[$index]);
                }
            }
        }
        $possible_attacks = array_values($possible_attacks);

        return $possible_attacks;
    }

    // Takes an array of possible moves and returns the same array but with any options removed that would leave the player's own king in check 
    private function removeSelfChecks($moving_piece_id, $friendly_king_ids, $enemy_attacks, $moves_and_captures, $game_data)
    {
        $possible_moves = $moves_and_captures['possible_moves'];
        $corresponding_captures = $moves_and_captures['corresponding_captures'];

        $move_piece_location = array((int)$game_data['pieces'][$moving_piece_id]['x'], (int)$game_data['pieces'][$moving_piece_id]['y']);
        $move_piece_type = $game_data['pieces'][$moving_piece_id]['type'];

        $enemies_attacking_move_piece_location = $enemy_attacks['attacked_squares'][$move_piece_location[0]][$move_piece_location[1]];

        $king_locations = array();
        $enemies_attacking_king_locations = array();

        $attackers_to_recheck = array();

        foreach ($friendly_king_ids as $friendly_king_id) {
            $king_locations[$friendly_king_id] = array((int)$game_data['pieces'][$friendly_king_id]['x'], (int)$game_data['pieces'][$friendly_king_id]['y']);
        }

        foreach ($king_locations as $king_id => $king_location) {
            $enemies_attacking_king_locations[$king_id]['attacking'] = $enemy_attacks['attacked_squares'][$king_location[0]][$king_location[1]];
            $enemies_attacking_king_locations[$king_id]['semi_attacking'] = $enemy_attacks['semi_attacked_squares'][$king_location[0]][$king_location[1]];

            if (count($enemies_attacking_king_locations[$king_id]['attacking']) != 0) {
                $attackers_to_recheck = array_merge($attackers_to_recheck, $enemies_attacking_king_locations[$king_id]['attacking']);
            }
        }

        // If the moving piece is a king or warriorking, it can't move onto an attacked square
        if (in_array($move_piece_type, ["king", "warriorking"])) {
            foreach ($possible_moves as $move_index => $possible_move) {
                if (
                    $possible_move != $move_piece_location
                    && count($enemy_attacks['attacked_squares'][$possible_move[0]][$possible_move[1]]) != 0
                ) {
                    unset($possible_moves[$move_index]);
                    unset($corresponding_captures[$move_index]);
                }
            }
        }

        $need_to_check = false;
        foreach ($enemies_attacking_king_locations as $king_id => $enemies) {
            if (count($enemies['attacking']) != 0 || count($enemies['semi_attacking']) != 0) {
                $need_to_check = true;
            }
        }

        if ($need_to_check) {
            foreach ($possible_moves as $move_index => $possible_move) {
                //$this->printWithJavascript("Moving piece: ".$moving_piece_id.", Possible move: ".$possible_move[0].", ".$possible_move[1]);

                $attackers_to_recheck_copy = $attackers_to_recheck;

                // Any enemy pieces which are attacking the moving piece and semi-attacking a king should be rechecked
                foreach ($enemies_attacking_king_locations as $enemies) {
                    $attackers_to_recheck_copy = array_merge($attackers_to_recheck_copy, array_intersect($enemies_attacking_move_piece_location, $enemies['semi_attacking']));
                }

                if (in_array($move_piece_type, ["pawn", "warriorking", "tiger", "elephant"])) {
                    foreach ($corresponding_captures[$move_index] as $capture_location) {
                        // If any pieces would be captured were this move made (by a piece which might not land where it captures)
                        if ($game_data['squares'][$capture_location[0]][$capture_location[1]]['def_piece'] != null) {
                            // Any enemy pieces which are attacking the capture location and semi-attacking this king MIGHT be attacking the king if this move were made
                            $enemies_attacking_capture_location = $enemy_attacks['attacked_squares'][$capture_location[0]][$capture_location[1]];

                            foreach ($enemies_attacking_king_locations as $enemies) {
                                $attackers_to_recheck_copy = array_merge($attackers_to_recheck_copy, array_intersect($enemies_attacking_capture_location, $enemies['semi_attacking']));
                            }
                        }
                    }
                }

                $attackers_to_recheck_copy = array_unique($attackers_to_recheck_copy);

                if (count($attackers_to_recheck_copy) === 0) {
                    continue;
                }

                $simulated_move = $this->simulatePossibleMove($moving_piece_id, $possible_move, $corresponding_captures[$move_index], $game_data);
                $all_piece_data_sim = $simulated_move['all_piece_data_sim'];
                $board_state_sim = $simulated_move['board_state_sim'];

                foreach ($friendly_king_ids as $friendly_king_id) {
                    $king_location_sim = array((int)$all_piece_data_sim[$friendly_king_id]['x'], (int)$all_piece_data_sim[$friendly_king_id]['y']);

                    if ($this->arePiecesAttackingSquare($attackers_to_recheck_copy, $king_location_sim, array("pieces" => $all_piece_data_sim, "squares" => $board_state_sim))) {
                        unset($possible_moves[$move_index]);
                        unset($corresponding_captures[$move_index]);
                        break;
                    }
                }
            }
        }

        return array_values($possible_moves);
    }

    private function simulatePossibleMove($piece_id, $possible_move, $capture_squares_for_this_move, $game_data)
    {
        // Starting location of the moving piece
        $piece_starting_x = $game_data['pieces'][$piece_id]['x'];
        $piece_starting_y = $game_data['pieces'][$piece_id]['y'];

        // Remove the moving piece from its starting location
        $game_data['squares'][$piece_starting_x][$piece_starting_y]['def_piece'] = null;

        // Set as captured any pieces which would be captured in this move
        foreach ($capture_squares_for_this_move as $capture_square) {
            $piece_on_cap_square = $game_data['squares'][$capture_square[0]][$capture_square[1]]['def_piece'];

            if ($piece_on_cap_square != null) {
                $game_data['squares'][$capture_square[0]][$capture_square[1]]['def_piece'] = null;
                $game_data['pieces'][$piece_on_cap_square]['captured'] = "1";

                if ($game_data['pieces'][$piece_id]['type'] === "tiger") {
                    $possible_move[0] = $piece_starting_x;
                    $possible_move[1] = $piece_starting_y;
                }
            }
        }

        // Set the updated location of the moving piece
        $game_data['squares'][$possible_move[0]][$possible_move[1]]['def_piece'] = $piece_id;
        $game_data['pieces'][$piece_id]['x'] = $possible_move[0];
        $game_data['pieces'][$piece_id]['y'] = $possible_move[1];

        return array("all_piece_data_sim" => $game_data['pieces'], "board_state_sim" => $game_data['squares']);
    }

    // Returns true if any of the pieces specified in $piece_ids are attacking $square, else false
    private function arePiecesAttackingSquare($piece_ids, $square, $game_data)
    {
        foreach ($piece_ids as $piece_id) {
            if ($game_data['pieces'][$piece_id]['captured'] === "0") {
                $attacking_move_squares = $this->getAttackingMoveSquares($piece_id, $game_data)['attacking_squares'];

                if (in_array($game_data['pieces'][$piece_id]['type'], ["pawn", "nemesispawn"])) {
                    $attacking_move_squares = $this->removeUnavailablePawnAttacks($piece_id, $attacking_move_squares, $game_data);
                }

                $corresponding_captures = $this->getCorrespondingCaptures($piece_id, $attacking_move_squares, $game_data);
                $moves_and_captures = $this->removeIllegalCaptureMoves($piece_id, $attacking_move_squares, $corresponding_captures, $game_data);
                $attacking_move_squares = $moves_and_captures['possible_moves'];
                $corresponding_captures = $moves_and_captures['corresponding_captures'];

                foreach (array_keys($attacking_move_squares) as $attacking_move_index) {
                    foreach ($corresponding_captures[$attacking_move_index] as $corresponding_capture_square) {
                        if ($corresponding_capture_square === $square) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
