<?php

namespace ManiaControl\Players;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Statistics\StatisticManager;

/**
 * Player Detailed Page
 *
 * @author steeffeen & kremsy
 */
class PlayerDetailed {
	/**
	 * Constants
	 */
	const STATS_PER_COLUMN = 13;

	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Player Detailed instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// settings
		$this->width        = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$this->height       = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$this->quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$this->quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();
	}


	public function showPlayerDetailed(Player $player, $targetLogin) {
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);

		//Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->defaultListFrame($script);
		$maniaLink->add($frame);

		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		$y = $this->height / 2 - 7;

		//Nation Quad
		$countryQuad = new Quad();
		$frame->add($countryQuad);
		$countryCode = Formatter::mapCountry($target->getCountry());
		$countryQuad->setImage("file://Skins/Avatars/Flags/{$countryCode}.dds");
		$countryQuad->setPosition(-$this->width / 2 + 10, $y);
		$countryQuad->setSize(5, 5);
		$countryQuad->setZ(-0.1);
		$countryQuad->setHAlign(Control::LEFT);

		//Nickname
		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$this->width / 2 + 15, $y);
		$label->setText($target->nickname);
		$label->setHAlign(Control::LEFT);


		//Define MainLabel (Login)
		$y -= 8;
		$mainLabel = new Label_Text();
		$frame->add($mainLabel);
		$mainLabel->setPosition(-$this->width / 2 + 10, $y);
		$mainLabel->setTextSize(1.2);
		$mainLabel->setHAlign(Control::LEFT);
		$mainLabel->setText("Login:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Nation: ");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Province:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Authorization:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Ladder Rank:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Ladder Score:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText("Inscribed Zone:");

		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText('Avatar');

		//Login
		$y         = $this->height / 2 - 15;
		$mainLabel = new Label_Text();
		$frame->add($mainLabel);
		$mainLabel->setPosition(-$this->width / 2 + 30, $y);
		$mainLabel->setText($target->login);
		$mainLabel->setTextSize(1.2);
		$mainLabel->setHAlign(Control::LEFT);

		//Country
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText($target->getCountry());

		//Province
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText($target->getProvince());

		//AuthLevel
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText($this->maniaControl->authenticationManager->getAuthLevelName($target->authLevel));

		//LadderRank
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText($target->ladderRank);

		//LadderScore
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText(round($target->ladderScore, 2));

		//Played Since
		$y -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($y);
		$label->setText(date("d M Y", time() - 3600 * 24 * $target->daysSinceZoneInscription));

		$quad = new Quad();
		$frame->add($quad);
		$quad->setImage('file://' . $target->avatar);
		$quad->setPosition(-$this->width / 2 + 50, -$this->height / 2 + 34);
		$quad->setAlign(Control::RIGHT, Control::TOP);
		$quad->setSize(20, 20);

		//Statistics
		$frame->add($this->statisticsFrame($target));


		$quad = new Label_Button();
		$frame->add($quad);
		$quad->setStyle($quad::STYLE_CardMain_Quit);
		$quad->setHAlign(Control::LEFT);
		$quad->setScale(0.75);
		$quad->setText("Back");
		$quad->setPosition(-$this->width / 2 + 7, -$this->height / 2 + 7);
		$quad->setAction(PlayerCommands::ACTION_OPEN_PLAYERLIST);

		// render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'PlayerDetailed');
	}

	public function statisticsFrame($player) {
		$frame = new Frame();

		$playerStats = $this->maniaControl->statisticManager->getAllPlayerStats($player);
		$y           = $this->height / 2 - 15;
		$x           = -$this->width / 2 + 52;
		$id          = 1;

		foreach($playerStats as $stat) {
			$statProperties = $stat[0];
			$value          = $stat[1];

			if (floatval($value) == 0) {
				continue;
			}

			if ($statProperties->type == StatisticManager::STAT_TYPE_TIME) {
				$value = Formatter::formatTimeH($value);
			} else if ($statProperties->type == StatisticManager::STAT_TYPE_FLOAT) {
				$value = round(floatval($value), 2);
			}


			if ($id % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$frame->add($lineQuad);
				$lineQuad->setSize(49, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setPosition($x, $y, 0.001);
				$lineQuad->setHAlign(Control::LEFT);
			}

			$label = new Label_Text();
			$frame->add($label);
			$label->setPosition($x + 4, $y);
			$label->setText($statProperties->name);
			$label->setHAlign(Control::LEFT);
			$label->setTextSize(1.5);

			$label = new Label_Text();
			$frame->add($label);
			$label->setPosition($x + 40, $y);
			$label->setText($value);
			$label->setVAlign(Control::CENTER2);
			$label->setTextSize(1.5);

			$y -= 4;
			$id++;

			if ($id > self::STATS_PER_COLUMN) {
				$y = $this->height / 2 - 15;
				$x += 47;
				$id = 0;
			}
		}
		return $frame;
	}
} 