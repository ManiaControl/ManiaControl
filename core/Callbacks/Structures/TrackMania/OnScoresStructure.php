<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;

use ManiaControl\Callbacks\Structures\Common\CommonScoresStructure;
use ManiaControl\Callbacks\Structures\TrackMania\Models\PlayerScore;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the Trackmania OnScores Structure Callback
 *
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
			if ($player) {
				$playerScore = new PlayerScore();
				$playerScore->setPlayer($this->maniaControl->getPlayerManager()->getPlayer($jsonPlayer->login));
				$playerScore->setRank($jsonPlayer->rank);
				$playerScore->setMapPoints($jsonPlayer->mappoints);
				$playerScore->setMatchPoints($jsonPlayer->matchpoints);
				$playerScore->setRoundPoints($jsonPlayer->roundpoints);
				$playerScore->setBestRaceTime($jsonPlayer->bestracetime);
				$playerScore->setBestLapTime($jsonPlayer->bestlaptime);
				$playerScore->setStuntScore($jsonPlayer->stuntsscore);
				$playerScore->setBestRaceCheckpoints($jsonPlayer->bestracecheckpoints);
				$playerScore->setBestLapCheckpoints($jsonPlayer->bestlapcheckpoints);
				
				// removed in TM2020
				if (property_exists($jsonPlayer, 'bestracerespawns')) {
					$playerScore->setBestRaceRespawns($jsonPlayer->bestracerespawns);
				}
				// removed in TM2020
				if (property_exists($jsonPlayer, 'bestlaprespawns')) {
					$playerScore->setBestLapRespawns($jsonPlayer->bestlaprespawns);
				}

				//New attributes in 2.5.0
				if (property_exists($jsonPlayer, 'prevracetime')) {
					$playerScore->setPrevRaceTime($jsonPlayer->prevracetime);
				}

				if (property_exists($jsonPlayer, 'prevracerespawns')) {
					$playerScore->setPrevRaceRespawns($jsonPlayer->prevracerespawns);
				}

				if (property_exists($jsonPlayer, 'prevracecheckpoints')) {
					$playerScore->setPrevRaceCheckpoints($jsonPlayer->prevracecheckpoints);
				}

				if (property_exists($jsonPlayer, 'prevstuntsscore')) {
					$playerScore->setPrevStuntsScore($jsonPlayer->prevstuntsscore);
				}

				$this->playerScores[$jsonPlayer->login] = $playerScore;
			}
		}
	}
}