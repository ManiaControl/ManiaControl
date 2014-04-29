<?php

namespace ManiaControl\Admin;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Class managing Authentication Levels
 * 
 * @author steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AuthenticationManager implements CallbackListener {
	/*
	 * Constants
	 */
	const AUTH_LEVEL_PLAYER = 0;
	const AUTH_LEVEL_MODERATOR = 1;
	const AUTH_LEVEL_ADMIN = 2;
	const AUTH_LEVEL_SUPERADMIN = 3;
	const AUTH_LEVEL_MASTERADMIN = 4;
	const AUTH_NAME_PLAYER = 'Player';
	const AUTH_NAME_MODERATOR = 'Moderator';
	const AUTH_NAME_ADMIN = 'Admin';
	const AUTH_NAME_SUPERADMIN = 'SuperAdmin';
	const AUTH_NAME_MASTERADMIN = 'MasterAdmin';
	const CB_AUTH_LEVEL_CHANGED = 'AuthenticationManager.AuthLevelChanged';
	
	/*
	 * Public Properties
	 */
	public $authCommands = null;
	
	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Construct a new Authentication Manager
	 * 
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->authCommands = new AuthCommands($maniaControl);
		
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_ONINIT, $this, 'handleOnInit');
	}

	/**
	 * Handle ManiaControl OnInit Callback
	 */
	public function handleOnInit() {
		$this->updateMasterAdmins();
	}

	/**
	 * Update MasterAdmins based on config
	 * 
	 * @return bool
	 */
	private function updateMasterAdmins() {
		$mysqli = $this->maniaControl->database->mysqli;
		
		// Remove all MasterAdmins
		$adminQuery = "UPDATE `" . PlayerManager::TABLE_PLAYERS . "`
				SET `authLevel` = ?
				WHERE `authLevel` = ?;";
		$adminStatement = $mysqli->prepare($adminQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$adminLevel = self::AUTH_LEVEL_SUPERADMIN;
		$masterAdminLevel = self::AUTH_LEVEL_MASTERADMIN;
		$adminStatement->bind_param('ii', $adminLevel, $masterAdminLevel);
		$adminStatement->execute();
		if ($adminStatement->error) {
			trigger_error($adminStatement->error);
		}
		$adminStatement->close();
		
		// Set MasterAdmins
		$masterAdmins = $this->maniaControl->config->masteradmins->xpath('login');
		$adminQuery = "INSERT INTO `" . PlayerManager::TABLE_PLAYERS . "` (
				`login`,
				`authLevel`
				) VALUES (
				?, ?
				) ON DUPLICATE KEY UPDATE
				`authLevel` = VALUES(`authLevel`);";
		$adminStatement = $mysqli->prepare($adminQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$success = true;
		foreach ($masterAdmins as $masterAdmin) {
			$login = (string) $masterAdmin;
			$adminStatement->bind_param('si', $login, $masterAdminLevel);
			$adminStatement->execute();
			if ($adminStatement->error) {
				trigger_error($adminStatement->error);
				$success = false;
			}
		}
		$adminStatement->close();
		return $success;
	}

	/**
	 * Get a List of all Admins
	 * 
	 * @param $authLevel
	 * @return array null
	 */
	public function getAdmins($authLevel = -1) {
		$mysqli = $this->maniaControl->database->mysqli;
		if ($authLevel < 0) {
			$query = "SELECT * FROM `" . PlayerManager::TABLE_PLAYERS . "` WHERE `authLevel` > 0 ORDER BY `authLevel` DESC;";
		}
		else {
			$query = "SELECT * FROM `" . PlayerManager::TABLE_PLAYERS . "` WHERE `authLevel` = " . $authLevel . ";";
		}
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return null;
		}
		$admins = array();
		while ($row = $result->fetch_object()) {
			array_push($admins, $row);
		}
		return $admins;
	}

	/**
	 * Grant the Auth Level to the Player
	 * 
	 * @param Player $player
	 * @param int $authLevel
	 * @return bool
	 */
	public function grantAuthLevel(Player &$player, $authLevel) {
		if (!$player || !is_numeric($authLevel)) {
			return false;
		}
		$authLevel = (int) $authLevel;
		if ($authLevel >= self::AUTH_LEVEL_MASTERADMIN) {
			return false;
		}
		
		$mysqli = $this->maniaControl->database->mysqli;
		$authQuery = "INSERT INTO `" . PlayerManager::TABLE_PLAYERS . "` (
				`login`,
				`authLevel`
				) VALUES (
				?, ?
				) ON DUPLICATE KEY UPDATE
				`authLevel` = VALUES(`authLevel`);";
		$authStatement = $mysqli->prepare($authQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$authStatement->bind_param('si', $player->login, $authLevel);
		$authStatement->execute();
		if ($authStatement->error) {
			trigger_error($authStatement->error);
			$authStatement->close();
			return false;
		}
		$authStatement->close();
		
		$player->authLevel = $authLevel;
		$this->maniaControl->callbackManager->triggerCallback(self::CB_AUTH_LEVEL_CHANGED, $player);
		
		return true;
	}

	/**
	 * Send an Error Message to the Player
	 * 
	 * @param Player $player
	 * @return bool
	 */
	public function sendNotAllowed(Player $player) {
		if (!$player) {
			return false;
		}
		return $this->maniaControl->chat->sendError('You do not have the required Rights to perform this Action!', $player->login);
	}

	/**
	 * Check if the Player has enough Rights
	 * 
	 * @param Player $player
	 * @param int $neededAuthLevel
	 * @return bool
	 */
	public static function checkRight(Player $player, $neededAuthLevel) {
		return ($player->authLevel >= $neededAuthLevel);
	}

	/**
	 * Checks the permission by a right name
	 * 
	 * @param Player $player
	 * @param $rightName
	 * @return bool
	 */
	public function checkPermission(Player $player, $rightName) {
		$right = $this->maniaControl->settingManager->getSetting($this, $rightName);
		return $this->checkRight($player, $right);
	}

	/**
	 * Defines a Minimum Right Level needed for an action
	 * 
	 * @param $rightName
	 * @param $authLevelNeeded
	 */
	public function definePermissionLevel($rightName, $authLevelNeeded) {
		$this->maniaControl->settingManager->initSetting($this, $rightName, $authLevelNeeded);
	}

	/**
	 * Get Name of the Authentication Level from Level Int
	 * 
	 * @param mixed $authLevelInt
	 * @return string
	 */
	public static function getAuthLevelName($authLevelInt) {
		$authLevelInt = self::getAuthLevelInt($authLevelInt);
		if ($authLevelInt === self::AUTH_LEVEL_MASTERADMIN) {
			return self::AUTH_NAME_MASTERADMIN;
		}
		if ($authLevelInt === self::AUTH_LEVEL_SUPERADMIN) {
			return self::AUTH_NAME_SUPERADMIN;
		}
		if ($authLevelInt === self::AUTH_LEVEL_ADMIN) {
			return self::AUTH_NAME_ADMIN;
		}
		if ($authLevelInt === self::AUTH_LEVEL_MODERATOR) {
			return self::AUTH_NAME_MODERATOR;
		}
		return self::AUTH_NAME_PLAYER;
	}

	/**
	 * Get the Abbreviation of the Authentication Level from Level Int
	 * 
	 * @param mixed $authLevelInt
	 * @return string
	 */
	public static function getAuthLevelAbbreviation($authLevelInt) {
		$authLevelInt = self::getAuthLevelInt($authLevelInt);
		if ($authLevelInt === self::AUTH_LEVEL_MASTERADMIN) {
			return 'MA';
		}
		if ($authLevelInt === self::AUTH_LEVEL_SUPERADMIN) {
			return 'SA';
		}
		if ($authLevelInt === self::AUTH_LEVEL_ADMIN) {
			return 'AD';
		}
		if ($authLevelInt === self::AUTH_LEVEL_MODERATOR) {
			return 'MOD';
		}
		return '';
	}

	/**
	 * Get Authentication Level Int from Level Name
	 * 
	 * @param string $authLevelName
	 * @return int
	 */
	public static function getAuthLevel($authLevelName) {
		$authLevelName = strtolower($authLevelName);
		if ($authLevelName === self::AUTH_NAME_MASTERADMIN) {
			return self::AUTH_LEVEL_MASTERADMIN;
		}
		if ($authLevelName === self::AUTH_NAME_SUPERADMIN) {
			return self::AUTH_LEVEL_SUPERADMIN;
		}
		if ($authLevelName === self::AUTH_NAME_ADMIN) {
			return self::AUTH_LEVEL_ADMIN;
		}
		if ($authLevelName === self::AUTH_NAME_MODERATOR) {
			return self::AUTH_LEVEL_MODERATOR;
		}
		return self::AUTH_LEVEL_PLAYER;
	}

	/**
	 * Get the Authentication Level Int from the given Param
	 * 
	 * @param mixed $authLevelParam
	 * @return int
	 */
	public static function getAuthLevelInt($authLevelParam) {
		if (is_object($authLevelParam) && property_exists($authLevelParam, 'authLevel')) {
			return (int) $authLevelParam->authLevel;
		}
		return (int) $authLevelParam;
	}
}
