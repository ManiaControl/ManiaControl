<?php

namespace ManiaControl\Update;

use FML\ManiaLink;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Script\Features\Paging;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Players\Player;

/**
 * Ingame ChangeLog to display the latest updates to ManiaControl
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ChangeLog implements CommandListener {
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct a new update manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Chat commands
		$this->maniaControl->getCommandManager()->registerCommandListener('changelog', $this, 'handle_ChangeLog', true, 'Opens a ChangeLog ingame.');
	}

	/**
	 * Get the ChangeLog
	 * @return array
	 */
	private function getChangeLogArray() {
		static $changelog = null;
		if ($changelog === null) {
			$changelog = $this->readChangeLog();
		}

		return $changelog;
	}

	/**
	 * Displays the ChangeLog Window
	 * @param array $chatCallback
	 * @param Player $player
	 */
	public function handle_ChangeLog(array $chatCallback, Player $player) {
		// Build Manialink
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();
		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();

		$manialink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $manialink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);
		
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script, $paging);
		$manialink->addChild($frame);

		// Headline
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setHorizontalAlign($label::LEFT);
		$label->setPosition(-0.45*$width, 0.45*$height);
		$label->setSize($width * 0.6, 5);
		$label->setStyle($label::STYLE_TextCardSmall);
		$label->setText('Changelog of ManiaControl');
		$label->setTextColor('ff0');
		$label->setTextSize(3);
		$label->setVerticalAlign($label::TOP);

		$posX = -0.45*$width;
		$initialPosY = 0.4*$height - 5;
		$posY = $initialPosY;

		$changelog = $this->getChangeLogArray();
		if (empty($changelog)) {
			// TODO error
		}

		$i = 0;
		$numLines = 15;
		$pageFrame = null;
		foreach ($changelog as $line) {
			if ($i % $numLines == 0 || substr($line, 0, 3) === '###') {
				// page full, or new version number
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$paging->addPageControl($pageFrame);

				$posY = $initialPosY;
				$i = 0;
			}
			
			$label = new Label_Text();
			$pageFrame->addChild($label);
			$label->setHorizontalAlign($label::LEFT);
			$label->setPosition($posX, $posY);
			$label->setStyle($label::STYLE_TextCardMedium);
			$label->setText(str_replace('$', '$$', $line)); // prevent ManiaPlanet formatting
			$label->setTextSize(2);

			$posY -= 4;
			$i++;
		}

		// Display manialink
		$this->maniaControl->getManialinkManager()->sendManialink($manialink, $player);
	}

	/**
	 * Reads the changelog.txt in the root-directory into an array
	 */
	private function readChangeLog() {
		$changelog = file_get_contents('changelog.txt');
		if ($changelog === false) {
			return array();
		}

		return preg_split('/[\r\n]/', $changelog);
	}
}