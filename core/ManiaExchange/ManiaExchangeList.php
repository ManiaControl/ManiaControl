<?php

namespace ManiaControl\ManiaExchange;

use FML\Controls\Frame;
use FML\Controls\Gauge;
use FML\Controls\Label;
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
 * @copyright 2014-2020 ManiaControl Team
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

		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SEARCH_MAPNAME, $this, 'showListCommand');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SEARCH_AUTHOR, $this, 'showListCommand');
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
		//TODO do more clean solution
		if($environment == ""){
			$titleId           = $this->maniaControl->getServer()->titleId;
			//Set Environments on Trackmania
			$game      = explode('@', $titleId);
			$envNumber = ManiaExchangeMapSearch::getEnvironment($game[0]); //TODO enviroment as constant
			if ($envNumber > -1) {
				$environment = $envNumber;
			}
		}

		//Search the Maps
		$mxSearch = new ManiaExchangeMapSearch($this->maniaControl);
		$mxSearch->setAuthorName($author);
		$mxSearch->setEnvironments($environment);
		$mxSearch->setMapName($searchString);
		
		$mxSearch->fetchMapsAsync(function (array $maps) use (&$player) {
			if (!$maps) {
				$this->maniaControl->getChat()->sendError('No maps found, or MX is down!', $player);
				return;
			}
			$this->showManiaExchangeList($maps, $player);
		});

		// show temporary list to wait for Async
		$labelStyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame();
		$maniaLink->addChild($frame);

		$loadingLabel = new Label_Text();
		$frame->addChild($loadingLabel);
		$loadingLabel->setStyle($labelStyle);
		$loadingLabel->setText('Loading maps, please wait ...');
		$loadingLabel->setTextSize(2);

		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $player);
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
		$labelLine->addLabelEntryText('Id', $posX + 3.5, 8);
		$labelLine->addLabelEntryText('Name', $posX + 12.5, 37.5);
		$labelLine->addLabelEntryText('Author', $posX + 59, 43);
		$labelLine->addLabelEntryText('Type', $posX + 103, 14);
		$labelLine->addLabelEntryText('Mood', $posX + 118, 11);
		$labelLine->addLabelEntryText('Last Update', $posX + 130, $width - ($posX + 131));

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
			$labelLine->addLabelEntryText($map->author, $posX + 59, 20, self::ACTION_GET_MAPS_FROM_AUTHOR . '.' . $map->author);
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
			$karmaGauge = $this->maniaControl->getManialinkManager()->getElementBuilder()->buildKarmaGauge(
				$map,
				20,
				10
			);
			if ($karmaGauge) {
				$mapFrame->addChild($karmaGauge);
				$karmaGauge->setX($posX + 87);
			}


			$posY -= 4;
			$index++;
		}

		$searchFrame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMapSearch(self::ACTION_SEARCH_MAPNAME, self::ACTION_SEARCH_AUTHOR);
		$searchFrame->setY($height / 2 - 5);
		$frame->addChild($searchFrame);


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
