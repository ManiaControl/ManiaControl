<?php

namespace ManiaControl\Server;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Files\FileUtil;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Class offering Operations for the Server Directory
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Directory implements CallbackListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct new server directory instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_SERVERSTOP, $this, 'handleServerStopCallback');
	}

	/**
	 * Retrieve the Maps Folder Path
	 *
	 * @return string
	 */
	public function getMapsFolder() {
		return $this->maniaControl->getClient()->getMapsDirectory();
	}

	/**
	 * Retrieve the Skins Folder Path
	 *
	 * @return string
	 */
	public function getSkinsFolder() {
		return $this->maniaControl->getClient()->getSkinsDirectory();
	}

	/**
	 * Handle Server Stop Callback
	 */
	public function handleServerStopCallback() {
		$this->cleanLogsFolder();
		$this->cleanCacheFolder();
	}

	/**
	 * Clean the server logs folder
	 *
	 * @return bool
	 */
	private function cleanLogsFolder() {
		return FileUtil::cleanDirectory($this->getLogsFolder());
	}

	/**
	 * Retrieve the Logs Folder Path
	 *
	 * @return string
	 */
	public function getLogsFolder() {
		return $this->getUserDataFolder() . '..' . DIRECTORY_SEPARATOR . 'Logs' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Retrieve the GameData Folder Path
	 *
	 * @return string
	 */
	public function getGameDataFolder(){
		return $this->getUserDataFolder() . '..' . DIRECTORY_SEPARATOR . 'GameData' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Retrieve the User Data Folder Path
	 *
	 * @return string
	 */
	public function getUserDataFolder() {
		return $this->maniaControl->getClient()->gameDataDirectory();
	}

	/**
	 * Retrieve the Scripts Folder Path
	 *
	 * @return string
	 */
	public function getScriptsFolder(){
		return $this->getGameDataFolder() . 'Scripts'  . DIRECTORY_SEPARATOR;
	}

	/**
	 * @return bool
	 */
	private function cleanCacheFolder() {
		return FileUtil::cleanDirectory($this->getCacheFolder(), 50);
	}

	/**
	 * Retrieve the Cache Folder Path
	 *
	 * @return string
	 */
	public function getCacheFolder() {
		return $this->getUserDataFolder() . '..' . DIRECTORY_SEPARATOR . 'CommonData' . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR;
	}
}
