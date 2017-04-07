<?php

namespace ManiaControl\Callbacks;

use ManiaControl\Callbacks\Models\RecordCallback;
use ManiaControl\Callbacks\Structures\EliteBeginTurnStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnActionCustomEventStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnActionEvent;
use ManiaControl\Callbacks\Structures\ShootMania\OnArmorEmptyStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnCaptureStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnCommandStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnDefaultEventStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnHitStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnNearMissStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnPlayerObjectStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnPlayerRequestActionChange;
use ManiaControl\Callbacks\Structures\ShootMania\OnPlayerRequestRespawnStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnPlayerTriggersSectorStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnScoresStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnShootStructure;
use ManiaControl\Callbacks\Structures\ShootMania\OnShotDenyStructure;
use ManiaControl\ManiaControl;

/**
 * Class handling and parsing ShootMania Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
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
		if (!$this->maniaControl->getCallbackManager()->callbackListeningExists($name)) {
			//return; //Leave that disabled while testing/implementing Callbacks
		}
		switch ($name) {
			//MP4 New Callbacks
			case Callbacks::SM_SCORES:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_SCORES, new OnScoresStructure($this->maniaControl, $data));
				break;
			//TODO UI Properties Later
			case Callbacks::SM_ONEVENTDEFAULT:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONEVENTDEFAULT, new OnDefaultEventStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONSHOOT:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONSHOOT, new OnShootStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONHIT:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONHIT, new OnHitStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONNEARMISS:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONNEARMISS, new OnNearMissStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONARMOREMPTY:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONARMOREMPTY, new OnArmorEmptyStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONCAPTURE:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONCAPTURE, new OnCaptureStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONSHOTDENY:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONSHOTDENY, new OnShotDenyStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONCOMMAND:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONCOMMAND, new OnCommandStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONPLAYERREQUESTRESPAWN:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONPLAYERREQUESTRESPAWN, new OnPlayerRequestRespawnStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONACTIONCUSTOMEVENT:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONACTIONCUSTOMEVENT, new OnActionCustomEventStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONACTIONEVENT:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONACTIONEVENT, new OnActionEvent($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONPLAYERTOUCHESOBJECT:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONPLAYERTOUCHESOBJECT, new OnPlayerObjectStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONPLAYERTRIGGERSSECTOR:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONPLAYERTRIGGERSSECTOR, new OnPlayerTriggersSectorStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONPLAYERTHROWSOBJECT:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONPLAYERTHROWSOBJECT, new OnPlayerObjectStructure($this->maniaControl, $data));
				break;
			case Callbacks::SM_ONPLAYERREQUESTACTIONCHANGE:
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SM_ONPLAYERREQUESTACTIONCHANGE, new OnPlayerRequestActionChange($this->maniaControl, $data));
				break;
			case Callbacks::SM_COMBO_PAUSE:
				//TODO
				break;
			case Callbacks::SM_ELITE_STARTTURN:
				//TODO
				break;
			case Callbacks::SM_ELITE_ENDTURN:
				//TODO
				break;
			case Callbacks::SM_JOUST_ONRELOAD:
				//TODO
				break;
			case Callbacks::SM_JOUST_SELECTEDPLAYERS:
				//TODO
				break;
			case Callbacks::SM_JOUST_ROUNDRESULT:
				//TODO
				break;
			case Callbacks::SM_ROYAL_POINTS:
				//TODO
				break;
			case Callbacks::SM_ROYAL_PLAYERSPAWN:
				//TODO
				break;
			case Callbacks::SM_ROYAL_ROUNDWINNER:
				//TODO
				break;

			//Old Callbacks
			case 'LibXmlRpc_Rankings':
				$this->maniaControl->getServer()->getRankingManager()->updateRankings($data[0]);
				break;
			case 'LibAFK_IsAFK':
				$this->triggerAfkStatus($data[0]);
				break;
			case 'WarmUp_Status':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::WARMUPSTATUS, $data[0]);
				break;
			case 'Elite_BeginTurn':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ELITE_ONBEGINTURN, new EliteBeginTurnStructure($this->maniaControl, $data));
				break;
			case 'Elite_EndTurn':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ELITE_ONENDTURN, $data[0]);
				break;
			case 'Joust_SelectedPlayers':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::JOUST_SELECTEDPLAYERS, $data);
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
		$checkpointCallback->time = (int) $data[1];

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
		$finishCallback->time = (int) $data[1];

		$this->maniaControl->getCallbackManager()->triggerCallback($finishCallback);
	}
}
