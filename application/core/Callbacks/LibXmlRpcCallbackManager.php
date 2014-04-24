<?php

namespace ManiaControl\Callbacks;


use ManiaControl\ManiaControl;

class LibXmlRpcCallbackManager implements CallbackListener {
	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new LibXmlRpc Callbacks Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl, CallbackManager $callbackManager) {
		$this->maniaControl = $maniaControl;
		$callbackManager->registerCallbackListener(Callbacks::SCRIPTCALLBACK, $this, 'handleScriptCallbacks');
	}

	/**
	 * Handle Script Callbacks
	 *
	 * @param $name
	 * @param $data
	 */
	public function handleScriptCallbacks($name, $data) {
		switch($name) {
			case 'LibXmlRpc_BeginMatch':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::BEGINMATCH, $data[0]);
				break;
			case 'LibXmlRpc_LoadingMap':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::LOADINGMAP, $data[0]);
				break;
			case 'BeginMap':
			case 'LibXmlRpc_BeginMap':
				$this->maniaControl->mapManager->handleScriptBeginMap($data[0]);
				break;
			case 'LibXmlRpc_BeginSubmatch':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::BEGINSUBMATCH, $data[0]);
				break;
			case 'LibXmlRpc_BeginTurn':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::BEGINTURN, $data[0]);
				break;
			case 'LibXmlRpc_EndTurn':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::ENDTURN, $data[0]);
				break;
			case 'LibXmlRpc_EndRound':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::ENDROUND, $data[0]);
				break;
			case 'LibXmlRpc_EndSubmatch':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::ENDSUBMATCH, $data[0]);
				break;
			case 'EndMap':
			case 'LibXmlRpc_EndMap':
				$this->maniaControl->mapManager->handleScriptEndMap($data[0]);
				break;
			case 'LibXmlRpc_EndMatch':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::ENDMATCH, $data[0]);
				break;
			case 'LibXmlRpc_BeginWarmUp':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::BEGINWARMUP);
				break;
			case 'LibXmlRpc_EndWarmUp':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::ENDWARMUP);
				break;
			case 'LibXmlRpc_PlayerRanking': //TODO really useful? what does it have what RankingsManager not have?
				$this->triggerPlayerRanking($data[0]);
				break;
		}
	}

	/**
	 * Triggers the Ranking of a Player
	 *
	 * @param $data
	 */
	private function triggerPlayerRanking($data) {
		$player = $this->maniaControl->playerManager->getPlayer($data[1]);
		$this->maniaControl->callbackManager->triggerCallback(Callbacks::PLAYERRANKING, $player, $data[0], $data[6], $data[5]);
	}
}