<?php

namespace ManiaControl\Callbacks\Structures\ShootMania;


use ManiaControl\Callbacks\Structures\Common\CommonScoresStructure;
use ManiaControl\Callbacks\Structures\ShootMania\Models\PlayerScore;

use ManiaControl\ManiaControl;


/**
 * Structure Class for the Shootmania OnScores Structure Callback
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnScoresStructure extends CommonScoresStructure {

	/**
	 * OnScoresStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj = $this->getPlainJsonObject();

		foreach ($jsonObj->players as $jsonPlayer) {
			$player = $this->maniaControl->getPlayerManager()->getPlayer($jsonPlayer->login);
			if($player){
				$playerScore = new PlayerScore();
				$playerScore->setPlayer($this->maniaControl->getPlayerManager()->getPlayer($jsonPlayer->login));

				$playerScore->setRank($jsonPlayer->rank);
				$playerScore->setRoundPoints($jsonPlayer->roundpoints);
				$playerScore->setMapPoints($jsonPlayer->mappoints);
				$playerScore->setMatchPoints($jsonPlayer->matchpoints);

				$this->playerScores[$jsonPlayer->login] = $playerScore;
			}
		}
	}
}