<?php

namespace ManiaControl\Callbacks;

use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\ManiaControl;

/**
 * Class handling and parsing ShootMania Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ShootManiaCallbacks implements CallbackListener {
	/*
	 * Constants
	 */
	const CB_TIMEATTACK_ONSTART      = 'TimeAttack_OnStart';
	const CB_TIMEATTACK_ONRESTART    = 'TimeAttack_OnRestart';
	const CB_TIMEATTACK_ONCHECKPOINT = 'TimeAttack_OnCheckpoint';
	const CB_TIMEATTACK_ONFINISH     = 'TimeAttack_OnFinish';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Create a new ShootMania Callbacks Instance
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
		switch ($name) {
			case 'LibXmlRpc_Rankings':
				$this->maniaControl->getServer()->getRankingManager()->updateRankings($data[0]);
				break;
			case 'LibXmlRpc_Scores':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SCORES, $data);
				break;
			case 'LibAFK_IsAFK':
				$this->triggerAfkStatus($data[0]);
				break;
			case 'WarmUp_Status':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::WARMUPSTATUS, $data[0]);
				break;
			case self::CB_TIMEATTACK_ONCHECKPOINT:
				$this->handleTimeAttackOnCheckpoint($name, $data);
				break;
			case self::CB_TIMEATTACK_ONFINISH:
				$this->handleTimeAttackOnFinish($name, $data);
				break;
		}
	}

	/**
	 * Triggers the AFK Status of an Player
	 *
	 * @param string $login
	 */
	private function triggerAfkStatus($login) {
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
		$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::AFKSTATUS, $player);
	}

	/**
	 * Handle TimeAttack OnCheckpoint Callback
	 *
	 * @param string $name
	 * @param array  $data
	 */
	public function handleTimeAttackOnCheckpoint($name, array $data) {
		$login  = $data[0];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
		if (!$player) {
			return;
		}

		// Trigger checkpoint callback
		$checkpointCallback              = new RecordCallback();
		$checkpointCallback->rawCallback = array($name, $data);
		$checkpointCallback->name        = $checkpointCallback::CHECKPOINT;
		$checkpointCallback->setPlayer($player);
		$checkpointCallback->time = (int)$data[1];

		$this->maniaControl->getCallbackManager()->triggerCallback($checkpointCallback);
	}

	/**
	 * Handle TimeAttack OnFinish Callback
	 *
	 * @param string $name
	 * @param array  $data
	 */
	public function handleTimeAttackOnFinish($name, array $data) {
		$login  = $data[0];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
		if (!$player) {
			return;
		}

		// Trigger finish callback
		$finishCallback              = new RecordCallback();
		$finishCallback->rawCallback = array($name, $data);
		$finishCallback->name        = $finishCallback::FINISH;
		$finishCallback->setPlayer($player);
		$finishCallback->time = (int)$data[1];

		$this->maniaControl->getCallbackManager()->triggerCallback($finishCallback);
	}
}
