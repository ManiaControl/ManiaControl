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
use ManiaControl\Callbacks\CallbackManager;
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
	 * Show the PlayerList Widget to the Player
	 *
	 * @param Player $player
	 */
	public function showStatsList(Player $player) {
		$width        = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height       = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

		$width = $width * 1.4; //TODO setting

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
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


		// get Players by Main-Order
		//$players = $this->maniaControl->playerManager->getPlayers();
		$time   = $this->maniaControl->statisticManager->getStatsRanking(PlayerManager::STAT_SERVERTIME);
		$kills  = $this->maniaControl->statisticManager->getStatsRanking(StatisticCollector::STAT_ON_KILL);
		$deaths = $this->maniaControl->statisticManager->getStatsRanking(StatisticCollector::STAT_ON_DEATH);


		$x                   = $xStart;
		$array['$oId']       = $x + 5;
		$array['$oNickname'] = $x + 14;
		$array['$oS']        = $x += 55;
		$array['$oK']        = $x += 20;
		$array['$oD']        = $x += 8;
		$array['$oK/D']      = $x += 8;

		//$array = array("Id" => $xStart + 5, "Nickname" => $xStart + 14, "K" => $xStart + 50, "D" => $xStart + 58);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		// define standard properties
		$hAlign    = Control::LEFT;
		$style     = Label_Text::STYLE_TextCardSmall;
		$textSize  = 1.5;
		$textColor = 'FFF';
		$i         = 1;
		$y -= 10;
		foreach($time as $playerId => $value) {
			$listPlayer = $this->maniaControl->playerManager->getPlayerByIndex($playerId);
			//var_dump($listPlayer);
			/**
			 *
			 * @var Player $listPlayer
			 */

			$playerFrame = new Frame();
			$frame->add($playerFrame);

			//$this->maniaControl->statisticManager->get

			$displayArray = array();

			if(!isset($kills[$playerId])) {
				$killStat = 0;
			} else {
				$killStat = $kills[$playerId];
			}

			if(!isset($deaths[$playerId])) {
				$deathStat = 0;
			} else {
				$deathStat = $deaths[$playerId];
			}

			$displayArray['Server Time'] = Formatter::formatTimeH($value);
			var_dump($value);
			$displayArray['Kills'] = strval($killStat);
			//var_dump($deaths);
			$displayArray['Deaths'] = strval($deathStat);

			//	var_dump($displayArray);
			if($deathStat == 0) {
				$displayArray['Kill-Death Ratio'] = '-';
			} else {
				$displayArray['Kill-Death Ratio'] = strval(round($killStat / $deathStat, 2));
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
				$label->setText($array);
				$label->setTextColor($textColor);
				$script->addTooltip($label, $descriptionLabel, array(Script::OPTION_TOOLTIP_TEXT => '$o ' . $key));
				if($x == $xStart + 55) {
					$x += 10; //TODO improve
				}
				$x += 8;
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