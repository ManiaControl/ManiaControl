<?php

namespace ManiaControl\Players;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\EchoListener;
use ManiaControl\Communication\CommunicationAnswer;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Communication\CommunicationMethods;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use Maniaplanet\DedicatedServer\Xmlrpc\AlreadyInListException;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;
use Maniaplanet\DedicatedServer\Xmlrpc\NotInListException;
use Maniaplanet\DedicatedServer\Xmlrpc\PlayerStateException;
use Maniaplanet\DedicatedServer\Xmlrpc\ServerOptionsException;
use Maniaplanet\DedicatedServer\Xmlrpc\UnknownPlayerException;

/**
 * Player Actions Class
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerActions implements EchoListener, CommunicationListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const TEAM_BLUE                     = 0;
	const TEAM_RED                      = 1;
	const SPECTATOR_USER_SELECTABLE     = 0;
	const SPECTATOR_SPECTATOR           = 1;
	const SPECTATOR_PLAYER              = 2;
	const SPECTATOR_BUT_KEEP_SELECTABLE = 3;
	const ECHO_WARN_PLAYER              = 'ManiaControl.PlayerManager.WarnPlayer';

	/*
	 * Permission Setting Constants
	 */
	const SETTING_PERMISSION_FORCE_PLAYER_PLAY = 'Force Player to Play';
	const SETTING_PERMISSION_FORCE_PLAYER_TEAM = 'Force Player to Team';
	const SETTING_PERMISSION_FORCE_PLAYER_SPEC = 'Force Player to Spec';
	const SETTING_PERMISSION_MUTE_PLAYER       = 'Mute Player';
	const SETTING_PERMISSION_WARN_PLAYER       = 'Warn Player';
	const SETTING_PERMISSION_KICK_PLAYER       = 'Kick Player';
	const SETTING_PERMISSION_BAN_PLAYER        = 'Ban Player';


	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct a new PlayerActions instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_BAN_PLAYER, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_KICK_PLAYER, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_WARN_PLAYER, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_MUTE_PLAYER, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_FORCE_PLAYER_PLAY, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_FORCE_PLAYER_TEAM, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_FORCE_PLAYER_SPEC, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		// Echo Warn Command (Usage: sendEcho json_encode("player" => "loginName")
		$this->maniaControl->getEchoManager()->registerEchoListener(self::ECHO_WARN_PLAYER, $this, function ($params) {
			$this->warnPlayer(null, $params->player, false);
		});

		//Communication Manager Methods
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::WARN_PLAYER, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "login")) {
				return new CommunicationAnswer("You have to provide a valid player Login", true);
			}
			$success = $this->warnPlayer(null, $data->login, false);
			return new CommunicationAnswer(array("success" => $success));
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::MUTE_PLAYER, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "login")) {
				return new CommunicationAnswer("You have to provide a valid player Login", true);
			}
			$success = $this->mutePlayer(null, $data->login, false);
			return new CommunicationAnswer(array("success" => $success));
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::UNMUTE_PLAYER, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "login")) {
				return new CommunicationAnswer("You have to provide a valid player Login", true);
			}
			$success = $this->unMutePlayer(null, $data->login, false);
			return new CommunicationAnswer(array("success" => $success));
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::KICK_PLAYER, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "login")) {
				return new CommunicationAnswer("You have to provide a valid player Login", true);
			}

			$message = "";
			if (property_exists($data, "message")) {
				$message = $data->message;
			}

			$success = $this->kickPlayer(null, $data->login, $message, false);

			return new CommunicationAnswer(array("success" => $success));
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::FORCE_PLAYER_TO_SPEC, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "login")) {
				return new CommunicationAnswer("You have to provide a valid player Login", true);
			}
			//TODO allow parameters like spectator state
			$success = $this->forcePlayerToSpectator(null, $data->login, self::SPECTATOR_BUT_KEEP_SELECTABLE, true, false);
			return new CommunicationAnswer(array("success" => $success));
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::FORCE_PLAYER_TO_PLAY, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "login")) {
				return new CommunicationAnswer("You have to provide a valid player Login", true);
			}

			//TODO allow parameters like spectator state
			if (property_exists($data, "teamId")) {
				$success = $this->forcePlayerToTeam(null, $data->login, $data->teamId, false);
			} else {
				$success = $this->forcePlayerToPlay(null, $data->login, true, true, false);
			}

			return new CommunicationAnswer(array("success" => $success));
		});
	}

	/**
	 * Force a Player to a certain Team
	 *
	 * @api
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int    $teamId
	 * @param bool   $calledByAdmin
	 * @return bool
	 */
	public function forcePlayerToTeam($adminLogin, $targetLogin, $teamId, $calledByAdmin = true) {
		if ($calledByAdmin) {
			$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
			if (!$admin) {
				return false;
			}
			if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_FORCE_PLAYER_TEAM)) {
				$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
				return false;
			}
		}
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return false;
		}

		if ($target->isSpectator) {
			try {
				if (!$this->forcePlayerToPlay($adminLogin, $targetLogin, true, false, $calledByAdmin)) {
					return false;
				}
			} catch (FaultException $e) {
				$this->maniaControl->getChat()->sendException($e, $adminLogin);
			}
		}

		try {
			$this->maniaControl->getClient()->forcePlayerTeam($target->login, $teamId);
		} catch (ServerOptionsException $e) {
			$this->forcePlayerToPlay($adminLogin, $targetLogin);
			return false;
		} catch (UnknownPlayerException $e) {
			$this->maniaControl->getChat()->sendException($e, $adminLogin);
			return false;
		} catch (GameModeException $e) {
			$this->maniaControl->getChat()->sendException($e, $adminLogin);
			return false;
		}

		$message = false;
		$teamName = '';
		if ($teamId === self::TEAM_BLUE) {
			$teamName = '$00fBlue';
		} elseif ($teamId === self::TEAM_RED) {
			$teamName = '$f00Red';
		}

		if ($teamName) {
			if ($calledByAdmin) {
				$title = $admin->getAuthLevelName();
				$message = $this->maniaControl->getChat()->formatMessage(
					"{$title} %s forced %s into the %s-Team!",
					$admin,
					$target,
					$teamName
				);
			} else {
				$message = $this->maniaControl->getChat()->formatMessage(
					'%s got forced %s into the %s-Team!',
					$target,
					$teamName
				);
			}
		}

		if ($message) {
			$this->maniaControl->getChat()->sendInformation($message);
			Logger::logInfo($chatMessage, true);
		}

		return true;
	}

	/**
	 * Force a Player to Play
	 *
	 * @api
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param bool   $userIsAbleToSelect
	 * @param bool   $displayAnnouncement
	 * @param bool   $calledByAdmin
	 * @return bool
	 */
	public function forcePlayerToPlay($adminLogin, $targetLogin, $userIsAbleToSelect = true, $displayAnnouncement = true, $calledByAdmin = true) {
		if ($calledByAdmin) {
			$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
			if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_FORCE_PLAYER_PLAY)) {
				$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
				return false;
			}
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return false;
		}

		try {
			$this->maniaControl->getClient()->forceSpectator($target->login, self::SPECTATOR_PLAYER);
		} catch (ServerOptionsException $e) {
			$this->maniaControl->getChat()->sendException($e, $adminLogin);
			return false;
		} catch (UnknownPlayerException $e) {
			$this->maniaControl->getChat()->sendException($e, $adminLogin);
			return false;
		}

		if ($userIsAbleToSelect) {
			try {
				$this->maniaControl->getClient()->forceSpectator($target->login, self::SPECTATOR_USER_SELECTABLE);
			} catch (ServerOptionsException $e) {
				if ($calledByAdmin) {
					$this->maniaControl->getChat()->sendException($e, $admin);
				}
				return false;
			}
		}

		// Announce force
		if ($displayAnnouncement) {
			if ($calledByAdmin) {
				$title = $admin->getAuthLevelName();
				$message = $this->maniaControl->getChat()->formatMessage(
					"{$title} %s forced %s to Play!",
					$admin,
					$target
				);
			} else {
				$message = $this->maniaControl->getChat()->formatMessage(
					'%s got forced to Play!',
					$target
				);
			}

			$this->maniaControl->getChat()->sendInformation($message);
		}

		return true;
	}

	/**
	 * Force a Player to Spectator
	 *
	 * @api
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int    $spectatorState
	 * @param bool   $releaseSlot
	 * @param bool   $calledByAdmin
	 * @return bool
	 */
	public function forcePlayerToSpectator($adminLogin, $targetLogin, $spectatorState = self::SPECTATOR_BUT_KEEP_SELECTABLE, $releaseSlot = true, $calledByAdmin = true) {
		if ($calledByAdmin) {
			$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
			if (!$admin) {
				return false;
			}

			if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_FORCE_PLAYER_SPEC)) {
				$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
				return false;
			}
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);

		if (!$target || $target->isSpectator) {
			return false;
		}

		try {
			$this->maniaControl->getClient()->forceSpectator($target->login, $spectatorState);
		} catch (ServerOptionsException $e) {
			$this->maniaControl->getChat()->sendException($e, $adminLogin);
			return false;
		}

		if ($calledByAdmin) {
			$title = $admin->getAuthLevelName();
			$message = $this->maniaControl->getChat()->formatMessage(
				"{$title} %s forced %s to Spectator!",
				$admin,
				$target
			);
		} else {
			$message = $this->maniaControl->getChat()->formatMessage(
				'%s got forced to Spectator!',
				$target
			);
		}

		$this->maniaControl->getChat()->sendInformation($message);
		Logger::logInfo($message, true);

		if ($releaseSlot) {
			// Free player slot
			try {
				$this->maniaControl->getClient()->spectatorReleasePlayerSlot($target->login);
			} catch (PlayerStateException $e) {
			} catch (UnknownPlayerException $e) {
			}
		}

		return true;
	}

	/**
	 * UnMute a Player
	 *
	 * @api
	 * @param      $adminLogin
	 * @param      $targetLogin
	 * @param bool $calledByAdmin
	 * @return bool
	 */
	public function unMutePlayer($adminLogin, $targetLogin, $calledByAdmin = true) {
		if ($calledByAdmin) {
			$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
			if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_MUTE_PLAYER)) {
				$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
				return false;
			}
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return false;
		}

		try {
			$this->maniaControl->getClient()->unIgnore($targetLogin);
		} catch (NotInListException $e) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'%s is not muted!',
				$targetLogin
			);
			$this->maniaControl->getChat()->sendError($message, $adminLogin);
			return false;
		}

		if ($calledByAdmin) {
			$title = $admin->getAuthLevelName();
			$message = $this->maniaControl->getChat()->formatMessage(
				"{$title} %s un-muted %s!",
				$admin,
				$target
			);
		} else {
			$message = $this->maniaControl->getChat()->formatMessage(
				'%s got un-muted!',
				$target
			);
		}

		$this->maniaControl->getChat()->sendInformation($message);
		Logger::logInfo($message, true);

		return true;
	}

	/**
	 * Mute a Player
	 *
	 * @api
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param bool   $calledByAdmin
	 * @return bool
	 */
	public function mutePlayer($adminLogin, $targetLogin, $calledByAdmin = true) {
		if ($calledByAdmin) {
			$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
			if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_MUTE_PLAYER)) {
				$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
				return false;
			}
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return false;
		}

		try {
			$this->maniaControl->getClient()->ignore($targetLogin);
		} catch (AlreadyInListException $e) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'%s is already muted!',
				$targetLogin
			);
			$this->maniaControl->getChat()->sendError($message, $adminLogin);
			return false;
		} catch (UnknownPlayerException $e) {
			$this->maniaControl->getChat()->sendException($e, $adminLogin);
			return false;
		}

		if ($calledByAdmin) {
			$title = $admin->getAuthLevelName();
			$message = $this->maniaControl->getChat()->formatMessage(
				"{$title} %s muted %s!",
				$admin,
				$target
			);
		} else {
			$message = $this->maniaControl->getChat()->formatMessage(
				'%s got muted!',
				$target
			);
		}

		$this->maniaControl->getChat()->sendInformation($message);
		Logger::logInfo($message, true);

		return true;
	}

	/**
	 * Warn a Player
	 *
	 * @api
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param bool   $calledByAdmin
	 * @return bool
	 */
	public function warnPlayer($adminLogin, $targetLogin, $calledByAdmin = true) {
		if ($calledByAdmin) {
			$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
			if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_WARN_PLAYER)) {
				$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
				return false;
			}
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return false;
		}

		// Display warning message
		$message = '$s$f00This is an administrative warning.{br}{br}$gWhatever you wrote or you have done is against {br} our server\'s policy.
						{br}Not respecting other players, or{br}using offensive language might result in a{br}$f00kick, or ban $ff0the next time.
						{br}{br}$gThe server administrators.';
		$message = preg_split('/{br}/', $message);

		// Build Manialink
		$width        = 80;
		$height       = 50;
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowSubStyle();

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame     = new Frame();
		$maniaLink->addChild($frame);
		$frame->setPosition(0, 10);
		$frame->setZ(ManialinkManager::MAIN_MANIALINK_Z_VALUE);

		// Background
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->setZ(-1);

		// Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->addChild($closeQuad);
		$closeQuad->setPosition($width / 2 - 3, $height / 2 - 3, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle($closeQuad::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		// Headline
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setY($height / 2 - 5);
		$label->setStyle($label::STYLE_TextCardMedium);
		$label->setTextSize(4);
		$label->setText('Administrative Warning');
		$label->setTextColor('f00');

		$posY = $height / 2 - 15;
		foreach ($message as $line) {
			// Message lines
			$label = new Label_Text();
			$frame->addChild($label);
			$label->setY($posY);
			$label->setStyle($label::STYLE_TextCardMedium);
			$label->setText($line);
			$label->setTextColor('ff0');
			$label->setTextSize(1.3);
			$posY -= 4;
		}

		// Display manialink
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $target);

		if ($calledByAdmin) {
			$title = $admin->getAuthLevelName();
			$message = $this->maniaControl->getChat()->formatMessage(
				"{$title} %s warned %s!",
				$admin,
				$target
			);
		} else {
			$message = $this->maniaControl->getChat()->formatMessage(
				'%s got warned!',
				$target
			);
		}

		$this->maniaControl->getChat()->sendInformation($message);
		Logger::log($message, true);

		return true;
	}


	/**
	 * Kick a Player
	 *
	 * @api
	 * @param        $adminLogin
	 * @param        $targetLogin
	 * @param string $message
	 * @param bool   $calledByAdmin
	 * @return bool
	 */
	public function kickPlayer($adminLogin, $targetLogin, $message = '', $calledByAdmin = true) {
		if ($calledByAdmin) {
			$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
			if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_KICK_PLAYER)) {
				$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
				return false;
			}
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return false;
		}

		if ($target->isFakePlayer()) {
			try {
				$this->maniaControl->getClient()->disconnectFakePlayer($target->login);
			} catch (PlayerStateException $e) {
				$this->maniaControl->getChat()->sendException($e, $adminLogin);
				return false;
			} catch (UnknownPlayerException $e) {
				$this->maniaControl->getChat()->sendException($e, $adminLogin);
				return false;
			}
		} else {
			try {
				$this->maniaControl->getClient()->kick($target->login, $message);
			} catch (UnknownPlayerException $e) {
				$this->maniaControl->getChat()->sendException($e, $adminLogin);
				return false;
			}
		}

		if ($calledByAdmin) {
			$title = $admin->getAuthLevelName();
			$message = $this->maniaControl->getChat()->formatMessage(
				"{$title} %s kicked %s!",
				$admin,
				$target
			);
		} else {
			$message = $this->maniaControl->getChat()->formatMessage(
				'%s got kicked!',
				$target
			);
		}

		$this->maniaControl->getChat()->sendInformation($message);
		Logger::logInfo($message, true);

		return true;
	}


	/**
	 * Ban a Player
	 *
	 * @api
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param string $message
	 */
	public function banPlayer($adminLogin, $targetLogin, $message = '') {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_BAN_PLAYER)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return;
		}

		//Todo Validate (Problem: Not connected player isFakePlayer)
		if ($target->isOfficial && $target->isFakePlayer()) {
			$this->maniaControl->getChat()->sendError('It is not possible to Ban a bot!', $admin);
			return;
		}

		try {
			$this->maniaControl->getClient()->ban($target->login, $message);
		} catch (UnknownPlayerException $e) {
			$this->maniaControl->getChat()->sendError('Unknown player!', $admin);
			return;
		}

		$title = $admin->getAuthLevelName();
		$message = $this->maniaControl->getChat()->formatMessage(
			"{$title} %s banned %s!",
			$admin,
			$target
		);
		$this->maniaControl->getChat()->sendInformation($message);
		Logger::logInfo($message, true);
	}

	/**
	 * Unbans a Player
	 *
	 * @api
	 * @param string $adminLogin
	 * @param string $targetLogin
	 */
	public function unBanPlayer($adminLogin, $targetLogin) {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_BAN_PLAYER)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return;
		}

		//Todo Validate (Problem: Not connected player isFakePlayer)
		if ($target->isOfficial && $target->isFakePlayer()) {
			$this->maniaControl->getChat()->sendError('It is not possible to unban a bot!', $admin);
			return;
		}

		try {
			$this->maniaControl->getClient()->unBan($targetLogin);
		} catch (NotInListException $e) {
			$this->maniaControl->getChat()->sendError('This player is not Banned!', $admin);
			return;
		}
		
		$title = $admin->getAuthLevelName();
		$message = $this->maniaControl->getChat()->formatMessage(
			"{$title} %s unbanned %s!",
			$admin,
			$target
		);
		$this->maniaControl->getChat()->sendInformation($message);
		Logger::logInfo($message, true);
	}

	/**
	 * Grands the Player an Authorization Level
	 *
	 * @api
	 * @deprecated
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int    $authLevel
	 */
	public function grandAuthLevel($adminLogin, $targetLogin, $authLevel) {
		$this->grantAuthLevel($adminLogin, $targetLogin, $authLevel);
	}

	/**
	 * Grants the Player an Authorization Level
	 *
	 * @api
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int    $authLevel
	 */
	public function grantAuthLevel($adminLogin, $targetLogin, $authLevel) {
		$admin  = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$admin || !$target) {
			return;
		}

		$authLevelName = AuthenticationManager::getAuthLevelName($authLevel);
		if (!AuthenticationManager::checkRight($admin, $authLevel + 1)) {
			$this->maniaControl->getChat()->sendError("You do not have the permission to add a {$authLevelName}!", $admin);
			return;
		}

		if (AuthenticationManager::checkRight($target, $authLevel)) {
			$this->maniaControl->getChat()->sendError("This Player is already {$authLevelName}!", $admin);
			return;
		}

		$success = $this->maniaControl->getAuthenticationManager()->grantAuthLevel($target, $authLevel);
		if (!$success) {
			$this->maniaControl->getChat()->sendError('Error occurred.', $admin);
			return;
		}

		$title = $admin->getAuthLevelName();
		$message = $this->maniaControl->getChat()->formatMessage(
			"{$title} %s added %s as {$authLevelName}!",
			$admin,
			$target
		);
		$this->maniaControl->getChat()->sendInformation($message);
		Logger::logInfo($message, true);
	}

	/**
	 * Revokes all Rights from the Player
	 *
	 * @api
	 * @param string $adminLogin
	 * @param string $targetLogin
	 */
	public function revokeAuthLevel($adminLogin, $targetLogin) {
		$admin  = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$admin || !$target) {
			return;
		}

		if ($admin->authLevel <= $target->authLevel) {
			$this->maniaControl->getChat()->sendError("You cannot revoke the Rights of a {$target->getAuthLevelName()}!", $admin);
			return;
		}

		if (AuthenticationManager::checkRight($target, AuthenticationManager::AUTH_LEVEL_MASTERADMIN)) {
			$this->maniaControl->getChat()->sendError("MasterAdmins cannot be removed!", $admin);
			return;
		}

		$success = $this->maniaControl->getAuthenticationManager()->grantAuthLevel($target, AuthenticationManager::AUTH_LEVEL_PLAYER);
		if (!$success) {
			$this->maniaControl->getChat()->sendError('Error occurred.', $admin);
			return;
		}

		$title = $admin->getAuthLevelName();
		$message = $this->maniaControl->getChat()->formatMessage(
			"{$title} %s revoked the Rights of %s!",
			$admin,
			$target
		);
		$this->maniaControl->getChat()->sendInformation($message);
		Logger::logInfo($message, true);
	}
}
