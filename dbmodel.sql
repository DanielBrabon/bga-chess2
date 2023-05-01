
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- ChessSequel implementation : © <Daniel Brabon> <dev.d8dms@simplelogin.co>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `player` ADD `player_army` VARCHAR(16) NOT NULL DEFAULT 'classic';
ALTER TABLE `player` ADD `player_stones` TINYINT(1) UNSIGNED NOT NULL DEFAULT 3;
ALTER TABLE `player` ADD `player_bid` TINYINT(1) UNSIGNED DEFAULT NULL;
ALTER TABLE `player` ADD `player_king_move_available` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `pieces` (
  `piece_id` TINYINT(1) UNSIGNED NOT NULL,
  `color` CHAR(6) NOT NULL,
  `type` VARCHAR(15) NOT NULL,
  `x` TINYINT(1) UNSIGNED,
  `y` TINYINT(1) UNSIGNED,
  `last_x` TINYINT(1) UNSIGNED DEFAULT NULL,
  `last_y` TINYINT(1) UNSIGNED DEFAULT NULL,
  `moves_made` INT(10) UNSIGNED DEFAULT 0,
  `state` TINYINT(1) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`piece_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `legal_moves` (
  `move_id` INT(10) UNSIGNED NOT NULL,
  `piece_id` TINYINT(1) UNSIGNED NOT NULL,
  `x` TINYINT(1) UNSIGNED NOT NULL,
  `y` TINYINT(1) UNSIGNED NOT NULL,
  PRIMARY KEY (`move_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `capture_squares` (
  `cs_id` INT(16) UNSIGNED AUTO_INCREMENT,
  `move_id` INT(10) UNSIGNED NOT NULL,
  `x` TINYINT(1) UNSIGNED NOT NULL,
  `y` TINYINT(1) UNSIGNED NOT NULL,
  PRIMARY KEY (`cs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `capture_queue` (
  `cq_id` INT(10) UNSIGNED NOT NULL,
  `x` TINYINT(1) UNSIGNED NOT NULL,
  `y` TINYINT(1) UNSIGNED NOT NULL,
  PRIMARY KEY (`cq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pos_history` (
  `ph_id` INT(10) UNSIGNED AUTO_INCREMENT,
  `pos_string` VARCHAR(115) NOT NULL,
  PRIMARY KEY (`ph_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
