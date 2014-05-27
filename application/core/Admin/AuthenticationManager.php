<?php

namespace ManiaControl\Admin;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Settings\Setting;

/**
 * Class managing Authentication Levels
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AuthenticationManager implements CallbackListener {
	/*
	 * Constants
	 */
	const AUTH_LEVEL_PLAYER      = 0;
	const AUTH_LEVEL_MODERATOR   = 1;
	const AUTH_LEVEL_ADMIN       = 2;
	const AUTH_LEVEL_SUPERADMIN  = 3;
	const AUTH_LEVEL_MASTERADMIN = 4;
	const AUTH_NAME_PLAYER       = 'Player';
	const AUTH_NAME_MODERATOR    = 'Moderator';
	const AUTH_NAME_ADMIN        = 'Admin';
	const AUTH_NAME_SUPERADMIN   = 'SuperAdmin';
	const AUTH_NAME_MASTERADMIN  = 'MasterAdmin';
	const CB_AUTH_LEVEL_CHANGED  = 'AuthenticationManager.AuthLevelChanged';

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

		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::ONINIT, $this, 'handleOnInit');
	}

	/**
	 * Get Name of the Authentication Level from Level Int
	 *
	 * @param mixed $authLevelInt
	 * @return string
	 */
	public static function getAuthLevelName($authLevelInt) {
		$authLevelInt = self::getAuthLevelInt($authLevelInt);
		switch ($authLevelInt) {
			case self::AUTH_LEVEL_MASTERADMIN:
				return self::AUTH_NAME_MASTERADMIN;
			case self::AUTH_LEVEL_SUPERADMIN:
				return self::AUTH_NAME_SUPERADMIN;
			case self::AUTH_LEVEL_ADMIN:
				return self::AUTH_NAME_ADMIN;
			case self::AUTH_LEVEL_MODERATOR:
				return self::AUTH_NAME_MODERATOR;
		}
		return self::AUTH_NAME_PLAYER;
	}

	/**
	 * Get the Authentication Level Int from the given Param
	 *
	 * @param mixed $authLevelParam
	 * @return int
	 */
	public static function getAuthLevelInt($authLevelParam) {
		if (is_object($authLevelParam) && property_exists($authLevelParam, 'authLevel')) {
			return (int)$authLevelParam->authLevel;
		}
		if (is_string($authLevelParam)) {
			return self::getAuthLevel($authLevelParam);
		}
		return (int)$authLevelParam;
	}

	/**
	 * Get Authentication Level Int from Level Name
	 *
	 * @param string $authLevelName
	 * @return int
	 */
	public static function getAuthLevel($authLevelName) {
		$authLevelName = (string)$authLevelName;
		switch ($authLevelName) {
			case self::AUTH_NAME_MASTERADMIN:
				return self::AUTH_LEVEL_MASTERADMIN;
			case self::AUTH_NAME_SUPERADMIN:
				return self::AUTH_LEVEL_SUPERADMIN;
			case self::AUTH_NAME_ADMIN:
				return self::AUTH_LEVEL_ADMIN;
			case self::AUTH_NAME_MODERATOR:
				return self::AUTH_LEVEL_MODERATOR;
		}
		return self::AUTH_LEVEL_PLAYER;
	}

	/**
	 * Get the Abbreviation of the Authentication Level from Level Int
	 *
	 * @param mixed $authLevelInt
	 * @return string
	 */
	public static function getAuthLevelAbbreviation($authLevelInt) {
		$authLevelInt = self::getAuthLevelInt($authLevelInt);
		switch ($authLevelInt) {
			case self::AUTH_LEVEL_MASTERADMIN:
				return 'MA';
			case self::AUTH_LEVEL_SUPERADMIN:
				return 'SA';
			case self::AUTH_LEVEL_ADMIN:
				return 'AD';
			case self::AUTH_LEVEL_MODERATOR:
				return 'MOD';
		}
		return '';
	}

	/**
	 * Handle ManiaControl OnInit Callback
	 */
	public function handleOnInit() {
		$this->updateMasterAdmins();
	}

	/**
	 * Update MasterAdmins based on Config
	 *
	 * @return bool
	 */
	private function updateMasterAdmins() {
		$masterAdminsElements = $this->maniaControl->config->xpath('masteradmins');
		if (!$masterAdminsElements) {
			$this->maniaControl->log("Missing MasterAdmins configuration!", true);
			return false;
		}
		$masterAdminsElement = $masterAdminsElements[0];

		$mysqli = $this->maniaControl->database->mysqli;

		// Remove all MasterAdmins
		$adminQuery     = "UPDATE `" . PlayerManager::TABLE_PLAYERS . "`
				SET `authLevel` = ?
				WHERE `authLevel` = ?;";
		$adminStatement = $mysqli->prepare($adminQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$adminLevel       = self::AUTH_LEVEL_SUPERADMIN;
		$masterAdminLevel = self::AUTH_LEVEL_MASTERADMIN;
		$adminStatement->bind_param('ii', $adminLevel, $masterAdminLevel);
		$adminStatement->execute();
		if ($adminStatement->error) {
			trigger_error($adminStatement->error);
		}
		$adminStatement->close();

		// Set configured MasterAdmins
		$loginElements  = $masterAdminsElement->xpath('login');
		$adminQuery     = "INSERT INTO `" . PlayerManager::TABLE_PLAYERS . "` (
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
		foreach ($loginElements as $loginElement) {
			$login = (string)$loginElement;
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
	 * Get all connected Players with at least the given Auth Level
	 *
	 * @param int $authLevel
	 * @return Player[]
	 */
	public function getConnectedAdmins($authLevel = self::AUTH_LEVEL_MODERATOR) {
		$players = $this->maniaControl->playerManager->getPlayers();
		$admins  = array();
		foreach ($players as $player) {
			if (self::checkRight($player, $authLevel)) {
				array_push($admins, $player);
			}
		}
		return $admins;
	}

	/**
	 * Check whether the Player has enough Rights
	 *
	 * @param Player      $player
	 * @param int|Setting $neededAuthLevel
	 * @return bool
	 */
	public static function checkRight(Player $player, $neededAuthLevel) {
		if ($neededAuthLevel instanceof Setting) {
			$neededAuthLevel = $neededAuthLevel->value;
		}
		return ($player->authLevel >= $neededAuthLevel);
	}

	/**
	 * Get a List of all Admins
	 *
	 * @param int $authLevel
	 * @return Player[]
	 */
	public function getAdmins($authLevel = self::AUTH_LEVEL_MODERATOR) {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "SELECT `login` FROM `" . PlayerManager::TABLE_PLAYERS . "`
				WHERE `authLevel` > " . $authLevel . "
				ORDER BY `authLevel` DESC;";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return null;
		}
		$admins = array();
		while ($row = $result->fetch_object()) {
			$player = $this->maniaControl->playerManager->getPlayer($row->login, false);
			if ($player) {
				array_push($admins, $player);
			}
		}
		$result->free();
		return $admins;
	}

	/**
	 * Grant the Auth Level to the Player
	 *
	 * @param Player $player
	 * @param int    $authLevel
	 * @return bool
	 */
	public function grantAuthLevel(Player &$player, $authLevel) {
		if (!$player || !is_numeric($authLevel)) {
			return false;
		}
		$authLevel = (int)$authLevel;
		if ($authLevel >= self::AUTH_LEVEL_MASTERADMIN) {
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
	 * Checks the permission by a right name
	 *
	 * @param Player $player
	 * @param        $rightName
	 * @return bool
	 */
	public function checkPermission(Player $player, $rightName) {
		$right = $this->maniaControl->settingManager->getSettingValue($this, $rightName);
		return $this->checkRight($player, $this->getAuthLevel($right));
	}

	/**
	 * Define a Minimum Right Level needed for an Action
	 *
	 * @param string $rightName
	 * @param int    $authLevelNeeded
	 */
	public function definePermissionLevel($rightName, $authLevelNeeded) {
		$this->maniaControl->settingManager->initSetting($this, $rightName, $this->getPermissionLevelNameArray($authLevelNeeded));
	}

	/**
	 * Get the PermissionLevelNameArray
	 *
	 * @param $authLevelNeeded
	 * @return array[]
	 */
	private function getPermissionLevelNameArray($authLevelNeeded) {
		switch ($authLevelNeeded) {
			case self::AUTH_LEVEL_MODERATOR:
				return array(self::AUTH_NAME_MODERATOR, self::AUTH_NAME_ADMIN, self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN);
			case self::AUTH_LEVEL_ADMIN:
				return array(self::AUTH_NAME_ADMIN, self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_MODERATOR);
			case self::AUTH_LEVEL_SUPERADMIN:
				return array(self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_MODERATOR, self::AUTH_NAME_ADMIN);
			case self::AUTH_LEVEL_MASTERADMIN:
				return array(self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_MODERATOR, self::AUTH_NAME_ADMIN, self::AUTH_NAME_SUPERADMIN);
		}
		return array("-");
	}
}
