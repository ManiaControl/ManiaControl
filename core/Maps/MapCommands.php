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
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\Xmlrpc\ChangeInProgressException;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;
use MCTeam\KarmaPlugin;
use MCTeam\LocalRecordsPlugin;

/**
 * Class offering Commands to manage Maps
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2018 ManiaControl Team
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
		$this->maniaControl->getCommandManager()->registerCommandListener('nextmap', $this, 'command_showNextMap', false, 'Shows which map is next.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('maps', 'list'), $this, 'command_List', false, 'Shows the current maplist (with various options).');
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
			$this->maniaControl->getChat()->sendInformation("Next Map is $<{$map->name}$> from $<{$map->authorNick}$> requested by $<{$requester->nickname}$>.", $player);
		} else {
			$mapIndex = $this->maniaControl->getClient()->getNextMapIndex();
			$maps     = $this->maniaControl->getMapManager()->getMaps();
			$map      = $maps[$mapIndex];
			$this->maniaControl->getChat()->sendInformation("Next Map is $<{$map->name}$> from $<{$map->authorNick}$>.", $player);
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
		// Get map
		$map = $this->maniaControl->getMapManager()->getCurrentMap();
		if (!$map) {
			$this->maniaControl->getChat()->sendError("Couldn't remove map.", $player);
			return;
		}

		// Remove map
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
		// Get map
		$map = $this->maniaControl->getMapManager()->getCurrentMap();
		if (!$map) {
			$this->maniaControl->getChat()->sendError("Couldn't erase map.", $player);
			return;
		}

		// Erase map
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

		// Shuffles the maps
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
			$this->maniaControl->getChat()->sendUsageInfo('Usage example: //addmap 1234', $player);
			return;
		}

		// add Map from Mania Exchange
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

		$message = $player->getEscapedNickname() . ' skipped the current Map!';
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
		$message = $player->getEscapedNickname() . ' restarted the current Map!';
		$this->maniaControl->getChat()->sendSuccess($message);
		Logger::logInfo($message, true);

		try {
			$this->maniaControl->getClient()->restartMap();
		} catch (ChangeInProgressException $e) {
		}
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
		$message = $player->getEscapedNickname() . ' replays the current Map!';
		$this->maniaControl->getChat()->sendSuccess($message);
		Logger::logInfo($message, true);

		$this->maniaControl->getMapManager()->getMapQueue()->addFirstMapToMapQueue($player, $this->maniaControl->getMapManager()->getCurrentMap());
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
		if (isset($chatCommand[1])) {
			if (strstr($chatCommand[1], '.txt')) {
				$maplist = $chatCommand[1];
			} else {
				$maplist = $chatCommand[1] . '.txt';
			}
		} else {
			$maplist = 'maplist.txt';
		}

		$maplist = 'MatchSettings' . DIRECTORY_SEPARATOR . $maplist;
		try {
			$this->maniaControl->getClient()->saveMatchSettings($maplist);

			$message = 'Maplist $<$fff' . $maplist . '$> written.';
			$this->maniaControl->getChat()->sendSuccess($message, $player);
			Logger::logInfo($message, true);
		} catch (FaultException $e) {
			$this->maniaControl->getChat()->sendError('Cannot write maplist $<$fff' . $maplist . '$>!', $player);
		}
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
		if (isset($chatCommand[1])) {
			if (strstr($chatCommand[1], '.txt')) {
				$maplist = $chatCommand[1];
			} else {
				$maplist = $chatCommand[1] . '.txt';
			}
		} else {
			$maplist = 'maplist.txt';
		}

		$maplist = 'MatchSettings' . DIRECTORY_SEPARATOR . $maplist;
		try {
			$this->maniaControl->getClient()->loadMatchSettings($maplist);

			$message = 'Maplist $<$fff' . $maplist . '$> loaded.';
			$this->maniaControl->getMapManager()->restructureMapList();
			$this->maniaControl->getChat()->sendSuccess($message, $player);
			Logger::logInfo($message, true);
		} catch (FaultException $e) {
			$this->maniaControl->getChat()->sendError('Cannot load maplist $<$fff' . $maplist . '$>!', $player);
		}
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
		$this->showGivenMapList($chatCommands, $player);
	}

	/**
	 * Interface to show a given map list
	 *
	 * @param array  $chatCommands
	 * @param Player $player
	 * @param Map[]  $mapList
	 */	 
	public function showGivenMapList(array $chatCommands, Player $player, $mapList = null) {
		if (isset($chatCommands[1])) {
			$listParam = strtolower($chatCommands[1]);
			switch ($listParam) {
				case 'help':
					$this->maniaControl->getChat()->sendInformation('Command /list accepts the following options: best|worst|first|last|bestrecs|worstrecs|norecs|newest|oldest|shortest|longest|author <login>|authoraz|authorza', $player->login);
					break;
				case 'best':
					$this->showMapListKarma(true, $player, $mapList);
					break;
				case 'worst':
					$this->showMapListKarma(false, $player, $mapList);
					break;
				case 'first':
					$this->showMapListByName(true, $player, $mapList);
					break;
				case 'last':
					$this->showMapListByName(false, $player, $mapList);
					break;
				case 'bestrecs':
					$this->showMapListRecords(true, $player, $mapList);
					break;
				case 'worstrecs':
					$this->showMapListRecords(false, $player, $mapList);
					break;
				case 'norecs':
					$this->showMapListNoRecords($player, $mapList);
					break;
				case 'newest':
					$this->showMapListDate(true, $player, $mapList);
					break;
				case 'oldest':
					$this->showMapListDate(false, $player, $mapList);
					break;
				case 'shortest':
					$this->showMapListBestTime(false, $player, $mapList);
					break;
				case 'longest':
					$this->showMapListBestTime(true, $player, $mapList);
					break;
				case 'author':
					if (isset($chatCommands[2])) {
						$this->showMapListAuthor($chatCommands[2], $player, $mapList);
					} else {
						$this->showMapListAllAuthors(true, $player, $mapList);
					}
					break;
				case 'authoraz':
					$this->showMapListAllAuthors(true, $player, $mapList);
					break;
				case 'authorza':
					$this->showMapListAllAuthors(false, $player, $mapList);
					break;
				default:
					$this->maniaControl->getMapManager()->getMapList()->showMapList($player, $mapList);
					break;
			}
		} else {
			$this->maniaControl->getMapManager()->getMapList()->showMapList($player, $mapList);
		}
	}

	/**
	 * Show a Karma based MapList
	 *
	 * @param bool   $best
	 * @param Player $player
	 * @param Map[]  $maps
	 */
	private function showMapListKarma($best, Player $player, $maps = null) {
		/** @var \MCTeam\KarmaPlugin $karmaPlugin */
		$karmaPlugin    = $this->maniaControl->getPluginManager()->getPlugin(MapList::DEFAULT_KARMA_PLUGIN);

		if ($karmaPlugin) {
			$mapListObject = $this->maniaControl->getMapManager()->getMapList();
			$player->setCache($mapListObject, MapList::CACHE_LAST_SORT, MapList::ACTION_SORT_BY_KARMA);
			$player->setCache($mapListObject, MapList::CACHE_LAST_SORT_ORDER, $best);

			$displayMxKarma = $this->maniaControl->getSettingManager()->getSettingValue($karmaPlugin, $karmaPlugin::SETTING_WIDGET_DISPLAY_MX);

			if (!$maps) {
				$maps    = $this->maniaControl->getMapManager()->getMaps();
			}
			$mapList = array();
			foreach ($maps as $map) {
				if ($map instanceof Map) {
					if ($this->maniaControl->getSettingManager()->getSettingValue($karmaPlugin, $karmaPlugin::SETTING_NEWKARMA) == true) {
						if ($displayMxKarma && $map->mx) {
							$karma = $map->mx->ratingVoteAverage / 100;
						} else {
							$karma = $karmaPlugin->getMapKarma($map);
						}
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

			$mapListObject->showMapList($player, $mapList);
		} else {
			$this->maniaControl->getChat()->sendError('KarmaPlugin is not enabled!', $player->login);
		}
	}

	/**
	 * Show a local records based list
	 *
	 * @param bool   $best
	 * @param Player $player
	 * @param Map[]  $maps
	 */
	private function showMapListRecords($best, Player $player, $maps = null) {
		/** @var \MCTeam\LocalRecordsPlugin $localRecordsPlugin */
		$localRecordsPlugin = $this->maniaControl->getPluginManager()->getPlugin(MapList::DEFAULT_LOCAL_RECORDS_PLUGIN);

		if ($localRecordsPlugin) {
			$mapListObject = $this->maniaControl->getMapManager()->getMapList();
			$player->setCache($mapListObject, MapList::CACHE_LAST_SORT, MapList::ACTION_SORT_BY_LOCAL_RECORD);
			$player->setCache($mapListObject, MapList::CACHE_LAST_SORT_ORDER, $best);

			if (!$maps) {
				$maps    = $this->maniaControl->getMapManager()->getMaps();
			}
			$mapRecs = array();
			$mapIds  = array();
			$mapList = array();
			$index = 0;
			foreach ($maps as $map) {
				$local = $localRecordsPlugin->getLocalRecord($map, $player);
				if ($local) {
					$mapRecs[$map->getEscapedName()] = $local->rank;
					$mapIds[$map->getEscapedName()]  = $index;
				}
				$index++;
			}

			asort($mapRecs);
				
			foreach ($mapRecs as $name => $rank) {
				$mapList[] = $maps[$mapIds[$name]];
			}
			foreach ($maps as $map) {
				$name = $map->getEscapedName();
				if (!array_key_exists($name, $mapRecs)) {
					$mapList[] = $map;
				}
			}
			if (!$best) {
				$mapList = array_reverse($mapList);
			}
			$mapListObject->showMapList($player, $mapList);
		} else {
			$this->maniaControl->getChat()->sendError('LocalRecordsPlugin is not enabled!', $player->login);
		}
	}

	/**
	 * Shows a list of maps without local records
	 *
	 * @param Player $player
	 * @param Map[]  $maps
	 */
	private function showMapListNoRecords(Player $player, $maps = null) {
		/** @var \MCTeam\LocalRecordsPlugin $localRecordsPlugin */
		$localRecordsPlugin = $this->maniaControl->getPluginManager()->getPlugin(MapList::DEFAULT_LOCAL_RECORDS_PLUGIN);

		if ($localRecordsPlugin) {
			if (!$maps) {
				$maps    = $this->maniaControl->getMapManager()->getMaps();
			}
			$mapList = array();
			$index = 0;
			foreach ($maps as $map) {
				$local = $localRecordsPlugin->getLocalRecord($map, $player);
				if (!$local) {
					$mapList[] = $map;
				}
			}

			$this->maniaControl->getMapManager()->getMapList()->showMapList($player, $mapList);
		} else {
			$this->maniaControl->getChat()->sendError('LocalRecordsPlugin is not enabled!', $player->login);
		}
	}



	/**
	 * Show a Date based MapList
	 *
	 * @param bool   $newest
	 * @param Player $player
	 * @param Map[]  $maps
	 */
	private function showMapListDate($newest, Player $player, $maps = null) {
		if (!$maps) {
			$maps    = $this->maniaControl->getMapManager()->getMaps();
		}

		usort($maps, function (Map $mapA, Map $mapB) {
			return ($mapA->index - $mapB->index);
		});
		if ($newest) {
			$maps = array_reverse($maps);
		}

		$this->maniaControl->getMapManager()->getMapList()->showMapList($player, $maps);
	}

	/**
	 * Show a Name based MapList
	 *
	 * @param bool   $first
	 * @param Player $player
	 * @param Map[]  $maps
	 */
	private function showMapListByName($first, Player $player, $maps = null) {
		$mapListObject = $this->maniaControl->getMapManager()->getMapList();
		$player->setCache($mapListObject, MapList::CACHE_LAST_SORT, MapList::ACTION_SORT_BY_NAME);
		$player->setCache($mapListObject, MapList::CACHE_LAST_SORT_ORDER, $first);
		
		if (!$maps) {
			$maps    = $this->maniaControl->getMapManager()->getMaps();
		}

		usort($maps, function (Map $mapA, Map $mapB) {
			return strcmp(Formatter::stripCodes($mapA->name), Formatter::stripCodes($mapB->name));
		});
		if (!$first) {
			$maps = array_reverse($maps);
		}

		$mapListObject->showMapList($player, $maps);
	}

	/**
	 * Show a best time based MapList
	 *
	 * @param bool   $longest
	 * @param Player $player
	 * @param Map[]  $maps
	 */
	private function showMapListBestTime($longest, Player $player, $maps = null) {
		/** @var \MCTeam\LocalRecordsPlugin $localRecordsPlugin */
		$localRecordsPlugin    = $this->maniaControl->getPluginManager()->getPlugin(MapList::DEFAULT_LOCAL_RECORDS_PLUGIN);

		if ($localRecordsPlugin) {
			if (!$maps) {
				$maps    = $this->maniaControl->getMapManager()->getMaps();
			}
			$mapList = array();
			foreach ($maps as $map) {
				if ($map instanceof Map) {
					$records = $localRecordsPlugin->getLocalRecords($map);
					if (count($records) > 0) {
						$map->bestTime = $records[0]->time;
					} else {
						$map->bestTime = 1e9;
					}

					$mapList[] = $map;
				}
			}

			usort($mapList, function (Map $mapA, Map $mapB) {
				return ($mapA->bestTime - $mapB->bestTime);
			});
			if ($longest) {
				$mapList = array_reverse($mapList);
			}

			$this->maniaControl->getMapManager()->getMapList()->showMapList($player, $mapList);
		} else {
			$this->maniaControl->getChat()->sendError('LocalRecordsPlugin is not enabled!', $player->login);
		}
	}

	/**
	 * Show a Author login based MapList
	 *
	 * @param bool   $ascending
	 * @param Player $player
	 * @param Map[]  $maps
	 */
	private function showMapListAllAuthors($ascending, Player $player, $maps = null) {
		$mapListObject = $this->maniaControl->getMapManager()->getMapList();
		$player->setCache($mapListObject, MapList::CACHE_LAST_SORT, MapList::ACTION_SORT_BY_AUTHOR);
		$player->setCache($mapListObject, MapList::CACHE_LAST_SORT_ORDER, $ascending);

		if (!$maps) {
			$maps    = $this->maniaControl->getMapManager()->getMaps();
		}

		usort($maps, function (Map $mapA, Map $mapB) {
			return strcmp($mapA->authorNick, $mapB->authorNick);
		});
		if ($ascending) {
			$maps = array_reverse($maps);
		}
		
		$mapListObject->showMapList($player, $maps);
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
