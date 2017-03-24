<?php

namespace ManiaControl\Callbacks;

use ManiaControl\Callbacks\Structures\ArmorEmptyStructure;
use ManiaControl\Callbacks\Structures\CaptureStructure;
use ManiaControl\Callbacks\Structures\ManiaPlanet\StartServerStructure;
use ManiaControl\Callbacks\Structures\NearMissStructure;
use ManiaControl\Callbacks\Structures\PlayerHitStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\CallbacksListStructure;
use ManiaControl\ManiaControl;

/**
 * Class converting LibXmlRpc Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
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
		//var_dump($data);
		switch ($name) {
			//New callbacks
			case 'XmlRpc.CallbacksList':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::XMLRPC_CALLBACKSLIST, new CallbacksListStructure($this->maniaControl, $data));
				break;
			case 'Maniaplanet.StartServer_Start':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::MP_STARTSERVERSTART, new StartServerStructure($this->maniaControl, $data));
				break;
			case 'Maniaplanet.StartServer_End':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::MP_STARTSERVEREND, new StartServerStructure($this->maniaControl, $data));
				break;
			//OLD Callbacks
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
			case 'LibXmlRpc_BeginTurnStop':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::BEGINTURNSTOP, $data[0]);
				break;
			case 'LibXmlRpc_BeginRound':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::BEGINROUND, $data[0]);
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
			case 'LibXmlRpc_EndTurnStop':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDTURNSTOP, $data[0]);
				break;
			case 'LibXmlRpc_EndRound':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDROUND, $data[0]);
				break;
			case 'LibXmlRpc_EndRoundStop':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ENDROUNDSTOP, $data[0]);
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
			case 'LibXmlRpc_Rankings':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::RANKINGS, $data[0]);
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
			case 'LibXmlRpc_OnShoot':
				$player = $this->maniaControl->getPlayerManager()->getPlayer($data[0]);
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONSHOOT, $player, $data[1]);
				break;
			case 'LibXmlRpc_OnHit':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONHIT, new PlayerHitStructure($this->maniaControl, $data));
				break;
			case 'LibXmlRpc_OnNearMiss':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONNEARMISS, new NearMissStructure($this->maniaControl, $data));
				break;
			case 'LibXmlRpc_OnArmorEmpty':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONARMOREMPTY, new ArmorEmptyStructure($this->maniaControl, $data));
				break;
			case 'LibXmlRpc_OnCapture':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONCAPTURE, new CaptureStructure($this->maniaControl, $data));
				break;
			case 'LibXmlRpc_OnPlayerRequestRespawn':
				$player = $this->maniaControl->getPlayerManager()->getPlayer($data[0]);
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::ONPLAYERREQUESTRESPAWN, $player);
				break;
			case 'LibXmlRpc_Scores':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SCORES, $data);
				break;
			case 'LibXmlRpc_ScoresReady':
				$this->maniaControl->getCallbackManager()->triggerCallback(Callbacks::SCORESREADY, $data);
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
