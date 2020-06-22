<?php

namespace ManiaControl\Maps;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Commands\CommandListener;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\Xmlrpc\NextMapException;
use Maniaplanet\DedicatedServer\Xmlrpc\NotInListException;

/**
 * ManiaControl Map Queue Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapQueue implements CallbackListener, CommandListener, UsageInformationAble {
	use UsageInformationTrait;
	
	/*
	 * Constants
	 */
	const CB_MAPQUEUE_CHANGED = 'MapQueue.MapQueueBoxChanged';

	const SETTING_SKIP_MAP_ON_LEAVE         = 'Skip Map when the requester leaves';
	const SETTING_SKIP_MAPQUEUE_ADMIN       = 'Skip Map when admin leaves';
	const SETTING_MAPLIMIT_PLAYER           = 'Maximum maps per player in the Map-Queue (-1 = unlimited)';
	const SETTING_MAPLIMIT_ADMIN            = 'Maximum maps per admin (Admin+) in the Map-Queue (-1 = unlimited)';
	const SETTING_MESSAGE_FORMAT            = 'Message Format';
	const SETTING_BUFFERSIZE                = 'Size of the Map-Queue buffer (recently played maps)';
	const SETTING_PERMISSION_CLEAR_MAPQUEUE = 'Clear MapQueue';
	const SETTING_PERMISSION_QUEUE_BUFFER   = 'Queue maps in buffer';

	const ADMIN_COMMAND_CLEAR_MAPQUEUE = 'clearmapqueue';
	const ADMIN_COMMAND_CLEAR_JUKEBOX  = 'clearjukebox';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $queuedMaps   = array();
	private $nextMap      = null;
	private $buffer       = array();
	private $nextNoQueue  = false;

	/**
	 * Construct a new map queue instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_PODIUMSTART, $this, 'endMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'beginMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::AFTERINIT, $this, 'handleAfterInit');

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SKIP_MAP_ON_LEAVE, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SKIP_MAPQUEUE_ADMIN, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAPLIMIT_PLAYER, 1);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAPLIMIT_ADMIN, -1);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MESSAGE_FORMAT, '$fa0');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_BUFFERSIZE, 10);

		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_CLEAR_MAPQUEUE, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_QUEUE_BUFFER, AuthenticationManager::AUTH_LEVEL_ADMIN);

		// Admin Commands
		$this->maniaControl->getCommandManager()->registerCommandListener(self::ADMIN_COMMAND_CLEAR_JUKEBOX, $this, 'command_ClearMapQueue', true, 'Clears the Map-Queue.');
		$this->maniaControl->getCommandManager()->registerCommandListener(self::ADMIN_COMMAND_CLEAR_MAPQUEUE, $this, 'command_ClearMapQueue', true, 'Clears the Map-Queue.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('jb', 'jukebox', 'mapqueue'), $this, 'command_MapQueue', false, 'Shows current maps in Map-Queue.');
	}

	/**
	 * Don't queue on the next MapChange
	 */
	public function dontQueueNextMapChange() {
		$this->nextNoQueue = true;
	}

	/**
	 * Add current map to buffer on startup
	 */
	public function handleAfterInit() {
		$currentMap     = $this->maniaControl->getMapManager()->getCurrentMap();
		$this->buffer[] = $currentMap->uid;
	}

	/**
	 * Clear the map-queue via admin command clear map queue
	 *
	 * @param array  $chatCallback
	 * @param Player $admin
	 */
	public function command_ClearMapQueue(array $chatCallback, Player $admin) {
		$this->clearMapQueue($admin);
	}

	/**
	 * Clear the Map Queue
	 *
	 * @param Player $admin |null
	 */
	public function clearMapQueue(Player $admin = null) {
		if ($admin && !$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_CLEAR_MAPQUEUE)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}

		$messagePrefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MESSAGE_FORMAT);
		if ($admin && empty($this->queuedMaps)) {
			$this->maniaControl->getChat()->sendError($messagePrefix . 'There are no maps in the jukebox!', $admin);
			return;
		}

		// Destroy map - queue list
		$this->queuedMaps = array();

		if ($admin) {
			$title   = $admin->getAuthLevelName();
			$message = $this->maniaControl->getChat()->formatMessage(
				"{$messagePrefix}{$title} %s cleared the Map-Queue!",
				$admin
			);
			$this->maniaControl->getChat()->sendInformation($message);
			Logger::logInfo($message, true);
		}

		// Trigger callback
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('clear'));
	}

	/**
	 * Handle the mapqueue/jukebox command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_MapQueue(array $chatCallback, Player $player) {
		$chatCommands = explode(' ', $chatCallback[1][2]);

		if (isset($chatCommands[1])) {
			$listParam = strtolower($chatCommands[1]);
			switch ($listParam) {
				case 'list':
					$this->showMapQueue($player);
					break;
				case 'display':
					$this->showMapQueueManialink($player);
					break;
				case 'clear':
					$this->clearMapQueue($player);
					break;
				default:
					$this->showMapQueue($player);
					break;
			}
		} else {
			$this->showMapQueue($player);
		}
	}

	/**
	 * Show current mapqueue in the chat
	 *
	 * @param Player $player
	 */
	public function showMapQueue(Player $player) {
		$messagePrefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MESSAGE_FORMAT);
		if (empty($this->queuedMaps)) {
			$this->maniaControl->getChat()->sendInformation($messagePrefix . 'There are no maps in the jukebox!', $player);
			return;
		}

		$message = $messagePrefix . 'Upcoming maps in the Map-Queue:';
		$index   = 1;
		foreach ($this->queuedMaps as $queuedMap) {
			$message .= $this->maniaControl->getChat()->formatMessage(
				' %s. [%s]',
				$index,
				Formatter::stripCodes($queuedMap[1]->name)
			);
			$index++;
		}

		$this->maniaControl->getChat()->sendInformation($message, $player);
	}

	/**
	 * Show current mapqueue in a manialink
	 *
	 * @param Player $player
	 */
	public function showMapQueueManialink(Player $player) {
		$messagePrefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MESSAGE_FORMAT);
		if (empty($this->queuedMaps)) {
			$this->maniaControl->getChat()->sendInformation($messagePrefix . 'There are no Maps in the Jukebox!', $player);
			return;
		}

		$maps = array();
		foreach ($this->queuedMaps as $queuedMap) {
			array_push($maps, $queuedMap[1]);
		}

		$this->maniaControl->getMapManager()->getMapList()->showMapList($player, $maps);
	}

	/**
	 * Return the current queue buffer
	 *
	 * @return string[]
	 */
	public function getQueueBuffer() {
		return $this->buffer;
	}

	/**
	 * Add map as first map in queue (for /replay)
	 *
	 * @param Player $player
	 * @param Map    $map
	 */
	public function addFirstMapToMapQueue(Player $player, Map $map) {
		if ($map) {
			if (array_key_exists($map->uid, $this->queuedMaps)) {
				unset($this->queuedMaps[$map->uid]);
			}
			array_unshift($this->queuedMaps, array($player, $map, true));
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('add', $map));
		}
	}

	/**
	 * Adds a Map to the Map-Queue from Plugins or whatever
	 *
	 * @param $uid
	 * @return bool
	 */
	public function serverAddMapToMapQueue($uid) {
		$map = $this->maniaControl->getMapManager()->getMapByUid($uid);

		if (!$map) {
			return false;
		}

		$this->queuedMaps[$uid] = array(null, $map);

		$messagePrefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MESSAGE_FORMAT);
		$message = $this->maniaControl->getChat()->formatMessage(
			$messagePrefix . '%s has been added to the Map-Queue by the Server.',
			$map
		);
		$this->maniaControl->getChat()->sendInformation($message);

		// Trigger callback
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('add', $this->queuedMaps[$uid]));

		return true;
	}

	/**
	 * Add a Map to the map-queue
	 *
	 * @param string $login
	 * @param string $uid
	 */
	public function addMapToMapQueue($login, $uid) {
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
		if (!$player) {
			return;
		}

		// Check if the Player is muted
		if ($player->isMuted()) {
			$this->maniaControl->getChat()->sendError('Muted Players are not allowed to queue a map.', $player);
			return;
		}

		//Check if player is allowed to add (another) map
		$isModerator = $this->maniaControl->getAuthenticationManager()->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		$mapsForPlayer = 0;
		foreach ($this->queuedMaps as $queuedMap) {
			if ($queuedMap[0]->login == $login) {
				$mapsForPlayer++;
			}
		}

		if ($isModerator) {
			$maxAdmin = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAPLIMIT_ADMIN);
			if ($maxAdmin >= 0 && $mapsForPlayer >= $maxAdmin) {
				$message = $this->maniaControl->getChat()->formatMessage(
					'You already have %s map(s) in the Map-Queue!',
					$maxAdmin
				);
				$this->maniaControl->getChat()->sendError($message, $player);
				return;
			}
		} else {
			$maxPlayer = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAPLIMIT_PLAYER);
			if ($maxPlayer >= 0 && $mapsForPlayer >= $maxPlayer) {
				$message = $this->maniaControl->getChat()->formatMessage(
					'You already have %s map(s) in the Map-Queue!',
					$maxPlayer
				);
				$this->maniaControl->getChat()->sendError($message, $player);
				return;
			}
		}

		// Check if the map is already juked
		$map = null;
		if ($uid instanceof Map) {
			$map = $uid;
			$uid = $map->uid;
		}
		if (array_key_exists($uid, $this->queuedMaps)) {
			$this->maniaControl->getChat()->sendError('This map is already in the Map-Queue!', $player);
			return;
		}

		//TODO recently maps not able to add to queue-amps setting, and management
		// Check if map is in the buffer
		$messagePrefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MESSAGE_FORMAT);
		if (in_array($uid, $this->buffer)) {
			$this->maniaControl->getChat()->sendInformation($messagePrefix . 'This map has recently been played!', $player);
			if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CLEAR_MAPQUEUE)) {
				return;
			}
		}

		if (!$map) {
			$map = $this->maniaControl->getMapManager()->getMapByUid($uid);
		}

		$this->queuedMaps[$uid] = array($player, $map);

		$message = $this->maniaControl->getChat()->formatMessage(
			$messagePrefix . '%s has been added to the Map-Queue by %s.',
			$map,
			$player
		);
		$this->maniaControl->getChat()->sendInformation($message);

		// Trigger callback
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('add', $this->queuedMaps[$uid]));
	}

	/**
	 * Remove a Map from the Map queue
	 *
	 * @param Player|null $player
	 * @param string      $uid
	 */
	public function removeFromMapQueue($player, $uid) {
		if (!isset($this->queuedMaps[$uid])) {
			return;
		}
		/** @var Map $map */
		$map = $this->queuedMaps[$uid][1];
		unset($this->queuedMaps[$uid]);

		if ($player) {
			$messagePrefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MESSAGE_FORMAT);
			$message = $this->maniaControl->getChat()->formatMessage(
				$messagePrefix . '%s has been removed from the Map-Queue by %s.',
				$map,
				$player
			);
			$this->maniaControl->getChat()->sendInformation($message);
		}

		// Trigger callback
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('remove', $map));
	}

	/**
	 * Called on endmap
	 *
	 * @internal
	 */
	public function endMap() {
		// Don't queue next map (for example on skip to map)
		if ($this->nextNoQueue) {
			$this->nextNoQueue = false;
			return;
		}
		
		$messagePrefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MESSAGE_FORMAT);

		$this->nextMap = null;
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SKIP_MAP_ON_LEAVE)) {
			// Skip Map if requester has left
			foreach ($this->queuedMaps as $queuedMap) {
				/** @var Player $player */
				$player = $queuedMap[0];
				/** @var Map $map */
				$map = $queuedMap[1];

				// Check if map is added via replay vote/command
				if (isset($queuedMap[2]) && $queuedMap[2] === true) {
					break;
				}

				// Player found, so play this map (or if it got juked by the server)
				if ($player == null || $this->maniaControl->getPlayerManager()->getPlayer($player->login)) {
					break;
				}

				if (!$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SKIP_MAPQUEUE_ADMIN)) {
					//Check if the queuer is a admin
					if ($player->authLevel > 0) {
						break;
					}
				}

				// Trigger callback
				$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_MAPQUEUE_CHANGED, array('skip', $queuedMap[0]));

				// Player not found, so remove the map from the mapqueue
				array_shift($this->queuedMaps);

				$message = $this->maniaControl->getChat()->formatMessage(
					$messagePrefix . '%s will be skipped, because %s left the game!',
					$map,
					$player
				);
				$this->maniaControl->getChat()->sendInformation($message);
			}
		}

		$this->nextMap = array_shift($this->queuedMaps);

		// Check if Map Queue is empty
		if (!$this->nextMap || !isset($this->nextMap[1])) {
			return;
		}
		/** @var Player $player */
		$player = $this->nextMap[0];
		/** @var Map $map */
		$map = $this->nextMap[1];

		// Message only if it's juked by a player (not by the server)
		if ($player) {
			$message = $this->maniaControl->getChat()->formatMessage(
				$messagePrefix . 'Next map will be %s as requested by %s.',
				$map,
				$player
			);
			$this->maniaControl->getChat()->sendInformation($message);
		}

		try {
			$this->maniaControl->getClient()->setNextMapIdent($map->uid, true);
		} catch (NextMapException $e) {
		} catch (NotInListException $e) {
		}
	}

	/**
	 * Called on begin map
	 *
	 * @param Map $map
	 */
	public function beginMap(Map $map) {
		if (in_array($map->uid, $this->buffer)) {
			return;
		}

		if (count($this->buffer) >= $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_BUFFERSIZE)) {
			array_shift($this->buffer);
		}

		$this->buffer[] = $map->uid;
	}

	/**
	 * Return the next Map if the next map is a queuedmap or null if it's not
	 *
	 * @return Map
	 */
	public function getNextMap() {
		return $this->nextMap;
	}

	/**
	 * Return the first Queued Map
	 *
	 * @return array(Player $player, Map $map)
	 */
	public function getNextQueuedMap() {
		foreach ($this->queuedMaps as $queuedMap) {
			//return the first Queued Map
			return $queuedMap;
		}
		return null;
	}

	/**
	 * Return a list with the indexes of the queued maps
	 *
	 * @return array
	 */
	public function getQueuedMapsRanking() {
		$index      = 1;
		$queuedMaps = array();
		foreach ($this->queuedMaps as $queuedMap) {
			$map                   = $queuedMap[1];
			$queuedMaps[$map->uid] = $index;
			$index++;
		}
		return $queuedMaps;
	}

	/**
	 * Return the Queuer of a Map
	 *
	 * @param string $uid
	 * @return mixed
	 */
	public function getQueuer($uid) {
		return $this->queuedMaps[$uid][0];
	}

	/**
	 * Dummy Function for testing
	 */
	public function printAllMaps() {
		foreach ($this->queuedMaps as $map) {
			$map = $map[1];
			var_dump($map->name);
		}
	}
} 
