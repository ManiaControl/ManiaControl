<?php

namespace ManiaControl\Players;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Statistics\StatisticManager;
use ManiaControl\Utils\Formatter;

/**
 * Player Detailed Page
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerDetailed {
	/*
	 * Constants
	 */
	const STATS_PER_COLUMN = 13;

	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Player Detailed Instance
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

	/**
	 * Show a Frame with detailed Information about the Target Player
	 *
	 * @param Player $player
	 * @param string $targetLogin
	 */
	public function showPlayerDetailed(Player $player, $targetLogin) {
		/** @var Player $target */
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);

		//Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script);
		$maniaLink->add($frame);

		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		$posY = $this->height / 2 - 7;

		//Nation Quad
		$countryQuad = new Quad();
		$frame->add($countryQuad);
		$countryQuad->setImage("file://ZoneFlags/Login/{$targetLogin}/country");
		$countryQuad->setPosition(-$this->width / 2 + 10, $posY);
		$countryQuad->setSize(5, 5);
		$countryQuad->setZ(-0.1);
		$countryQuad->setHAlign($countryQuad::LEFT);

		//Nickname
		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$this->width / 2 + 15, $posY);
		$label->setText($target->nickname);
		$label->setHAlign($label::LEFT);


		//Define MainLabel (Login)
		$posY -= 8;
		$mainLabel = new Label_Text();
		$frame->add($mainLabel);
		$mainLabel->setPosition(-$this->width / 2 + 10, $posY);
		$mainLabel->setTextSize(1.2);
		$mainLabel->setHAlign($mainLabel::LEFT);
		$mainLabel->setText('Login: ');

		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText('Nation: ');

		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText('Province: ');

		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText('Authorization: ');

		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText("Ladder Rank:");

		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText('Ladder Score: ');

		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText('Inscribed Zone: ');

		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText('Avatar');

		//Login
		$posY      = $this->height / 2 - 15;
		$mainLabel = new Label_Text();
		$frame->add($mainLabel);
		$mainLabel->setPosition(-$this->width / 2 + 30, $posY);
		$mainLabel->setText($target->login);
		$mainLabel->setTextSize(1.2);
		$mainLabel->setHAlign($mainLabel::LEFT);

		//Country
		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText($target->getCountry());

		//Province
		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText($target->getProvince());

		//AuthLevel
		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText($this->maniaControl->authenticationManager->getAuthLevelName($target->authLevel));

		//LadderRank
		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText($target->ladderRank);

		//LadderScore
		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText(round($target->ladderScore, 2));

		//Played Since
		$posY -= 5;
		$label = clone $mainLabel;
		$frame->add($label);
		$label->setY($posY);
		$label->setText(date('d M Y', time() - 3600 * 24 * $target->daysSinceZoneInscription));

		$quad = new Quad();
		$frame->add($quad);
		$quad->setImage('file://Avatars/' . $targetLogin . "/default");
		$quad->setPosition(-$this->width / 2 + 50, -$this->height / 2 + 34);
		$quad->setAlign($quad::RIGHT, $quad::TOP);
		$quad->setSize(20, 20);

		//Statistics
		$frame->add($this->statisticsFrame($target));


		$quad = new Label_Button();
		$frame->add($quad);
		$quad->setStyle($quad::STYLE_CardMain_Quit);
		$quad->setHAlign($quad::LEFT);
		$quad->setScale(0.75);
		$quad->setText('Back');
		$quad->setPosition(-$this->width / 2 + 7, -$this->height / 2 + 7);
		$quad->setAction(PlayerCommands::ACTION_OPEN_PLAYERLIST);

		// render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'PlayerDetailed');
	}

	/**
	 * Build a Frame with Statistics about the given Player
	 *
	 * @param Player $player
	 * @return Frame
	 */
	public function statisticsFrame(Player $player) {
		$frame = new Frame();

		$playerStats = $this->maniaControl->statisticManager->getAllPlayerStats($player);
		$posY        = $this->height / 2 - 15;
		$posX        = -$this->width / 2 + 52;
		$index       = 1;

		foreach ($playerStats as $stat) {
			$value = (float)$stat[1];
			if (!$value) {
				continue;
			}

			$statProperties = $stat[0];
			if ($statProperties->type === StatisticManager::STAT_TYPE_TIME) {
				$value = Formatter::formatTimeH($value);
			} else if ($statProperties->type === StatisticManager::STAT_TYPE_FLOAT) {
				$value = round($value, 2);
			}

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$frame->add($lineQuad);
				$lineQuad->setSize(49, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setPosition($posX, $posY, 0.001);
				$lineQuad->setHAlign($lineQuad::LEFT);
			}

			$label = new Label_Text();
			$frame->add($label);
			$label->setPosition($posX + 4, $posY);
			$label->setText($statProperties->name);
			$label->setHAlign($label::LEFT);
			$label->setTextSize(1.5);

			$label = new Label_Text();
			$frame->add($label);
			$label->setPosition($posX + 40, $posY);
			$label->setText($value);
			$label->setTextSize(1.5);

			$posY -= 4;
			$index++;

			if ($index > self::STATS_PER_COLUMN) {
				$posY = $this->height / 2 - 15;
				$posX += 47;
				$index = 0;
			}
		}
		return $frame;
	}
} 