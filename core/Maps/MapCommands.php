<?php

namespace ManiaControl\Maps;

use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ElementBuilder;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Utils\DataUtil;
use Maniaplanet\DedicatedServer\Xmlrpc\ChangeInProgressException;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;

/**
 * Class offering Commands to manage Maps
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapCommands implements CommandListener, ManialinkPageAnswerListener, CallbackListener {
	/*
	 * Constants
	 */
	const ACTION_OPEN_MAPLIST = 'MapCommands.OpenMapList';
	const ACTION_OPEN_XLIST   = 'MapCommands.OpenMXList';
	const ACTION_RESTART_MAP  = 'MapCommands.RestartMap';
	const ACTION_SKIP_MAP     = 'MapCommands.NextMap';
	const ACTION_SHOW_AUTHOR  = 'MapList.ShowAuthorList.';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct a new map commands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initActionsMenuButtons();

		// Admin commands
		$this->maniaControl->getCommandManager()->registerCommandListener(array('nextmap', 'next', 'skip'), $this, 'command_NextMap', true, 'Skips to the next map.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('restartmap', 'resmap', 'res'), $this, 'command_RestartMap', true, 'Restarts the current map.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('replaymap', 'replay'), $this, 'command_ReplayMap', true, 'Replays the current map (after the end of the map).');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('addmap', 'add'), $this, 'command_AddMap', true, 'Adds map from ManiaExchange.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('removemap', 'removethis'), $this, 'command_RemoveMap', true, 'Removes the current map.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('erasemap', 'erasethis'), $this, 'command_EraseMap', true, 'Erases the current map.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('shufflemaps', 'shuffle'), $this, 'command_ShuffleMaps', true, 'Shuffles the maplist.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('writemaplist', 'wml'), $this, 'command_WriteMapList', true, 'Writes the current maplist to a file.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('readmaplist', 'rml'), $this, 'command_ReadMapList', true, 'Loads a maplist into the server.');

		// Player commands
		$this->maniaControl->getCommandManager()->registerCommandListener(array('nextmap', 'next'), $this, 'command_showNextMap', false, 'Shows which map is next.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('maps', 'list'), $this, 'command_List', false, 'Shows the current maplist (or variations).');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('xmaps', 'xlist'), $this, 'command_xList', false, 'Shows maps from ManiaExchange.');

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_OPEN_XLIST, $this, 'command_xList');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_OPEN_MAPLIST, $this, 'command_List');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_RESTART_MAP, $this, 'command_RestartMap');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SKIP_MAP, $this, 'command_NextMap');
	}

	/**
	 * Add all Actions Menu Buttons
	 */
	private function initActionsMenuButtons() {
		// Menu Open xList
		$itemQuad = new Quad();
		$itemQuad->setImageUrl($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON));
		$itemQuad->setImageFocusUrl($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_MOVER));
		$itemQuad->setAction(self::ACTION_OPEN_XLIST);
		$this->maniaControl->getActionsMenu()->addPlayerMenuItem($itemQuad, 5, 'Open MX List');

		// Menu Open List
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ToolRoot);
		$itemQuad->setAction(self::ACTION_OPEN_MAPLIST);
		$this->maniaControl->getActionsMenu()->addPlayerMenuItem($itemQuad, 10, 'Open MapList');

		// Menu RestartMap
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Reload);
		$itemQuad->setAction(self::ACTION_RESTART_MAP);
		$this->maniaControl->getActionsMenu()->addAdminMenuItem($itemQuad, 10, 'Restart Map');

		// Menu NextMap
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowFastNext);
		$itemQuad->setAction(self::ACTION_SKIP_MAP);
		$this->maniaControl->getActionsMenu()->addAdminMenuItem($itemQuad, 20, 'Skip Map');
	}

	/**
	 * Show which map is the next
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_ShowNextMap(array $chatCallback, Player $player) {
		$nextQueued = $this->maniaControl->getMapManager()->getMapQueue()->getNextQueuedMap();
		if ($nextQueued) {
			/** @var Player $requester */
			$requester = $nextQueued[0];
			/** @var Map $map */
			$map = $nextQueued[1];
			$message = $this->maniaControl->getChat()->formatMessage(
				'Next Map is %s, requested by %s.',
				$map,
				$requester
			);
			$this->maniaControl->getChat()->sendInformation($message, $player);
		} else {
			$mapIndex = $this->maniaControl->getClient()->getNextMapIndex();
			$map      = $this->maniaControl->getMapManager()->getMapByIndex($mapIndex);
			$message = $this->maniaControl->getChat()->formatMessage(
				'Next Map is %s.',
				$map
			);
			$this->maniaControl->getChat()->sendInformation($message, $player);
		}
	}

	/**
	 * Handle //removemap command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_RemoveMap(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_REMOVE_MAP)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		
		$map = $this->maniaControl->getMapManager()->getCurrentMap();
		if (!$map) {
			$this->maniaControl->getChat()->sendError('Could not fetch current map to remove!', $player);
			return;
		}

		// no chat message necessary, the MapManager will do that
		$this->maniaControl->getMapManager()->removeMap($player, $map->uid);
	}

	/**
	 * Handle //erasemap command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_EraseMap(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_ERASE_MAP)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$map = $this->maniaControl->getMapManager()->getCurrentMap();
		if (!$map) {
			$this->maniaControl->getChat()->sendError('Could not fetch current map to erase!', $player);
			return;
		}

		// no chat message necessary, the MapManager will do that
		$this->maniaControl->getMapManager()->removeMap($player, $map->uid, true);
	}

	/**
	 * Handle shufflemaps command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_ShuffleMaps(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_SHUFFLE_MAPS)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		// no chat message necessary, the MapManager will do that
		$this->maniaControl->getMapManager()->shuffleMapList($player);
	}

	/**
	 * Handle addmap command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_AddMap(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chatCallback[1][2], 2);
		if (count($params) < 2) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'Usage example: %s',
				'//addmap 1234'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$this->maniaControl->getMapManager()->addMapFromMx($params[1], $player->login);
	}

	/**
	 * Handle /nextmap Command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_NextMap(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_SKIP_MAP)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$this->maniaControl->getMapManager()->getMapActions()->skipMap();

		$message = $this->maniaControl->getChat()->formatMessage(
			'%s skipped the current Map!',
			$player
		);
		$this->maniaControl->getChat()->sendSuccess($message);
		Logger::logInfo($message, true);
	}

	/**
	 * Handle restartmap command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_RestartMap(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_RESTART_MAP)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		try {
			$this->maniaControl->getClient()->restartMap();
		} catch (ChangeInProgressException $e) {
			$this->maniaControl->getChat()->sendException($e, $player);
			return;
		}

		$message = $this->maniaControl->getChat()->formatMessage(
			'%s restarted the current Map!',
			$player
		);
		$this->maniaControl->getChat()->sendSuccess($message);
		Logger::logInfo($message, true);
	}

	/**
	 * Handle replaymap command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_ReplayMap(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_RESTART_MAP)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$this->maniaControl->getMapManager()->getMapQueue()->addFirstMapToMapQueue($player, $this->maniaControl->getMapManager()->getCurrentMap());
		
		$message = $this->maniaControl->getChat()->formatMessage(
			'%s queued the current Map to replay!',
			$player
		);
		$this->maniaControl->getChat()->sendSuccess($message);
		Logger::logInfo($message, true);
	}

	/**
	 * Handle writemaplist command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_WriteMapList(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$chatCommand = explode(' ', $chat[1][2]);
		$maplist = 'MatchSettings' . DIRECTORY_SEPARATOR;
		if (isset($chatCommand[1])) {
			if (!DataUtil::endsWith($chatCommand[1], '.txt')) {
				$maplist .= $chatCommand[1] . '.txt';
			} else {
				$maplist .= $chatCommand[1];
			}
		} else {
			$maplist .= 'maplist.txt';
		}

		try {
			$this->maniaControl->getClient()->saveMatchSettings($maplist);
		} catch (FaultException $e) {
			$this->maniaControl->getChat()->sendException($e, $player);
			$message = $this->maniaControl->getChat()->formatMessage(
				'Cannot write maplist %s!',
				$maplist
			);
			$this->maniaControl->getChat()->sendError($message, $player);
			return;
		}

		$message = $this->maniaControl->getChat()->formatMessage(
			'Maplist %s written.',
			$maplist
		);
		$this->maniaControl->getChat()->sendSuccess($message, $player);
		Logger::logInfo($message, true);
	}

	/**
	 * Handle readmaplist command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_ReadMapList(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$chatCommand = explode(' ', $chat[1][2]);
		$maplist = 'MatchSettings' . DIRECTORY_SEPARATOR;
		if (isset($chatCommand[1])) {
			if (!DataUtil::endsWith($chatCommand[1], '.txt')) {
				$maplist .= $chatCommand[1] . '.txt';
			} else {
				$maplist .= $chatCommand[1];
			}
		} else {
			$maplist .= 'maplist.txt';
		}

		try {
			$this->maniaControl->getClient()->loadMatchSettings($maplist);
		} catch (FaultException $e) {
			$this->maniaControl->getChat()->sendException($e, $player);
			$message = $this->maniaControl->getChat()->formatMessage(
				'Cannot load maplist %s!',
				$maplist
			);
			$this->maniaControl->getChat()->sendError($message, $player);
			return;
		}

		$message = $this->maniaControl->getChat()->formatMessage(
			'Maplist %s loaded.',
			$maplist
		);
		$this->maniaControl->getMapManager()->restructureMapList();
		$this->maniaControl->getChat()->sendSuccess($message, $player);
		Logger::logInfo($message, true);
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];

		$login  = $callback[1][1];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);

		if (strstr($actionId, self::ACTION_SHOW_AUTHOR)) {
			$login = str_replace(self::ACTION_SHOW_AUTHOR, '', $actionId);
			$this->showMapListAuthor($login, $player);
		}
	}

	/**
	 * Show the Player a List of Maps from the given Author
	 *
	 * @param string $author
	 * @param Player $player
	 */
	private function showMapListAuthor($author, Player $player) {
		$maps    = $this->maniaControl->getMapManager()->getMaps();
		$mapList = array();
		/** @var Map $map */
		foreach ($maps as $map) {
			if ($map->authorLogin == $author) {
				array_push($mapList, $map);
			}
		}

		if (empty($mapList)) {
			$this->maniaControl->getChat()->sendError('There are no maps to show!', $player->login);
			return;
		}

		$this->maniaControl->getMapManager()->getMapList()->showMapList($player, $mapList);
	}

	/**
	 * Handle /maps command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_List(array $chatCallback, Player $player) {
		$chatCommands = explode(' ', $chatCallback[1][2]);
		$this->maniaControl->getMapManager()->getMapList()->playerCloseWidget($player);

		if (isset($chatCommands[1])) {
			$listParam = strtolower($chatCommands[1]);
			switch ($listParam) {
				case 'best':
					$this->showMapListKarma(true, $player);
					break;
				case 'worst':
					$this->showMapListKarma(false, $player);
					break;
				case 'newest':
					$this->showMapListDate(true, $player);
					break;
				case 'oldest':
					$this->showMapListDate(false, $player);
					break;
				case 'author':
					if (isset($chatCommands[2])) {
						$this->showMaplistAuthor($chatCommands[2], $player);
					} else {
						$this->maniaControl->getChat()->sendError('Missing Author Login!', $player);
					}
					break;
				default:
					$this->maniaControl->getMapManager()->getMapList()->showMapList($player);
					break;
			}
		} else {
			$this->maniaControl->getMapManager()->getMapList()->showMapList($player);
		}
	}

	/**
	 * Show a Karma based MapList
	 *
	 * @param bool   $best
	 * @param Player $player
	 */
	private function showMapListKarma($best, Player $player) {
		/** @var \MCTeam\KarmaPlugin $karmaPlugin */
		$karmaPlugin = $this->maniaControl->getPluginManager()->getPlugin(ElementBuilder::DEFAULT_KARMA_PLUGIN);

		if ($karmaPlugin) {
			$displayMxKarma = $this->maniaControl->getSettingManager()->getSettingValue($karmaPlugin, $karmaPlugin::SETTING_WIDGET_DISPLAY_MX);
			//Sort by Mx Karma in Maplist
			if ($displayMxKarma) { //TODO

				//Sort by Local Karma in Maplist
			} else {

			}

			$maps    = $this->maniaControl->getMapManager()->getMaps();
			$mapList = array();
			foreach ($maps as $map) {
				if ($map instanceof Map) {
					if ($this->maniaControl->getSettingManager()->getSettingValue($karmaPlugin, $karmaPlugin::SETTING_NEWKARMA) === true) {
						$karma      = $karmaPlugin->getMapKarma($map);
						$map->karma = round($karma * 100.);
					} else {
						$votes = $karmaPlugin->getMapVotes($map);
						$min   = 0;
						$plus  = 0;
						foreach ($votes as $vote) {
							if (isset($vote->vote)) {
								if ($vote->vote !== 0.5) {
									if ($vote->vote < 0.5) {
										$min = $min + $vote->count;
									} else {
										$plus = $plus + $vote->count;
									}
								}
							}
						}
						$map->karma = $plus - $min;
					}
					$mapList[] = $map;
				}
			}

			usort($mapList, function (Map $mapA, Map $mapB) {
				return ($mapA->karma - $mapB->karma);
			});
			if ($best) {
				$mapList = array_reverse($mapList);
			}

			$this->maniaControl->getMapManager()->getMapList()->showMapList($player, $mapList);
		} else {
			$this->maniaControl->getChat()->sendError('KarmaPlugin is not enabled!', $player->login);
		}
	}

	/**
	 * Show a Date based MapList
	 *
	 * @param bool   $newest
	 * @param Player $player
	 */
	private function showMapListDate($newest, Player $player) {
		$maps = $this->maniaControl->getMapManager()->getMaps();

		usort($maps, function (Map $mapA, Map $mapB) {
			return ($mapA->index - $mapB->index);
		});
		if ($newest) {
			$maps = array_reverse($maps);
		}

		$this->maniaControl->getMapManager()->getMapList()->showMapList($player, $maps);
	}

	/**
	 * Handle ManiaExchange list command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_xList(array $chatCallback, Player $player) {
		$this->maniaControl->getMapManager()->getMXList()->showListCommand($chatCallback, $player);
	}
}
