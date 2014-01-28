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
use FML\ManiaLink;
use FML\Script\Script;
use KarmaPlugin;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ColorUtil;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;

/**
 * MapList Widget Class
 *
 * @author steeffeen & kremsy
 */
class MapList implements ManialinkPageAnswerListener, CallbackListener {
	/**
	 * Constants
	 */
	const ACTION_UPDATE_MAP     = 'MapList.UpdateMap';
	const ACTION_ERASE_MAP      = 'MapList.EraseMap';
	const ACTION_SWITCH_MAP     = 'MapList.SwitchMap';
	const ACTION_QUEUED_MAP     = 'MapList.QueueMap';
	const ACTION_UNQUEUE_MAP    = 'MapList.UnQueueMap';
	const ACTION_CHECK_UPDATE   = 'MapList.CheckUpdate';
	const ACTION_CLEAR_MAPQUEUE = 'MapList.ClearMapQueue';
	const MAX_MAPS_PER_PAGE     = 15;
	const DEFAULT_KARMA_PLUGIN  = 'KarmaPlugin';

	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $mapListShown = array();

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
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'updateWidget');

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CHECK_UPDATE, $this, 'checkUpdates');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLEAR_MAPQUEUE, $this, 'clearMapQueue');
	}

	/**
	 * Clears the Map Queue
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function clearMapQueue(array  $chatCallback, Player $player) {
		//Clears the Map Queue
		$this->maniaControl->mapManager->mapQueue->clearMapQueue($player);
	}

	/**
	 * Check for Map Updates
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function checkUpdates(array $chatCallback, Player $player) {
		//Update Mx Infos
		$this->maniaControl->mapManager->mxManager->fetchManiaExchangeMapInformations();
		//Reshow the Maplist
		$this->showMapList($player);
	}


	/**
	 * Displayes a MapList on the screen
	 *
	 * @param Player $player
	 */
	public function showMapList(Player $player) {
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		$this->mapListShown[$player->login] = true;

		// Get Maps
		$mapList = $this->maniaControl->mapManager->getMaps();

		$pagesId = '';
		if (count($mapList) > self::MAX_MAPS_PER_PAGE) {
			$pagesId = 'MapListPages';
		}

		//Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->defaultListFrame($script, $pagesId);
		$maniaLink->add($frame);

		//Admin Buttons
		if ($this->maniaControl->authenticationManager->checkPermission($player, MapQueue::SETTING_PERMISSION_CLEAR_MAPQUEUE)) {
			//Clear Map-Queue
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

			//Check Update
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
		$array = array('Id' => $x + 5, 'Mx Id' => $x + 10, 'Map Name' => $x + 20, 'Author' => $x + 68, 'Karma' => $x + 115, 'Actions' => $width / 2 - 15);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		// Predefine Description Label
		$descriptionLabel = new Label();
		$frame->add($descriptionLabel);
		$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
		$descriptionLabel->setPosition($x + 10, -$height / 2 + 5);
		$descriptionLabel->setSize($width * 0.7, 4);
		$descriptionLabel->setTextSize(2);
		$descriptionLabel->setVisible(false);

		$queuedMaps = $this->maniaControl->mapManager->mapQueue->getQueuedMapsRanking();
		/**
		 * @var KarmaPlugin $karmaPlugin
		 */
		$karmaPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_KARMA_PLUGIN);

		$id         = 1;
		$y          = $height / 2 - 10;
		$pageFrames = array();
		/**
		 * @var Map $map
		 */
		foreach($mapList as $map) {
			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				if (!empty($pageFrames)) {
					$pageFrame->setVisible(false);
				}
				array_push($pageFrames, $pageFrame);
				$y = $height / 2 - 10;
				$script->addPage($pageFrame, count($pageFrames), $pagesId);
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

			if ($this->maniaControl->mapManager->getCurrentMap() === $map) {
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
				$mxQuad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON));
				$mxQuad->setImageFocus($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_MOVER));
				$mxQuad->setX($x + 65);
				$mxQuad->setUrl($map->mx->pageurl);
				$mxQuad->setZ(0.01);
				$script->addTooltip($mxQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => "View $<" . $map->name . "$> on Mania-Exchange"));

				if ($map->updateAvailable()) {
					$mxQuad = new Quad();
					$mapFrame->add($mxQuad);
					$mxQuad->setSize(3, 3);
					$mxQuad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_GREEN));
					$mxQuad->setImageFocus($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_GREEN_MOVER));
					$mxQuad->setX($x + 62);
					$mxQuad->setUrl($map->mx->pageurl);
					$mxQuad->setZ(0.01);
					$script->addTooltip($mxQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => "Update of $<" . $map->name . "$> available on Mania-Exchange"));

					//Update Button
					if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
						$mxQuad->setAction(self::ACTION_UPDATE_MAP . '.' . $map->uid);
					}
				}

			}

			// Display Maps
			$array  = array($id => $x + 5, $mxId => $x + 10, Formatter::stripDirtyCodes($map->name) => $x + 20, $map->authorNick => $x + 68);
			$labels = $this->maniaControl->manialinkManager->labelLine($mapFrame, $array);
			$script->addTooltip($labels[3], $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => '$<' . $map->name . '$> made by $<' . $map->authorLogin . '$>'));

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

				//Checks if the Player who openend the Widget has queued the map
				$queuer = $this->maniaControl->mapManager->mapQueue->getQueuer($map->uid);
				if ($queuer->login == $player->login) {
					$script->addTooltip($label, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => 'Remove $<' . $map->name . '$> from the Map Queue'));
					$label->setAction(self::ACTION_UNQUEUE_MAP . '.' . $map->uid);
				} else {
					$script->addTooltip($label, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => '$<' . $map->name . '$> is on Map-Queue Position: ' . $queuedMaps[$map->uid]));
				}

			} else {
				// Map-Queue-Map-Button
				$queueLabel = new Label_Button();
				$mapFrame->add($queueLabel);
				$queueLabel->setX($width / 2 - 15);
				$queueLabel->setZ(0.2);
				$queueLabel->setSize(3, 3);
				$queueLabel->setAction(self::ACTION_QUEUED_MAP . '.' . $map->uid);
				$queueLabel->setText('+');
				$queueLabel->setTextColor('09f');

				$script->addTooltip($queueLabel, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => 'Add $<' . $map->name . '$> to the Map Queue'));
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
				$script->addToggle($eraseLabel, $confirmFrame);
				$script->addTooltip($eraseLabel, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => 'Remove Map: $<' . $map->name . '$>'));

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
				$script->addToggle($switchLabel, $confirmFrame);

				$script->addTooltip($switchLabel, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => 'Switch Directly to Map: $<' . $map->name . '$>'));

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
	 * @param           $y
	 * @param           $id
	 * @param bool      $mapUid
	 * @return Frame
	 */
	public function buildConfirmFrame(Manialink $maniaLink, $y, $id, $mapUid = false) {
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
		} else {
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
	 * @param array $callback
	 */
	public function handleWidgetOpened(array $callback) {
		$player       = $callback[1];
		$openedWidget = $callback[2];
		//unset when another main widget got opened
		if ($openedWidget != 'MapList') {
			unset($this->mapListShown[$player->login]);
		}
	}

	/**
	 * Closes the widget
	 *
	 * @param array $callback
	 */
	public function closeWidget(array $callback) {
		$player = $callback[1];
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
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId);
		if (count($actionArray) <= 2) {
			return;
		}

		$action = $actionArray[0] . '.' . $actionArray[1];
		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		$mapId  = (int)$actionArray[2];

		switch($action) {
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
				} catch(\Exception $e) {
					$this->maniaControl->chat->sendError("Error while Jumping to Map Index");
					break;
				}
				$mapList = $this->maniaControl->mapManager->getMaps();
				$map     = $mapList[$mapId];

				$message = '$<' . $player->nickname . '$> skipped to Map $z$<' . $map->name . '$>!';
				$this->maniaControl->chat->sendSuccess($message);
				$this->maniaControl->log($message, true);

				$this->playerCloseWidget($player);
				break;
			case self::ACTION_QUEUED_MAP:
				$this->maniaControl->mapManager->mapQueue->addMapToMapQueue($callback[1][1], $actionArray[2]);
				break;
			case self::ACTION_UNQUEUE_MAP:
				$this->maniaControl->mapManager->mapQueue->removeFromMapQueue($player, $actionArray[2]);
				break;
		}
	}

	/**
	 * Reopen the widget on Map Begin, MapListChanged, etc.
	 *
	 * @param array $callback
	 */
	public function updateWidget(array $callback) {
		foreach($this->mapListShown as $login => $shown) {
			if ($shown) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				if ($player != null) {
					$this->showMapList($player);
				} else {
					unset($this->mapListShown[$login]);
				}
			}
		}
	}
} 