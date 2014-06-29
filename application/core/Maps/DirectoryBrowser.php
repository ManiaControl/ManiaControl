<?php

namespace ManiaControl\Maps;

use FML\ManiaLink;
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
	const ACTION_SHOW = 'Maps.DirectoryBrowser.Show';
	const WIDGET_NAME = 'Maps.DirectoryBrowser.Widget';

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
	 * Handle Show Page Answer
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

		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $player);
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
			var_dump($fileName);
			if (is_dir($fileName)) {
				$subDirectory = $directory . $fileName . DIRECTORY_SEPARATOR;
				$subMapFiles  = $this->scanMapFiles($subDirectory);
				if (is_array($subMapFiles)) {
					$mapFiles = array_merge($mapFiles, $subMapFiles);
				}
			} else {
				if ($this->isMapFileName($fileName)) {
					$fullFileName = $directory . $fileName;
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
