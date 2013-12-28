<?php

namespace ManiaControl\Maps;
use ManiaControl\ManiaControl;

/**
 * Jukebox Class
 *
 * @author steeffeen & kremsy
 */
class Jukebox {
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


	}

	public function addMapToJukebox($uid){

		//Check if the map is already juked
		if(array_key_exists($uid, $this->jukedMaps)){
			//TODO message map already juked
			return;
		}

		//TODO recently maps not able to add to jukebox setting, and management

		$this->jukedMaps[$uid] = $this->maniaControl->mapManager->getMapByUid($uid);

		//TODO Message

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_JUKEBOX_CHANGED, array('add', $this->jukedMaps[$uid]));
	}
} 