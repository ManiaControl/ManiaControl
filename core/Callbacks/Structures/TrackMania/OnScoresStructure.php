<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;

use ManiaControl\Callbacks\Structures\Common\CommonScoresStructure;
use ManiaControl\Callbacks\Structures\TrackMania\Models\PlayerScore;
use ManiaControl\Callbacks\Structures\TrackMania\Models\TeamScore;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the Trackmania OnScores Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnScoresStructure extends CommonScoresStructure {

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$jsonObj = $this->getPlainJsonObject();
		
		foreach ($jsonObj->players as $jsonPlayer) {
			$playerScore = new PlayerScore();
			$playerScore->setPlayer($this->maniaControl->getPlayerManager()->getPlayer($jsonPlayer->login));
			$playerScore->setRank($jsonPlayer->rank);
			$playerScore->setRoundPoints($jsonPlayer->roundpoints);
			$playerScore->setMapPoints($jsonPlayer->mappoints);
			$playerScore->setBestRaceTime($jsonPlayer->bestracetime);
			$playerScore->setBestLapTime($jsonPlayer->bestlaptime);
			$playerScore->setStuntScore($jsonPlayer->stuntscore);

			$this->playerScores[$jsonPlayer->login] = $playerScore;
		}

	}


}