<?php

namespace ManiaControl\Maps;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;

/**
 * Jukebox Class
 *
 * @author steeffeen & kremsy
 */
class Jukebox implements CallbackListener {
	/**
	 * Constants
	 */
	const CB_JUKEBOX_CHANGED =  'Jukebox.JukeBoxChanged';

	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $jukedMaps = array();

	/**
	 * Create a new server jukebox
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this,'beginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ENDMAP, $this,'endMap');

	}

	/**
	 * Adds a Map to the jukebox
	 * @param $login
	 * @param $uid
	 */
	public function addMapToJukebox($login, $uid){

		//Check if the map is already juked
		if(array_key_exists($uid, $this->jukedMaps)){
			//TODO message map already juked
			return;
		}

		//TODO recently maps not able to add to jukebox setting, and management

		//$this->jukedMapsUid[$uid] = array($login, $this->maniaControl->mapManager->getMapByUid($uid));
		$this->jukedMaps[$uid] = array($login, $this->maniaControl->mapManager->getMapByUid($uid));

		//TODO Message

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_JUKEBOX_CHANGED, array('add', $this->jukedMaps[$uid]));

		$this->printAllMaps();
	}

	/**
	 * Revmoes a Map from the jukebox
	 * @param $login
	 * @param $uid
	 */
	public function removeFromJukebox($login, $uid){
		//unset($this->jukedMapsUid[$uid]);
		unset($this->jukedMaps[$uid]);


	}

	public function beginMap(){


	}

	public function endMap(){

		//TODO setting admin no skip
		//TODO setting skip map if requester left

		//Skip Map if requester has left
		for($i = 0; $i < count($this->jukedMaps); $i++){
			$jukedMap = reset($this->jukedMaps);

			//found player, so play this map
			if($this->maniaControl->playerManager->getPlayer($jukedMap[0]) != null){
				break;
			}

			// Trigger callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_JUKEBOX_CHANGED, array('skip', $jukedMap[0]));

			//Player not found, so remove the map from the jukebox
			array_shift($this->jukedMaps);

			//TODO Message, report skip
		}

		$nextMap = array_shift($this->jukedMaps);

		//Check if Jukebox is empty
		if($nextMap == null)
			return;

		$nextMap = $nextMap[1];

		//Set pointer back to last map
		//end($this->jukedMaps);

		$success = $this->maniaControl->client->query('ChooseNextMap', $nextMap->fileName);
		if (!$success) {
			trigger_error('[' . $this->maniaControl->client->getErrorCode() . '] ChooseNextMap - ' . $this->maniaControl->client->getErrorCode(), E_USER_WARNING);
			return;
		}

	}

	
	public function printAllMaps(){
		foreach($this->jukedMaps as $map){
			$map = $map[1];
			var_dump($map->name);
		}
	}

} 