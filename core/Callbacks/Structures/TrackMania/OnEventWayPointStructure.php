<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\ManiaControl;
use ManiaControl\Utils\Formatter;

/**
 * Structure Class for the Default Event Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnEventWayPointStructure extends BaseStructure {
	private $time;
	private $player;
	private $racetime;
	private $laptime;
	private $stuntsscore;
	private $checkpointinrace;
	private $checkpointinlap;
	private $isendrace;
	private $isendlap;
	private $blockid;
	private $speed;
	private $distance;

	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->time = $this->getPlainJsonObject()->time;
		$this->player = $this->maniaControl->getPlayerManager()->getPlayer($this->getPlainJsonObject()->login);
		$this->racetime = (int) $this->getPlainJsonObject()->racetime;
		$this->laptime  = (int) $this->getPlainJsonObject()->laptime;
		$this->stuntsscore = $this->getPlainJsonObject()->stuntsscore;
		$this->checkpointinrace = (int) $this->getPlainJsonObject()->checkpointinrace;
		$this->checkpointinlap = (int) $this->getPlainJsonObject()->checkpointinlap;
		$this->isendrace = $this->getPlainJsonObject()->isendrace;
		$this->isendlap = $this->getPlainJsonObject()->isendlap;
		$this->blockid  = $this->getPlainJsonObject()->blockid;
		$this->speed    = $this->getPlainJsonObject()->speed;
		$this->distance = $this->getPlainJsonObject()->distance;
		
		// Build callback
		$wayPointCallback              = new RecordCallback();
		$wayPointCallback->rawCallback = $data;
		$wayPointCallback->setPlayer($this->player);
		$wayPointCallback->blockId       = $this->blockid;
		$wayPointCallback->time          = $this->racetime;
		$wayPointCallback->checkpoint    = $this->checkpointinrace;
		$wayPointCallback->isEndRace     = Formatter::parseBoolean($this->isendrace);
		$wayPointCallback->lapTime       = $this->laptime;
		$wayPointCallback->lapCheckpoint = $this->checkpointinlap;
		$wayPointCallback->lap           = 0;
		$wayPointCallback->isEndLap      = Formatter::parseBoolean($this->isendlap);
		if ($wayPointCallback->checkpoint > 0) {
			$currentMap            = $this->maniaControl->getMapManager()->getCurrentMap();
			$wayPointCallback->lap += $wayPointCallback->checkpoint / $currentMap->nbCheckpoints;
		}
		if ($wayPointCallback->isEndRace) {
			$wayPointCallback->name = $wayPointCallback::FINISH;
		} else if ($wayPointCallback->isEndLap) {
			$wayPointCallback->name = $wayPointCallback::LAPFINISH;
		} else {
			$wayPointCallback->name = $wayPointCallback::CHECKPOINT;
		}
		$this->maniaControl->getCallbackManager()->triggerCallback($wayPointCallback);
	}

	/**
	 * Returns Server time when the event occured
	 *
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	* < player who triggered the action
	*
	* @return \ManiaControl\Players\Player
	*/
	public function getPlayer() {
		return $this->player;
	}
	
}