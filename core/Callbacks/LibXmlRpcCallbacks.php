<?php

namespace ManiaControl\Callbacks;

use ManiaControl\Callbacks\Structures\PlayerHitStructure;
use ManiaControl\ManiaControl;

/**
 * Class converting LibXmlRpc Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class LibXmlRpcCallbacks implements CallbackListener {
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Create a new LibXmlRpc Callbacks Instance
	 *
	 * @param ManiaControl    $maniaControl
	 * @param CallbackManager $callbackManager
	 */
	public function __construct(ManiaControl $maniaControl, CallbackManager $callbackManager) {
		$this->maniaControl = $maniaControl;

		$callbackManager->registerCallbackListener(Callbacks::SCRIPTCALLBACK, $this, 'handleScriptCallback');
	}

	/**
	 * Handle the Script Callback
	 *
	 * @param string $name
	 * @param mixed  $data
	 */
	public function handleScriptCallback($name, $data) {
		var_dump($name);
		switch ($name) {
			case 'LibXmlRpc_BeginMatch':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::BEGINMATCH, $data[0]);
				break;
			case 'LibXmlRpc_LoadingMap':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::LOADINGMAP, $data[0]);
				break;
			case 'BeginMap':
			case 'LibXmlRpc_BeginMap':
				if (!isset($data[2])) {
					$data[2] = 'False';
				}
				$this->maniaControl->getMapManager()->handleScriptBeginMap($data[1], $data[2]);
				break;
			case 'LibXmlRpc_BeginSubmatch':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::BEGINSUBMATCH, $data[0]);
				break;
			case 'LibXmlRpc_BeginTurn':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::BEGINTURN, $data[0]);
				break;
			case 'LibXmlRpc_BeginPlaying':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::BEGINPLAYING);
				break;
			case 'LibXmlRpc_EndPlaying':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDPLAYING);
				break;
			case 'LibXmlRpc_EndTurn':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDTURN, $data[0]);
				break;
			case 'LibXmlRpc_EndRound':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDROUND, $data[0]);
				break;
			case 'LibXmlRpc_EndSubmatch':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDSUBMATCH, $data[0]);
				break;
			case 'EndMap':
			case 'LibXmlRpc_EndMap':
				$this->maniaControl->getMapManager()->handleScriptEndMap();
				break;
			case 'LibXmlRpc_BeginPodium':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::BEGINPODIUM);
				break;
			case 'LibXmlRpc_EndPodium':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDPODIUM);
				break;
			case 'LibXmlRpc_UnloadingMap':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::UNLOADINGMAP, $data[0]);
				break;
			case 'LibXmlRpc_EndMatch':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDMATCH, $data[0]);
				break;
			case 'LibXmlRpc_BeginWarmUp':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::BEGINWARMUP);
				break;
			case 'LibXmlRpc_EndWarmUp':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDWARMUP);
				break;
			case 'LibXmlRpc_PlayerRanking':
				//TODO really useful? what does it have what RankingsManager not have?
				$this->triggerPlayerRanking($data[0]);
				break;
			case 'LibXmlRpc_OnStartLine':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONSTARTLINE, $data[0]);
				break;
			case 'LibXmlRpc_OnWayPoint':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONWAYPOINT, $data);
				break;
			case 'LibXmlRpc_OnGiveUp':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONGIVEUP, $data[0]);
				break;
			case 'LibXmlRpc_OnRespawn':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONRESPAWN, $data[0]);
				break;
			case 'LibXmlRpc_OnStunt':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONSTUNT, $data);
				break;
			case 'LibXmlRpc_OnShoot': //TODO testing
				$player = $this->maniaControl->getPlayerManager()->getPlayer($data[0]);
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONSHOOT, $player, $data[1]);
				break;
			case 'LibXmlRpc_OnHit':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONHIT, new PlayerHitStructure($this->maniaControl, $data));
				break;
		}
	}


	/**
	 * Trigger the Ranking of a Player
	 *
	 * @param array $data
	 */
	private function triggerPlayerRanking(array $data) {
		$player = $this->maniaControl->getPlayerManager()->getPlayer($data[1]);
		$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::PLAYERRANKING, $player, $data[0], $data[6], $data[5]);
	}
}
