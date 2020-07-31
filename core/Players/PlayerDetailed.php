<?php

namespace ManiaControl\Players;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Statistics\StatisticManager;
use ManiaControl\Utils\Formatter;

/**
 * Player Detailed Page
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerDetailed {
	/*
	 * Constants
	 */
	const STATS_PER_COLUMN = 14;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Create a new Player Detailed Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Settings
		$this->width        = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$this->height       = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();
		$this->quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowStyle();
		$this->quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowSubStyle();

		//Class variables
		$this->contentY     = $this->height / 2 - 15;
		$this->infoColWidth = $this->width * 0.3;
		$this->margin       = 10;
		$this->padding      = 2;
	}

	/**
	 * Show a Frame with detailed Information about the Target Player
	 *
	 * @param Player $player
	 * @param string $targetLogin
	 */
	public function showPlayerDetailed(Player $player, $targetLogin) {
		/** @var Player $target */
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);

		// Create ManiaLink
		$manialink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $manialink->getScript();

		// Main frame
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script);
		$manialink->addChild($frame);


		$colWidth = ($this->infoColWidth - $this->padding) / 2;
		$posX     = -$this->width / 2 + $this->margin;

		$posY = $this->contentY + 8;
		//Nation Quad
		$countryQuad = new Quad();
		$frame->addChild($countryQuad);
		$countryQuad->setImageUrl("file://ZoneFlags/Login/{$targetLogin}/country");
		$countryQuad->setPosition($posX, $posY);
		$countryQuad->setSize(5, 5);
		$countryQuad->setZ(-0.1);
		$countryQuad->setHorizontalAlign($countryQuad::LEFT);

		//Nickname
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition($posX + 5 + $this->padding, $posY);
		$label->setText($target->nickname);
		$label->setHorizontalAlign($label::LEFT);

		//Define MainLabel (Login)
		$posY      = $this->contentY;
		$mainLabel = new Label_Text();
		$frame->addChild($mainLabel);
		$mainLabel->setPosition($posX, $posY);
		$mainLabel->setTextSize(1.2);
		$mainLabel->setHorizontalAlign($mainLabel::LEFT);
		$mainLabel->setText('Login: ');
		$mainLabel->setWidth($colWidth);

		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText('Nation: ');

		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText('Province: ');

		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText('Authorization: ');

		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText("Ladder Rank:");

		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText('Ladder Score: ');

		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText('Inscribed Zone: ');

		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText('Avatar');

		//Login
		$posY      = $this->contentY;
		$mainLabel = new Label_Text();
		$frame->addChild($mainLabel);
		$mainLabel->setPosition($posX + $colWidth, $posY);
		$mainLabel->setText($target->login);
		$mainLabel->setTextSize(1.2);
		$mainLabel->setHorizontalAlign($mainLabel::LEFT);
		$mainLabel->setWidth($colWidth);

		//Country
		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText($target->getCountry());

		//Province
		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText($target->getProvince());

		//AuthLevel
		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText($this->maniaControl->getAuthenticationManager()->getAuthLevelName($target->authLevel));

		//LadderRank
		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText($target->ladderRank);

		//LadderScore
		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText(round($target->ladderScore, 2));

		//Played Since
		$posY  -= 5;
		$label = clone $mainLabel;
		$frame->addChild($label);
		$label->setY($posY);
		$label->setText(date('d M Y', time() - 3600 * 24 * $target->daysSinceZoneInscription));

		$quad = new Quad();
		$frame->addChild($quad);
		$quad->setImageUrl('file://Avatars/' . $targetLogin . "/default");
		$quad->setPosition($posX + $colWidth, $posY - 2.5);
		$quad->setAlign($quad::LEFT, $quad::TOP);
		$quad->setSize(20, 20);

		//Statistics
		$frame->addChild($this->statisticsFrame($target));


		$quad = new Label_Button();
		$frame->addChild($quad);
		$quad->setStyle($quad::STYLE_CardMain_Quit);
		$quad->setHorizontalAlign($quad::LEFT);
		$quad->setScale(0.75);
		$quad->setText('Back');
		$quad->setPosition(-$this->width / 2 + 7, -$this->height / 2 + 7);
		$quad->setAction(PlayerCommands::ACTION_OPEN_PLAYERLIST);

		// render and display xml
		$this->maniaControl->getManialinkManager()->displayWidget($manialink, $player, 'PlayerDetailed');
	}

	/**
	 * Build a Frame with Statistics about the given Player
	 *
	 * @param Player $player
	 * @return Frame
	 */
	public function statisticsFrame(Player $player) {
		$frame = new Frame();
		$frame->setPosition(-$this->width / 2 + $this->infoColWidth + $this->margin, $this->contentY);

		$playerStats = $this->maniaControl->getStatisticManager()->getAllPlayerStats($player);

		$posY               = 0;
		$posX               = 0;
		$statisticsColWidth = $this->width - $this->infoColWidth - $this->margin;
		$cols               = 2;
		$colWidth           = $statisticsColWidth / $cols;
		$index              = 1;

		foreach ($playerStats as $stat) {
			$value = (float) $stat[1];
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
				$frame->addChild($lineQuad);
				$lineQuad->setSize($colWidth, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setPosition($posX, $posY, -0.001);
				$lineQuad->setHorizontalAlign($lineQuad::LEFT);
			}

			$label = new Label_Text();
			$frame->addChild($label);
			$label->setPosition($posX + $this->padding, $posY);
			$label->setText($statProperties->name);
			$label->setHorizontalAlign($label::LEFT);
			$label->setTextSize(1.5);
			$label->setWidth($colWidth / 2 - $this->padding);

			$label = new Label_Text();
			$frame->addChild($label);
			$label->setPosition($posX + $colWidth - $this->padding, $posY);
			$label->setHorizontalAlign(Label_Text::RIGHT);
			$label->setText($value);
			$label->setTextSize(1.5);
			$label->setWidth($colWidth / 2 - $this->padding);

			$posY -= 4;
			$index++;

			if ($index > self::STATS_PER_COLUMN) {
				$posY  = 0;
				$posX  += $statisticsColWidth / 2;
				$index = 1;
			}
		}
		return $frame;
	}
} 