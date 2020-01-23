<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\Callbacks\Structures\ShootMania\Models\JoustScore;
use ManiaControl\ManiaControl;


/**
 * Structure Class for the OnJoustRoundResults Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnJoustRoundResultsStructure extends BaseStructure {

	/** @var \ManiaControl\Callbacks\Structures\ShootMania\Models\JoustScore $playerScores */
	private $playerScores = array();

	/**
	 * OnJoustRoundResultsStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj = $this->getPlainJsonObject();

		foreach ($jsonObj->players as $jsonPlayer) {
			$playerScore = new JoustScore();
			$playerScore->setPlayer($this->maniaControl->getPlayerManager()->getPlayer($jsonPlayer->login));
			$playerScore->setScore($jsonPlayer->score);

			$this->playerScores[$jsonPlayer->login] = $playerScore;
		}
	}

	/**
	 * Get the Player Scores
	 *
	 * @api
	 * @return \ManiaControl\Callbacks\Structures\ShootMania\Models\JoustScore
	 */
	public function getPlayerScores() {
		return $this->playerScores;
	}
}