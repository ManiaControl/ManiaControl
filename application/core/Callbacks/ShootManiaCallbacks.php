<?php

namespace ManiaControl\Callbacks;

use ManiaControl\ManiaControl;

/**
 * Class handling and parsing ShootMania Callbacks
 *
 * @author steeffeen
 */
class ShootManiaCallbacks implements CallbackListener {
	/*
	 * Constants
	 */
	const SCB_LIBXMLRPC_BEGINMAP = 'LibXmlRpc_BeginMap';
	const SCB_LIBXMLRPC_ENDMAP = 'LibXmlRpc_EndMap';
	const SCB_OLD_BEGINMAP = 'BeginMap';
	const SCB_OLD_ENDMAP = 'EndMap';
	const SCB_TIMEATTACK_ONSTART = 'TimeAttack_OnStart';
	const SCB_TIMEATTACK_ONRESTART = 'TimeAttack_OnRestart';
	const SCB_TIMEATTACK_ONCHECKPOINT = 'TimeAttack_OnCheckpoint';
	const SCB_TIMEATTACK_ONFINISH = 'TimeAttack_OnFinish';
	
	/*
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new ShootMania Callbacks Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl, CallbackManager $callbackManager) {
		$this->maniaControl = $maniaControl;
		
		// Register for script callbacks
		$callbackManager->registerScriptCallbackListener(self::SCB_LIBXMLRPC_BEGINMAP, $this, 'callback_LibXmlRpc_BeginMap');
		$callbackManager->registerScriptCallbackListener(self::SCB_OLD_BEGINMAP, $this, 'callback_LibXmlRpc_BeginMap');
		$callbackManager->registerScriptCallbackListener(self::SCB_LIBXMLRPC_ENDMAP, $this, 'callback_LibXmlRpc_EndMap');
		$callbackManager->registerScriptCallbackListener(self::SCB_OLD_ENDMAP, $this, 'callback_LibXmlRpc_EndMap');
		
		$callbackManager->registerScriptCallbackListener(self::SCB_TIMEATTACK_ONCHECKPOINT, $this, 'callback_TimeAttack_OnCheckpoint');
		$callbackManager->registerScriptCallbackListener(self::SCB_TIMEATTACK_ONFINISH, $this, 'callback_TimeAttack_OnFinish');
	}

	/**
	 * Handle LibXmlRpc BeginMap Script Callback
	 *
	 * @param array $callback
	 */
	public function callback_LibXmlRpc_BeginMap(array $callback) {
		$this->maniaControl->mapManager->handleScriptBeginMap($callback);
	}

	/**
	 * Handle LibXmlRpc EndMap Script Callback
	 *
	 * @param array $callback
	 */
	public function callback_LibXmlRpc_EndMap(array $callback) {
		$this->maniaControl->mapManager->handleScriptEndMap($callback);
	}

	/**
	 * Handle TimeAttack OnCheckpoint Script Callback
	 *
	 * @param array $callback
	 */
	public function callback_TimeAttack_OnCheckpoint(array $callback) {
		$login = $callback[1][0];
		$time = (int) $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player || $time <= 0) {
			return;
		}
		// Trigger trackmania player checkpoint callback
		$checkpointCallback = array($player->pid, $player->login, $time, 0, 0);
		$this->maniaControl->callbackManager->triggerCallback(CallbackManager::CB_TM_PLAYERCHECKPOINT, 
				array(CallbackManager::CB_TM_PLAYERCHECKPOINT, $checkpointCallback));
	}

	/**
	 * Handle TimeAttack OnFinish Script Callback
	 *
	 * @param array $callback
	 */
	public function callback_TimeAttack_OnFinish(array $callback) {
		$login = $callback[1][0];
		$time = (int) $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player || $time <= 0) {
			return;
		}
		// Trigger trackmania player finish callback
		$finishCallback = array($player->pid, $player->login, $time);
		$this->maniaControl->callbackManager->triggerCallback(CallbackManager::CB_TM_PLAYERFINISH, 
				array(CallbackManager::CB_TM_PLAYERFINISH, $finishCallback));
	}
}
