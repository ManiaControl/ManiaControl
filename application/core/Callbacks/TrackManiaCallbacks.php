<?php

namespace ManiaControl\Callbacks;

use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\ManiaControl;

/**
 * Class handling and parsing TrackMania Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TrackManiaCallbacks implements CallbackListener {
	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new TrackMania Callbacks Instance
	 *
	 * @param ManiaControl    $maniaControl
	 * @param CallbackManager $callbackManager
	 */
	public function __construct(ManiaControl $maniaControl, CallbackManager $callbackManager) {
		$this->maniaControl = $maniaControl;

		// Register for callbacks
		$callbackManager->registerCallbackListener(Callbacks::ONWAYPOINT, $this, 'handleOnWayPointCallback');
	}

	/**
	 * Handle OnWayPoint Callback
	 *
	 * @param array $callback
	 */
	public function handleOnWayPointCallback(array $callback) {
		$login  = $callback[0];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player) {
			return;
		}

		// Build callback
		$checkpointCallback              = new RecordCallback();
		$checkpointCallback->rawCallback = $callback;
		$checkpointCallback->setPlayer($player);
		$checkpointCallback->blockId       = $callback[1];
		$checkpointCallback->time          = (int)$callback[2];
		$checkpointCallback->checkpoint    = (int)$callback[3];
		$checkpointCallback->isEndRace     = (bool)$callback[4];
		$checkpointCallback->lapTime       = (int)$callback[5];
		$checkpointCallback->lapCheckpoint = (int)$callback[6];
		$checkpointCallback->isEndLap      = (bool)$callback[7];

		if ($checkpointCallback->isEndRace) {
			$checkpointCallback->name = $checkpointCallback::FINISH;
		} else if ($checkpointCallback->isEndLap) {
			$checkpointCallback->name = $checkpointCallback::LAPFINISH;
		} else {
			$checkpointCallback->name = $checkpointCallback::CHECKPOINT;
		}

		$this->maniaControl->callbackManager->triggerCallback($checkpointCallback);
	}
}
