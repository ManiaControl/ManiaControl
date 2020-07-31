<?php

namespace ManiaControl;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Communication\CommunicationAnswer;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Communication\CommunicationMethods;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Maps\Map;
use ManiaControl\Players\Player;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\Xmlrpc\UnknownPlayerException;

/**
 * Chat Utility Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Chat implements CallbackListener, CommunicationListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const SETTING_FORMAT_ERROR                       = 'Error Format';
	const SETTING_FORMAT_INFORMATION                 = 'Information Format';
	const SETTING_FORMAT_SUCCESS                     = 'Success Format';
	const SETTING_FORMAT_USAGEINFO                   = 'UsageInfo Format';
	const SETTING_FORMAT_MESSAGE_INPUT_COLOR         = 'Format Message Input Color';
	const SETTING_FORMAT_MESSAGE_MAP_AUTHOR_LOGIN    = 'Format Message Add Map Author Login';
	const SETTING_FORMAT_MESSAGE_MAP_AUTHOR_NICKNAME = 'Format Message Add Map Author Nickname';
	const SETTING_FORMAT_MESSAGE_PLAYER_LOGIN        = 'Format Message Add Player Login';
	const SETTING_PUBLIC_PREFIX                      = 'Public Messages Prefix';
	const SETTING_PRIVATE_PREFIX                     = 'Private Messages Prefix';
	const CHAT_BUFFER_SIZE                           = 200;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $chatBuffer   = array();

	/**
	 * Construct chat utility
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_ERROR, '$f30');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_INFORMATION, '$fff');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_SUCCESS, '$0f0');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_USAGEINFO, '$f80');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_MESSAGE_INPUT_COLOR, '$fff');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_MESSAGE_MAP_AUTHOR_LOGIN, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_MESSAGE_MAP_AUTHOR_NICKNAME, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_FORMAT_MESSAGE_PLAYER_LOGIN, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_PUBLIC_PREFIX, '» ');
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_PRIVATE_PREFIX, '»» ');

		//Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERCHAT, $this, 'onPlayerChat');

		//Socket Listenings
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::SEND_CHAT_MESSAGE, $this, "communcationSendChat");
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::GET_SERVER_CHAT, $this, function ($data) {
			return new CommunicationAnswer($this->chatBuffer);
		});
	}

	/**
	 * Build the chat message prefix
	 *
	 * @param string|bool  $prefixParam
	 * @param string|array $login
	 * @return string
	 */
	private function buildPrefix($prefixParam, $login = null) {
		if (is_string($prefixParam)) {
			return $prefixParam;
		}
		if ($prefixParam === true) {
			if ($login) {
				$prefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_PRIVATE_PREFIX);
			} else {
				$prefix = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_PUBLIC_PREFIX);
			}
			return $prefix;
		}
		return '';
	}

	/**
	 * Handles SendChat Communication Request
	 *
	 * @param $data
	 * @return CommunicationAnswer
	 */
	public function communcationSendChat($data) {
		if (!is_object($data) || !property_exists($data, "message")) {
			return new CommunicationAnswer("You have to provide a valid message", true);
		}

		$prefix = true;
		if (property_exists($data, "prefix")) {
			$prefix = $data->prefix;
		}

		$login = null;
		if (property_exists($data, "login")) {
			$login = $data->login;
		}

		$adminLevel = 0;
		if (property_exists($data, "adminLevel")) {
			$adminLevel = $data->adminLevel;
		}

		$type = "default";
		if (property_exists($data, "type")) {
			$type = $data->type;
		}

		switch ($type) {
			case "information":
				if ($adminLevel) {
					$this->sendInformationToAdmins($data->message, $adminLevel, $prefix);
				} else {
					$this->sendInformation($data->message, $login, $prefix);
				}
				break;
			case "success":
				if ($adminLevel) {
					$this->sendInformationToAdmins($data->message, $adminLevel, $prefix);
				} else {
					$this->sendSuccess($data->message, $login, $prefix);
				}
				break;
			case "error":
				if ($adminLevel) {
					$this->sendErrorToAdmins($data->message, $adminLevel, $prefix);
				} else {
					$this->sendError($data->message, $login, $prefix);
				}
				break;
			case "usage":
				$this->sendUsageInfo($data->message, $login, $prefix);
				break;
			default:
				if ($adminLevel) {
					$this->sendMessageToAdmins($data->message, $adminLevel, $prefix);
				} else {
					$this->sendChat($data->message, $login, $prefix);
				}
		}

		return new CommunicationAnswer();
	}

	/**
	 * Format the given message with the given inputs and colors the inputs.
	 * @param string $message
	 * @param mixed ...$inputs
	 * @return string
	 */
	public function formatMessage($message, ...$inputs) {
		$addMapAuthorLogin = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_MESSAGE_MAP_AUTHOR_LOGIN);
		$addMapAuthorNickname = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_MESSAGE_MAP_AUTHOR_NICKNAME);
		$addPlayerLogin = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_MESSAGE_PLAYER_LOGIN);
		$formatInputColor = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_MESSAGE_INPUT_COLOR);

		$formattedInputs = array($message);
		foreach ($inputs as $input) {
			$strInput = null;

			if (is_bool($input)) {
				$strInput = $input ? 'true' : 'false';
			} elseif ($input instanceof Map) {
				$strInput = $input->getEscapedName();
				if ($addMapAuthorNickname && $input->authorNick) {
					$strInput .= " (by {$input->authorNick}";
					if ($addMapAuthorLogin && $input->authorLogin) {
						$strInput .= " ({$input->authorLogin})";
					}
					$strInput .= ")";
				} elseif ($addMapAuthorLogin && $input->authorLogin) {
					$strInput .= " (by {$input->authorLogin})";
				}
			} elseif ($input instanceof Player) {
				$strInput = $input->getEscapedNickname();
				if ($addPlayerLogin && $input->login) {
					$strInput .= " ({$input->login})";
				}
			} else {
				$strInput = strval($input);
			}

			array_push($formattedInputs, Formatter::escapeText($formatInputColor . $strInput));
		}

		return call_user_func_array('sprintf', $formattedInputs);
	}

	/**
	 * Stores the ChatMessage in the Buffer
	 *
	 * @param $data
	 */
	public function onPlayerChat($data) {
		$login  = $data[1][1];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);

		$nickname = "";
		if ($player) {
			$nickname = $player->nickname;
		}
		array_push($this->chatBuffer, array("user" => $login, "nickname" => $nickname, "message" => $data[1][2]));
		if (count($this->chatBuffer) > self::CHAT_BUFFER_SIZE) {
			array_shift($this->chatBuffer);
		}
	}

	/**
	 * Send a chat message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @param bool        $multiCall
	 * @return bool
	 */
	public function sendChat($message, $login = null, $prefix = true, $multiCall = true) {
		if (!$this->maniaControl->getClient()) {
			return false;
		}

		$prefix      = $this->buildPrefix($prefix, $login);
		$chatMessage = '$<$z$ff0' . $prefix . $message . '$>';

		if ($login) {
			if (!is_array($login)) {
				$login = Player::parseLogin($login);
			}
			try {
				return $this->maniaControl->getClient()->chatSendServerMessage($chatMessage, $login, $multiCall);
			} catch (UnknownPlayerException $e) {
				return false;
			}
		}

		return $this->maniaControl->getClient()->chatSendServerMessage($chatMessage, null, $multiCall);
	}

	/**
	 * Send an Error Message to all Connected Admins
	 *
	 * @param string $message
	 * @param int    $minLevel
	 * @param bool   $prefix
	 */
	public function sendErrorToAdmins($message, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_ERROR);
		$this->sendMessageToAdmins($format . $message, $minLevel, $prefix);
	}

	/**
	 * Send an Error Message to the Chat
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @return bool
	 */
	public function sendError($message, $login = null, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_ERROR);
		return $this->sendChat($format . $message, $login, $prefix);
	}

	/**
	 * Send the Exception Information to the Chat
	 *
	 * @param \Exception $exception
	 * @param string     $login
	 * @return bool
	 */
	public function sendException(\Exception $exception, $login = null) {
		$message = "Exception occurred: '{$exception->getMessage()}' ({$exception->getCode()})";
		return $this->sendError($message, $login);
	}

	/**
	 * Send a Exception Message to all Connected Admins
	 *
	 * @param \Exception  $exception
	 * @param int         $minLevel
	 * @param bool|string $prefix
	 */
	public function sendExceptionToAdmins(\Exception $exception, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		$format  = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_ERROR);
		$message = $format . "Exception: '{$exception->getMessage()}' ({$exception->getCode()})";
		$this->sendMessageToAdmins($message, $minLevel, $prefix);
	}

	/**
	 * Send an information message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @param bool        $multiCall
	 * @return bool
	 */
	public function sendInformation($message, $login = null, $prefix = true, $multiCall = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_INFORMATION);
		return $this->sendChat($format . $message, $login, $prefix, $multiCall);
	}

	/**
	 * Sends a Information Message to all connected Admins
	 *
	 * @param string      $message
	 * @param int         $minLevel
	 * @param bool|string $prefix
	 * @return bool
	 */
	public function sendInformationToAdmins($message, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_INFORMATION);
		return $this->sendMessageToAdmins($format . $message, $minLevel, $prefix);
	}

	/**
	 * Send a Message to all connected Admins
	 *
	 * @param string      $message
	 * @param int         $minLevel
	 * @param bool|string $prefix
	 * @return bool
	 */
	public function sendMessageToAdmins($message, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		$admins = $this->maniaControl->getAuthenticationManager()->getConnectedAdmins($minLevel);
		return $this->sendChat($message, $admins, $prefix);
	}

	/**
	 * Send a success message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param bool|string $prefix
	 * @return bool
	 */
	public function sendSuccess($message, $login = null, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_SUCCESS);
		return $this->sendChat($format . $message, $login, $prefix);
	}


	/**
	 * Sends a Success Message to all connected Admins
	 *
	 * @param string      $message
	 * @param int         $minLevel
	 * @param bool|string $prefix
	 * @return bool
	 */
	public function sendSuccessToAdmins($message, $minLevel = AuthenticationManager::AUTH_LEVEL_MODERATOR, $prefix = true) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_SUCCESS);
		return $this->sendMessageToAdmins($format . $message, $minLevel, $prefix);
	}

	/**
	 * Send an usage info message to the given login
	 *
	 * @param string      $message
	 * @param string      $login
	 * @param string|bool $prefix
	 * @return bool
	 */
	public function sendUsageInfo($message, $login = null, $prefix = false) {
		$format = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_FORMAT_USAGEINFO);
		return $this->sendChat($format . $message, $login, $prefix);
	}
}
