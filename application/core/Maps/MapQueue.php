<?php

namespace ManiaControl\Maps;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * MapQueue Class
 *
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapQueue implements CallbackListener, CommandListener {
	/*
	 * Constants
	 */
	const CB_MAPQUEUE_CHANGED = 'MapQueue.MapQueueBoxChanged';

	const SETTING_SKIP_MAP_ON_LEAVE         = 'Skip Map when the requester leaves';
	const SETTING_SKIP_MAPQUEUE_ADMIN       = 'Skip Map when admin leaves';
	const SETTING_MAPLIMIT_PLAYER           = 'Maximum maps per player in the Map-Queue (-1 = unlimited)';
	const SETTING_MAPLIMIT_ADMIN            = 'Maximum maps per admin (Admin+) in the Map-Queue (-1 = unlimited)';
	const SETTING_BUFFERSIZE                = 'Size of the Map-Queue buffer (recently played maps)';
	const SETTING_PERMISSION_CLEAR_MAPQUEUE = 'Clear Mapqueue';
	const SETTING_PERMISSION_QUEUE_BUFFER   = 'Queue maps in buffer';

	const ADMIN_COMMAND_CLEAR_MAPQUEUE = 'clearmapqueue';
	const ADMIN_COMMAND_CLEAR_JUKEBOX  = 'clearjukebox';
	
	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $queuedMaps = array();
	private $nextMap = null;
	private $buffer = array();

	/**
	 * Create a new server MapQueue
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_ENDMAP, $this, 'endMap');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'beginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_AFTERINIT, $this, 'handleAfterInit');

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SKIP_MAP_ON_LEAVE, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SKIP_MAPQUEUE_ADMIN, false);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAPLIMIT_PLAYER, 1);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAPLIMIT_ADMIN, -1);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_BUFFERSIZE, 10);

		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CLEAR_MAPQUEUE, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_QUEUE_BUFFER, AuthenticationManager::AUTH_LEVEL_ADMIN);

		//Register Admin Commands
		$this->maniaControl->commandManager->registerCommandListener(self::ADMIN_COMMAND_CLEAR_JUKEBOX, $this, 'command_ClearMapQueue', true);
		$this->maniaControl->commandManager->registerCommandListener(self::ADMIN_COMMAND_CLEAR_MAPQUEUE, $this, 'command_ClearMapQueue', true);
		$this->maniaControl->commandManager->registerCommandListener(array('jb', 'jukebox', 'mapqueue'), $this, 'command_MapQueue');
	}

	/**
	 * Adds current map to buffer on startup
	 */
	public function handleAfterInit() {
		$currentMap = $this->maniaControl->mapManager->getCurrentMap();
		$this->buffer[] = $currentMap->uid;
	}

	/**
	 * Clears the map-queue via admin command clearmap queue
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $admin
	 * @internal param \ManiaControl\Players\Player $player
	 */
	public function command_ClearMapQueue(array $chat, Player $admin) {
		$this->clearMapQueue($admin);
	}

	/**
	 * Handles the mapqueue/jukebox command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_MapQueue(array $chat, Player $player) {
		$chatCommands = explode(' ', $chat[1][2]);

		if(isset($chatCommands[1])) {
			if($chatCommands[1] == ' ' || $chatCommands[1] == 'list') {
				$this->showMapQueue($player);
			} elseif($chatCommands[1] == 'display') {
				$this->showMapQueueManialink($player);
			} elseif($chatCommands[1] == 'clear') {
				$this->clearMapQueue($player);
			}
		} else {
			$this->showMapQueue($player);
		}
	}

	/**
	 * Shows current mapqueue in the chat
	 *
	 * @param $player
	 */
	public function showMapQueue($player) {
		if(count($this->queuedMaps) == 0) {
			$this->maniaControl->chat->sendError('$fa0There are no maps in the jukebox!', $player->login);
			return;
		}

		$message = '$fa0Upcoming maps in the Map-Queue:';
		$i = 1;
		foreach($this->queuedMaps as $queuedMap) {
			$message .= ' $<$fff'.$i.'$>. [$<$fff'.Formatter::stripCodes($queuedMap[1]->name).'$>]';
			$i++;
		}

		$this->maniaControl->chat->sendInformation($message, $player->login);
	}

	/**
	 * Shows current mapqueue in a manialink
	 *
	 * @param $player
	 */
	public function showMapQueueManialink($player) {
		if(count($this->queuedMaps) == 0) {
			$this->maniaControl->chat->sendError('$fa0There are no maps in the jukebox!', $player->login);
			return;
		}

		$maps = array();
		foreach($this->queuedMaps as $queuedMap) {
			$maps[] = $queuedMap[1];
		}

		$this->maniaControl->mapManager->mapList->showMapList($player, $maps);
	}

	/**
	 * Returns the current queue buffer
	 *
	 * @return array
	 */
	public function getQueueBuffer() {
		return $this->buffer;
	}

	/**
	 * Clears the Map Queue
	 *
	 * @param $admin
	 */
	public function clearMapQueue($admin) {
		if (!$this->maniaControl->authenticationManager->checkPermission($admin, self::SETTING_PERMISSION_CLEAR_MAPQUEUE)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}

		if(count($this->queuedMaps) == 0) {
			$this->maniaControl->chat->sendError('$fa0There are no maps in the jukebox!', $admin->login);
			return;
		}

		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);

		//Destroy map - queue list
		$this->queuedMaps = array();

		$this->maniaControl->chat->sendInformation('$fa0'. $title . ' $<$fff' . $admin->nickname . '$> cleared the Queued-Map list!');
		$this->maniaControl->log($title . ' ' . Formatter::stripCodes($admin->nickname) . ' cleared the Queued-Map list!');

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('clear'));
	}

	/**
	 * Adds map as first map in queue (for /replay)
	 *
	 * @param $player
	 * @param $map
	 */
	public function addFirstMapToMapQueue($player, $map) {
		if ($map) {
			if (array_key_exists($map->uid, $this->queuedMaps)) {
				unset($this->queuedMaps[$map->uid]);
			}

			array_unshift($this->queuedMaps, array($player, $map));
		}
	}

	/**
	 * Adds a Map to the map-queue
	 *
	 * @param $login
	 * @param $uid
	 */
	public function addMapToMapQueue($login, $uid) {
		$player = $this->maniaControl->playerManager->getPlayer($login);

		//Check if player is allowed to add (another) map
		$admin = false;
		if($this->maniaControl->authenticationManager->checkRight($player, 2) ||
		   $this->maniaControl->authenticationManager->checkRight($player, 3) ||
		   $this->maniaControl->authenticationManager->checkRight($player, 4)) {
			$admin = true;
		}

		$mapsForPlayer = 0;
		foreach($this->queuedMaps as $queuedMap) {
			if($queuedMap[0]->login == $login) {
				$mapsForPlayer++;
			}
		}

		$maxPlayer = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAPLIMIT_PLAYER);
		$maxAdmin  = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAPLIMIT_ADMIN);

		if($admin && $maxAdmin != -1) {
			if($mapsForPlayer == $maxAdmin) {
				$this->maniaControl->chat->sendError('You already have $<$fff'.$maxAdmin.'$> map(s) in the Map-Queue!', $login);
				return;
			}
		} elseif(!$admin && $maxPlayer != -1) {
			if($mapsForPlayer == $maxPlayer) {
				$this->maniaControl->chat->sendError('You already have $<$fff'.$maxPlayer.'$> map(s) in the Map-Queue!', $login);
				return;
			}
		}

		//Check if the map is already juked
		if (array_key_exists($uid, $this->queuedMaps)) {
			$this->maniaControl->chat->sendError('That map is already in the Map-Queue!', $login);
			return;
		}

		//TODO recently maps not able to add to queue-amps setting, and management
		// Check if map is in the buffer
		if(in_array($uid, $this->buffer)) {
			$this->maniaControl->chat->sendError('That map has recently been played!', $login);
			if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CLEAR_MAPQUEUE)) {
				return;
			}
		}

		$map = $this->maniaControl->mapManager->getMapByUid($uid);

		$this->queuedMaps[$uid] = array($player, $map);

		$this->maniaControl->chat->sendInformation('$fa0$<$fff' . $map->name . '$> has been added to the Map-Queue by $<$fff' . $player->nickname . '$>.');

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('add', $this->queuedMaps[$uid]));
	}

	/**
	 * Revmoes a Map from the Map queue
	 *
	 * @param \ManiaControl\Players\Player $player
	 * @param                              $uid
	 * @internal param $login
	 */
	public function removeFromMapQueue(Player $player, $uid) {
		if (!isset($this->queuedMaps[$uid])) {
			return;
		}
		$map = $this->queuedMaps[$uid][1];
		unset($this->queuedMaps[$uid]);

		$this->maniaControl->chat->sendInformation('$fa0$<$fff' . $map->name . '$> is removed from the Map-Queue by $<$fff' . $player->nickname . '$>.');

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('remove', $map));
	}


	/**
	 * Called on endmap
	 *
	 * @param Map $map
	 */
	public function endMap(Map $map) {
		$this->nextMap = null;
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_SKIP_MAP_ON_LEAVE) == TRUE) {

			//Skip Map if requester has left
			foreach($this->queuedMaps as $queuedMap) {
				$player = $queuedMap[0];

				//found player, so play this map
				if ($this->maniaControl->playerManager->getPlayer($player->login)) {
					break;
				}

				if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_SKIP_MAPQUEUE_ADMIN) == FALSE) {
					//Check if the queuer is a admin
					if ($player->authLevel > 0) {
						break;
					}
				}

				// Trigger callback
				$this->maniaControl->callbackManager->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('skip', $queuedMap[0]));

				//Player not found, so remove the map from the mapqueue
				array_shift($this->queuedMaps);

				$this->maniaControl->chat->sendInformation('$fa0$<$fff' . $queuedMap[0]->name .'$> is skipped because $<' . $player->nickname . '$> left the game!');
			}
		}

		$this->nextMap = array_shift($this->queuedMaps);

		//Check if Map Queue is empty
		if (!$this->nextMap || !isset($this->nextMap[1])) {
			return;
		}
		$map = $this->nextMap[1];
		$this->maniaControl->chat->sendInformation('$fa0Next map will be $<$fff' . $map->name .'$> as requested by $<' . $this->nextMap[0]->nickname . '$>.');

		$this->maniaControl->client->chooseNextMap($map->fileName);
	}

	/**
	 * Called on begin map
	 *
	 * @param Map $map
	 */
	public function beginMap(Map $map) {
		if(in_array($map->uid, $this->buffer)) {
			return;
		}

		if(count($this->buffer) >= $this->maniaControl->settingManager->getSetting($this, self::SETTING_BUFFERSIZE)) {
			array_shift($this->buffer);
		}

		$this->buffer[] = $map->uid;
	}

	/**
	 * Returns the next Map if the next map is a queuedmap or null if it's not
	 *
	 * @return null
	 */
	public function getNextMap() {
		return $this->nextMap;
	}


	/**
	 * Returns the first Queued Map
	 *
	 * @return array(Player $player, Map $map)
	 */
	public function getNextQueuedMap() {
		foreach($this->queuedMaps as $queuedMap) {
			//return the first Queued Map
			return $queuedMap;
		}
		return null;
	}

	/**
	 * Returns a list with the indexes of the queued maps
	 *
	 * @return array
	 */
	public function getQueuedMapsRanking() {
		$i          = 1;
		$queuedMaps = array();
		foreach($this->queuedMaps as $queuedMap) {
			$map                   = $queuedMap[1];
			$queuedMaps[$map->uid] = $i;
			$i++;
		}
		return $queuedMaps;
	}

	/**
	 * Returns the Queuer of a Map
	 *
	 * @param $uid
	 * @return mixed
	 */
	public function getQueuer($uid) {
		return $this->queuedMaps[$uid][0];
	}

	/**
	 * Dummy Function for testing
	 */
	public function printAllMaps() {
		foreach($this->queuedMaps as $map) {
			$map = $map[1];
			var_dump($map->name);
		}
	}
} 
