<?php

namespace ManiaControl\Maps;

require_once __DIR__ . '/Map.php';
require_once __DIR__ . '/MapCommands.php';

use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;

// TODO: xlist command

/**
 * Manager for maps
 *
 * @author kremsy & steeffeen
 */
class MapManager implements CallbackListener {
	/**
	 * Constants
	 */
	const TABLE_MAPS = 'mc_maps';
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $mapCommands = null;
	private $mapList = array();

	/**
	 * Construct map manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Init database tables
		$this->initTables();
		
		// Create map commands instance
		$this->mapCommands = new MapCommands($maniaControl);
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'handleBeginMap');
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_MAPS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`uid` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`name` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
				`authorLogin` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`fileName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`environment` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`mapType` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`index`),
				UNIQUE KEY `uid` (`uid`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Map data' AUTO_INCREMENT=1;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		return $result;
	}

	/**
	 * Save map to the database
	 *
	 * @param \ManiaControl\Maps\Map $map        	
	 * @return boolean
	 */
	private function saveMap(Map &$map) {
		$mysqli = $this->maniaControl->database->mysqli;
		$mapQuery = "INSERT INTO `" . self::TABLE_MAPS . "` (
				`uid`,
				`name`,
				`authorLogin`,
				`fileName`,
				`environment`,
				`mapType`
				) VALUES (
				?, ?, ?, ?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`index` = LAST_INSERT_ID(`index`);";
		$mapStatement = $mysqli->prepare($mapQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$mapStatement->bind_param('ssssss', $map->uid, $map->name, $map->authorLogin, $map->fileName, $map->environment, $map->mapType);
		$mapStatement->execute();
		if ($mapStatement->error) {
			trigger_error($mapStatement->error);
			$mapStatement->close();
			return false;
		}
		$map->index = $mapStatement->insert_id;
		$mapStatement->close();
		return true;
	}

	/**
	 * Add a map to the MapList
	 *
	 * @param \ManiaControl\Maps\Map $map        	
	 * @return bool
	 */
	private function addMap(Map $map) {
		$this->saveMap($map);
		$this->mapList[$map->uid] = $map;
		return true;
	}

	/**
	 * Fetch current map
	 *
	 * @return \ManiaControl\Maps\Map
	 */
	public function getCurrentMap() {
		if (!$this->maniaControl->client->query('GetCurrentMapInfo')) {
			trigger_error("Couldn't fetch map info. " . $this->maniaControl->getClientErrorText());
			return null;
		}
		$rpcMap = $this->maniaControl->client->getResponse();
		$map = new Map($this->maniaControl, $rpcMap);
		$this->addMap($map);
		return $map;
	}

	/**
	 * Handle OnInit callback
	 *
	 * @param array $callback        	
	 */
	public function handleOnInit(array $callback) {
		$map = $this->getCurrentMap();
		if (!$map) {
			return;
		}
		$this->addMap($map);
	}

	/**
	 * Handle BeginMap callback
	 *
	 * @param array $callback        	
	 */
	public function handleBeginMap(array $callback) {
		$map = $this->getCurrentMap();
		if (!$map) {
			return;
		}
		$this->addMap($map);
	}
} 
