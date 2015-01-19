<?php

namespace ManiaControl\ManiaExchange;

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
 * @copyright 2014-2015 ManiaControl Team
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

		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SEARCH_MAPNAME, $this, 'showList');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SEARCH_AUTHOR, $this, 'showList');
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
				$this->showList($callback, $player);
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
	public function showList(array $chatCallback, Player $player) {
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

		// search for matching maps
		$this->maniaControl->getMapManager()->getMXManager()->fetchMapsAsync(function (array $maps) use (&$player) {
			if (!$maps) {
				$this->maniaControl->getChat()->sendError('No maps found, or MX is down!', $player->login);
				return;
			}
			$this->showManiaExchangeList($maps, $player);
		}, $searchString, $author, $environment);
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
		$maniaLink->add($frame);

		//Predefine description Label
		$descriptionLabel = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($posY - 12);
		$array = array('$oId' => $posX + 3.5, '$oName' => $posX + 12.5, '$oAuthor' => $posX + 59, '$oKarma' => $posX + 85, '$oType' => $posX + 103, '$oMood' => $posX + 118, '$oLast Update' => $posX + 130);
		$this->maniaControl->getManialinkManager()->labelLine($headFrame, $array);

		$index     = 0;
		$posY      = $height / 2 - 16;
		$pageFrame = null;

		foreach ($maps as $map) {
			//TODO order possibilities
			if ($index % self::MAX_MX_MAPS_PER_PAGE === 0) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$posY = $height / 2 - 16;
				$paging->addPage($pageFrame);
			}

			// Map Frame
			$mapFrame = new Frame();
			$pageFrame->add($mapFrame);

			if ($index % 2 === 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$mapFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			$time        = Formatter::time_elapsed_string(strtotime($map->updated));
			$array       = array('$s' . $map->id => $posX + 3.5, '$s' . $map->name => $posX + 12.5, '$s' . $map->author => $posX + 59, '$s' . str_replace('Arena', '', $map->maptype) => $posX + 103, '$s' . $map->mood => $posX + 118, '$s' . $time => $posX + 130);
			$labels      = $this->maniaControl->getManialinkManager()->labelLine($mapFrame, $array);
			$authorLabel = $labels[2];
			$authorLabel->setAction(self::ACTION_GET_MAPS_FROM_AUTHOR . '.' . $map->author);

			$mapFrame->setY($posY);

			$mxQuad = new Quad();
			$mapFrame->add($mxQuad);
			$mxQuad->setSize(3, 3);
			$mxQuad->setImage($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON));
			$mxQuad->setImageFocus($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_MOVER));
			$mxQuad->setX($posX + 56);
			$mxQuad->setUrl($map->pageurl);
			$mxQuad->setZ(0.01);
			$description = 'View $<' . $map->name . '$> on Mania-Exchange';
			$mxQuad->addTooltipLabelFeature($descriptionLabel, $description);

			if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)
			) {
				$addQuad = new Quad_Icons64x64_1();
				$mapFrame->add($addQuad);
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
				$mapFrame->add($awardQuad);
				$awardQuad->setSize(3, 3);
				$awardQuad->setSubStyle($awardQuad::SUBSTYLE_OfficialRace);
				$awardQuad->setX($posX + 97);
				$awardQuad->setZ(0.01);

				$awardLabel = new Label_Text();
				$mapFrame->add($awardLabel);
				$awardLabel->setX($posX + 98.5);
				$awardLabel->setHAlign($awardLabel::LEFT);
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
				$karmaGauge->setX($posX + 89);
				$karmaGauge->setSize(16.5, 9);
				$karmaGauge->setDrawBg(false);
				$karma = floatval($karma);
				$karmaGauge->setRatio($karma + 0.15 - $karma * 0.15);
				$karmaColor = ColorUtil::floatToStatusColor($karma);
				$karmaGauge->setColor($karmaColor . '9');

				$karmaLabel = new Label();
				$mapFrame->add($karmaLabel);
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
		$frame->add($label);
		$label->setPosition(-$width / 2 + 5, $height / 2 - 5);
		$label->setHAlign($label::LEFT);
		$label->setTextSize(1.3);
		$label->setText('Search: ');

		$entry = new Entry();
		$frame->add($entry);
		$entry->setStyle(Label_Text::STYLE_TextValueSmall);
		$entry->setHAlign($entry::LEFT);
		$entry->setPosition(-$width / 2 + 15, $height / 2 - 5);
		$entry->setTextSize(1);
		$entry->setSize($width * 0.25, 4);
		$entry->setName('SearchString');


		//Search for Map-Name
		$label = new Label_Button();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 63, $height / 2 - 5);
		$label->setText('MapName');
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
		$label->setText('Author');
		$label->setTextSize(1.3);

		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setPosition(-$width / 2 + 82, $height / 2 - 5, 0.01);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setSize(18, 5);
		$quad->setAction(self::ACTION_SEARCH_AUTHOR);

		// render and display xml
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $player, 'ManiaExchangeList');
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
