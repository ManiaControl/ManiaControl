<?php

namespace ManiaControl\Maps;

require_once __DIR__ . '/Map.php';

use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;

/**
 * Manager for maps
 *
 * @author kremsy & steeffeen
 */
class MapManager implements CallbackListener {
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $mapList = array();

	/**
	 * Construct map manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	
	// TODO: database init
	// TODO: erasemap from server
	// TODO: implement of a method which are called by xlist command and results maplists from maniaexcahnge (or extra class for it)
	// TODO: admin add from maniaexchange, would handle it here
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		$this->initTables();
		
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'handleBeginMap');
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		// TODO: Initialize database table
		return true;
	}

	/**
	 * Add a map to the MapList
	 *
	 * @param \ManiaControl\Maps\Map $map        	
	 * @return bool
	 */
	private function addMap(Map $map) {
		if (!$map) {
			return false;
		}
		// TODO: Save map in database
		$this->mapList[$map->uid] = $map;
		return true;
	}

	/**
	 * Handle BeginMap callback
	 *
	 * @param array $callback        	
	 */
	public function handleBeginMap(array $callback) {
		$rpcMap = $this->maniaControl->server->getCurrentMap();
		$map = new Map($this->maniaControl, $rpcMap);
		$this->addMap($map);
	}
} 