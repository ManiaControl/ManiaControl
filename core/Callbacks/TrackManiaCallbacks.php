<?php

namespace ManiaControl\Callbacks;

use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\Callbacks\Structures\TrackMania\OnDefaultEventStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnEventStartLineStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnCommandStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnScoresStructure;
use ManiaControl\Callbacks\Structures\TrackMania\OnEventWayPointStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Utils\Formatter;

/**
 * Class handling and parsing TrackMania Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TrackManiaCallbacks implements CallbackListener {
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;


	/**
	 * Create a new TrackMania Callbacks Instance
	 *
	 * @param ManiaControl    $maniaControl
	 * @param CallbackManager $callbackManager
	 */
	public function __construct(ManiaControl $maniaControl, CallbackManager $callbackManager) {
		$this->maniaControl = $maniaControl;

		// Register for script callbacks
		$callbackManager->registerCallbackListener(Callbacks::SCRIPTCALLBACK, $this, 'handleScriptCallbacks');
	}

	/**
	 * Handle Script Callbacks
	 *
	 * @param string $name
	 * @param mixed  $data
	 */
	public function handleScriptCallbacks($name, $data) {
		if (!$this->maniaControl->getCallbackManager()->callbackListeningExists($name)) {
			//return; //Leave that disabled while testing/implementing Callbacks
		}
		switch ($name) {
			//MP4 New Callbacks
			case Callbacks::TM_SCORES:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::TM_SCORES, new OnScoresStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONEVENTDEFAULT:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::TM_ONEVENTDEFAULT, new OnDefaultEventStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONEVENTSTARTLINE:
			$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::TM_ONEVENTSTARTLINE, new OnEventStartLineStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONCOMMAND:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::TM_ONCOMMAND, new OnCommandStructure($this->maniaControl, $data));
				break;
			case Callbacks::TM_ONPLAYERADDED:
				break;
			case Callbacks::TM_ONPLAYERREMOVED:
				break;
			case Callbacks::TM_ONWAYPOINT:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::TM_ONWAYPOINT, new OnEventWayPointStructure($this->maniaControl, $data));
				break;
			case Callbacks:: TM_ONGIVEUP:
				break;
			case Callbacks::TM_ONRESPAWN:
				break;
			case Callbacks::TM_ONSTUNT:
				break;
			case Callbacks::TM_ONSTARTCOUNTDOWN:
				break;
			case Callbacks::TM_WARMUPSTART:
				break;
			case Callbacks::TM_WARMUPSTARTROUND:
				break;
			case Callbacks::TM_WARMUPENDROUND:
				break;
			case Callbacks::TM_WARMUPEND:
				break;
		}
	}


	/**
	 * Handle OnWayPoint Callback
	 *
	 * @param array $callback
	 */
	public function handleOnWayPointCallback(array $callback) {
		$login  = $callback[0];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
		if (!$player) {
			return;
		}

		// Build callback
		$wayPointCallback              = new RecordCallback();
		$wayPointCallback->rawCallback = $callback;
		$wayPointCallback->setPlayer($player);
		$wayPointCallback->blockId       = $callback[1];
		$wayPointCallback->time          = (int) $callback[2];
		$wayPointCallback->checkpoint    = (int) $callback[3];
		$wayPointCallback->isEndRace     = Formatter::parseBoolean($callback[4]);
		$wayPointCallback->lapTime       = (int) $callback[5];
		$wayPointCallback->lapCheckpoint = (int) $callback[6];
		$wayPointCallback->lap           = 0;
		$wayPointCallback->isEndLap      = Formatter::parseBoolean($callback[7]);

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
	 * Handle Hard-Coded Player Checkpoint Callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerCheckpointCallback(array $callback) {
		$data   = $callback[1];
		$login  = $data[1];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
		if (!$player) {
			return;
		}

		// Build checkpoint callback
		$checkpointCallback                   = new RecordCallback();
		$checkpointCallback->isLegacyCallback = true;
		$checkpointCallback->rawCallback      = $callback;
		$checkpointCallback->setPlayer($player);
		$checkpointCallback->time          = (int) $data[2];
		$checkpointCallback->lap           = (int) $data[3];
		$checkpointCallback->checkpoint    = (int) $data[4];
		$checkpointCallback->lapCheckpoint = $checkpointCallback->checkpoint;

		if ($checkpointCallback->lap > 0) {
			$currentMap                        = $this->maniaControl->getMapManager()->getCurrentMap();
			$checkpointCallback->lapCheckpoint -= $checkpointCallback->lap * $currentMap->nbCheckpoints;
		}

		if ($checkpointCallback->lapCheckpoint === 0) {
			$checkpointCallback->name = $checkpointCallback::LAPFINISH;
		} else {
			$checkpointCallback->name = $checkpointCallback::CHECKPOINT;
		}

		$this->maniaControl->getCallbackManager()->triggerCallback($checkpointCallback);
	}

	/**
	 * Handle Hard-Coded Player Finish Callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerFinishCallback(array $callback) {
		$data   = $callback[1];
		$login  = $data[1];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
		if (!$player) {
			return;
		}

		// Build finish callback
		$finishCallback                   = new RecordCallback();
		$finishCallback->name             = $finishCallback::FINISH;
		$finishCallback->isLegacyCallback = true;
		$finishCallback->rawCallback      = $callback;
		$finishCallback->setPlayer($player);
		$finishCallback->time = (int) $data[2];

		$this->maniaControl->getCallbackManager()->triggerCallback($finishCallback);
	}
}
