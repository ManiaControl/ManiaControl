<?php

namespace ManiaControl\ManiaExchange;

use FML\Components\CheckBox;
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
use ManiaControl\Manialinks\LabelLine;
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
 * @copyright 2014-2017 ManiaControl Team
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
	const ACTION_TOGGLE_MP4           = 'ManiaExchangeList.ToggleMp4';
	const MAX_MX_MAPS_PER_PAGE        = 14;
	const CACHE_SHOWMP4ONLY           = 'ManiaExchangeList.Mp4Only';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $mapListShown = array();

	/**
	 * Construct a new MX List instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SEARCH_MAPNAME, $this, 'showListCommand');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SEARCH_AUTHOR, $this, 'showListCommand');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_TOGGLE_MP4, $this, 'toggleMP4MapsOnly');
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
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
		$mapId  = (int) $actionArray[2];


		switch ($action) {
			case self::ACTION_GET_MAPS_FROM_AUTHOR:
				$callback[1][2] = 'auth:' . $actionArray[2];
				$this->showListCommand($callback, $player);
				break;
			case self::ACTION_ADD_MAP:
				$this->maniaControl->getMapManager()->addMapFromMx($mapId, $player->login);
				break;
		}
	}

	/**
	 * Shows the List
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function showListCommand(array $chatCallback, Player $player) {
		$this->mapListShown[$player->login] = true;
		$params                             = explode(' ', $chatCallback[1][2]);
		$searchString                       = '';
		$author                             = '';
		$environment                        = '';
		if (count($params) >= 1) {
			foreach ($params as $param) {
				if ($param === '/xlist' || $param === MapCommands::ACTION_OPEN_XLIST) {
					continue;
				}
				if ($param === self::ACTION_SEARCH_MAPNAME) {
					$searchString = $chatCallback[1][3][0]['Value'];
				} else if ($param === self::ACTION_SEARCH_AUTHOR) {
					$author = $chatCallback[1][3][0]['Value'];
				} else if (strtolower(substr($param, 0, 5)) === 'auth:') {
					$author = substr($param, 5);
				} else if (strtolower(substr($param, 0, 4)) === 'env:') {
					$environment = substr($param, 4);
				} else {
					if (!$searchString) {
						$searchString = $param;
					} else {
						// concatenate words in name
						$searchString .= '%20' . $param;
					}
				}
			}
		}

		$this->getMXMapsAndShowList($player, $author, $environment, $searchString);
	}

	/**
	 * Gets MX Maps and displays maplist
	 *
	 * @param \ManiaControl\Players\Player $player
	 * @param string                       $author
	 * @param string                       $environment
	 * @param string                       $searchString
	 */
	private function getMXMapsAndShowList(Player $player, $author = '', $environment = '', $searchString = '') {
		//Search the Maps
		$mxSearch = new ManiaExchangeMapSearch($this->maniaControl);
		$mxSearch->setAuthorName($author);
		$mxSearch->setEnvironments($environment);
		$mxSearch->setMapName($searchString);
		if ($player->getCache($this, self::CACHE_SHOWMP4ONLY)) {
			$mxSearch->setMapGroup(2);
		}
		$mxSearch->fetchMapsAsync(function (array $maps) use (&$player) {
			if (!$maps) {
				$this->maniaControl->getChat()->sendError('No maps found, or MX is down!', $player->login);
				return;
			}
			$this->showManiaExchangeList($maps, $player);
		});
	}


	/**
	 * Display the Mania Exchange List
	 *
	 * @param MXMapInfo[] $maps
	 * @param Player      $player
	 * @internal param array $chatCallback
	 */
	private function showManiaExchangeList(array $maps, Player $player) {
		// Start offsets
		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();
		$posX   = -$width / 2;
		$posY   = $height / 2;

		//Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script, $paging);
		$maniaLink->addChild($frame);

		//Predefine description Label
		$descriptionLabel = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultDescriptionLabel();
		$frame->addChild($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->addChild($headFrame);
		$headFrame->setY($posY - 12);

		$labelLine = new LabelLine($headFrame);
		$labelLine->addLabelEntryText('Id', $posX + 3.5, 9);
		$labelLine->addLabelEntryText('Name', $posX + 12.5, 38.5);
		$labelLine->addLabelEntryText('Author', $posX + 59, 44);
		$labelLine->addLabelEntryText('Type', $posX + 103, 15);
		$labelLine->addLabelEntryText('Mood', $posX + 118, 12);
		$labelLine->addLabelEntryText('Last Update', $posX + 130, $width - ($posX + 130));

		$labelLine->render();

		$index     = 0;
		$posY      = $height / 2 - 16;
		$pageFrame = null;

		foreach ($maps as $map) {
			//TODO order possibilities
			if ($index % self::MAX_MX_MAPS_PER_PAGE === 0) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$posY = $height / 2 - 16;
				$paging->addPageControl($pageFrame);
			}

			// Map Frame
			$mapFrame = new Frame();
			$pageFrame->addChild($mapFrame);

			if ($index % 2 === 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$mapFrame->addChild($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(-0.1);
			}

			$time      = Formatter::timeElapsedString(strtotime($map->updated));
			$labelLine = new LabelLine($mapFrame);

			$labelLine->addLabelEntryText($map->id, $posX + 3.5, 9);
			$labelLine->addLabelEntryText($map->name, $posX + 12.5, 38.5);
			$labelLine->addLabelEntryText($map->author, $posX + 59, 44, self::ACTION_GET_MAPS_FROM_AUTHOR . '.' . $map->author);
			$labelLine->addLabelEntryText(str_replace('Arena', '', $map->maptype), $posX + 103, 15);
			$labelLine->addLabelEntryText($map->mood, $posX + 118, 12);
			$labelLine->addLabelEntryText($time, $posX + 130, $width - ($posX + 130));

			$labelLine->setPrefix('$s');
			$labelLine->render();

			$mapFrame->setY($posY);

			$mxQuad = new Quad();
			$mapFrame->addChild($mxQuad);
			$mxQuad->setSize(3, 3);
			$mxQuad->setImageUrl($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON));
			$mxQuad->setImageFocusUrl($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_MOVER));
			$mxQuad->setX($posX + 56);
			$mxQuad->setUrl($map->pageurl);
			$mxQuad->setZ(0.01);
			$description = 'View $<' . $map->name . '$> on Mania-Exchange';
			$mxQuad->addTooltipLabelFeature($descriptionLabel, $description);

			if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
				$addQuad = new Quad_Icons64x64_1();
				$mapFrame->addChild($addQuad);
				$addQuad->setX($posX + 53);
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
				$mapFrame->addChild($awardQuad);
				$awardQuad->setSize(3, 3);
				$awardQuad->setSubStyle($awardQuad::SUBSTYLE_OfficialRace);
				$awardQuad->setX($posX + 97);
				$awardQuad->setZ(0.01);

				$awardLabel = new Label_Text();
				$mapFrame->addChild($awardLabel);
				$awardLabel->setX($posX + 98.5);
				$awardLabel->setHorizontalAlign($awardLabel::LEFT);
				$awardLabel->setText($map->awards);
				$awardLabel->setTextSize(1.3);
			}

			//Map Karma
			$karma     = $map->ratingVoteAverage / 100;
			$voteCount = $map->ratingVoteCount;
			if (is_numeric($karma) && $voteCount > 0) {
				$karmaGauge = new Gauge();
				$mapFrame->addChild($karmaGauge);
				$karmaGauge->setZ(2);
				$karmaGauge->setX($posX + 89);
				$karmaGauge->setSize(16.5, 9);
				$karmaGauge->setDrawBackground(false);
				$karma = floatval($karma);
				$karmaGauge->setRatio($karma + 0.15 - $karma * 0.15);
				$karmaColor = ColorUtil::floatToStatusColor($karma);
				$karmaGauge->setColor($karmaColor . '9');

				$karmaLabel = new Label();
				$mapFrame->addChild($karmaLabel);
				$karmaLabel->setZ(2);
				$karmaLabel->setX($posX + 89);
				$karmaLabel->setSize(16.5 * 0.9, 5);
				$karmaLabel->setTextSize(0.9);
				$karmaLabel->setTextColor('000');
				$karmaLabel->setText('  ' . round($karma * 100.) . '% (' . $voteCount . ')');
			}


			$posY -= 4;
			$index++;
		}

		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition(-$width / 2 + 5, $height / 2 - 5);
		$label->setHorizontalAlign($label::LEFT);
		$label->setTextSize(1.3);
		$label->setText('Search: ');

		$entry = new Entry();
		$frame->addChild($entry);
		$entry->setStyle(Label_Text::STYLE_TextValueSmall);
		$entry->setHorizontalAlign($entry::LEFT);
		$entry->setPosition(-$width / 2 + 15, $height / 2 - 5);
		$entry->setTextSize(1);
		$entry->setSize($width * 0.25, 4);
		$entry->setName('SearchString');


		//Search for Map-Name
		$label = new Label_Button();
		$frame->addChild($label);
		$label->setPosition(-$width / 2 + 63, $height / 2 - 5);
		$label->setText('MapName');
		$label->setTextSize(1.3);

		$quad = new Quad_BgsPlayerCard();
		$frame->addChild($quad);
		$quad->setPosition(-$width / 2 + 63, $height / 2 - 5);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize(18, 5);
		$quad->setAction(self::ACTION_SEARCH_MAPNAME);
		$quad->setZ(-0.1);

		//Search for Author
		$label = new Label_Button();
		$frame->addChild($label);
		$label->setPosition(-$width / 2 + 82, $height / 2 - 5);
		$label->setText('Author');
		$label->setTextSize(1.3);

		$quad = new Quad_BgsPlayerCard();
		$frame->addChild($quad);
		$quad->setPosition(-$width / 2 + 82, $height / 2 - 5);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize(18, 5);
		$quad->setAction(self::ACTION_SEARCH_AUTHOR);
		$quad->setZ(-0.1);

		//Seach for MP4Maps
		$quad = new Quad();
		$quad->setPosition($width / 2 - 30, $height / 2 - 5, -0.01)->setSize(4, 4);
		$checkBox = new CheckBox(self::ACTION_TOGGLE_MP4, $player->getCache($this, self::CACHE_SHOWMP4ONLY), $quad);
		$quad->setAction(self::ACTION_TOGGLE_MP4);
		$frame->addChild($checkBox);

		$label = new Label_Button();
		$frame->addChild($label);
		$label->setPosition($width / 2 - 28, $height / 2 - 5);
		$label->setText('Only MP4-Maps');
		$label->setTextSize(1.3);
		$label->setHorizontalAlign($label::LEFT);


		// render and display xml
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $player, 'ManiaExchangeList');
	}

	/**
	 * Toggle to view mp4 maps only for a player
	 *
	 * @param array                        $callback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function toggleMP4MapsOnly(array $callback, Player $player) {
		if ($player->getCache($this, self::CACHE_SHOWMP4ONLY) === true) {
			$player->setCache($this, self::CACHE_SHOWMP4ONLY, false);
		} else {
			$player->setCache($this, self::CACHE_SHOWMP4ONLY, true);
		}
		$this->getMXMapsAndShowList($player);
	}

	/**
	 * Unset the player if he opened another Main Widget
	 *
	 * @param Player $player
	 * @param        $openedWidget
	 */
	public function handleWidgetOpened(Player $player, $openedWidget) {
		//unset when another main widget got opened
		if ($openedWidget !== 'ManiaExchangeList') {
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
