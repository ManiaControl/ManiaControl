<?php

namespace ManiaControl\Statistics;


use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

class SimpleStatsList implements ManialinkPageAnswerListener, CallbackListener {
	/**
	 * Constants
	 */
	const ACTION_OPEN_STATSLIST = 'SimpleStatsList.OpenStatsList';

	/**
	 * Private Properties
	 */
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

		//$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
	}

	/**
	 * Add the menu entry
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		// Action Open StatsList
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_STATSLIST, $this, 'command_ShowStatsList');
		//TODO command

		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Stats);
		$itemQuad->setAction(self::ACTION_OPEN_STATSLIST);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, true, 14, 'Open Statistics');

		//TODO setting if a stat get shown
		//TODO sort out stats where no player have a point
		$this->registerStat(PlayerManager::STAT_SERVERTIME, 10, "ST", 20, StatisticManager::STAT_TYPE_TIME);
		$this->registerStat(StatisticCollector::STAT_ON_HIT, 20, "H");
		$this->registerStat(StatisticCollector::STAT_ON_NEARMISS, 30, "NM");
		$this->registerStat(StatisticCollector::STAT_ON_KILL, 40, "K");
		$this->registerStat(StatisticCollector::STAT_ON_DEATH, 50, "D");
		$this->registerStat(StatisticCollector::STAT_ON_CAPTURE, 60, "C");

		//TODO register from classes:
		if($this->maniaControl->pluginManager->getPlugin('DonationPlugin')) {
			$this->registerStat(\DonationPlugin::STAT_PLAYER_DONATIONS, 70, "D", 20);
		}

		if($this->maniaControl->pluginManager->getPlugin('KarmaPlugin')) {
			$this->registerStat(\DonationPlugin::STAT_PLAYER_DONATIONS, 80, "VM");
		}
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


	public function registerStat($statName, $order, $headShortCut, $width = 8, $format = StatisticManager::STAT_TYPE_INT) {
		$this->statArray[$order]                 = array();
		$this->statArray[$order]["Name"]         = $statName;
		$this->statArray[$order]["HeadShortCut"] = '$o' . $headShortCut;
		$this->statArray[$order]["Width"]        = $width;
		$this->statArray[$order]["Format"]       = $format;
		$this->statsWidth += $width;
	}


	/**
	 * Show the PlayerList Widget to the Player
	 *
	 * @param Player $player
	 */
	public function showStatsList(Player $player) {
		$height       = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();


		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);

		$width = $this->statsWidth + 80;

		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

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
		$y      = $height / 2;

		// Predefine Description Label
		$descriptionLabel = new Label();
		$frame->add($descriptionLabel);
		$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
		$descriptionLabel->setPosition($xStart + 10, -$height / 2 + 5);
		$descriptionLabel->setSize($width * 0.7, 4);
		$descriptionLabel->setTextSize(2);
		$descriptionLabel->setVisible(false);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);


		$playTime = $this->maniaControl->statisticManager->getStatsRanking(StatisticCollector::STAT_PLAYTIME);
		$shots    = $this->maniaControl->statisticManager->getStatsRanking(StatisticCollector::STAT_ON_SHOOT);

		$x                   = $xStart;
		$array['$oId']       = $x + 5;
		$array['$oNickname'] = $x + 14;

		$x = $xStart + 55;

		$statRankings = array();
		foreach($this->statArray as $key => $stat) {
			$statRankings[$stat["Name"]]  = $this->maniaControl->statisticManager->getStatsRanking($stat["Name"]);
			$array[$stat['HeadShortCut']] = $x;
			$x += $stat["Width"];
		}

		$standardWidth  = 10;
		$array['$oK/D'] = $x;
		$x += $standardWidth;
		$array['$oH/h'] = $x;

		$order = PlayerManager::STAT_SERVERTIME;

		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		// define standard properties
		$hAlign    = Control::LEFT;
		$style     = Label_Text::STYLE_TextCardSmall;
		$textSize  = 1.5;
		$textColor = 'FFF';
		$i         = 1;
		$y -= 10;
		foreach($statRankings[$order] as $playerId => $value) {
			$listPlayer = $this->maniaControl->playerManager->getPlayerByIndex($playerId);
			if($i == 15) {
				break;
			}

			/** @var Player $listPlayer * */

			$playerFrame = new Frame();
			$frame->add($playerFrame);

			$displayArray = array();

			foreach($this->statArray as $stat) {
				$statValue = 0;
				if(isset($statRankings[$stat['Name']][$playerId])) {
					$statValue = $statRankings[$stat['Name']][$playerId];
					if($stat['Format'] == StatisticManager::STAT_TYPE_TIME) {
						$statValue = Formatter::formatTimeH($statValue);
					}
				}
				$displayArray[$stat['Name']] = array("Value" => strval($statValue), "Width" => $stat['Width']);
			}

			isset($playTime[$playerId]) ? $playTimeStat = $playTime[$playerId] : $playTimeStat = 0;

			if(isset($statRankings[StatisticCollector::STAT_ON_DEATH][$playerId])) {
				$deathStat                        = $statRankings[StatisticCollector::STAT_ON_DEATH][$playerId];
				$killStat                         = $statRankings[StatisticCollector::STAT_ON_KILL][$playerId];
				$displayArray['Kill-Death Ratio'] = array("Value" => round($killStat / $deathStat, 2), "Width" => $standardWidth);
			} else {
				$displayArray['Kill-Death Ratio'] = array("Value" => "-", "Width" => $standardWidth);
			}

			if($playTimeStat == 0) {
				$displayArray['Hits per Hour'] = array("Value" => "-", "Width" => $standardWidth);
			} else {
				$hitStat                       = $statRankings[StatisticCollector::STAT_ON_HIT][$playerId];
				$displayArray['Hits per Hour'] = array("Value" => strval(round(intval($hitStat) / (intval($playTimeStat) / 3600), 1)), "Width" => $standardWidth);
			}

			$array = array($i => $xStart + 5, $listPlayer->nickname => $xStart + 14);
			$this->maniaControl->manialinkManager->labelLine($playerFrame, $array);

			$x = $xStart + 55;
			foreach($displayArray as $key => $array) {
				$label = new Label_Text();
				$playerFrame->add($label);
				$label->setHAlign($hAlign);
				$label->setX($x);
				$label->setStyle($style);
				$label->setTextSize($textSize);
				$label->setText($array['Value']);
				$label->setTextColor($textColor);
				$script->addTooltip($label, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => '$o ' . $key));
				$x += $array['Width'];
			}


			$playerFrame->setY($y);

			if($i % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}


			$i++;
			$y -= 4;
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'PlayerList');
	}
} 