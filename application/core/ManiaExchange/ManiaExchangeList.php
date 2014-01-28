<?php

namespace ManiaControl\ManiaExchange;

use FML\Controls\Control;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Maps\MapCommands;
use ManiaControl\Maps\MapManager;
use ManiaControl\Players\Player;


/**
 * ManiaExchange List Widget Class
 *
 * @author steeffeen & kremsy
 */
class ManiaExchangeList implements CallbackListener, ManialinkPageAnswerListener {
	/**
	 * Constants
	 */
	const ACTION_ADD_MAP              = 'ManiaExchangeList.AddMap';
	const ACTION_SEARCH_MAPNAME       = 'ManiaExchangeList.SearchMapName';
	const ACTION_SEARCH_AUTHOR        = 'ManiaExchangeList.SearchAuthor';
	const ACTION_GET_MAPS_FROM_AUTHOR = 'ManiaExchangeList.GetMapsFromAuthor';
	const MAX_MX_MAPS_PER_PAGE        = 14;

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

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SEARCH_MAPNAME, $this, 'showManiaExchangeList');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SEARCH_AUTHOR, $this, 'showManiaExchangeList');
	}


	public function showList(array $chatCallback, Player $player) {
		$this->showManiaExchangeList($chatCallback, $player);
	}

	/**
	 * Display the Mania Exchange List
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function showManiaExchangeList(array $chatCallback, Player $player) {
		$this->mapListShown[$player->login] = true;

		$params = explode(' ', $chatCallback[1][2]);

		$searchString = '';
		$author       = '';
		$environment  = '';
		if (count($params) >= 1) {
			foreach($params as $param) {
				if ($param == '/xlist' || $param == MapCommands::ACTION_OPEN_XLIST) {
					continue;
				}
				if ($param == self::ACTION_SEARCH_MAPNAME) {
					$searchString = $chatCallback[1][3][0]['Value'];
				} else if ($param == self::ACTION_SEARCH_AUTHOR) {
					$author = $chatCallback[1][3][0]['Value'];
				} else if (strtolower(substr($param, 0, 5)) == 'auth:') {
					$author = substr($param, 5);
				} else if (strtolower(substr($param, 0, 4)) == 'env:') {
					$environment = substr($param, 4);
				} else {
					if ($searchString == '') {
						$searchString = $param;
					} else { // concatenate words in name
						$searchString .= '%20' . $param;
					}
				}
			}
		}

		// search for matching maps
		$maps = $this->maniaControl->mapManager->mxManager->getMaps($searchString, $author, $environment);

		// check if there are any results
		if ($maps == null) {
			$this->maniaControl->chat->sendError('No maps found, or MX is down!', $player->login);
			return;
		}

		// Start offsets
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$x      = -$width / 2;
		$y      = $height / 2;

		//Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();

		$pagesId = 'MxListPages';

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->defaultListFrame($script, $pagesId);
		$maniaLink->add($frame);

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
		$headFrame->setY($y - 12);
		$array = array('$oMx Id' => $x + 5, '$oName' => $x + 17, '$oAuthor' => $x + 65, '$oType' => $x + 100, '$oMood' => $x + 115, '$oLast Update' => $x + 130);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$i          = 0;
		$y          = $height / 2 - 16;
		$pageFrames = array();
		foreach($maps as $map) { //TODO order possabilities
			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				if (!empty($pageFrames)) {
					$pageFrame->setVisible(false);
				}
				array_push($pageFrames, $pageFrame);
				$y = $height / 2 - 16;
				$script->addPage($pageFrame, count($pageFrames), $pagesId);
			}

			// Map Frame
			$mapFrame = new Frame();
			$pageFrame->add($mapFrame);

			if ($i % 2 == 0) {
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

			//TODO permission Clear Jukebox
			if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
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
			if ($map->awards > 0) {
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
			if ($i % self::MAX_MX_MAPS_PER_PAGE == 0) {
				unset($pageFrame);
			}
		}

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 5, $height / 2 - 5);
		$label->setHAlign(Control::LEFT);
		$label->setTextSize(1.3);
		$label->setText("Search:");

		$entry = new Entry();
		$frame->add($entry);
		$entry->setStyle(Label_Text::STYLE_TextValueSmall);
		$entry->setHAlign(Control::LEFT);
		$entry->setPosition(-$width / 2 + 15, $height / 2 - 5);
		$entry->setTextSize(1);
		$entry->setSize($width * 0.25, 4);
		$entry->setName('SearchString');


		//Search for Map-Name
		$label = new Label_Button();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 63, $height / 2 - 5);
		$label->setText("MapName");
		$label->setTextSize(1.3);

		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setPosition(-$width / 2 + 63, $height / 2 - 5, 0.01);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize(18, 5);
		$quad->setAction(self::ACTION_SEARCH_MAPNAME);

		//Search for Author
		$label = new Label_Button();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 82, $height / 2 - 5);
		$label->setText("Author");
		$label->setTextSize(1.3);

		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setPosition(-$width / 2 + 82, $height / 2 - 5, 0.01);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize(18, 5);
		$quad->setAction(self::ACTION_SEARCH_AUTHOR);

		// render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'ManiaExchangeList');
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
			case self::ACTION_GET_MAPS_FROM_AUTHOR:
				$callback[1][2] = 'auth:' . $actionArray[2];
				$this->showManiaExchangeList($callback, $player);
				break;
			case self::ACTION_ADD_MAP:
				$this->maniaControl->mapManager->addMapFromMx($mapId, $player->login);
				break;
		}
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
		if ($openedWidget != 'ManiaExchangeList') {
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


} 