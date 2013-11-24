<?php

namespace ManiaControl\Admin;

use ManiaControl\FileUtil;
use ManiaControl\ManiaControl;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Class managing authentication levels
 *
 * @author steeffeen & kremsy
 */
class AuthenticationManager implements CommandListener {
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

	/**
	 * Construct authentication manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->loadConfig();
		
		$this->maniaControl->commandManager->registerCommandListener('/addadmin', $this, 'command_AddAdmin');
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
		if (!$player || !is_int($authLevel) || $authLevel  >= self::AUTH_LEVEL_MASTERADMIN) {
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
	 * Handle //addadmin command
	 *
	 * @param array $chatCallback        	
	 * @param
	 *        	\ManiaControl\Players\Player
	 * @return boolean
	 */
	public function command_AddAdmin(array $chatCallback, Player $player) {
		var_dump($chatCallback);
		if (!$this->checkRight($player, self::AUTH_LEVEL_SUPERADMIN)) {
			$this->sendNotAllowed($player);
			return false;
		}
		$text = $chatCallback[1][2];
		$commandParts = explode(' ', $text);
		if (!array_key_exists(1, $commandParts)) {
			$this->sendAddAdminUsageInfo($player);
			return false;
		}
		return true;
	}

	/**
	 * Send usage example for //addadmin command
	 *
	 * @param Player $player        	
	 * @return bool
	 */
	private function sendAddAdminUsageInfo(Player $player) {
		$message = "Usage Example: '//addadmin login'";
		return $this->maniaControl->chat->sendUsageInfo($message);
	}

	/**
	 * Check if the player has enough rights
	 *
	 * @param \ManiaControl\Players\Player $login        	
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
