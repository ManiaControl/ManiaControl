<?php

namespace TheM;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;
use ManiaControl\Settings\SettingManager;

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
	// Added to check if status of player changed
	private $specStatus = array();

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @throws \Exception
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$allowOthers = $this->maniaControl->settingManager->getSettingValue($this, self::ACCEPT_OTHER_MODES);
		if (!$allowOthers && $this->maniaControl->server->titleId != 'SMStormRoyal@nadeolabs') {
			$error = 'This plugin only supports Royal (check Settings)!';
			throw new \Exception($error);
		}

		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'changePointlimit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'changePointlimit');
		// added to check if player enters or leaves specmode
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERINFOCHANGED, $this, 'checkStatus');
		// added to check if scriptsettings have changed and act on it.
		$this->maniaControl->callbackManager->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'handleSettingChangedCallback');
		// Added to add additional pointslimit check on beginning of the round.
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACK, $this, 'handleCallbacks');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_MODESCRIPTCALLBACKARRAY, $this, 'handleCallbacks');
		
		// Added to fill variable with current player status
		foreach($this->maniaControl->playerManager->getPlayers() as $player) {
			if ($player->isSpectator) {
				$this->specStatus[$player->login]=1;
			} else {
				$this->specStatus[$player->login]=0;
			}
		}
		$this->changePointlimit();
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
	
	// Handle Beginround
	public function handleCallbacks(array $callback) {
		$callbackName = $callback[1][0];
		switch($callbackName) {
			
			// ROYAL
		case 'LibXmlRpc_BeginRound':	
		
			$this->changePointlimit();
		break;
		}
		
	}

	// handle scriptsettings changes
	public function handleSettingChangedCallback($settingClass) {
		if ($settingClass !== get_class()) {
			return;
		}
		$this->changePointlimit();
	}
	
	// on player info changed, this checks to see if it is about the spectator status
	public function checkStatus(array $callback) {
		$specStatus  = (int)$callback[1][0]['SpectatorStatus'];
		$login  = $callback[1][0]['Login'];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		
		if($this->specStatus[$login] != $specStatus)
		{
			$this->changePointlimit();
		}
		
	}

	/**
	 * Function called on player connect and disconnect, changing the pointlimit.
	 *
	 * @param Player $player
	 */
	public function changePointlimit() {
		$numberOfPlayers    = 0;
		$numberOfSpectators = 0;
		

		/** @var  Player $player */
		foreach($this->maniaControl->playerManager->getPlayers() as $player) {
			if ($player->isSpectator) {
				$this->specStatus[$player->login]=1; // used for player status changes
				$numberOfSpectators++;
			} else {
				$numberOfPlayers++;
				$this->specStatus[$player->login]=0; // used for player status changes
			}
		}

		$pointlimit = ($numberOfPlayers * $this->maniaControl->settingManager->getSettingValue($this, self::DYNPNT_MULTIPLIER)) + $this->maniaControl->settingManager->getSettingValue($this, self::DYNPNT_OFFSET);

		$min_value = $this->maniaControl->settingManager->getSettingValue($this, self::DYNPNT_MIN);
		$max_value = $this->maniaControl->settingManager->getSettingValue($this, self::DYNPNT_MAX);
		if ($pointlimit < $min_value) {
			$pointlimit = $min_value;
		}
		if ($pointlimit > $max_value) {
			$pointlimit = $max_value;
		}
		// added to only change the pointlimit if it needs changing
		$setting = $this->maniaControl->client->getModeScriptSettings(); // get current pointlimit
		$old = $setting['S_MapPointsLimit'];
		
		if ( $old != $pointlimit)
		{
			try{
				$this->maniaControl->client->setModeScriptSettings(array('S_MapPointsLimit' => $pointlimit));
			}catch(FaultException $e){
			}
	
			$this->maniaControl->chat->sendChat('$<$fffPointlimit changed to : $> '.$pointlimit ." (was $old)"); // notice about pointlimit change
		}

	}
}
