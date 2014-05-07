<?php

namespace TheM;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;

/**
 * Dynamic Pointlimit plugin
 * Based on the Linearmode plugin for MPAseco by kremsy
 *
 * @author TheM
 */
class DynamicPointlimitPlugin implements CallbackListener, CommandListener, Plugin {
	/**
	 * Constants
	 */
	const ID      = 21;
	const VERSION = 0.11;

	const DYNPNT_MULTIPLIER  = 'Pointlimit multiplier';
	const DYNPNT_OFFSET      = 'Pointlimit offset';
	const DYNPNT_MIN         = 'Minimum pointlimit';
	const DYNPNT_MAX         = 'Maximum pointlimit';
	const ACCEPT_OTHER_MODES = 'Activate in Other mode as Royal';

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		$maniaControl->settingManager->initSetting(get_class(), self::ACCEPT_OTHER_MODES, false);
		$maniaControl->settingManager->initSetting(get_class(), self::DYNPNT_MULTIPLIER, 10);
		$maniaControl->settingManager->initSetting(get_class(), self::DYNPNT_OFFSET, 0);
		$maniaControl->settingManager->initSetting(get_class(), self::DYNPNT_MIN, 30);
		$maniaControl->settingManager->initSetting(get_class(), self::DYNPNT_MAX, 200);
	}

	/**
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @throws \Exception
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'changePointlimit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'changePointlimit');

		$allowOthers = $this->maniaControl->settingManager->getSetting($this, self::ACCEPT_OTHER_MODES);
		if (!$allowOthers && $this->maniaControl->server->titleId != 'SMStormRoyal@nadeolabs') {
			$error = 'This plugin only supports Royal (check Settings)!';
			throw new \Exception($error);
		}
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
	}

	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName() {
		return 'Dynamic Pointlimit Plugin';
	}

	/**
	 * Get Plugin Version
	 *
	 * @return float
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor() {
		return 'TheM';
	}

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription() {
		return 'Plugin offers a dynamic pointlimit according to the amount of players on the server.';
	}

	/**
	 * Function called on player connect and disconnect, changing the pointlimit.
	 *
	 * @param Player $player
	 */
	public function changePointlimit(Player $player) {
		$numberOfPlayers    = 0;
		$numberOfSpectators = 0;

		/** @var  Player $player */
		foreach($this->maniaControl->playerManager->getPlayers() as $player) {
			if ($player->isSpectator) {
				$numberOfSpectators++;
			} else {
				$numberOfPlayers++;
			}
		}

		$pointlimit = ($numberOfPlayers * $this->maniaControl->settingManager->getSetting($this, self::DYNPNT_MULTIPLIER)) + $this->maniaControl->settingManager->getSetting($this, self::DYNPNT_OFFSET);

		$min_value = $this->maniaControl->settingManager->getSetting($this, self::DYNPNT_MIN);
		$max_value = $this->maniaControl->settingManager->getSetting($this, self::DYNPNT_MAX);
		if ($pointlimit < $min_value) {
			$pointlimit = $min_value;
		}
		if ($pointlimit > $max_value) {
			$pointlimit = $max_value;
		}

		$this->maniaControl->client->setModeScriptSettings(array('S_MapPointsLimit' => $pointlimit));
	}
}