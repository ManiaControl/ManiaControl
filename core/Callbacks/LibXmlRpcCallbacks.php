<?php

namespace ManiaControl\Callbacks;

use ManiaControl\Callbacks\Structures\ArmorEmptyStructure;
use ManiaControl\Callbacks\Structures\CaptureStructure;
use ManiaControl\Callbacks\Structures\Common\BaseTimeStructure;
use ManiaControl\Callbacks\Structures\Common\StatusCallbackStructure;
use ManiaControl\Callbacks\Structures\ManiaPlanet\LoadingUnloadingMapStructure;
use ManiaControl\Callbacks\Structures\ManiaPlanet\ModeUseTeamsStructure;
use ManiaControl\Callbacks\Structures\ManiaPlanet\StartEndStructure;
use ManiaControl\Callbacks\Structures\ManiaPlanet\StartServerStructure;
use ManiaControl\Callbacks\Structures\NearMissStructure;
use ManiaControl\Callbacks\Structures\PlayerHitStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\AllApiVersionsStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\ApiVersionStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\CallbackHelpStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\CallbackListStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\DocumentationStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\MethodHelpStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\MethodListStructure;
use ManiaControl\ManiaControl;

/**
 * Class converting LibXmlRpc Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
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
		//Internal Callbacks always triggered
		switch($name){
			case 'Maniaplanet.StartMap_Start': //Use the MapManager Callback
				//No use for this Implementation right now (as the MapManager Callback should be used
				break;
			case 'Maniaplanet.StartMap_End': //Use the MapManager Callback
				$jsonData = json_decode($data[0]);
				$this->maniaControl->getMapManager()->handleScriptBeginMap($jsonData->map->uid, $jsonData->restarted);
				break;
			case 'Maniaplanet.EndMap_Start':
				//no need for this implementation, callback handled by Map Manager
				break;
			case 'Maniaplanet.EndMap_End': //Use the MapManager Callback
				$this->maniaControl->getMapManager()->handleScriptEndMap(); //Verify if better here or at EndMap_End
				break;
		}

		if (!$this->maniaControl->getCallbackManager()->callbackListeningExists($name)) {
			return; //Leave that disabled while testing/implementing Callbacks
		}
		switch ($name) {
			//New callbacks
			case Callbacks::XMLRPC_CALLBACKSLIST:
			case Callbacks::XMLRPC_ENABLEDCALLBACKS:
			case Callbacks::XMLRPC_DISABLEDCALLBACKS:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new CallbackListStructure($this->maniaControl, $data));
				break;
			case Callbacks::XMLRPC_CALLBACKHELP:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new CallbackHelpStructure($this->maniaControl, $data));
				break;
			case Callbacks::XMLRPC_APIVERSION:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new ApiVersionStructure($this->maniaControl, $data));
				break;
			case Callbacks::XMLRPC_ALLAPIVERSIONS:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new AllApiVersionsStructure($this->maniaControl, $data));
				break;
			case Callbacks::XMLRPC_DOCUMENTATION:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new DocumentationStructure($this->maniaControl, $data));
				break;
			case Callbacks::XMLRPC_METHODSLIST:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new MethodListStructure($this->maniaControl, $data));
				break;
			case Callbacks::XMLRPC_METHODHELP:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new MethodHelpStructure($this->maniaControl, $data));
				break;
			case Callbacks::MP_STARTMATCHEND:
			case Callbacks::MP_STARTMATCHSTART:
			case Callbacks::MP_STARTROUNDSTART:
			case Callbacks::MP_STARTROUNDEND:
			case Callbacks::MP_STARTTURNSTART:
			case Callbacks::MP_STARTTURNEND:
			case Callbacks::MP_STARTPLAYLOOP:
			case Callbacks::MP_ENDPLAYLOOP:
			case Callbacks::MP_ENDTURNSTART:
			case Callbacks::MP_ENDTURNEND:
			case Callbacks::MP_ENDROUNDSTART:
			case Callbacks::MP_ENDROUNDEND:
			case Callbacks::MP_ENDMATCHSTART:
			case Callbacks::MP_ENDMATCHEND:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new StartEndStructure($this->maniaControl, $data));
				break;
			case Callbacks::MP_STARTSERVERSTART:
			case Callbacks::MP_STARTSERVEREND:
			case Callbacks::MP_ENDSERVERSTART:
			case Callbacks::MP_ENDSERVEREND:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new StartServerStructure($this->maniaControl, $data));
				break;
			case Callbacks::MP_LOADINGMAPEND:
			case Callbacks::MP_UNLOADINGMAPSTART:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new LoadingUnloadingMapStructure($this->maniaControl, $data));
				break;
			case Callbacks::MP_LOADINGMAPSTART:
			case Callbacks::MP_UNLOADINGMAPEND:
			case Callbacks::MP_PODIUMSTART:
			case Callbacks::MP_PODIUMEND:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new BaseTimeStructure($this->maniaControl, $data));
				break;
			case Callbacks::MP_WARMUP_START:
			case Callbacks::MP_WARMUP_END:
				$this->maniaControl->getCallbackManager()->triggerCallback($name);
				break;
			case Callbacks::MP_WARMUP_STATUS:
			case Callbacks::MP_PAUSE_STATUS:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new StatusCallbackStructure($this->maniaControl, $data));
				break;
			case Callbacks::MP_USES_TEAMMODE:
				$this->maniaControl->getCallbackManager()->triggerCallback($name, new ModeUseTeamsStructure($this->maniaControl, $data));
				break;
		}
	}
	
}
