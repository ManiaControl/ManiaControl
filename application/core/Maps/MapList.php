<?php

namespace ManiaControl\Maps;

use CustomVotesPlugin;
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Gauge;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ColorUtil;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;
use MCTeam\KarmaPlugin;

/**
 * MapList Widget Class
 * 
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapList implements ManialinkPageAnswerListener, CallbackListener {
	/*
	 * Constants
	 */
	const ACTION_UPDATE_MAP = 'MapList.UpdateMap';
	const ACTION_ERASE_MAP = 'MapList.EraseMap';
	const ACTION_SWITCH_MAP = 'MapList.SwitchMap';
	const ACTION_START_SWITCH_VOTE = 'MapList.StartMapSwitchVote';
	const ACTION_QUEUED_MAP = 'MapList.QueueMap';
	const ACTION_UNQUEUE_MAP = 'MapList.UnQueueMap';
	const ACTION_CHECK_UPDATE = 'MapList.CheckUpdate';
	const ACTION_CLEAR_MAPQUEUE = 'MapList.ClearMapQueue';
	const ACTION_PAGING_CHUNKS = 'MapList.PagingChunk.';
	const MAX_MAPS_PER_PAGE = 15;
	const MAX_PAGES_PER_CHUNK = 2;
	const DEFAULT_KARMA_PLUGIN = 'MCTeam\KarmaPlugin';
	const DEFAULT_CUSTOM_VOTE_PLUGIN = 'CustomVotesPlugin';
	
	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $mapListShown = array();
	private $mapsInListShown = array();

	/**
	 * Create a new MapList Instance
	 * 
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Register for Callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(MapQueue::CB_MAPQUEUE_CHANGED, $this, 'updateWidgetQueue');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_MAPS_UPDATED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_KARMA_UPDATED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'updateWidget');
		
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CHECK_UPDATE, $this, 'checkUpdates');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLEAR_MAPQUEUE, $this, 'clearMapQueue');
	}

	/**
	 * Clears the Map Queue
	 * 
	 * @param array $chatCallback
	 * @param Player $player
	 */
	public function clearMapQueue(array $chatCallback, Player $player) {
		// Clears the Map Queue
		$this->maniaControl->mapManager->mapQueue->clearMapQueue($player);
	}

	/**
	 * Check for Map Updates
	 * 
	 * @param array $chatCallback
	 * @param Player $player
	 */
	public function checkUpdates(array $chatCallback, Player $player) {
		// Update Mx Infos
		$this->maniaControl->mapManager->mxManager->fetchManiaExchangeMapInformations();
		
		// Reshow the Maplist
		$this->showMapList($player);
	}

	/**
	 * Display a MapList on the Screen
	 * 
	 * @param Player $player
	 * @param array $maps
	 * @param int $chunk
	 * @param int $startPage
	 */
	public function showMapList(Player $player, $maps = null, $chunk = 0, $startPage = null) {
		$width = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		
		$this->mapListShown[$player->login] = true;
		$queueBuffer = $this->maniaControl->mapManager->mapQueue->getQueueBuffer();
		
		// Get Maps
		$mapList = array();
		if (is_array($maps)) {
			$mapList = $maps;
			$pageCount = ceil(count($mapList) / self::MAX_MAPS_PER_PAGE);
		}
		else if ($maps !== 'redirect') {
			$mapList = $this->maniaControl->mapManager->getMaps($chunk * self::MAX_PAGES_PER_CHUNK * self::MAX_MAPS_PER_PAGE, self::MAX_PAGES_PER_CHUNK * self::MAX_MAPS_PER_PAGE);
			$pageCount = ceil($this->maniaControl->mapManager->getMapsCount() / self::MAX_MAPS_PER_PAGE);
		}
		else if (array_key_exists($player->login, $this->mapsInListShown)) {
			$mapList = $this->mapsInListShown[$player->login];
			$pageCount = ceil(count($mapList) / self::MAX_MAPS_PER_PAGE);
		}
		
		$this->mapsInListShown[$player->login] = $mapList;
		
		// Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script = $maniaLink->getScript();
		$paging = new Paging();
		$script->addFeature($paging);
		$paging->setCustomMaxPageNumber($pageCount);
		$paging->setChunkActionAppendsPageNumber(true);
		$paging->setChunkActions(self::ACTION_PAGING_CHUNKS);
		
		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $paging);
		$maniaLink->add($frame);
		
		// Admin Buttons
		if ($this->maniaControl->authenticationManager->checkPermission($player, MapQueue::SETTING_PERMISSION_CLEAR_MAPQUEUE)) {
			// Clear Map-Queue
			$label = new Label_Button();
			$frame->add($label);
			$label->setText("Clear Map-Queue");
			$label->setTextSize(1);
			$label->setPosition($width / 2 - 8, -$height / 2 + 9);
			$label->setHAlign(Control::RIGHT);
			
			$quad = new Quad_BgsPlayerCard();
			$frame->add($quad);
			$quad->setPosition($width / 2 - 5, -$height / 2 + 9, 0.01);
			$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
			$quad->setHAlign(Control::RIGHT);
			$quad->setSize(29, 4);
			$quad->setAction(self::ACTION_CLEAR_MAPQUEUE);
		}
		
		if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_CHECK_UPDATE)) {
			// Check Update
			$label = new Label_Button();
			$frame->add($label);
			$label->setText("Check MX Updates");
			$label->setTextSize(1);
			$label->setPosition($width / 2 - 41, -$height / 2 + 9, 0.01);
			$label->setHAlign(Control::RIGHT);
			
			$quad = new Quad_BgsPlayerCard();
			$frame->add($quad);
			$quad->setPosition($width / 2 - 37, -$height / 2 + 9, 0.01);
			$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
			$quad->setHAlign(Control::RIGHT);
			$quad->setSize(35, 4);
			$quad->setAction(self::ACTION_CHECK_UPDATE);
			
			$mxQuad = new Quad();
			$frame->add($mxQuad);
			$mxQuad->setSize(3, 3);
			$mxQuad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_GREEN));
			$mxQuad->setImageFocus($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_GREEN_MOVER));
			$mxQuad->setPosition($width / 2 - 67, -$height / 2 + 9);
			$mxQuad->setZ(0.01);
			$mxQuad->setAction(self::ACTION_CHECK_UPDATE);
		}
		
		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($height / 2 - 5);
		$x = -$width / 2;
		$array = array('Id' => $x + 5, 'Mx Id' => $x + 10, 'Map Name' => $x + 20, 'Author' => $x + 68, 'Karma' => $x + 115, 
				'Actions' => $width / 2 - 15);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);
		
		// Predefine description Label
		$descriptionLabel = $this->maniaControl->manialinkManager->styleManager->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);
		
		$queuedMaps = $this->maniaControl->mapManager->mapQueue->getQueuedMapsRanking();
		/**
		 *
		 * @var KarmaPlugin $karmaPlugin
		 */
		$karmaPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_KARMA_PLUGIN);

		$pageNumber = 1 + $chunk * self::MAX_PAGES_PER_CHUNK;
		$startPageNumber = (is_int($startPage) ? $startPage : $pageNumber);
		$paging->setStartPageNumber($startPageNumber);
		
		$id = 1 + $chunk * self::MAX_PAGES_PER_CHUNK * self::MAX_MAPS_PER_PAGE;
		$y = $height / 2 - 10;
		$pageFrames = array();
		/**
		 *
		 * @var Map $map
		 */
		$currentMap = $this->maniaControl->mapManager->getCurrentMap();
		$mxIcon = $this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON);
		$mxIconHover = $this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_MOVER);
		$mxIconGreen = $this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_GREEN);
		$mxIconGreenHover = $this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_GREEN_MOVER);
		
		foreach ($mapList as $map) {
			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				if (!empty($pageFrames)) {
					$pageFrame->setVisible(false);
				}
				array_push($pageFrames, $pageFrame);
				$y = $height / 2 - 10;
				
				$paging->addPage($pageFrame, $pageNumber);
				$pageNumber++;
			}
			
			// Map Frame
			$mapFrame = new Frame();
			$pageFrame->add($mapFrame);
			$mapFrame->setZ(0.1);
			$mapFrame->setY($y);
			
			if ($id % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$mapFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}
			
			if ($currentMap === $map) {
				$currentQuad = new Quad_Icons64x64_1();
				$mapFrame->add($currentQuad);
				$currentQuad->setX($x + 3.5);
				$currentQuad->setZ(0.2);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}
			
			$mxId = '-';
			if (isset($map->mx->id)) {
				$mxId = $map->mx->id;
				
				$mxQuad = new Quad();
				$mapFrame->add($mxQuad);
				$mxQuad->setSize(3, 3);
				$mxQuad->setImage($mxIcon);
				$mxQuad->setImageFocus($mxIconHover);
				$mxQuad->setX($x + 65);
				$mxQuad->setUrl($map->mx->pageurl);
				$mxQuad->setZ(0.01);
				$description = 'View $<' . $map->name . '$> on Mania-Exchange';
				$mxQuad->addTooltipLabelFeature($descriptionLabel, $description);
				
				if ($map->updateAvailable()) {
					$mxQuad = new Quad();
					$mapFrame->add($mxQuad);
					$mxQuad->setSize(3, 3);
					$mxQuad->setImage($mxIconGreen);
					$mxQuad->setImageFocus($mxIconGreenHover);
					$mxQuad->setX($x + 62);
					$mxQuad->setUrl($map->mx->pageurl);
					$mxQuad->setZ(0.01);
					$description = 'Update for $<' . $map->name . '$> available on Mania-Exchange!';
					$mxQuad->addTooltipLabelFeature($descriptionLabel, $description);
					
					// Update Button
					if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
						$mxQuad->setAction(self::ACTION_UPDATE_MAP . '.' . $map->uid);
					}
				}
			}
			
			// Display Maps
			$array = array($id => $x + 5, $mxId => $x + 10, Formatter::stripDirtyCodes($map->name) => $x + 20, $map->authorNick => $x + 68);
			$labels = $this->maniaControl->manialinkManager->labelLine($mapFrame, $array);
			if (isset($labels[3])) {
				/**
				 *
				 * @var Label $label
				 */
				$label = $labels[3];
				$description = '$<' . $map->name . '$> made by $<' . $map->authorLogin . '$>';
				$label->addTooltipLabelFeature($descriptionLabel, $description);
			}
			
			// TODO action detailed map info including mx info
			
			// Map-Queue-Map-Label
			if (isset($queuedMaps[$map->uid])) {
				$label = new Label_Text();
				$mapFrame->add($label);
				$label->setX($width / 2 - 15);
				$label->setAlign(Control::CENTER, Control::CENTER);
				$label->setZ(0.2);
				$label->setTextSize(1.5);
				$label->setText($queuedMaps[$map->uid]);
				$label->setTextColor('fff');
				
				// Checks if the Player who openend the Widget has queued the map
				$queuer = $this->maniaControl->mapManager->mapQueue->getQueuer($map->uid);
				if ($queuer->login == $player->login) {
					$description = 'Remove $<' . $map->name . '$> from the Map Queue';
					$label->addTooltipLabelFeature($descriptionLabel, $description);
					$label->setAction(self::ACTION_UNQUEUE_MAP . '.' . $map->uid);
				}
				else {
					$description = '$<' . $map->name . '$> is on Map-Queue Position: ' . $queuedMaps[$map->uid];
					$label->addTooltipLabelFeature($descriptionLabel, $description);
				}
			}
			else {
				// Map-Queue-Map-Button
				$queueLabel = new Label_Button();
				$mapFrame->add($queueLabel);
				$queueLabel->setX($width / 2 - 15);
				$queueLabel->setZ(0.2);
				$queueLabel->setSize(3, 3);
				$queueLabel->setText('+');

				if(in_array($map->uid, $queueBuffer)) {
					if ($this->maniaControl->authenticationManager->checkPermission($player, MapQueue::SETTING_PERMISSION_CLEAR_MAPQUEUE)) {
						$queueLabel->setAction(self::ACTION_QUEUED_MAP . '.' . $map->uid);
					}
					$queueLabel->setTextColor('f00');
					$description = '$<' . $map->name . '$> has recently been played!';
					$queueLabel->addTooltipLabelFeature($descriptionLabel, $description);
				} else {
					$queueLabel->setTextColor('09f');
					$queueLabel->setAction(self::ACTION_QUEUED_MAP . '.' . $map->uid);
					$description = 'Add $<' . $map->name . '$> to the Map Queue';
					$queueLabel->addTooltipLabelFeature($descriptionLabel, $description);
				}
			}
			
			if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_REMOVE_MAP)) {
				// erase map quad
				$eraseLabel = new Label_Button();
				$mapFrame->add($eraseLabel);
				$eraseLabel->setX($width / 2 - 5);
				$eraseLabel->setZ(0.2);
				$eraseLabel->setSize(3, 3);
				$eraseLabel->setTextSize(1);
				$eraseLabel->setText('x');
				$eraseLabel->setTextColor('a00');
				
				$confirmFrame = $this->buildConfirmFrame($maniaLink, $y, $id, $map->uid);
				$eraseLabel->addToggleFeature($confirmFrame);
				$description = 'Remove Map: $<' . $map->name . '$>';
				$eraseLabel->addTooltipLabelFeature($descriptionLabel, $description);
			}
			
			if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
				// Switch to map
				$switchLabel = new Label_Button();
				$mapFrame->add($switchLabel);
				$switchLabel->setX($width / 2 - 10);
				$switchLabel->setZ(0.2);
				$switchLabel->setSize(3, 3);
				$switchLabel->setTextSize(2);
				$switchLabel->setText('»');
				$switchLabel->setTextColor('0f0');
				
				$confirmFrame = $this->buildConfirmFrame($maniaLink, $y, $id);
				$switchLabel->addToggleFeature($confirmFrame);
				
				$description = 'Switch Directly to Map: $<' . $map->name . '$>';
				$switchLabel->addTooltipLabelFeature($descriptionLabel, $description);
			}
			else if ($this->maniaControl->pluginManager->isPluginActive(self::DEFAULT_CUSTOM_VOTE_PLUGIN)) {
				// Switch Map Voting
				$switchLabel = new Label_Button();
				$mapFrame->add($switchLabel);
				$switchLabel->setX($width / 2 - 10);
				$switchLabel->setZ(0.2);
				$switchLabel->setSize(3, 3);
				$switchLabel->setTextSize(2);
				$switchLabel->setText('»');
				$switchLabel->setTextColor('0f0');
				
				$switchLabel->setAction(self::ACTION_START_SWITCH_VOTE . '.' . ($id - 1));
				
				$description = 'Start Map-Switch Vote: $<' . $map->name . '$>';
				$switchLabel->addTooltipLabelFeature($descriptionLabel, $description);
			}
			
			// Display Karma bar
			if ($karmaPlugin) {
				$karma = $karmaPlugin->getMapKarma($map);
				$votes = $karmaPlugin->getMapVotes($map);
				if (is_numeric($karma)) {
					$karmaGauge = new Gauge();
					$mapFrame->add($karmaGauge);
					$karmaGauge->setZ(2);
					$karmaGauge->setX($x + 120);
					$karmaGauge->setSize(20, 9);
					$karmaGauge->setDrawBg(false);
					$karma = floatval($karma);
					$karmaGauge->setRatio($karma + 0.15 - $karma * 0.15);
					$karmaColor = ColorUtil::floatToStatusColor($karma);
					$karmaGauge->setColor($karmaColor . '9');
					
					$karmaLabel = new Label();
					$mapFrame->add($karmaLabel);
					$karmaLabel->setZ(2);
					$karmaLabel->setX($x + 120);
					$karmaLabel->setSize(20 * 0.9, 5);
					$karmaLabel->setTextSize(0.9);
					$karmaLabel->setTextColor('000');
					$karmaLabel->setAlign(Control::CENTER, Control::CENTER);
					$karmaLabel->setText('  ' . round($karma * 100.) . '% (' . $votes['count'] . ')');
				}
			}
			
			$y -= 4;
			if ($id % self::MAX_MAPS_PER_PAGE == 0) {
				unset($pageFrame);
			}
			$id++;
		}
		
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'MapList');
	}

	/**
	 * Builds the confirmation frame
	 * 
	 * @param ManiaLink $maniaLink
	 * @param $y
	 * @param $id
	 * @param bool $mapUid
	 * @return Frame
	 */
	public function buildConfirmFrame(Manialink $maniaLink, $y, $id, $mapUid = false) {
		$width = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$quadStyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();
		
		$confirmFrame = new Frame();
		$maniaLink->add($confirmFrame);
		$confirmFrame->setPosition($width / 2 + 6, $y);
		$confirmFrame->setVisible(false);
		
		$quad = new Quad();
		$confirmFrame->add($quad);
		$quad->setStyles($quadStyle, $quadSubstyle);
		$quad->setSize(12, 4);
		
		$quad = new Quad_BgsPlayerCard();
		$confirmFrame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_BgCardSystem);
		$quad->setSize(11, 3.5);
		
		$label = new Label_Button();
		$confirmFrame->add($label);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setText('Sure?');
		$label->setTextSize(1);
		$label->setScale(0.90);
		$label->setX(-1.3);
		
		$buttLabel = new Label_Button();
		$confirmFrame->add($buttLabel);
		$buttLabel->setPosition(3.2, 0.4, 0.2);
		$buttLabel->setSize(3, 3);
		$buttLabel->setAlign(Control::CENTER, Control::CENTER);
		
		if (!$mapUid) {
			$quad->setAction(self::ACTION_SWITCH_MAP . '.' . ($id - 1));
			$buttLabel->setText('»');
			$buttLabel->setTextColor('0f0');
			$buttLabel->setTextSize(2);
		}
		else {
			$buttLabel->setTextSize(1);
			$buttLabel->setText('x');
			$buttLabel->setTextColor('a00');
			$quad->setAction(self::ACTION_ERASE_MAP . '.' . ($id - 1) . '.' . $mapUid);
		}
		return $confirmFrame;
	}

	/**
	 * Unset the player if he opened another Main Widget
	 * 
	 * @param Player $player
	 * @param $openedWidget
	 */
	public function handleWidgetOpened(Player $player, $openedWidget) {
		// unset when another main widget got opened
		if ($openedWidget != 'MapList') {
			unset($this->mapListShown[$player->login]);
		}
	}

	/**
	 * Closes the widget
	 * 
	 * @param \ManiaControl\Players\Player $player
	 */
	public function closeWidget(Player $player) {
		unset($this->mapListShown[$player->login]);
	}

	/**
	 * Closes the widget
	 * 
	 * @param Player $player
	 */
	public function playerCloseWidget(Player $player) {
		unset($this->mapListShown[$player->login]);
		$this->maniaControl->manialinkManager->closeWidget($player);
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 * 
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		$actionArray = explode('.', $actionId);
		
		if (count($actionArray) <= 2) {
			return;
		}
		
		$action = $actionArray[0] . '.' . $actionArray[1];
		$login = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		$mapId = (int) $actionArray[2];
		
		switch ($action) {
			case self::ACTION_UPDATE_MAP:
				$mapUid = $actionArray[2];
				$this->maniaControl->mapManager->updateMap($player, $mapUid);
				break;
			case self::ACTION_ERASE_MAP:
				$mapUid = $actionArray[3];
				$this->maniaControl->mapManager->removeMap($player, $mapUid);
				$this->showMapList($player);
				break;
			case self::ACTION_SWITCH_MAP:
				try {
					$this->maniaControl->client->jumpToMapIndex($mapId);
				}
				catch (Exception $e) {
					// TODO: is it even possible that an exception other than connection errors will be thrown? - remove try-catch?
					$this->maniaControl->chat->sendError("Error while Jumping to Map Index");
					break;
				}
				$mapList = $this->maniaControl->mapManager->getMaps();
				$map = $mapList[$mapId];
				
				$message = '$<' . $player->nickname . '$> skipped to Map $z$<' . $map->name . '$>!';
				$this->maniaControl->chat->sendSuccess($message);
				$this->maniaControl->log($message, true);
				
				$this->playerCloseWidget($player);
				break;
			case self::ACTION_START_SWITCH_VOTE:
				/**
				 *
				 * @var $votesPlugin CustomVotesPlugin
				 */
				$votesPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_CUSTOM_VOTE_PLUGIN);
				$mapList = $this->maniaControl->mapManager->getMaps();
				$map = $mapList[$mapId];
				
				$message = '$<' . $player->nickname . '$>$s started a vote to switch to $<' . $map->name . '$>!';
				
				/**
				 *
				 * @var Map $map
				 */
				$votesPlugin->defineVote('switchmap', "Goto " . $map->name, true, $message);
				
				$self = $this;
				$votesPlugin->startVote($player, 'switchmap', function ($result) use(&$self, &$votesPlugin, &$map) {
					$self->maniaControl->chat->sendInformation('$sVote Successfully -> Map switched!');
					$votesPlugin->undefineVote('switchmap');
					
					try {
						$index = $self->maniaControl->mapManager->getMapIndex($map);
						$self->maniaControl->client->jumpToMapIndex($index);
					}
					catch (Exception $e) {
						// TODO temp added 19.04.2014
						$self->maniaControl->errorHandler->triggerDebugNotice("Exception line 557 MapList.php" . $e->getMessage());
						
						$self->maniaControl->chat->sendError("Error while Switching Map");
					}
				});
				break;
			case self::ACTION_QUEUED_MAP:
				$this->maniaControl->mapManager->mapQueue->addMapToMapQueue($callback[1][1], $actionArray[2]);
				$this->showMapList($player, 'redirect');
				break;
			case self::ACTION_UNQUEUE_MAP:
				$this->maniaControl->mapManager->mapQueue->removeFromMapQueue($player, $actionArray[2]);
				$this->showMapList($player, 'redirect');
				break;
			default:
				if (substr($actionId, 0, strlen(self::ACTION_PAGING_CHUNKS)) === self::ACTION_PAGING_CHUNKS) {
					// Paging chunks
					$neededPage = (int) substr($actionId, strlen(self::ACTION_PAGING_CHUNKS));
					$chunk = (int) ($neededPage / self::MAX_PAGES_PER_CHUNK - 0.5);
					$this->showMapList($player, null, $chunk, $neededPage);
				}
				break;
		}
	}

	/**
	 * Reopen the widget on Map Begin, MapListChanged, etc.
	 */
	public function updateWidget() {
		foreach ($this->mapListShown as $login => $shown) {
			if ($shown) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				if ($player) {
					$this->showMapList($player);
				}
				else {
					unset($this->mapListShown[$login]);
				}
			}
		}
	}

	/**
	 * Reopen the widget on MapQueue changed
	 */
	public function updateWidgetQueue() {
		foreach ($this->mapListShown as $login => $shown) {
			if ($shown) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				if ($player) {
					$this->showMapList($player, 'redirect');
				}
				else {
					unset($this->mapListShown[$login]);
				}
			}
		}
	}
} 