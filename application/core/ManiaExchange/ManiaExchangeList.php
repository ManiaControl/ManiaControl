<?php

namespace ManiaControl\ManiaExchange;

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
use FML\Script\Features\Paging;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Maps\MapCommands;
use ManiaControl\Maps\MapManager;
use ManiaControl\Players\Player;
use ManiaControl\Utils\ColorUtil;
use ManiaControl\Utils\Formatter;

/**
 * ManiaExchange List Widget Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaExchangeList implements CallbackListener, ManialinkPageAnswerListener {
	/*
	 * Constants
	 */
	const ACTION_ADD_MAP              = 'ManiaExchangeList.AddMap';
	const ACTION_SEARCH_MAPNAME       = 'ManiaExchangeList.SearchMapName';
	const ACTION_SEARCH_AUTHOR        = 'ManiaExchangeList.SearchAuthor';
	const ACTION_GET_MAPS_FROM_AUTHOR = 'ManiaExchangeList.GetMapsFromAuthor';
	const MAX_MX_MAPS_PER_PAGE        = 14;

	/*
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

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SEARCH_MAPNAME, $this, 'showList');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SEARCH_AUTHOR, $this, 'showList');
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

		switch ($action) {
			case self::ACTION_GET_MAPS_FROM_AUTHOR:
				$callback[1][2] = 'auth:' . $actionArray[2];
				$this->showList($callback, $player);
				break;
			case self::ACTION_ADD_MAP:
				$this->maniaControl->mapManager->addMapFromMx($mapId, $player->login);
				break;
		}
	}

	/**
	 * Shows the List
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function showList(array $chatCallback, Player $player) {
		$this->mapListShown[$player->login] = true;
		$params                             = explode(' ', $chatCallback[1][2]);
		$searchString                       = '';
		$author                             = '';
		$environment                        = '';
		if (count($params) >= 1) {
			foreach ($params as $param) {
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
		$self = $this;
		$this->maniaControl->mapManager->mxManager->getMapsAsync(function ($maps) use (&$self, &$player) {
			if (!$maps) {
				$self->maniaControl->chat->sendError('No maps found, or MX is down!', $player->login);
				return;
			}
			$self->showManiaExchangeList($maps, $player);
		}, $searchString, $author, $environment);
	}

	/**
	 * Display the Mania Exchange List
	 *
	 * @param        $maps
	 * @param Player $player
	 * @internal param array $chatCallback
	 */
	private function showManiaExchangeList($maps, Player $player) {
		// Start offsets
		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$x      = -$width / 2;
		$y      = $height / 2;

		//Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $paging);
		$maniaLink->add($frame);

		//Predefine description Label
		$descriptionLabel = $this->maniaControl->manialinkManager->styleManager->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 12);
		$array = array('$oId' => $x + 3.5, '$oName' => $x + 12.5, '$oAuthor' => $x + 59, '$oKarma' => $x + 85, '$oType' => $x + 103, '$oMood' => $x + 118, '$oLast Update' => $x + 130);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$i         = 0;
		$y         = $height / 2 - 16;
		$pageFrame = null;

		foreach ($maps as $map) {
			//TODO order possibilities
			/** @var MxMapInfo $map */
			if ($i % self::MAX_MX_MAPS_PER_PAGE === 0) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$y = $height / 2 - 16;
				$paging->addPage($pageFrame);
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
			$array  = array('$s' . $map->id => $x + 3.5, '$s' . $map->name => $x + 12.5, '$s' . $map->author => $x + 59, '$s' . str_replace("Arena", "", $map->maptype) => $x + 103, '$s' . $map->mood => $x + 118, '$s' . $time => $x + 130);
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
			$mxQuad->setX($x + 56);
			$mxQuad->setUrl($map->pageurl);
			$mxQuad->setZ(0.01);
			$description = 'View $<' . $map->name . '$> on Mania-Exchange';
			$mxQuad->addTooltipLabelFeature($descriptionLabel, $description);

			if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
				$addQuad = new Quad_Icons64x64_1();
				$mapFrame->add($addQuad);
				$addQuad->setX($x + 53);
				$addQuad->setZ(-0.1);
				$addQuad->setSubStyle($addQuad::SUBSTYLE_Add);
				$addQuad->setSize(4, 4);
				$addQuad->setAction(self::ACTION_ADD_MAP . '.' . $map->id);
				$addQuad->setZ(0.01);

				$description = 'Add-Map: $<' . $map->name . '$>';
				$addQuad->addTooltipLabelFeature($descriptionLabel, $description);
			}

			//Award Quad
			if ($map->awards > 0) {
				$awardQuad = new Quad_Icons64x64_1();
				$mapFrame->add($awardQuad);
				$awardQuad->setSize(3, 3);
				$awardQuad->setSubStyle($awardQuad::SUBSTYLE_OfficialRace);
				$awardQuad->setX($x + 97);
				$awardQuad->setZ(0.01);

				$awardLabel = new Label_Text();
				$mapFrame->add($awardLabel);
				$awardLabel->setX($x + 98.5);
				$awardLabel->setHAlign(Control::LEFT);
				$awardLabel->setText($map->awards);
				$awardLabel->setTextSize(1.3);
			}

			//Map Karma
			$karma     = $map->ratingVoteAverage / 100;
			$voteCount = $map->ratingVoteCount;
			if (is_numeric($karma) && $voteCount > 0) {
				$karmaGauge = new Gauge();
				$mapFrame->add($karmaGauge);
				$karmaGauge->setZ(2);
				$karmaGauge->setX($x + 89);
				$karmaGauge->setSize(16.5, 9);
				$karmaGauge->setDrawBg(false);
				$karma = floatval($karma);
				$karmaGauge->setRatio($karma + 0.15 - $karma * 0.15);
				$karmaColor = ColorUtil::floatToStatusColor($karma);
				$karmaGauge->setColor($karmaColor . '9');

				$karmaLabel = new Label();
				$mapFrame->add($karmaLabel);
				$karmaLabel->setZ(2);
				$karmaLabel->setX($x + 89);
				$karmaLabel->setSize(16.5 * 0.9, 5);
				$karmaLabel->setTextSize(0.9);
				$karmaLabel->setTextColor('000');
				$karmaLabel->setAlign(Control::CENTER, Control::CENTER);
				$karmaLabel->setText('  ' . round($karma * 100.) . '% (' . $voteCount . ')');
			}


			$y -= 4;
			$i++;
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
	 * Unset the player if he opened another Main Widget
	 *
	 * @param Player $player
	 * @param        $openedWidget
	 */
	public function handleWidgetOpened(Player $player, $openedWidget) {
		//unset when another main widget got opened
		if ($openedWidget != 'ManiaExchangeList') {
			unset($this->mapListShown[$player->login]);
		}
	}

	/**
	 * Closes the widget
	 *
	 * @param \ManiaControl\Players\Player $player
	 * @internal param array $callback
	 */
	public function closeWidget(Player $player) {
		unset($this->mapListShown[$player->login]);
	}

}