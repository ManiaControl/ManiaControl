<?php

namespace ManiaControl\Maps;

use FML\Controls\Control;
use FML\Controls\Entry;
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
	const ACTION_ADD_MAP              = 'MapList.AddMap';
	const ACTION_SEARCH_MAPNAME       = 'MapList.SearchMapName';
	const ACTION_SEARCH_AUTHOR        = 'MapList.SearchAuthor';
	const ACTION_GET_MAPS_FROM_AUTHOR = 'MapList.GetMapsFromAuthor';
	const ACTION_ERASE_MAP            = 'MapList.EraseMap';
	const ACTION_SWITCH_MAP           = 'MapList.SwitchMap';
	const ACTION_QUEUED_MAP           = 'MapList.QueueMap';
	const MAX_MAPS_PER_PAGE           = 15;
	const MAX_MX_MAPS_PER_PAGE        = 14;
	const SHOW_MX_LIST                = 1;
	const SHOW_MAP_LIST               = 2;
	const DEFAULT_KARMA_PLUGIN        = 'KarmaPlugin';

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
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(MapQueue::CB_MAPQUEUE_CHANGED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_MAPS_UPDATED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_KARMA_UPDATED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'updateWidget');

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SEARCH_MAPNAME, $this, 'showManiaExchangeList');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SEARCH_AUTHOR, $this, 'showManiaExchangeList');
	}


	/**
	 * Display the Mania Exchange List
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function showManiaExchangeList(array $chatCallback, Player $player) {
		$this->mapListShown[$player->login] = self::SHOW_MX_LIST;

		$params = explode(' ', $chatCallback[1][2]);

		$searchString = '';
		$author       = '';
		$environment  = '';
		if(count($params) >= 1) {
			foreach($params as $param) {
				if($param == '/xlist' || $param == MapCommands::ACTION_OPEN_XLIST) {
					continue;
				}
				if($param == self::ACTION_SEARCH_MAPNAME) {
					$searchString = $chatCallback[1][3][0]['Value'];
				} else if($param == self::ACTION_SEARCH_AUTHOR) {
					$author = $chatCallback[1][3][0]['Value'];
				} else if(strtolower(substr($param, 0, 5)) == 'auth:') {
					$author = substr($param, 5);
				} else if(strtolower(substr($param, 0, 4)) == 'env:') {
					$environment = substr($param, 4);
				} else {
					if($searchString == '') {
						$searchString = $param;
					} else { // concatenate words in name
						$searchString .= '%20' . $param;
					}
				}
			}
		}

		// search for matching maps
		$maps = $this->maniaControl->mapManager->mxInfoSearcher->getMaps($searchString, $author, $environment);

		// check if there are any results
		if($maps == null) {
			$this->maniaControl->chat->sendError('No maps found, or MX is down!', $player->login);
			return;
		}

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame     = $this->buildMainFrame();
		$maniaLink->add($frame);
		$frame->setZ(10);

		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		// Start offsets
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$x      = -$width / 2;
		$y      = $height / 2;

		// Pagers
		// Config
		$pagerSize = 6.;
		$pagesId   = 'MxListPages';

		$pagerPrev = new Quad_Icons64x64_1();
		$frame->add($pagerPrev);
		$pagerPrev->setPosition($width * 0.42, $height * -0.44, 2);
		$pagerPrev->setSize($pagerSize, $pagerSize);
		$pagerPrev->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_ArrowPrev);

		$pagerNext = new Quad_Icons64x64_1();
		$frame->add($pagerNext);
		$pagerNext->setPosition($width * 0.45, $height * -0.44, 2);
		$pagerNext->setSize($pagerSize, $pagerSize);
		$pagerNext->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_ArrowNext);

		$script->addPager($pagerPrev, -1, $pagesId);
		$script->addPager($pagerNext, 1, $pagesId);

		$pageCountLabel = new Label_Text();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign(Control::RIGHT);
		$pageCountLabel->setPosition($width * 0.40, $height * -0.44, 1);
		$pageCountLabel->setStyle($pageCountLabel::STYLE_TextTitle1);
		$pageCountLabel->setTextSize(1.3);
		$script->addPageLabel($pageCountLabel, $pagesId);

		// Predefine Description Label
		$descriptionLabel = new Label();
		$frame->add($descriptionLabel);
		$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
		$descriptionLabel->setPosition($x + 10, -$height / 2 + 5);
		$descriptionLabel->setSize($width * 0.7, 4);
		$descriptionLabel->setTextSize(2);
		$descriptionLabel->setVisible(false);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);
		$array = array('Mx Id' => $x + 5, 'Name' => $x + 17, 'Author' => $x + 65, 'Type' => $x + 100, 'Mood' => $x + 115, 'Last Update' => $x + 130);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$i = 0;
		$y -= 10;
		$pageFrames = array();
		foreach($maps as $map) { //TODO order possabilities
			if(!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				if(!empty($pageFrames)) {
					$pageFrame->setVisible(false);
				}
				array_push($pageFrames, $pageFrame);
				$y = $height / 2 - 10;
				$script->addPage($pageFrame, count($pageFrames), $pagesId);
			}

			// Map Frame
			$mapFrame = new Frame();
			$pageFrame->add($mapFrame);

			if($i % 2 == 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$mapFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			/** @var MxMapInfo $map */
			$time   = Formatter::time_elapsed_string(strtotime($map->updated));
			$array  = array($map->id => $x + 5, $map->name => $x + 17, $map->author => $x + 65, str_replace("Arena", "", $map->maptype) => $x + 100, $map->mood => $x + 115, $time => $x + 130);
			$labels = $this->maniaControl->manialinkManager->labelLine($mapFrame, $array);
			/** @var  Label_Text $authorLabel */
			$authorLabel = $labels[2];
			$authorLabel->setAction(self::ACTION_GET_MAPS_FROM_AUTHOR . '.' . $map->author);

			$mapFrame->setY($y);


			$mxQuad = new Quad();
			$mapFrame->add($mxQuad);
			$mxQuad->setSize(3, 3);
			$mxQuad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON));
			$mxQuad->setImageFocus($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_MOVER));
			$mxQuad->setX($x + 62);
			$mxQuad->setUrl($map->pageurl);
			$mxQuad->setZ(0.01);
			$script->addTooltip($mxQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => "View " . $map->name . " on Mania-Exchange"));

			if($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
				$addQuad = new Quad_Icons64x64_1();
				$mapFrame->add($addQuad);
				$addQuad->setX($x + 59);
				$addQuad->setZ(-0.1);
				$addQuad->setSubStyle($addQuad::SUBSTYLE_Add);
				$addQuad->setSize(4, 4);
				$addQuad->setAction(self::ACTION_ADD_MAP . '.' . $map->id);
				$addQuad->setZ(0.01);

				$script->addTooltip($addQuad, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => 'Add-Map: $<' . $map->name . '$>'));
			}

			//Award Quad
			if($map->awards > 0) {
				$awardQuad = new Quad_Icons64x64_1();
				$mapFrame->add($awardQuad);
				$awardQuad->setSize(3, 3);
				$awardQuad->setSubStyle($awardQuad::SUBSTYLE_OfficialRace);
				$awardQuad->setX($x + 93);
				$awardQuad->setZ(0.01);

				$awardLabel = new Label_Text();
				$mapFrame->add($awardLabel);
				$awardLabel->setX($x + 94.5);
				$awardLabel->setHAlign(Control::LEFT);
				$awardLabel->setText($map->awards);
				$awardLabel->setTextSize(1.3);
			}

			$y -= 4;
			$i++;
			if($i % self::MAX_MX_MAPS_PER_PAGE == 0) {
				unset($pageFrame);
			}
		}

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 5, -$height / 2 + 10);
		$label->setHAlign(Control::LEFT);
		$label->setTextSize(1.3);
		$label->setText("Search:");

		$entry = new Entry();
		$frame->add($entry);
		$entry->setStyle(Label_Text::STYLE_TextValueSmall);
		$entry->setHAlign(Control::LEFT);
		$entry->setPosition(-$width / 2 + 15, -$height / 2 + 10);
		$entry->setTextSize(1);
		$entry->setSize($width * 0.25, 4);
		$entry->setName('SearchString');


		//Search for Map-Name
		$label = new Label_Button();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 63, -$height / 2 + 10);
		$label->setText("MapName");
		$label->setTextSize(1.3);

		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setPosition(-$width / 2 + 63, -$height / 2 + 10, 0.01);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize(18, 5);
		$quad->setAction(self::ACTION_SEARCH_MAPNAME);

		//Search for Author
		$label = new Label_Button();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 82, -$height / 2 + 10);
		$label->setText("Author");
		$label->setTextSize(1.3);

		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setPosition(-$width / 2 + 82, -$height / 2 + 10, 0.01);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize(18, 5);
		$quad->setAction(self::ACTION_SEARCH_AUTHOR);

		// render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}

	/**
	 * Builds the mainFrame
	 *
	 * @return Frame $frame
	 */
	public function buildMainFrame() {
		$width        = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height       = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

		// mainframe
		$frame = new Frame();
		$frame->setSize($width, $height);
		$frame->setPosition(0, 0);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($width * 0.483, $height * 0.467, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		return $frame;
	}

	/**
	 * Displayes a MapList on the screen
	 *
	 * @param Player $player
	 */
	public function showMapList(Player $player) {
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		// Get Maplist
		$mapList = $this->maniaControl->mapManager->getMaps();

		$this->mapListShown[$player->login] = self::SHOW_MAP_LIST;

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame     = $this->buildMainFrame();
		$maniaLink->add($frame);
		$frame->setZ(10);

		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		// Pagers
		// Config
		$pagerSize = 6.;
		$pagesId   = 'MapListPages';

		if(count($mapList) > self::MAX_MAPS_PER_PAGE) {
			$pagerPrev = new Quad_Icons64x64_1();
			$frame->add($pagerPrev);
			$pagerPrev->setPosition($width * 0.42, $height * -0.44, 2);
			$pagerPrev->setSize($pagerSize, $pagerSize);
			$pagerPrev->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_ArrowPrev);

			$pagerNext = new Quad_Icons64x64_1();
			$frame->add($pagerNext);
			$pagerNext->setPosition($width * 0.45, $height * -0.44, 2);
			$pagerNext->setSize($pagerSize, $pagerSize);
			$pagerNext->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_ArrowNext);

			$script->addPager($pagerPrev, -1, $pagesId);
			$script->addPager($pagerNext, 1, $pagesId);

			$pageCountLabel = new Label_Text();
			$frame->add($pageCountLabel);
			$pageCountLabel->setHAlign(Control::RIGHT);
			$pageCountLabel->setPosition($width * 0.40, $height * -0.44, 1);
			$pageCountLabel->setStyle($pageCountLabel::STYLE_TextTitle1);
			$pageCountLabel->setTextSize(1.3);
			$script->addPageLabel($pageCountLabel, $pagesId);
		}

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($height / 2 - 5);
		$x     = -$width / 2;
		$array = array('Id' => $x + 5, 'Mx Id' => $x + 10, 'Map Name' => $x + 20, 'Author' => $x + 68, 'Karma' => $x + 115, 'Actions' => $width / 2 - 15);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		// Predefine Description Label
		$preDefinedDescriptionLabel = new Label();
		$preDefinedDescriptionLabel->setAlign(Control::LEFT, Control::TOP);
		$preDefinedDescriptionLabel->setPosition($x + 10, -$height / 2 + 5);
		$preDefinedDescriptionLabel->setSize($width * 0.7, 4);
		$preDefinedDescriptionLabel->setTextSize(2);
		$preDefinedDescriptionLabel->setVisible(false);

		$queuedMaps = $this->maniaControl->mapManager->mapQueue->getQueuedMapsRanking();
		/**
		 *
		 * @var KarmaPlugin $karmaPlugin
		 */
		$karmaPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_KARMA_PLUGIN);

		$id         = 1;
		$y          = $height / 2 - 10;
		$pageFrames = array();
		/**
		 *
		 * @var Map $map
		 */
		foreach($mapList as $map) {

			if(!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				if(!empty($pageFrames)) {
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

			if($id % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$mapFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			if($this->maniaControl->mapManager->getCurrentMap() === $map) {
				$currentQuad = new Quad_Icons64x64_1();
				$mapFrame->add($currentQuad);
				$currentQuad->setX($x + 3.5);
				$currentQuad->setZ(0.2);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}

			$mxId = '-';
			if(isset($map->mx->id)) {
				$mxId = $map->mx->id;
			}

			// Display Maps
			$array = array($id => $x + 5, $mxId => $x + 10, Formatter::stripDirtyCodes($map->name) => $x + 20, $map->authorNick => $x + 68);
			$this->maniaControl->manialinkManager->labelLine($mapFrame, $array);
			// TODO action detailed map info including mx info

			// MapQueue Description Label
			$descriptionLabel = clone $preDefinedDescriptionLabel;
			$frame->add($descriptionLabel);

			// Map-Queue-Map-Label
			if(isset($queuedMaps[$map->uid])) {
				$label = new Label_Text();
				$mapFrame->add($label);
				$label->setX($width / 2 - 15);
				$label->setAlign(Control::CENTER, Control::CENTER);
				$label->setZ(0.2);
				$label->setTextSize(1.5);
				$label->setText($queuedMaps[$map->uid]);
				$label->setTextColor('fff');
				$descriptionLabel->setText('$<' . $map->name . '$> is on Map-Queue Position: ' . $queuedMaps[$map->uid]);
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

				$descriptionLabel->setText('Add Map to the Map Queue: $<' . $map->name . '$>');
				$script->addTooltip($queueLabel, $descriptionLabel);
			}

			if($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_REMOVE_MAP)) {
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
				//$script->addToggle($eraseLabel, $confirmFrame); //TODO
				$script->addTooltip($eraseLabel, $confirmFrame, Script::OPTION_TOOLTIP_STAYONCLICK);

				// Description Label
				$descriptionLabel = clone $preDefinedDescriptionLabel;
				$frame->add($descriptionLabel);
				$descriptionLabel->setText('Remove Map: $<' . $map->name . '$>');
				$script->addTooltip($eraseLabel, $descriptionLabel);
			}

			if($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
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
				$script->addTooltip($switchLabel, $confirmFrame, Script::OPTION_TOOLTIP_STAYONCLICK); //TODO
				//$script->addToggle($switchLabel, $confirmFrame);

				$descriptionLabel = clone $preDefinedDescriptionLabel;
				$frame->add($descriptionLabel);
				$descriptionLabel->setText('Switch Directly to Map: $<' . $map->name . '$>');
				$script->addTooltip($switchLabel, $descriptionLabel);
			}

			// Display Karma bar
			if($karmaPlugin) {
				$karma = $karmaPlugin->getMapKarma($map);
				$votes = $karmaPlugin->getMapVotes($map);
				if(is_numeric($karma)) {
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
			if($id % self::MAX_MAPS_PER_PAGE == 0) {
				unset($pageFrame);
			}
			$id++;
		}
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
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

		if(!$mapUid) {
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
		if(count($actionArray) <= 2) {
			return;
		}

		$action = $actionArray[0] . '.' . $actionArray[1];
		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		$mapId  = (int)$actionArray[2];

		switch($action) {
			case self::ACTION_GET_MAPS_FROM_AUTHOR:
				$callback[1][2] = 'auth:' . $actionArray[2];
				$this->showManiaExchangeList($callback, $player);
				break;
			case self::ACTION_ADD_MAP:
				$this->maniaControl->mapManager->addMapFromMx($mapId, $player->login);
				break;
			case self::ACTION_ERASE_MAP:
				$this->maniaControl->mapManager->removeMap($mapId, $actionArray[3]);
				$this->showMapList($player);
				break;
			case self::ACTION_SWITCH_MAP:
				$this->maniaControl->client->query('JumpToMapIndex', $mapId);
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
		}
	}

	/**
	 * Reopen the widget on Map Begin, MapListChanged, etc.
	 *
	 * @param array $callback
	 */
	public function updateWidget(array $callback) {
		foreach($this->mapListShown as $login => $shown) {
			if($shown) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				if($player != null) {
					if($shown == self::SHOW_MX_LIST) {
						//TODO
					} else if($shown == self::SHOW_MAP_LIST) {
						$this->showMapList($player);
					}
				} else {
					unset($this->mapListShown[$login]);
				}
			}
		}
	}
} 