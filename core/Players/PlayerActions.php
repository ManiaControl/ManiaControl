<?php

namespace ManiaControl\Players;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use ManiaControl\Admin\AuthenticationManager;
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
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerActions {
	/*
	 * Constants
	 */
	const TEAM_BLUE                     = 0;
	const TEAM_RED                      = 1;
	const SPECTATOR_USER_SELECTABLE     = 0;
	const SPECTATOR_SPECTATOR           = 1;
	const SPECTATOR_PLAYER              = 2;
	const SPECTATOR_BUT_KEEP_SELECTABLE = 3;

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
	}

	/**
	 * Force a Player to a certain Team
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int    $teamId
	 */
	public function forcePlayerToTeam($adminLogin, $targetLogin, $teamId) {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_FORCE_PLAYER_TEAM)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target || !$admin) {
			return;
		}

		if ($target->isSpectator) {
			try {
				if (!$this->forcePlayerToPlay($adminLogin, $targetLogin, true, false)) {
					return;
				}
			} catch (FaultException $exception) {
				$this->maniaControl->getChat()->sendException($exception, $admin);
			}
		}

		try {
			$this->maniaControl->getClient()->forcePlayerTeam($target->login, $teamId);
		} catch (ServerOptionsException $exception) {
			$this->forcePlayerToPlay($adminLogin, $targetLogin);
			return;
		} catch (GameModeException $exception) {
			$this->maniaControl->getChat()->sendException($exception, $admin);
			return;
		}

		$chatMessage = false;
		$title       = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin->authLevel);
		if ($teamId === self::TEAM_BLUE) {
			$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' forced ' . $target->getEscapedNickname() . ' into the Blue-Team!';
		} else if ($teamId === self::TEAM_RED) {
			$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' forced ' . $target->getEscapedNickname() . ' into the Red-Team!';
		}
		if (!$chatMessage) {
			return;
		}
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);
	}

	/**
	 * Force a Player to Play
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param bool   $userIsAbleToSelect
	 * @param bool   $displayAnnouncement
	 * @return bool
	 */
	public function forcePlayerToPlay($adminLogin, $targetLogin, $userIsAbleToSelect = true, $displayAnnouncement = true) {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_FORCE_PLAYER_PLAY)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return false;
		}
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return false;
		}

		try {
			$this->maniaControl->getClient()->forceSpectator($target->login, self::SPECTATOR_PLAYER);
		} catch (ServerOptionsException $exception) {
			$this->maniaControl->getChat()->sendException($exception, $admin);
			return false;
		}

		if ($userIsAbleToSelect) {
			try {
				$this->maniaControl->getClient()->forceSpectator($target->login, self::SPECTATOR_USER_SELECTABLE);
			} catch (ServerOptionsException $exception) {
				$this->maniaControl->getChat()->sendException($exception, $admin);
				return false;
			}
		}

		// Announce force
		if ($displayAnnouncement) {
			$chatMessage = $admin->getEscapedNickname() . ' forced ' . $target->getEscapedNickname() . ' to Play!';
			$this->maniaControl->getChat()->sendInformation($chatMessage);
		}

		return true;
	}

	/**
	 * Force a Player to Spectator
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int    $spectatorState
	 * @param bool   $releaseSlot
	 */
	public function forcePlayerToSpectator($adminLogin, $targetLogin, $spectatorState = self::SPECTATOR_BUT_KEEP_SELECTABLE, $releaseSlot = true) {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_FORCE_PLAYER_SPEC)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);

		if (!$admin || !$target || $target->isSpectator) {
			return;
		}

		try {
			$this->maniaControl->getClient()->forceSpectator($target->login, $spectatorState);
		} catch (ServerOptionsException $exception) {
			$this->maniaControl->getChat()->sendException($exception, $admin->login);
			return;
		}

		$title       = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' forced ' . $target->getEscapedNickname() . ' to Spectator!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);

		if ($releaseSlot) {
			// Free player slot
			try {
				$this->maniaControl->getClient()->spectatorReleasePlayerSlot($target->login);
			} catch (PlayerStateException $e) {
			} catch (UnknownPlayerException $e) {
			}
		}
	}

	/**
	 * UnMute a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 */
	public function unMutePlayer($adminLogin, $targetLogin) {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_MUTE_PLAYER)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);

		if (!$target) {
			return;
		}

		try {
			$this->maniaControl->getClient()->unIgnore($targetLogin);
		} catch (NotInListException $e) {
			$this->maniaControl->getChat()->sendError('Player is not ignored!', $adminLogin);
			return;
		}

		$title       = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' un-muted ' . $target->getEscapedNickname() . '!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);
	}

	/**
	 * Mute a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 */
	public function mutePlayer($adminLogin, $targetLogin) {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_MUTE_PLAYER)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);

		if (!$target) {
			return;
		}

		try {
			$this->maniaControl->getClient()->ignore($targetLogin);
		} catch (AlreadyInListException $e) {
			$this->maniaControl->getChat()->sendError("Player already ignored!", $adminLogin);
			return;
		}

		$title       = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' muted ' . $target->getEscapedNickname() . '!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);
	}

	/**
	 * Warn a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 */
	public function warnPlayer($adminLogin, $targetLogin) {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_WARN_PLAYER)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);

		if (!$target) {
			return;
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
		$maniaLink->add($frame);
		$frame->setPosition(0, 10);

		// Background
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		// Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($width * 0.473, $height * 0.457, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle($closeQuad::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		// Headline
		$label = new Label_Text();
		$frame->add($label);
		$label->setY($height / 2 - 5);
		$label->setStyle($label::STYLE_TextCardMedium);
		$label->setTextSize(4);
		$label->setText('Administrative Warning');
		$label->setTextColor('f00');

		$posY = $height / 2 - 15;
		foreach ($message as $line) {
			// Message lines
			$label = new Label_Text();
			$frame->add($label);
			$label->setY($posY);
			$label->setStyle($label::STYLE_TextCardMedium);
			$label->setText($line);
			$label->setTextColor('ff0');
			$label->setTextSize(1.3);
			$posY -= 4;
		}

		// Display manialink
		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $target);

		// Announce warning
		$title       = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' warned ' . $target->getEscapedNickname() . '!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::log($chatMessage, true);
	}

	/**
	 * Kick a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param string $message
	 */
	public function kickPlayer($adminLogin, $targetLogin, $message = '') {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_KICK_PLAYER)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return;
		}

		try {
			if ($target->isFakePlayer()) {
				$this->maniaControl->getClient()->disconnectFakePlayer($target->login);
			} else {
				$this->maniaControl->getClient()->kick($target->login, $message);
			}
		} catch (UnknownPlayerException $e) {
			$this->maniaControl->getChat()->sendException($e, $admin);
			return;
		}

		// Announce kick
		$title       = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' kicked ' . $target->getEscapedNickname() . '!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);
	}

	/**
	 * Ban a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param string $message
	 */
	public function banPlayer($adminLogin, $targetLogin, $message = '') {
		$admin = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($admin, self::SETTING_PERMISSION_BAN_PLAYER)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$target) {
			return;
		}

		if ($target->isFakePlayer()) {
			$this->maniaControl->getChat()->sendError('It is not possible to Ban a bot', $admin);
			return;
		}

		$this->maniaControl->getClient()->ban($target->login, $message);

		// Announce ban
		$title       = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' banned ' . $target->getEscapedNickname() . '!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);
	}

	/**
	 * Grands the Player an Authorization Level
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int    $authLevel
	 */
	public function grandAuthLevel($adminLogin, $targetLogin, $authLevel) {
		$admin  = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$admin || !$target) {
			return;
		}

		$authLevelName = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($authLevel);
		if (!$this->maniaControl->getAuthenticationManager()->checkRight($admin, $authLevel + 1)
		) {
			$this->maniaControl->getChat()->sendError("You don't have the permission to add a {$authLevelName}!", $admin);
			return;
		}

		if ($this->maniaControl->getAuthenticationManager()->checkRight($target, $authLevel)
		) {
			$this->maniaControl->getChat()->sendError("This Player is already {$authLevelName}!", $admin);
			return;
		}

		$success = $this->maniaControl->getAuthenticationManager()->grantAuthLevel($target, $authLevel);
		if (!$success) {
			$this->maniaControl->getChat()->sendError('Error occurred.', $admin);
			return;
		}

		// Announce granting
		$title       = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' added ' . $target->getEscapedNickname() . ' as $< ' . $authLevelName . '$>!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);
	}

	/**
	 * Revokes all Rights from the Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 */
	public function revokeAuthLevel($adminLogin, $targetLogin) {
		$admin  = $this->maniaControl->getPlayerManager()->getPlayer($adminLogin);
		$target = $this->maniaControl->getPlayerManager()->getPlayer($targetLogin);
		if (!$admin || !$target) {
			return;
		}

		if (!$this->maniaControl->getAuthenticationManager()->checkRight($admin, $target->authLevel + 1)
		) {
			$title = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($target->authLevel);
			$this->maniaControl->getChat()->sendError("You can't revoke the Rights of a {$title}!", $admin);
			return;
		}

		if ($this->maniaControl->getAuthenticationManager()->checkRight($target, AuthenticationManager::AUTH_LEVEL_MASTERADMIN)
		) {
			$this->maniaControl->getChat()->sendError("MasterAdmins can't be removed!", $admin);
			return;
		}

		$success = $this->maniaControl->getAuthenticationManager()->grantAuthLevel($target, AuthenticationManager::AUTH_LEVEL_PLAYER);
		if (!$success) {
			$this->maniaControl->getChat()->sendError('Error occurred.', $admin);
			return;
		}

		// Announce revoke
		$title       = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' ' . $admin->getEscapedNickname() . ' revoked the Rights of ' . $target->getEscapedNickname() . '!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);
	}

	/**
	 * Check if a Player is muted
	 *
	 * @deprecated see Player/isMuted()
	 */
	public function isPlayerMuted($login) {
		return $this->maniaControl->getPlayerManager()->getPlayer($login)->isMuted();
	}
}
