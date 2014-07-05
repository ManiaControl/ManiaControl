<?php

namespace ManiaControl\Maps;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
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
	const ACTION_SHOW          = 'Maps.DirectoryBrowser.Show';
	const ACTION_OPEN_FOLDER   = 'Maps.DirectoryBrowser.OpenFolder.';
	const ACTION_NAVIGATE_UP   = 'Maps.DirectoryBrowser.NavigateUp';
	const ACTION_NAVIGATE_ROOT = 'Maps.DirectoryBrowser.NavigateRoot';
	const ACTION_ADD_FILE      = 'Maps.DirectoryBrowser.AddFile.';
	const ACTION_ERASE_FILE    = 'Maps.DirectoryBrowser.EraseFile';
	const WIDGET_NAME          = 'Maps.DirectoryBrowser.Widget';
	const CACHE_FOLDER_PATH    = 'FolderPath';

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
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_NAVIGATE_UP, $this, 'handleNavigateUp');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_NAVIGATE_ROOT, $this, 'handleNavigateRoot');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerRegexListener($this->buildOpenFolderActionRegex(), $this, 'handleOpenFolder');
	}

	/**
	 * Build the regex to register for 'OpenFolder' action
	 *
	 * @return string
	 */
	private function buildOpenFolderActionRegex() {
		return '/' . self::ACTION_OPEN_FOLDER . '*/';
	}

	/**
	 * Handle 'Show' action
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
	 * @param mixed  $nextFolder
	 */
	public function showManiaLink(Player $player, $nextFolder = null) {
		$oldFolderPath  = $player->getCache($this, self::CACHE_FOLDER_PATH);
		$isInMapsFolder = false;
		if (!$oldFolderPath) {
			$oldFolderPath  = $this->maniaControl->server->directory->getMapsFolder();
			$isInMapsFolder = true;
		}
		$folderPath = $oldFolderPath;
		if (is_string($nextFolder)) {
			$newFolderPath = realpath($oldFolderPath . $nextFolder);
			if ($newFolderPath) {
				$folderPath = $newFolderPath . DIRECTORY_SEPARATOR;
				$folderName = basename($newFolderPath);
				switch ($folderName) {
					case 'Maps':
						$mapsDir        = dirname($this->maniaControl->server->directory->getMapsFolder());
						$folderDir      = dirname($folderPath);
						$isInMapsFolder = ($mapsDir === $folderDir);
						break;
					case 'UserData':
						$dataDir   = dirname($this->maniaControl->server->directory->getGameDataFolder());
						$folderDir = dirname($folderPath);
						if ($dataDir === $folderDir) {
							// Prevent navigation out of maps directory
							return;
						}
						break;
				}
			}
		}
		$player->setCache($this, self::CACHE_FOLDER_PATH, $folderPath);

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $paging);
		$maniaLink->add($frame);

		$width     = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height    = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$index     = 0;
		$posY      = $height / 2 - 10;
		$pageFrame = null;

		$navigateRootQuad = new Quad_Icons64x64_1();
		$frame->add($navigateRootQuad);
		$navigateRootQuad->setPosition($width * -0.47, $height * 0.45)
		                 ->setSize(4, 4)
		                 ->setSubStyle($navigateRootQuad::SUBSTYLE_ToolRoot);

		$navigateUpQuad = new Quad_Icons64x64_1();
		$frame->add($navigateUpQuad);
		$navigateUpQuad->setPosition($width * -0.44, $height * 0.45)
		               ->setSize(4, 4)
		               ->setSubStyle($navigateUpQuad::SUBSTYLE_ToolUp);

		if (!$isInMapsFolder) {
			$navigateRootQuad->setAction(self::ACTION_NAVIGATE_ROOT);
			$navigateUpQuad->setAction(self::ACTION_NAVIGATE_UP);
		}

		$directoryLabel = new Label_Text();
		$frame->add($directoryLabel);
		$dataFolder    = $this->maniaControl->server->directory->getGameDataFolder();
		$directoryText = substr($folderPath, strlen($dataFolder));
		$directoryLabel->setPosition($width * -0.41, $height * 0.45)
		               ->setSize($width * 0.85, 4)
		               ->setHAlign($directoryLabel::LEFT)
		               ->setText($directoryText)
		               ->setTextSize(2);

		$mapFiles = $this->scanMapFiles($folderPath);

		if (is_array($mapFiles)) {
			if (empty($mapFiles)) {
				$emptyLabel = new Label();
				$frame->add($emptyLabel);
				$emptyLabel->setY(20)
				           ->setTextColor('aaa')
				           ->setText('No files found.')
				           ->setTranslate(true);
			} else {
				$canAddMaps   = $this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP);
				$canEraseMaps = $this->maniaControl->authenticationManager->checkPermission($player, MapManager::SETTING_PERMISSION_ERASE_MAP);

				foreach ($mapFiles as $filePath => $fileName) {
					$shortFilePath = substr($filePath, strlen($folderPath));

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
					$mapFrame->setY($posY);

					if ($index % 2 === 0) {
						// Striped background line
						$lineQuad = new Quad_BgsPlayerCard();
						$mapFrame->add($lineQuad);
						$lineQuad->setZ(-1)
						         ->setSize($width, 4)
						         ->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
					}

					// File name Label
					$nameLabel = new Label_Text();
					$mapFrame->add($nameLabel);
					$nameLabel->setX($width * -0.48)
					          ->setSize($width * 0.79, 4)
					          ->setHAlign($nameLabel::LEFT)
					          ->setTextSize(1)
					          ->setText($fileName)
					          ->setAction('test');

					if (is_dir($filePath)) {
						// Folder
						$folderAction = self::ACTION_OPEN_FOLDER . substr($shortFilePath, 0, -1);
						$nameLabel->setAction($folderAction);
					} else {
						// File
						if ($canAddMaps) {
							// 'Add' button
							$addButton = new Label_Button();
							$mapFrame->add($addButton);
							$addButton->setX($width * 0.36)
							          ->setSize($width * 0.07, 4)
							          ->setTextSize(2)
							          ->setTextColor('4f0')
							          ->setText('Add')
							          ->setTranslate(true)
							          ->setAction(self::ACTION_ADD_FILE);
						}

						if ($canEraseMaps) {
							// 'Erase' button
							$eraseButton = new Label_Button();
							$mapFrame->add($eraseButton);
							$eraseButton->setX($width * 0.44)
							            ->setSize($width * 0.07, 4)
							            ->setTextSize(2)
							            ->setTextColor('f40')
							            ->setText('Erase')
							            ->setTranslate(true)
							            ->setAction(self::ACTION_ERASE_FILE);
						}
					}

					$posY -= 4;
					$index++;
				}
			}
		} else {
			$errorLabel = new Label();
			$frame->add($errorLabel);
			$errorLabel->setY(20)
			           ->setTextColor('f30')
			           ->setText('No access to the directory.')
			           ->setTranslate(true);
		}

		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, self::WIDGET_NAME);
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
			if (!is_readable($fullFileName)) {
				continue;
			}
			if (is_dir($fullFileName)) {
				$mapFiles[$fullFileName . DIRECTORY_SEPARATOR] = $fileName . DIRECTORY_SEPARATOR;
				continue;
			} else {
				if ($this->isMapFileName($fileName)) {
					$mapFiles[$fullFileName] = $fileName;
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

	/**
	 * Handle 'NavigateRoot' action
	 *
	 * @param array  $actionCallback
	 * @param Player $player
	 */
	public function handleNavigateRoot(array $actionCallback, Player $player) {
		$player->destroyCache($this, self::CACHE_FOLDER_PATH);
		$this->showManiaLink($player);
	}

	/**
	 * Handle 'NavigateUp' action
	 *
	 * @param array  $actionCallback
	 * @param Player $player
	 */
	public function handleNavigateUp(array $actionCallback, Player $player) {
		$this->showManiaLink($player, '..');
	}

	/**
	 * Handle 'OpenFolder' Page Action
	 *
	 * @param array  $actionCallback
	 * @param Player $player
	 */
	public function handleOpenFolder(array $actionCallback, Player $player) {
		$actionName = $actionCallback[1][2];
		$folderName = substr($actionName, strlen(self::ACTION_OPEN_FOLDER));
		$this->showManiaLink($player, $folderName);
	}
}
