<?php

namespace ManiaControl\Maps;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;

/**
 * Maps Directory Browser
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class DirectoryBrowser implements ManialinkPageAnswerListener {
	/*
	 * Constants
	 */
	const ACTION_SHOW       = 'Maps.DirectoryBrowser.Show';
	const ACTION_ADD_FILE   = 'Maps.DirectoryBrowser.AddFile.';
	const ACTION_ERASE_FILE = 'Maps.DirectoryBrowser.EraseFile';
	const WIDGET_NAME       = 'Maps.DirectoryBrowser.Widget';

	/*
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Directory Browser Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for ManiaLink Actions
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SHOW, $this, 'handleActionShow');
	}

	/**
	 * Handle 'Show' Page Action
	 *
	 * @param array  $actionCallback
	 * @param Player $player
	 */
	public function handleActionShow(array $actionCallback, Player $player) {
		$this->showManiaLink($player);
	}

	/**
	 * Build and show the Browser ManiaLink to the given Player
	 *
	 * @param Player $player
	 */
	public function showManiaLink(Player $player) {
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $paging);
		$maniaLink->add($frame);

		$mapFiles = $this->getFilteredMapFiles();

		$width     = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height    = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$index     = 0;
		$posY      = $height / 2 - 10;
		$pageFrame = null;

		foreach ($mapFiles as $fileName) {
			if ($index % 15 === 0) {
				// New Page
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$posY = $height / 2 - 10;
				$paging->addPage($pageFrame);
			}

			// Map Frame
			$mapFrame = new Frame();
			$pageFrame->add($mapFrame);
			$mapFrame->setPosition(0, $posY, 0.1);

			if ($index % 2 !== 0) {
				// Striped background line
				$lineQuad = new Quad_BgsPlayerCard();
				$mapFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			// File name Label
			$nameLabel = new Label_Text();
			$mapFrame->add($nameLabel);
			$nameLabel->setX($width * -0.2);
			$nameLabel->setText($fileName);

			if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)) {
				// Add file button
				$addButton = new Label_Button();
				$mapFrame->add($addButton);
				$addButton->setPosition($width / 2 - 9, 0, 0.2);
				$addButton->setSize(3, 3);
				$addButton->setTextSize(2);
				$addButton->setText('Add');
				$addButton->setAction(self::ACTION_ADD_FILE);
			}

			if ($this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_REMOVE_MAP)) {
				// Erase file button
				$eraseButton = new Label_Button();
				$mapFrame->add($eraseButton);
				$eraseButton->setPosition($width / 2 - 9, 0, 0.2);
				$eraseButton->setSize(3, 3);
				$eraseButton->setTextSize(2);
				$eraseButton->setText('Add');
				$eraseButton->setAction(self::ACTION_ERASE_FILE);
			}

			$posY -= 4;
			$index++;
		}

		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, self::WIDGET_NAME);
	}

	/**
	 * Get the list of not yet added Map files
	 *
	 * @return array|bool
	 */
	protected function getFilteredMapFiles() {
		$mapFiles = $this->getMapFiles();
		if (!is_array($mapFiles)) {
			return false;
		}
		$filteredMapFiles = array();
		foreach ($mapFiles as $mapFile) {
			// TODO: filter already added maps
			array_push($filteredMapFiles, $mapFile);
		}
		return $filteredMapFiles;
	}

	/**
	 * Get the list of Map files
	 *
	 * @return array|bool
	 */
	protected function getMapFiles() {
		$mapsDirectory = $this->maniaControl->server->directory->getMapsFolder();
		return $this->scanMapFiles($mapsDirectory);
	}

	/**
	 * Scan the given directory for Map files
	 *
	 * @param string $directory
	 * @return array|bool
	 */
	protected function scanMapFiles($directory) {
		if (!is_readable($directory) || !is_dir($directory)) {
			return false;
		}
		$mapFiles = array();
		$dirFiles = scandir($directory);
		foreach ($dirFiles as $fileName) {
			if (substr($fileName, 0, 1) === '.') {
				continue;
			}
			$fullFileName = $directory . $fileName;
			if (is_dir($fullFileName)) {
				$subDirectory = $fullFileName . DIRECTORY_SEPARATOR;
				$subMapFiles  = $this->scanMapFiles($subDirectory);
				if (is_array($subMapFiles)) {
					$mapFiles = array_merge($mapFiles, $subMapFiles);
				}
			} else {
				if ($this->isMapFileName($fileName)) {
					array_push($mapFiles, $fullFileName);
				}
			}
		}
		return $mapFiles;
	}

	/**
	 * Check if the given file name represents a Map file
	 *
	 * @param string $fileName
	 * @return bool
	 */
	protected function isMapFileName($fileName) {
		$mapFileNameEnding = '.map.gbx';
		$fileNameEnding    = strtolower(substr($fileName, -strlen($mapFileNameEnding)));
		return ($fileNameEnding === $mapFileNameEnding);
	}
}
