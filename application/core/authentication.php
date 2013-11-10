<?php

namespace ManiaControl;

/**
 * Class handling authentication levels
 *
 * @author steeffeen & kremsy
 */
class Authentication {
	/**
	 * Constants
	 */
	const AUTH_LEVEL_PLAYER = 0;
	const AUTH_LEVEL_OPERATOR = 1;
	const AUTH_LEVEL_ADMIN = 2;
	const AUTH_LEVEL_SUPERADMIN = 3;
	const AUTH_LEVEL_XSUPERADMIN = 4;
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Construct authentication manager
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->loadConfig();
	}

	/**
	 * Load config and initialize strong superadmins
	 *
	 * @return bool
	 */
	private function loadConfig() {
		$config = FileUtil::loadConfig('authentication.xml');
		$mysqli = $this->maniaControl->database->mysqli;
		
		// Remove all XSuperadmins
		$adminQuery = "UPDATE `" . PlayerHandler::TABLE_PLAYERS . "`
				SET `authLevel` = ?
				WHERE `authLevel` = ?;";
		$adminStatement = $mysqli->prepare($adminQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$adminLevel = self::AUTH_LEVEL_SUPERADMIN;
		$xAdminLevel = self::AUTH_LEVEL_XSUPERADMIN;
		$adminStatement->bind_param('ii', $adminLevel, $xAdminLevel);
		$adminStatement->execute();
		if ($adminStatement->error) {
			trigger_error($adminStatement->error);
		}
		$adminStatement->close();
		
		// Set XSuperAdmins
		$xAdmins = $config->xsuperadmins->xpath('login');
		$adminQuery = "INSERT INTO `" . PlayerHandler::TABLE_PLAYERS . "` (
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
		if (!$player || $authLevel  >= self::AUTH_LEVEL_XSUPERADMIN) {
			return false;
		}
		$mysqli = $this->maniaControl->database->mysqli;
		$authQuery = "INSERT INTO `" . PlayerHandler::TABLE_PLAYERS . "` (
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
		return $success;
	}

	/**
	 * Sends an error message to the login
	 *
	 * @param string $login        	
	 * @return bool
	 */
	public function sendNotAllowed(Player $player) {
		if (!$player) {
			return false;
		}
		return $this->maniaControl->chat->sendError('You do not have the required rights to perform this command!', $player->login);
	}

	/**
	 * Check if the player has enough rights
	 *
	 * @param Player $login        	
	 * @param int $neededAuthLevel        	
	 * @return bool
	 */
	public static function checkRight(Player $player, $neededAuthLevel) {
		if (!$player) {
			return false;
		}
		return ($player->authLevel >= $neededAuthLevel);
	}
}

?>
