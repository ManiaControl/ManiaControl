<?php

namespace ManiaControl\Maps;

use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\ChangeInProgressException;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;

/**
 * Class offering Commands to manage Maps
 *
 * @author    steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
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

	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create MapCommands instance
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initActionsMenuButtons();

		// Register for admin chat commands
		$this->maniaControl->commandManager->registerCommandListener(array('nextmap', 'next', 'skip'), $this, 'command_NextMap', true);
		$this->maniaControl->commandManager->registerCommandListener(array('restartmap', 'resmap', 'res'), $this, 'command_RestartMap', true);
		$this->maniaControl->commandManager->registerCommandListener(array('replaymap', 'replay'), $this, 'command_ReplayMap', true);
		$this->maniaControl->commandManager->registerCommandListener(array('addmap', 'add'), $this, 'command_AddMap', true);
		$this->maniaControl->commandManager->registerCommandListener(array('removemap', 'removethis', 'erasemap', 'erasethis'), $this, 'command_RemoveMap', true);
		$this->maniaControl->commandManager->registerCommandListener(array('shufflemaps', 'shuffle'), $this, 'command_ShuffleMaps', true);
		$this->maniaControl->commandManager->registerCommandListener(array('writemaplist', 'wml'), $this, 'command_WriteMapList', true);
		$this->maniaControl->commandManager->registerCommandListener(array('readmaplist', 'rml'), $this, 'command_ReadMapList', true);

		// Register for player chat commands
		$this->maniaControl->commandManager->registerCommandListener('nextmap', $this, 'command_showNextMap');
		$this->maniaControl->commandManager->registerCommandListener(array('maps', 'list'), $this, 'command_List');
		$this->maniaControl->commandManager->registerCommandListener(array('xmaps', 'xlist'), $this, 'command_xList');

		// Menu Buttons
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_XLIST, $this, 'command_xList');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_MAPLIST, $this, 'command_List');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_RESTART_MAP, $this, 'command_RestartMap');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SKIP_MAP, $this, 'command_NextMap');
	}

	/**
	 * Add all Actions Menu Buttons
	 */
	private function initActionsMenuButtons() {
		// Menu Open xList
		$itemQuad = new Quad();
		$itemQuad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON));
		$itemQuad->setImageFocus($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_MOVER));
		$itemQuad->setAction(self::ACTION_OPEN_XLIST);
		$this->maniaControl->actionsMenu->addPlayerMenuItem($itemQuad, 5, 'Open MX List');

		// Menu Open List
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ToolRoot);
		$itemQuad->setAction(self::ACTION_OPEN_MAPLIST);
		$this->maniaControl->actionsMenu->addPlayerMenuItem($itemQuad, 10, 'Open MapList');

		// Menu RestartMap
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Reload);
		$itemQuad->setAction(self::ACTION_RESTART_MAP);
		$this->maniaControl->actionsMenu->addAdminMenuItem($itemQuad, 10, 'Restart Map');

		// Menu NextMap
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowFastNext);
		$itemQuad->setAction(self::ACTION_SKIP_MAP);
		$this->maniaControl->actionsMenu->addAdminMenuItem($itemQuad, 20, 'Skip Map');
	}

	/**
	 * Shows which map is the next
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_ShowNextMap(array $chat, Player $player) {
		$nextQueued = $this->maniaControl->mapManager->mapQueue->getNextQueuedMap();
		if ($nextQueued) {
			/** @var Player $requester */
			$requester = $nextQueued[0];
			/** @var Map $map */
			$map = $nextQueued[1];
			$this->maniaControl->chat->sendInformation("Next map is $<" . $map->name . "$> from $<" . $map->authorNick . "$> requested by $<" . $requester->nickname . "$>.", $player->login);
		} else {

			$mapIndex = $this->maniaControl->client->getNextMapIndex();
			$maps     = $this->maniaControl->mapManager->getMaps();
			$map      = $maps[$mapIndex];
			$this->maniaControl->chat->sendInformation("Next map is $<" . $map->name . "$> from $<" . $map->authorNick . "$>.", $player->login);
		}
	}

	/**
	 * Handle removemap command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_RemoveMap(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_REMOVE_MAP)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		// Get map
		$map = $this->maniaControl->mapManager->getCurrentMap();
		if (!$map) {
			$this->maniaControl->chat->sendError("Couldn't remove map.", $player->login);
			return;
		}

		//RemoveMap
		$this->maniaControl->mapManager->removeMap($player, $map->uid);
	}

	/**
	 * Handle shufflemaps command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_ShuffleMaps(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_SHUFFLE_MAPS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		// Shuffles the maps
		$this->maniaControl->mapManager->shuffleMapList($player);
	}

	/**
	 * Handle addmap command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_AddMap(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$params = explode(' ', $chatCallback[1][2], 2);
		if (count($params) < 2) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //addmap 1234', $player->login);
			return;
		}

		// add Map from Mania Exchange
		$this->maniaControl->mapManager->addMapFromMx($params[1], $player->login);
	}

	/**
	 * Handle /nextmap Command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_NextMap(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_SKIP_MAP)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		try {
			$this->maniaControl->client->nextMap();
		} catch(ChangeInProgressException $e) {
		}
		$message = '$<' . $player->nickname . '$> skipped the current Map!';
		$this->maniaControl->chat->sendSuccess($message);
		$this->maniaControl->log($message, true);
	}

	/**
	 * Handle restartmap command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_RestartMap(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_RESTART_MAP)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$message = '$<' . $player->nickname . '$> restarted the current Map!';
		$this->maniaControl->chat->sendSuccess($message);
		$this->maniaControl->log($message, true);

		try {
			$this->maniaControl->client->restartMap();
		} catch(ChangeInProgressException $e) {
		}
	}

	////$this->maniaControl->mapManager->mapQueue->addFirstMapToMapQueue($this->currentVote->voter, $this->maniaControl->mapManager->getCurrentMap());
	/**
	 * Handle replaymap command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_ReplayMap(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_RESTART_MAP)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$message = '$<' . $player->nickname . '$> replays the current Map!';
		$this->maniaControl->chat->sendSuccess($message);
		$this->maniaControl->log($message, true);

		$this->maniaControl->mapManager->mapQueue->addFirstMapToMapQueue($player, $this->maniaControl->mapManager->getCurrentMap());
	}

	/**
	 * Handle writemaplist command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_WriteMapList(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, 3)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$chatCommand = explode(' ', $chat[1][2]);
		if(isset($chatCommand[1])) {
			if(strstr($chatCommand[1], '.txt')) {
				$maplist = $chatCommand[1];
			} else {
				$maplist = $chatCommand.'.txt';
			}
		} else {
			$maplist = 'maplist.txt';
		}

		$maplist = 'MatchSettings/'.$maplist;
		try {
			$this->maniaControl->client->saveMatchSettings($maplist);

			$message = 'Maplist $<$fff'.$maplist.'$> written.';
			$this->maniaControl->chat->sendSuccess($message, $player);
			$this->maniaControl->log($message, true);
		} catch(FaultException $e) {
			$this->maniaControl->chat->sendError('Cannot write maplist $<$fff'.$maplist.'$>!', $player);
		}
	}

	/**
	 * Handle readmaplist command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_ReadMapList(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, 3)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$chatCommand = explode(' ', $chat[1][2]);
		if(isset($chatCommand[1])) {
			if(strstr($chatCommand[1], '.txt')) {
				$maplist = $chatCommand[1];
			} else {
				$maplist = $chatCommand[1].'.txt';
			}
		} else {
			$maplist = 'maplist.txt';
		}

		$maplist = 'MatchSettings/'.$maplist;
		try {
			$this->maniaControl->client->loadMatchSettings($maplist);

			$message = 'Maplist $<$fff'.$maplist.'$> loaded.';
			$this->maniaControl->mapManager->restructureMapList();
			$this->maniaControl->chat->sendSuccess($message, $player);
			$this->maniaControl->log($message, true);
		} catch(FaultException $e) {
			$this->maniaControl->chat->sendError('Cannot load maplist $<$fff'.$maplist.'$>!', $player);
		}
	}

	/**
	 * Handle /maps command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_List(array $chatCallback, Player $player) {
		$chatCommands = explode(' ', $chatCallback[1][2]);
		$this->maniaControl->mapManager->mapList->playerCloseWidget($player);
		if(isset($chatCommands[1])) {
			if($chatCommands[1] == ' ' || $chatCommands[1] == 'all') {
				$this->maniaControl->mapManager->mapList->showMapList($player);
			} elseif($chatCommands[1] == 'best') {
				$this->showMapListKarma(true, $player);
			} elseif($chatCommands[1] == 'worst') {
				$this->showMapListKarma(false, $player);
			} elseif($chatCommands[1] == 'newest') {
				$this->showMapListDate(true, $player);
			} elseif($chatCommands[1] == 'oldest') {
				$this->showMapListDate(false, $player);
			} elseif($chatCommands[1] == 'author') {
				if(isset($chatCommands[2])) {
					$this->showMaplistAuthor($chatCommands[2], $player);
				} else {
					$this->maniaControl->chat->sendError('There are no maps to show!', $player->login);
				}
			}
		} else {
			$this->maniaControl->mapManager->mapList->showMapList($player);
		}
	}

	private function showMapListAuthor($author, $player) {
		$maps = $this->maniaControl->mapManager->getMaps();
		$mapList = array();
		/** @var Map $map */
		foreach($maps as $map) {
			if($map->authorLogin == $author) {
				$mapList[] = $map;
			}
		}

		if(count($mapList) == 0) {
			$this->maniaControl->chat->sendError('There are no maps to show!', $player->login);
			return;
		}

		$this->maniaControl->mapManager->mapList->showMapList($player, $mapList);
	}

	private function showMapListKarma($best, $player) {
		/** @var \MCTeam\KarmaPlugin $karmaPlugin */
		$karmaPlugin = $this->maniaControl->pluginManager->getPlugin(MapList::DEFAULT_KARMA_PLUGIN);
		if($karmaPlugin) {
			$maps = $this->maniaControl->mapManager->getMaps();
			$mapList = array();
			foreach($maps as $map) {
				if($map instanceof Map) {
					if($this->maniaControl->settingManager->getSetting($karmaPlugin, $karmaPlugin::SETTING_NEWKARMA) === true) {
						$karma = $karmaPlugin->getMapKarma($map);
						$map->karma = round($karma * 100.);
					} else {
						$votes = $karmaPlugin->getMapVotes($map);
						$min = 0;
						$plus = 0;
						foreach($votes as $vote) {
							if(isset($vote->vote)) {
								if($vote->vote != 0.5) {
									if($vote->vote < 0.5) {
										$min = $min+$vote->count;
									} else {
										$plus = $plus+$vote->count;
									}
								}
							}
						}
						$map->karma = $plus-$min;
					}
					$mapList[] = $map;
				}
			}

			usort($mapList, array($this, 'sortByKarma'));
			if($best) {
				$mapList = array_reverse($mapList);
			}

			$this->maniaControl->mapManager->mapList->showMapList($player, $mapList);
		} else {
			$this->maniaControl->chat->sendError('KarmaPlugin is not enabled!', $player->login);
		}
	}

	private function sortByKarma($a, $b) {
		return $a->karma - $b->karma;
	}

	private function showMapListDate($newest, $player) {
		$maps = $this->maniaControl->mapManager->getMaps();

		usort($maps, function($a, $b) {
			return $a->index - $b->index;
		});

		if($newest) {
			$maps = array_reverse($maps);
		}

		$this->maniaControl->mapManager->mapList->showMapList($player, $maps);
	}

	/**
	 * Handle ManiaExchange list command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_xList(array $chatCallback, Player $player) {
		$this->maniaControl->mapManager->mxList->showList($chatCallback, $player);
	}
}
