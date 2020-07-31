<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 07. Apr. 2017
 * Time: 22:01
 */

namespace ManiaControl\Callbacks\Structures\ShootMania\Models;


use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * JoustScore Model
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class JoustScore implements UsageInformationAble {
	use UsageInformationTrait;

	/** @var  \ManiaControl\Players\Player $player */
	private $player;
	private $score;

	/**
	 * Gets the Player
	 *
	 * @api
	 * @return \ManiaControl\Players\Player
	 */
	public function getPlayer() {
		return $this->player;
	}

	/**
	 * Sets the Player
	 *
	 * @api
	 * @param \ManiaControl\Players\Player $player
	 */
	public function setPlayer($player) {
		$this->player = $player;
	}

	/**
	 * Gets the Score
	 *
	 * @api
	 * @return int
	 */
	public function getScore() {
		return $this->score;
	}

	/**
	 * Sets the Score
	 *
	 * @api
	 * @param mixed int
	 */
	public function setScore($score) {
		$this->score = $score;
	}
}