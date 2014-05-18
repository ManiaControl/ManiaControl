<?php

namespace ManiaControl\Maps;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Gauge;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Utils\ColorUtil;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\Xmlrpc\NotInListException;
use MCTeam\CustomVotesPlugin;
use MCTeam\KarmaPlugin;

/**
 * MapList Widget Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapList implements ManialinkPageAnswerListener, CallbackListener {
	/*
	 * Constants
	 */
	const ACTION_UPDATE_MAP          = 'MapList.UpdateMap';
	const ACTION_ERASE_MAP           = 'MapList.EraseMap';
	const ACTION_SWITCH_MAP          = 'MapList.SwitchMap';
	const ACTION_START_SWITCH_VOTE   = 'MapList.StartMapSwitchVote';
	const ACTION_QUEUED_MAP          = 'MapList.QueueMap';
	const ACTION_UNQUEUE_MAP         = 'MapList.UnQueueMap';
	const ACTION_CHECK_UPDATE        = 'MapList.CheckUpdate';
	const ACTION_CLEAR_MAPQUEUE      = 'MapList.ClearMapQueue';
	const ACTION_PAGING_CHUNKS       = 'MapList.PagingChunk.';
	const MAX_MAPS_PER_PAGE          = 15;
	const MAX_PAGES_PER_CHUNK        = 2;
	const DEFAULT_KARMA_PLUGIN       = 'MCTeam\KarmaPlugin';
	const DEFAULT_CUSTOM_VOTE_PLUGIN = 'MCTeam\CustomVotesPlugin';
	const CACHE_CURRENT_PAGE         = 'CurrentPage';
	const WIDGET_NAME                = 'MapList';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;

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
		$this->maniaControl->callbackManager->registerCallbackListener(MapQueue::CB_MAPQUEUE_CHANGED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_MAPS_UPDATED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_KARMA_UPDATED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINMAP, $this, 'updateWidget');

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CHECK_UPDATE, $this, 'checkUpdates');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLEAR_MAPQUEUE, $this, 'clearMapQueue');
	}

	/**
	 * Clears the Map Queue
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function clearMapQueue(array $chatCallback, Player $player) {
		// Clears the Map Queue
		$this->maniaControl->mapManager->mapQueue->clearMapQueue($player);
	}

	/**
	 * Check for Map Updates
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function checkUpdates(array $chatCallback, Player $player) {
		// Update Mx Infos
		$this->maniaControl->mapManager->mxManager->fetchManiaExchangeMapInformation();

		// Reshow the Maplist
		$this->showMapList($player);
	}

	/**
	 * Display a MapList on the Screen
	 *
	 * @param Player $player
	 * @param array  $mapList
	 * @param int    $pageIndex
	 */
	public function showMapList(Player $player, $mapList = null, $pageIndex = -1) {
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		if ($pageIndex < 0) {
			$pageIndex = (int)$player->getCache($this, self::CACHE_CURRENT_PAGE);
		}
		$player->setCache($this, self::CACHE_CURRENT_PAGE, $pageIndex);
		$queueBuffer = $this->maniaControl->mapManager->mapQueue->getQueueBuffer();

		$chunkIndex     = $this->getChunkIndexFromPageNumber($pageIndex);
		$mapsBeginIndex = $this->getChunkMapsBeginIndex($chunkIndex);

		// Get Maps
		if (!is_array($mapList)) {
			$mapList = $this->maniaControl->mapManager->getMaps();
		}
		$mapList = array_slice($mapList, $mapsBeginIndex, self::MAX_PAGES_PER_CHUNK * self::MAX_MAPS_PER_PAGE);

		$totalMapsCount = $this->maniaControl->mapManager->getMapsCount();
		$pagesCount     = ceil($totalMapsCount / self::MAX_MAPS_PER_PAGE);

		// Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);
		$paging->setCustomMaxPageNumber($pagesCount);
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
		$x     = -$width / 2;
		$array = array('Id' => $x + 5, 'Mx Id' => $x + 10, 'Map Name' => $x + 20, 'Author' => $x + 68, 'Karma' => $x + 115, 'Actions' => $width / 2 - 16);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		// Predefine description Label
		$descriptionLabel = $this->maniaControl->manialinkManager->styleManager->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		$queuedMaps = $this->maniaControl->mapManager->mapQueue->getQueuedMapsRanking();
		/** @var KarmaPlugin $karmaPlugin */
		$karmaPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_KARMA_PLUGIN);

		$pageNumber = 1 + $chunkIndex * self::MAX_PAGES_PER_CHUNK;
		$paging->setStartPageNumber($pageIndex + 1);

		$index     = 0;
		$id        = 1 + $mapsBeginIndex;
		$y         = $height / 2 - 10;
		$pageFrame = null;

		/** @var Map $map */
		$currentMap       = $this->maniaControl->mapManager->getCurrentMap();
		$mxIcon           = $this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON);
		$mxIconHover      = $this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_MOVER);
		$mxIconGreen      = $this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_GREEN);
		$mxIconGreenHover = $this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_GREEN_MOVER);

		foreach ($mapList as $map) {
			if ($index % self::MAX_MAPS_PER_PAGE === 0) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
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
			$array  = array($id => $x + 5, $mxId => $x + 10, Formatter::stripDirtyCodes($map->name) => $x + 20, $map->authorNick => $x + 68);
			$labels = $this->maniaControl->manialinkManager->labelLine($mapFrame, $array);
			if (isset($labels[3])) {
				/** @var Label $label */
				$label       = $labels[3];
				$description = 'Click to checkout all maps by $<' . $map->authorLogin . '$>!';
				$label->setAction(MapCommands::ACTION_SHOW_AUTHOR . $map->authorLogin);
				$label->addTooltipLabelFeature($descriptionLabel, $description);
			}

			// TODO action detailed map info including mx info

			// Map-Queue-Map-Label
			if (isset($queuedMaps[$map->uid])) {
				$label = new Label_Text();
				$mapFrame->add($label);
				$label->setX($width / 2 - 13);
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
				} else {
					$description = '$<' . $map->name . '$> is on Map-Queue Position: ' . $queuedMaps[$map->uid];
					$label->addTooltipLabelFeature($descriptionLabel, $description);
				}
			} else {
				// Map-Queue-Map-Button
				$queueLabel = new Label_Button();
				$mapFrame->add($queueLabel);
				$queueLabel->setX($width / 2 - 13);
				$queueLabel->setZ(0.2);
				$queueLabel->setSize(3, 3);
				$queueLabel->setText('+');

				if (in_array($map->uid, $queueBuffer)) {
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

				$confirmFrame = $this->buildConfirmFrame($maniaLink, $y, $map->uid, true);
				$eraseLabel->addToggleFeature($confirmFrame);
				$description = 'Remove Map: $<' . $map->name . '$>';
				$eraseLabel->addTooltipLabelFeature($descriptionLabel, $description);
			}

			if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
				// Switch to map
				$switchLabel = new Label_Button();
				$mapFrame->add($switchLabel);
				$switchLabel->setX($width / 2 - 9);
				$switchLabel->setZ(0.2);
				$switchLabel->setSize(3, 3);
				$switchLabel->setTextSize(2);
				$switchLabel->setText('»');
				$switchLabel->setTextColor('0f0');

				$confirmFrame = $this->buildConfirmFrame($maniaLink, $y, $map->uid);
				$switchLabel->addToggleFeature($confirmFrame);

				$description = 'Switch Directly to Map: $<' . $map->name . '$>';
				$switchLabel->addTooltipLabelFeature($descriptionLabel, $description);
			}
			if ($this->maniaControl->pluginManager->isPluginActive(self::DEFAULT_CUSTOM_VOTE_PLUGIN)) {
				if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
					// Switch Map Voting for Admins
					$switchQuad = new Quad_UIConstruction_Buttons();
					$mapFrame->add($switchQuad);
					$switchQuad->setX($width / 2 - 17);
					$switchQuad->setZ(0.2);
					$switchQuad->setSubStyle($switchQuad::SUBSTYLE_Validate_Step2);
					$switchQuad->setSize(3.8, 3.8);
					$switchQuad->setAction(self::ACTION_START_SWITCH_VOTE . '.' . $map->uid);
					$description = 'Start Map-Switch Vote: $<' . $map->name . '$>';
					$switchQuad->addTooltipLabelFeature($descriptionLabel, $description);
				} else {
					// Switch Map Voting for Player
					$switchLabel = new Label_Button();
					$mapFrame->add($switchLabel);
					$switchLabel->setX($width / 2 - 7);
					$switchLabel->setZ(0.2);
					$switchLabel->setSize(3, 3);
					$switchLabel->setTextSize(2);
					$switchLabel->setText('»');
					$switchLabel->setTextColor('0f0');
					$switchLabel->setAction(self::ACTION_START_SWITCH_VOTE . '.' . ($map->uid));
					$description = 'Start Map-Switch Vote: $<' . $map->name . '$>';
					$switchLabel->addTooltipLabelFeature($descriptionLabel, $description);
				}
			}

			// Display Karma bar
			if ($karmaPlugin) {
				$karma = $karmaPlugin->getMapKarma($map);
				$votes = $karmaPlugin->getMapVotes($map);
				if (is_numeric($karma)) {
					if ($this->maniaControl->settingManager->getSettingValue($karmaPlugin, $karmaPlugin::SETTING_NEWKARMA)) {
						$karmaText = '  ' . round($karma * 100.) . '% (' . $votes['count'] . ')';
					} else {
						$min  = 0;
						$plus = 0;
						foreach ($votes as $vote) {
							if (isset($vote->vote)) {
								if ($vote->vote != 0.5) {
									if ($vote->vote < 0.5) {
										$min = $min + $vote->count;
									} else {
										$plus = $plus + $vote->count;
									}
								}
							}
						}
						$endKarma  = $plus - $min;
						$karmaText = '  ' . $endKarma . ' (' . $votes['count'] . 'x / ' . round($karma * 100.) . '%)';
					}

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
					$karmaLabel->setText($karmaText);
				}
			}

			$y -= 4;
			$id++;
			$index++;
		}

		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, self::WIDGET_NAME);
	}

	/**
	 * Get the Chunk Index with the given Page Index
	 *
	 * @param int $pageIndex
	 * @return int
	 */
	private function getChunkIndexFromPageNumber($pageIndex) {
		$mapsCount  = $this->maniaControl->mapManager->getMapsCount();
		$pagesCount = ceil($mapsCount / self::MAX_MAPS_PER_PAGE);
		if ($pageIndex > $pagesCount - 1) {
			$pageIndex = $pagesCount - 1;
		}
		return floor($pageIndex / self::MAX_PAGES_PER_CHUNK);
	}

	/**
	 * Calculate the First Map Index to show for the given Chunk
	 *
	 * @param int $chunkIndex
	 * @return int
	 */
	private function getChunkMapsBeginIndex($chunkIndex) {
		return $chunkIndex * self::MAX_PAGES_PER_CHUNK * self::MAX_MAPS_PER_PAGE;
	}

	/**
	 * Builds the confirmation frame
	 *
	 * @param ManiaLink $maniaLink
	 * @param float     $y
	 * @param bool      $mapUid
	 * @param bool      $erase
	 * @return Frame
	 */
	public function buildConfirmFrame(Manialink $maniaLink, $y, $mapUid, $erase = false) {
		// TODO: get rid of the confirm frame to decrease xml size & network usage

		$width        = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
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
		$label->setText('Sure?');
		$label->setTextSize(1);
		$label->setScale(0.90);
		$label->setX(-1.3);

		$buttLabel = new Label_Button();
		$confirmFrame->add($buttLabel);
		$buttLabel->setPosition(3.2, 0.4, 0.2);
		$buttLabel->setSize(3, 3);

		if (!$erase) {
			$quad->setAction(self::ACTION_SWITCH_MAP . '.' . $mapUid);
			$buttLabel->setText('»');
			$buttLabel->setTextColor('0f0');
			$buttLabel->setTextSize(2);
		} else {
			$buttLabel->setTextSize(1);
			$buttLabel->setText('x');
			$buttLabel->setTextColor('a00');
			$quad->setAction(self::ACTION_ERASE_MAP . '.' . $mapUid);
		}
		return $confirmFrame;
	}

	/**
	 * Unset the player if he opened another Main Widget
	 *
	 * @param Player $player
	 * @param string $openedWidget
	 */
	public function handleWidgetOpened(Player $player, $openedWidget) {
		// unset when another main widget got opened
		if ($openedWidget !== self::WIDGET_NAME) {
			$player->destroyCache($this, self::CACHE_CURRENT_PAGE);
		}
	}

	/**
	 * Close the widget
	 *
	 * @param Player $player
	 */
	public function closeWidget(Player $player) {
		// TODO: resolve duplicate with 'playerCloseWidget'
		$player->destroyCache($this, self::CACHE_CURRENT_PAGE);
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId);

		if (count($actionArray) <= 2) {
			return;
		}

		$action = $actionArray[0] . '.' . $actionArray[1];
		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		$mapUid = $actionArray[2];

		switch ($action) {
			case self::ACTION_UPDATE_MAP:
				$this->maniaControl->mapManager->updateMap($player, $mapUid);
				$this->showMapList($player);
				break;
			case self::ACTION_ERASE_MAP:
				$this->maniaControl->mapManager->removeMap($player, $mapUid);
				break;
			case self::ACTION_SWITCH_MAP:
				//Don't queue on Map-Change
				$this->maniaControl->mapManager->mapQueue->dontQueueNextMapChange();
				try {
					$this->maniaControl->client->jumpToMapIdent($mapUid);
				} catch (NotInListException $e) {
					$this->maniaControl->chat->sendError("Error on Jumping to Map Ident!");
					break;
				}

				$map = $this->maniaControl->mapManager->getMapByUid($mapUid);

				$message = $player->getEscapedNickname() . ' skipped to Map $z' . $map->getEscapedName() . '!';
				$this->maniaControl->chat->sendSuccess($message);
				$this->maniaControl->log($message, true);

				$this->playerCloseWidget($player);
				break;
			case self::ACTION_START_SWITCH_VOTE:
				/** @var CustomVotesPlugin $votesPlugin */
				$votesPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_CUSTOM_VOTE_PLUGIN);
				$map         = $this->maniaControl->mapManager->getMapByUid($mapUid);

				$message = $player->getEscapedNickname() . '$s started a vote to switch to ' . $map->getEscapedName() . '!';

				$votesPlugin->defineVote('switchmap', "Goto " . $map->name, true, $message);

				$self = $this;
				$votesPlugin->startVote($player, 'switchmap', function ($result) use (&$self, &$votesPlugin, &$map) {
					$self->maniaControl->chat->sendInformation('$sVote Successful -> Map switched!');
					$votesPlugin->undefineVote('switchmap');

					//Don't queue on Map-Change
					$this->maniaControl->mapManager->mapQueue->dontQueueNextMapChange();

					try {
						$self->maniaControl->client->JumpToMapIdent($map->uid);
					} catch (NotInListException $e) {
					}
				});
				break;
			case self::ACTION_QUEUED_MAP:
				$this->maniaControl->mapManager->mapQueue->addMapToMapQueue($callback[1][1], $mapUid);
				$this->showMapList($player);
				break;
			case self::ACTION_UNQUEUE_MAP:
				$this->maniaControl->mapManager->mapQueue->removeFromMapQueue($player, $mapUid);
				$this->showMapList($player);
				break;
			default:
				if (substr($actionId, 0, strlen(self::ACTION_PAGING_CHUNKS)) === self::ACTION_PAGING_CHUNKS) {
					// Paging chunks
					$neededPage = (int)substr($actionId, strlen(self::ACTION_PAGING_CHUNKS));
					$this->showMapList($player, null, $neededPage - 1);
				}
				break;
		}
	}

	/**
	 * Close the widget for
	 *
	 * @param Player $player
	 */
	public function playerCloseWidget(Player $player) {
		$player->destroyCache($this, self::CACHE_CURRENT_PAGE);
		$this->maniaControl->manialinkManager->closeWidget($player);
	}

	/**
	 * Reopen the widget on Map Begin, MapListChanged, etc.
	 */
	public function updateWidget() {
		$players = $this->maniaControl->playerManager->getPlayers();
		foreach ($players as $player) {
			/** @var Player $player */
			$currentPage = $player->getCache($this, self::CACHE_CURRENT_PAGE);
			if ($currentPage !== null) {
				$this->showMapList($player, null, $currentPage);
			}
		}
	}
} 