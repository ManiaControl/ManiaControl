<?php

namespace ManiaControl\Callbacks\Models;

use ManiaControl\Players\Player;

/**
 * Base Model Class for Callbacks
 *
 * @deprecated
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class BaseCallback {
	/*
	 * Public Properties
	 */
	public $name = null;
	public $rawCallback = null;
	public $isLegacyCallback = null;

	public $pid = null;
	public $login = null;
	/** @var Player $player */
	public $player = null;

	/**
	 * Set the corresponding Player
	 *
	 * @param Player $player
	 */
	public function setPlayer(Player $player) {
		$this->pid    = $player->pid;
		$this->login  = $player->login;
		$this->player = $player;
	}
}
