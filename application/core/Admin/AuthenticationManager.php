<?php

namespace ManiaControl\Admin;

use ManiaControl\FileUtil;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

require_once __DIR__ . '/AuthCommands.php';

/**
 * Class managing authentication levels
 *
 * @author steeffeen & kremsy
 */
class AuthenticationManager {
	/**
	 * Constants
	 */
	const AUTH_LEVEL_PLAYER = 0;
	const AUTH_LEVEL_OPERATOR = 1;
	const AUTH_LEVEL_ADMIN = 2;
	const AUTH_LEVEL_SUPERADMIN = 3;
	const AUTH_LEVEL_MASTERADMIN = 4;
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $authCommands = null;

	/**
	 * Construct authentication manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->loadConfig();
		
		$this->authCommands = new AuthCommands($maniaControl);
	}

	/**
	 * Load config and initialize strong superadmins
	 *
	 * @return bool
	 */
	private function loadConfig() {
		$config = FileUtil::loadConfig('authentication.xml');
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
		$xAdminLevel = self::AUTH_LEVEL_MASTERADMIN;
		$adminStatement->bind_param('ii', $adminLevel, $xAdminLevel);
		$adminStatement->execute();
		if ($adminStatement->error) {
			trigger_error($adminStatement->error);
		}
		$adminStatement->close();
		
		// Set MasterAdmins
		$xAdmins = $config->masteradmins->xpath('login');
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
		$adminStatement->bind_param('si', $login, $xAdminLevel);
		$success = true;
		foreach ($xAdmins as $xAdmin) {
			$login = (string) $xAdmin;
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
	 * Grant the auth level to the player
	 *
	 * @param Player $player        	
	 * @param int $authLevel        	
	 * @return bool
	 */
	public function grantAuthLevel(Player $player, $authLevel) {
		if (!$player || !is_int($authLevel) || $authLevel >= self::AUTH_LEVEL_MASTERADMIN) {
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
		$success = $authStatement->execute();
		if ($authStatement->error) {
			trigger_error($authStatement->error);
			$authStatement->close();
			return false;
		}
		$authStatement->close();
		return $success;
	}

	/**
	 * Sends an error message to the player
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function sendNotAllowed(Player $player) {
		if (!$player) {
			return false;
		}
		return $this->maniaControl->chat->sendError('You do not have the required Rights to perform this Command!', $player->login);
	}

	/**
	 * Check if the player has enough rights
	 *
	 * @param Player $player
	 * @param int $neededAuthLevel        	
	 * @return bool
	 */
	public static function checkRight(Player $player, $neededAuthLevel) {
		return ($player->authLevel >= $neededAuthLevel);
	}

	/**
	 * Get Name of the Authentication Level from Level Int
	 *
	 * @param int $authLevelInt        	
	 * @return string
	 */
	public static function getAuthLevelName($authLevelInt) {
		if ($authLevelInt == self::AUTH_LEVEL_MASTERADMIN) {
			return 'MasterAdmin';
		}
		if ($authLevelInt == self::AUTH_LEVEL_SUPERADMIN) {
			return 'SuperAdmin';
		}
		if ($authLevelInt == self::AUTH_LEVEL_ADMIN) {
			return 'Admin';
		}
		if ($authLevelInt == self::AUTH_LEVEL_OPERATOR) {
			return 'Operator';
		}
		return 'Player';
	}

	/**
	 * Get Authentication Level Int from Level Name
	 *
	 * @param string $authLevelName        	
	 * @return int
	 */
	public static function getAuthLevel($authLevelName) {
		$authLevelName = strtolower($authLevelName);
		if ($authLevelName == 'MasterAdmin') {
			return self::AUTH_LEVEL_MASTERADMIN;
		}
		if ($authLevelName == 'SuperAdmin') {
			return self::AUTH_LEVEL_SUPERADMIN;
		}
		if ($authLevelName == 'Admin') {
			return self::AUTH_LEVEL_ADMIN;
		}
		if ($authLevelName == 'Operator') {
			return self::AUTH_LEVEL_OPERATOR;
		}
		return self::AUTH_LEVEL_PLAYER;
	}
}

?>
