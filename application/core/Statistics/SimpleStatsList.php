<?php

namespace ManiaControl\Statistics;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Utils\Formatter;

/**
 * Simple Stats List Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SimpleStatsList implements ManialinkPageAnswerListener, CallbackListener, CommandListener {
	/*
	 * Constants
	 */
	const ACTION_OPEN_STATSLIST = 'SimpleStatsList.OpenStatsList';
	const ACTION_SORT_STATS     = 'SimpleStatsList.SortStats';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $statArray = array();
	private $statsWidth = 0;

	/**
	 * Create a PlayerList Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::ONINIT, $this, 'handleOnInit');
	}

	/**
	 * Add the menu entry
	 */
	public function handleOnInit() {
		$this->maniaControl->commandManager->registerCommandListener('stats', $this, 'command_ShowStatsList', false, 'Shows statistics.');

		// Action Open StatsList
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_STATSLIST, $this, 'command_ShowStatsList');


		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Stats);
		$itemQuad->setAction(self::ACTION_OPEN_STATSLIST);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, true, 14, 'Open Statistics');

		//TODO settings if a stat get shown
		$this->registerStat(PlayerManager::STAT_SERVERTIME, 10, "ST", 20, StatisticManager::STAT_TYPE_TIME);
		$this->registerStat(StatisticCollector::STAT_ON_HIT, 20, "H");
		$this->registerStat(StatisticCollector::STAT_ON_NEARMISS, 30, "NM");
		$this->registerStat(StatisticCollector::STAT_ON_KILL, 40, "K");
		$this->registerStat(StatisticCollector::STAT_ON_DEATH, 50, "D");
		$this->registerStat(StatisticCollector::STAT_ON_CAPTURE, 60, "C");

		$this->registerStat(StatisticManager::SPECIAL_STAT_KD_RATIO, 70, "K/D", 12, StatisticManager::STAT_TYPE_FLOAT);
		$this->registerStat(StatisticManager::SPECIAL_STAT_LASER_ACC, 80, "LAcc", 15, StatisticManager::STAT_TYPE_FLOAT);
		$this->registerStat(StatisticManager::SPECIAL_STAT_HITS_PH, 85, "H/h", 15, StatisticManager::STAT_TYPE_FLOAT);
	}

	/**
	 * Register a Certain Stat
	 *
	 * @param string $statName
	 * @param int    $order
	 * @param string $headShortCut
	 * @param int    $width
	 * @param string $format
	 */
	public function registerStat($statName, $order, $headShortCut, $width = 10, $format = StatisticManager::STAT_TYPE_INT) {
		// TODO: use own model class
		$this->statArray[$order]                 = array();
		$this->statArray[$order]["Name"]         = $statName;
		$this->statArray[$order]["HeadShortCut"] = '$o' . $headShortCut;
		$this->statArray[$order]["Width"]        = $width;
		$this->statArray[$order]["Format"]       = $format;
		$this->statsWidth += $width;
	}

	/**
	 * Show the stat List
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function command_ShowStatsList(array $callback, Player $player) {
		$this->showStatsList($player);
	}

	/**
	 * Show the StatsList Widget to the Player
	 *
	 * @param Player $player
	 * @param string $order
	 */
	public function showStatsList(Player $player, $order = PlayerManager::STAT_SERVERTIME) {
		$height       = $this->maniaControl->manialinkManager->getStyleManager()->getListWidgetsHeight();
		$quadStyle    = $this->maniaControl->manialinkManager->getStyleManager()->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->getStyleManager()->getDefaultMainWindowSubStyle();


		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$width     = $this->statsWidth + 60;
		//TODO handle size when stats are empty

		// Main frame
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition(0, 0, 10);

		// Background
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		// Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($width * 0.483, $height * 0.467, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		// Start offsets
		$xStart = -$width / 2;
		$posY   = $height / 2;

		// Predefine Description Label
		$descriptionLabel = new Label();
		$frame->add($descriptionLabel);
		$descriptionLabel->setAlign($descriptionLabel::LEFT, $descriptionLabel::TOP);
		$descriptionLabel->setPosition($xStart + 10, -$height / 2 + 5);
		$descriptionLabel->setSize($width * 0.7, 4);
		$descriptionLabel->setTextSize(2);
		$descriptionLabel->setVisible(false);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($posY - 5);

		$posX                = $xStart;
		$array['$oId']       = $posX + 5;
		$array['$oNickname'] = $posX + 14;

		// Headline
		$posX         = $xStart + 55;
		$statRankings = array();
		foreach ($this->statArray as $key => $stat) {
			$ranking = $this->maniaControl->statisticManager->getStatsRanking($stat["Name"]);
			if (!empty($ranking)) {
				$statRankings[$stat["Name"]]  = $ranking;
				$array[$stat['HeadShortCut']] = $posX;
				$posX += $stat["Width"];
			} else {
				unset($this->statArray[$key]);
			}
		}

		$labels = $this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		// Description Label
		$index = 2;
		foreach ($this->statArray as $statArray) {
			if (!isset($labels[$index])) {
				break;
			}

			/** @var Label_Text $label */
			$label = $labels[$index];

			$label->setAction(self::ACTION_SORT_STATS . '.' . $statArray["Name"]);
			$label->addTooltipLabelFeature($descriptionLabel, '$o ' . $statArray["Name"]);
			$index++;
		}

		// define standard properties
		$textSize  = 1.5;
		$textColor = 'fff';
		$index     = 1;
		$posY -= 10;

		if (!isset($statRankings[$order])) {
			return;
		}

		foreach ($statRankings[$order] as $playerId => $value) {
			$listPlayer = $this->maniaControl->playerManager->getPlayerByIndex($playerId);
			if (!$listPlayer) {
				continue;
			}
			if ($index === 15) {
				break;
			}

			$playerFrame = new Frame();
			$frame->add($playerFrame);

			// Show current Player Arrow
			if ($playerId == $player->index) {
				$currentQuad = new Quad_Icons64x64_1();
				$playerFrame->add($currentQuad);
				$currentQuad->setX($xStart + 3.5);
				$currentQuad->setZ(0.2);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}

			$displayArray = array();

			foreach ($this->statArray as $stat) {
				$statValue = 0;
				if (isset($statRankings[$stat['Name']][$playerId])) {
					$statValue = $statRankings[$stat['Name']][$playerId];
					if ($stat['Format'] == StatisticManager::STAT_TYPE_TIME) {
						$statValue = Formatter::formatTimeH($statValue);
					} else if ($stat['Format'] == StatisticManager::STAT_TYPE_FLOAT) {
						$statValue = round(floatval($statValue), 2);
					}
				}
				$displayArray[$stat['Name']] = array('Value' => strval($statValue), 'Width' => $stat['Width']);
			}

			$array = array($index => $xStart + 5, $listPlayer->nickname => $xStart + 14);
			$this->maniaControl->manialinkManager->labelLine($playerFrame, $array);

			$posX = $xStart + 55;
			foreach ($displayArray as $key => $array) {
				$label = new Label_Text();
				$playerFrame->add($label);
				$label->setHAlign($label::LEFT);
				$label->setX($posX);
				$label->setStyle($label::STYLE_TextCardSmall);
				$label->setTextSize($textSize);
				$label->setText($array['Value']);
				$label->setTextColor($textColor);
				$label->addTooltipLabelFeature($descriptionLabel, '$o ' . $key);
				$posX += $array['Width'];
			}

			$playerFrame->setY($posY);

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			$index++;
			$posY -= 4;
		}

		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'SimpleStatsList');
	}

	/**
	 * Called on ManialinkPageAnswer
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId, 3);
		if (count($actionArray) <= 2) {
			return;
		}

		$action = $actionArray[0] . '.' . $actionArray[1];

		switch ($action) {
			case self::ACTION_SORT_STATS:
				$playerLogin = $callback[1][1];
				$player      = $this->maniaControl->playerManager->getPlayer($playerLogin);
				$this->showStatsList($player, $actionArray[2]);
				break;
		}
	}
}
