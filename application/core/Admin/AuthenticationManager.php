<?php

namespace ManiaControl\Admin;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

require_once __DIR__ . '/AuthCommands.php';

/**
 * Class managing Authentication Levels
 *
 * @author steeffeen & kremsy
 */
class AuthenticationManager {
	/**
	 * Constants
	 */
	const AUTH_LEVEL_PLAYER      = 0;
	const AUTH_LEVEL_MODERATOR   = 1;
	const AUTH_LEVEL_ADMIN       = 2;
	const AUTH_LEVEL_SUPERADMIN  = 3;
	const AUTH_LEVEL_MASTERADMIN = 4;
	const CB_AUTH_LEVEL_CHANGED  = 'AuthenticationManager.AuthLevelChanged';

	/**
	 * Public Properties
	 */
	public $authCommands = null;

	/**
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Construct authentication manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->updateMasterAdmins();

		$this->authCommands = new AuthCommands($maniaControl);
	}


	/**
	 * Set MasterAdmins
	 *
	 * @return bool
	 */
	private function updateMasterAdmins() {
		$mysqli = $this->maniaControl->database->mysqli;

		// Remove all MasterAdmins
		$adminQuery     = "UPDATE `" . PlayerManager::TABLE_PLAYERS . "`
				SET `authLevel` = ?
				WHERE `authLevel` = ?;";
		$adminStatement = $mysqli->prepare($adminQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$adminLevel       = self::AUTH_LEVEL_SUPERADMIN;
		$masterAdminLevel = self::AUTH_LEVEL_MASTERADMIN;
		$adminStatement->bind_param('ii', $adminLevel, $masterAdminLevel);
		$adminStatement->execute();
		if($adminStatement->error) {
			trigger_error($adminStatement->error);
		}
		$adminStatement->close();

		// Set MasterAdmins
		$masterAdmins   = $this->maniaControl->config->masteradmins->xpath('login');
		$adminQuery     = "INSERT INTO `" . PlayerManager::TABLE_PLAYERS . "` (
				`login`,
				`authLevel`
				) VALUES (
				?, ?
				) ON DUPLICATE KEY UPDATE
				`authLevel` = VALUES(`authLevel`);";
		$adminStatement = $mysqli->prepare($adminQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$adminStatement->bind_param('si', $login, $masterAdminLevel);
		$success = true;
		foreach($masterAdmins as $masterAdmin) {
			$login = (string)$masterAdmin;
			$adminStatement->execute();
			if($adminStatement->error) {
				trigger_error($adminStatement->error);
				$success = false;
			}
		}
		$adminStatement->close();
		return $success;
	}

	/**
	 * Grant the Auth Level to the Player
	 *
	 * @param Player $player
	 * @param int    $authLevel
	 * @return bool
	 */
	public function grantAuthLevel(Player &$player, $authLevel) {
		if(!$player || !is_numeric($authLevel)) {
			return false;
		}
		$authLevel = (int)$authLevel;
		if($authLevel >= self::AUTH_LEVEL_MASTERADMIN) {
			return false;
		}

		$mysqli        = $this->maniaControl->database->mysqli;
		$authQuery     = "INSERT INTO `" . PlayerManager::TABLE_PLAYERS . "` (
				`login`,
				`authLevel`
				) VALUES (
				?, ?
				) ON DUPLICATE KEY UPDATE
				`authLevel` = VALUES(`authLevel`);";
		$authStatement = $mysqli->prepare($authQuery);
		if($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$authStatement->bind_param('si', $player->login, $authLevel);
		$authStatement->execute();
		if($authStatement->error) {
			trigger_error($authStatement->error);
			$authStatement->close();
			return false;
		}
		$authStatement->close();

		$player->authLevel = $authLevel;
		$this->maniaControl->callbackManager->triggerCallback(self::CB_AUTH_LEVEL_CHANGED, array(self::CB_AUTH_LEVEL_CHANGED, $player));

		return true;
	}

	/**
	 * Send an Error Message to the Player
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function sendNotAllowed(Player $player) {
		if(!$player) {
			return false;
		}
		return $this->maniaControl->chat->sendError('You do not have the required Rights to perform this Command!', $player->login);
	}

	/**
	 * Check if the Player has enough Rights
	 *
	 * @param Player $player
	 * @param int    $neededAuthLevel
	 * @return bool
	 */
	public static function checkRight(Player $player, $neededAuthLevel) {
		return ($player->authLevel >= $neededAuthLevel);
	}

	/**
	 * Checks the permission  by a right name
	 *
	 * @param Player $player
	 * @param        $rightName
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
	 * @param int $authLevelInt
	 * @return string
	 */
	public static function getAuthLevelName($authLevelInt) {
		if($authLevelInt == self::AUTH_LEVEL_MASTERADMIN) {
			return 'MasterAdmin';
		}
		if($authLevelInt == self::AUTH_LEVEL_SUPERADMIN) {
			return 'SuperAdmin';
		}
		if($authLevelInt == self::AUTH_LEVEL_ADMIN) {
			return 'Admin';
		}
		if($authLevelInt == self::AUTH_LEVEL_MODERATOR) {
			return 'Moderator';
		}
		return 'Player';
	}

	/**
	 * Get the Abbreviation of the Authentication Level from Level Int
	 *
	 * @param int $authLevelInt
	 * @return string
	 */
	public static function getAuthLevelAbbreviation($authLevelInt) {
		if($authLevelInt == self::AUTH_LEVEL_MASTERADMIN) {
			return 'MA';
		}
		if($authLevelInt == self::AUTH_LEVEL_SUPERADMIN) {
			return 'SA';
		}
		if($authLevelInt == self::AUTH_LEVEL_ADMIN) {
			return 'AD';
		}
		if($authLevelInt == self::AUTH_LEVEL_MODERATOR) {
			return 'MOD';
		}
		return 'PL';
	}

	/**
	 * Get Authentication Level Int from Level Name
	 *
	 * @param string $authLevelName
	 * @return int
	 */
	public static function getAuthLevel($authLevelName) {
		$authLevelName = strtolower($authLevelName);
		if($authLevelName == 'MasterAdmin') {
			return self::AUTH_LEVEL_MASTERADMIN;
		}
		if($authLevelName == 'SuperAdmin') {
			return self::AUTH_LEVEL_SUPERADMIN;
		}
		if($authLevelName == 'Admin') {
			return self::AUTH_LEVEL_ADMIN;
		}
		if($authLevelName == 'Moderator') {
			return self::AUTH_LEVEL_MODERATOR;
		}
		return self::AUTH_LEVEL_PLAYER;
	}
}
