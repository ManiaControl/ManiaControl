<?php

namespace ManiaControl\Admin;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\EchoListener;
use ManiaControl\Communication\CommunicationAnswer;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Communication\CommunicationMethods;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Settings\Setting;

/**
 * Class managing Authentication Levels
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AuthenticationManager implements CallbackListener, EchoListener, CommunicationListener, UsageInformationAble {
	use UsageInformationTrait;

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

	const ECHO_GRANT_LEVEL  = 'ManiaControl.AuthenticationManager.GrandLevel';
	const ECHO_REVOKE_LEVEL = 'ManiaControl.AuthenticationManager.RevokeLevel';
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var AuthCommands $authCommands */
	private $authCommands = null;

	/**
	 * Construct a new Authentication Manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->authCommands = new AuthCommands($maniaControl);
		
		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'handleOnInit');

		// Echo Grant Level Command (Usage: sendEcho json_encode("player" => "loginName", "level" => "AUTH_LEVEL_NUMBER")
		$this->maniaControl->getEchoManager()->registerEchoListener(self::ECHO_GRANT_LEVEL, $this, function ($params) {
			if (property_exists($params, 'level') && property_exists($params, 'player')) {
				$player = $this->maniaControl->getPlayerManager()->getPlayer($params->player);
				if ($player) {
					$this->grantAuthLevel($player, $params->level);
				}
			}
		});

		// Echo Revoke Level Command (Usage: sendEcho json_encode("player" => "loginName")
		$this->maniaControl->getEchoManager()->registerEchoListener(self::ECHO_REVOKE_LEVEL, $this, function ($params) {
			if (property_exists($params, 'player')) {
				$player = $this->maniaControl->getPlayerManager()->getPlayer($params->player);
				if ($player) {
					$this->maniaControl->getAuthenticationManager()->grantAuthLevel($player, self::AUTH_LEVEL_PLAYER);
				}
			}
		});


		//Communication Listenings
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::GRANT_AUTH_LEVEL, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, 'level') || !property_exists($data, 'login')) {
				return new CommunicationAnswer("No valid level or player login provided!", true);
			}

			$player = $this->maniaControl->getPlayerManager()->getPlayer($data->login);
			if ($player) {
				$success = $this->grantAuthLevel($player, $data->level);
				return new CommunicationAnswer(array("success" => $success));
			} else {
				return new CommunicationAnswer("Player not found!", true);
			}
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::REVOKE_AUTH_LEVEL, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, 'login')) {
				return new CommunicationAnswer("No valid player login provided!", true);
			}

			$player = $this->maniaControl->getPlayerManager()->getPlayer($data->login);
			if ($player) {
				$success = $this->maniaControl->getAuthenticationManager()->grantAuthLevel($player, self::AUTH_LEVEL_PLAYER);
				return new CommunicationAnswer(array("success" => $success));
			} else {
				return new CommunicationAnswer("Player not found!", true);
			}
		});
	}

	/**
	 * Get Name of the Authentication Level from Level Int
	 *
	 * @api
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
			case self::AUTH_LEVEL_PLAYER:
				return self::AUTH_NAME_PLAYER;
		}
		return '-';
	}

	/**
	 * Get the Authentication Level Int from the given Param
	 *
	 * @api
	 * @param mixed $authLevelParam
	 * @return int
	 */
	public static function getAuthLevelInt($authLevelParam) {
		if (is_object($authLevelParam) && property_exists($authLevelParam, 'authLevel')) {
			return (int) $authLevelParam->authLevel;
		}
		if (is_string($authLevelParam)) {
			return self::getAuthLevel($authLevelParam);
		}
		return (int) $authLevelParam;
	}

	/**
	 * Get Authentication Level Int from Level Name
	 *
	 * @api
	 * @param string $authLevelName
	 * @return int
	 */
	public static function getAuthLevel($authLevelName) {
		$authLevelName = (string) $authLevelName;
		switch ($authLevelName) {
			case self::AUTH_NAME_MASTERADMIN:
				return self::AUTH_LEVEL_MASTERADMIN;
			case self::AUTH_NAME_SUPERADMIN:
				return self::AUTH_LEVEL_SUPERADMIN;
			case self::AUTH_NAME_ADMIN:
				return self::AUTH_LEVEL_ADMIN;
			case self::AUTH_NAME_MODERATOR:
				return self::AUTH_LEVEL_MODERATOR;
			case self::AUTH_NAME_PLAYER:
				return self::AUTH_LEVEL_PLAYER;
		}
		return -1;
	}

	/**
	 * Get the Abbreviation of the Authentication Level from Level Int
	 *
	 * @api
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
	 *
	 * @internal
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
		$masterAdminsElements = $this->maniaControl->getConfig()->xpath('masteradmins');
		if (!$masterAdminsElements) {
			Logger::logError('Missing MasterAdmins configuration!');
			return false;
		}
		$masterAdminsElement = $masterAdminsElements[0];

		$mysqli = $this->maniaControl->getDatabase()->getMysqli();

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
			$login = (string) $loginElement;
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
	 * @api
	 * @param int $authLevel
	 * @return Player[]
	 */
	public function getConnectedAdmins($authLevel = self::AUTH_LEVEL_MODERATOR) {
		$players = $this->maniaControl->getPlayerManager()->getPlayers();
		$admins  = array();
		foreach ($players as $player) {
			if (self::checkRight($player, $authLevel)) {
				array_push($admins, $player);
			}
		}
		return $admins;
	}

	/**
	 * Get all connected Players with less permission than the given Auth Level
	 *
	 * @api
	 * @param int $authLevel
	 * @return Player[]
	 */
	public function getConnectedPlayers($authLevel = self::AUTH_LEVEL_MODERATOR) {
		$players     = $this->maniaControl->getPlayerManager()->getPlayers();
		$playerArray = array();
		foreach ($players as $player) {
			if (!self::checkRight($player, $authLevel)) {
				array_push($playerArray, $player);
			}
		}
		return $playerArray;
	}

	/**
	 * Check whether the Player has enough Rights
	 *
	 * @api
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
	 * @api
	 * @param int $authLevel
	 * @return Player[]
	 */
	public function getAdmins($authLevel = self::AUTH_LEVEL_MODERATOR) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "SELECT `login` FROM `" . PlayerManager::TABLE_PLAYERS . "`
				WHERE `authLevel` >= " . $authLevel . "
				ORDER BY `authLevel` DESC;";
		$result = $mysqli->query($query);
		if (!$result) {
			trigger_error($mysqli->error);
			return null;
		}
		$admins = array();
		while ($row = $result->fetch_object()) {
			$player = $this->maniaControl->getPlayerManager()->getPlayer($row->login, false);
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
	 * @api
	 * @param Player $player
	 * @param int    $authLevel
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

		$mysqli        = $this->maniaControl->getDatabase()->getMysqli();
		$authQuery     = "INSERT INTO `" . PlayerManager::TABLE_PLAYERS . "` (
				`login`,
				`nickname`,
				`authLevel`
				) VALUES (
				?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`authLevel` = VALUES(`authLevel`);";
		$authStatement = $mysqli->prepare($authQuery);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$authStatement->bind_param('ssi', $player->login, $player->nickname, $authLevel);
		$authStatement->execute();
		if ($authStatement->error) {
			trigger_error($authStatement->error);
			$authStatement->close();
			return false;
		}
		$authStatement->close();

		$player->authLevel = $authLevel;
		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_AUTH_LEVEL_CHANGED, $player);

		$this->maniaControl->getActionsMenu()->rebuildAndShowAdminMenu();

		return true;
	}

	/**
	 * Send an Error Message to the Player
	 *
	 * @api
	 * @param Player $player
	 * @return bool
	 */
	public function sendNotAllowed(Player $player) {
		if (!$player) {
			return false;
		}
		return $this->maniaControl->getChat()->sendError('You do not have the required Rights to perform this Action!', $player);
	}

	/**
	 * Checks the permission by a right name
	 *
	 * @api
	 * @param Player $player
	 * @param        $rightName
	 * @return bool
	 */
	public function checkPermission(Player $player, $rightName) {
		$right = $this->maniaControl->getSettingManager()->getSettingValue($this, $rightName);
		return self::checkRight($player, self::getAuthLevel($right));
	}

	/**
	 * Checks the permission by a right name
	 *
	 * @api
	 * @param Plugin $plugin
	 * @param Player $player
	 * @param        $rightName
	 * @return bool
	 */
	public function checkPluginPermission(Plugin $plugin, Player $player, $rightName) {
		$right = $this->maniaControl->getSettingManager()->getSettingValue($plugin, $rightName);
		return self::checkRight($player, self::getAuthLevel($right));
	}

	/**
	 * Define a Minimum Right Level needed for an Action
	 *
	 * @api
	 * @param string $rightName
	 * @param int    $authLevelNeeded
	 * @param string $authLevelsAllowed
	 */
	public function definePermissionLevel($rightName, $authLevelNeeded, $authLevelsAllowed = self::AUTH_LEVEL_MODERATOR) {
		$this->maniaControl->getSettingManager()->initSetting($this, $rightName, self::getPermissionLevelNameArray($authLevelNeeded, $authLevelsAllowed));
	}

	/**
	 * Define a Minimum Right Level needed for an Action
	 *
	 * @api
	 * @param Plugin $plugin
	 * @param string $rightName
	 * @param int    $authLevelNeeded
	 * @param string $authLevelsAllowed
	 */
	public function definePluginPermissionLevel(Plugin $plugin, $rightName, $authLevelNeeded, $authLevelsAllowed = self::AUTH_LEVEL_MODERATOR) {
		$this->maniaControl->getSettingManager()->initSetting($plugin, $rightName, self::getPermissionLevelNameArray($authLevelNeeded, $authLevelsAllowed));
	}

	/**
	 * Get the PermissionLevelNameArray
	 *
	 * @api
	 * @param $authLevelNeeded
	 * @param $authLevelsAllowed
	 * @return array[]
	 */
	public static function getPermissionLevelNameArray($authLevelNeeded, $authLevelsAllowed = self::AUTH_LEVEL_MODERATOR) {
		assert($authLevelNeeded >= $authLevelsAllowed);

		switch ($authLevelsAllowed) {
			case self::AUTH_LEVEL_PLAYER:
				switch ($authLevelNeeded) {
					case self::AUTH_LEVEL_PLAYER:
						return array(self::AUTH_NAME_PLAYER, self::AUTH_NAME_MODERATOR, self::AUTH_NAME_ADMIN, self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN);
					case self::AUTH_LEVEL_MODERATOR:
						return array(self::AUTH_NAME_MODERATOR, self::AUTH_NAME_ADMIN, self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_PLAYER);
					case self::AUTH_LEVEL_ADMIN:
						return array(self::AUTH_NAME_ADMIN, self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_PLAYER, self::AUTH_NAME_MODERATOR);
					case self::AUTH_LEVEL_SUPERADMIN:
						return array(self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_PLAYER, self::AUTH_NAME_MODERATOR, self::AUTH_NAME_ADMIN);
					case self::AUTH_LEVEL_MASTERADMIN:
						return array(self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_PLAYER, self::AUTH_NAME_MODERATOR, self::AUTH_NAME_ADMIN, self::AUTH_NAME_SUPERADMIN);
				}
			break;

			case self::AUTH_LEVEL_MODERATOR:
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
			break;

			case self::AUTH_LEVEL_ADMIN:
				switch ($authLevelNeeded) {
					case self::AUTH_LEVEL_ADMIN:
						return array(self::AUTH_NAME_ADMIN, self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN);
					case self::AUTH_LEVEL_SUPERADMIN:
						return array(self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_ADMIN);
					case self::AUTH_LEVEL_MASTERADMIN:
						return array(self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_ADMIN, self::AUTH_NAME_SUPERADMIN);
				}
			break;

			case self::AUTH_LEVEL_SUPERADMIN:
				switch ($authLevelNeeded) {
					case self::AUTH_LEVEL_SUPERADMIN:
						return array(self::AUTH_NAME_SUPERADMIN, self::AUTH_NAME_MASTERADMIN);
					case self::AUTH_LEVEL_MASTERADMIN:
						return array(self::AUTH_NAME_MASTERADMIN, self::AUTH_NAME_SUPERADMIN);
				}
			break;

			// just for completeness, should not be used this way
			case self::AUTH_LEVEL_MASTERADMIN:
				switch ($authLevelNeeded) {
					case self::AUTH_LEVEL_MASTERADMIN:
						return array(self::AUTH_NAME_MASTERADMIN);
				}
			break;
		}

		return array("-");
	}
}
