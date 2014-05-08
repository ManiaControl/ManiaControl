<?php

namespace ManiaControl\Players;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;
use Maniaplanet\DedicatedServer\Xmlrpc\LoginUnknownException;
use Maniaplanet\DedicatedServer\Xmlrpc\NotInTeamModeException;
use Maniaplanet\DedicatedServer\Xmlrpc\PlayerAlreadyIgnoredException;
use Maniaplanet\DedicatedServer\Xmlrpc\PlayerIsNotSpectatorException;
use Maniaplanet\DedicatedServer\Xmlrpc\PlayerNotIgnoredException;

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
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a PlayerActions Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		//Define Rights
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_BAN_PLAYER, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_KICK_PLAYER, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_WARN_PLAYER, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_MUTE_PLAYER, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_FORCE_PLAYER_PLAY, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_FORCE_PLAYER_TEAM, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_FORCE_PLAYER_SPEC, AuthenticationManager::AUTH_LEVEL_MODERATOR);
	}

	/**
	 * Force a Player to a certain Team
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int    $teamId
	 */
	public function forcePlayerToTeam($adminLogin, $targetLogin, $teamId) {
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkPermission($admin, self::SETTING_PERMISSION_FORCE_PLAYER_TEAM)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$target || !$admin) {
			return;
		}

		if ($target->isSpectator) {
			$this->forcePlayerToPlay($adminLogin, $targetLogin, true, false);
		}

		try {
			$this->maniaControl->client->forcePlayerTeam($target->login, $teamId);
		} catch (NotInTeamModeException $e) {
			$this->forcePlayerToPlay($adminLogin, $targetLogin);
			return;
		}

		$chatMessage = false;
		$title       = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		if ($teamId == self::TEAM_BLUE) {
			$chatMessage = $title . ' $<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> into the Blue-Team!';
		} else if ($teamId == self::TEAM_RED) {
			$chatMessage = $title . ' $<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> into the Red-Team!';
		}
		if (!$chatMessage) {
			return;
		}
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Force a Player to Play
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param bool   $userIsAbleToSelect
	 * @param bool   $displayAnnouncement
	 * @internal param int $type
	 */
	public function forcePlayerToPlay($adminLogin, $targetLogin, $userIsAbleToSelect = true, $displayAnnouncement = true) {
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkPermission($admin, self::SETTING_PERMISSION_FORCE_PLAYER_PLAY)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$target) {
			return;
		}

		try {
			$this->maniaControl->client->forceSpectator($target->login, self::SPECTATOR_PLAYER);
		} catch (FaultException $e) {
			//TODO exception 'There are too many players' appeared 28.04.2014, wait for more before add to faultexception
			$this->maniaControl->chat->sendException($e, $admin->login);
			return;
		}

		if ($userIsAbleToSelect) {
			try {
				$this->maniaControl->client->forceSpectator($target->login, self::SPECTATOR_USER_SELECTABLE);
			} catch (Exception $e) {
				$this->maniaControl->chat->sendException($e, $admin->login);
				return;
			}
		}

		// Announce force
		if ($displayAnnouncement) {
			$chatMessage = '$<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> to Play!';
			$this->maniaControl->chat->sendInformation($chatMessage);
		}
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
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkPermission($admin, self::SETTING_PERMISSION_FORCE_PLAYER_SPEC)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);

		if (!$admin || !$target || $target->isSpectator) {
			return;
		}

		try {
			$this->maniaControl->client->forceSpectator($target->login, $spectatorState);
		} catch (Exception $e) {
			$this->maniaControl->chat->sendException($e, $admin->login);
			return;
		}

		$title       = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> to Spectator!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);

		if ($releaseSlot) {
			// Free player slot
			try {
				$this->maniaControl->client->spectatorReleasePlayerSlot($target->login);
			} catch (PlayerIsNotSpectatorException $e) {
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
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkPermission($admin, self::SETTING_PERMISSION_MUTE_PLAYER)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}

		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);

		if (!$target) {
			return;
		}

		try {
			$this->maniaControl->client->unIgnore($targetLogin);
		} catch (PlayerNotIgnoredException $e) {
			$this->maniaControl->chat->sendError("Player is not ignored!");
			return;
		}

		$title       = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> un-muted $<' . $target->nickname . '$>!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Mute a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 */
	public function mutePlayer($adminLogin, $targetLogin) {
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkPermission($admin, self::SETTING_PERMISSION_MUTE_PLAYER)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}

		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);

		if (!$target) {
			return;
		}

		try {
			$this->maniaControl->client->ignore($targetLogin);
		} catch (PlayerAlreadyIgnoredException $e) {
			$this->maniaControl->chat->sendError("Player already ignored!");
			return;
		}

		$title       = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> muted $<' . $target->nickname . '$>!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Warn a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 */
	public function warnPlayer($adminLogin, $targetLogin) {
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkPermission($admin, self::SETTING_PERMISSION_WARN_PLAYER)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}

		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);

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
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

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

		$y = $height / 2 - 15;
		foreach ($message as $line) {
			// Message lines
			$label = new Label_Text();
			$frame->add($label);
			$label->setY($y);
			$label->setStyle(Label_Text::STYLE_TextCardMedium);
			$label->setText($line);
			$label->setTextColor('ff0');
			$label->setTextSize(1.3);
			$y -= 4;
		}

		// Display manialink
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $target);

		// Announce warning
		$title       = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> warned $<' . $target->nickname . '$>!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Kick a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param string $message
	 */
	public function kickPlayer($adminLogin, $targetLogin, $message = '') {
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkPermission($admin, self::SETTING_PERMISSION_KICK_PLAYER)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$target) {
			return;
		}

		try {
			if ($target->isFakePlayer()) {
				$this->maniaControl->client->disconnectFakePlayer($target->login);
			} else {
				$this->maniaControl->client->kick($target->login, $message);
			}
		} catch (LoginUnknownException $e) {
			$this->maniaControl->chat->sendException($e, $admin->login);
			return;
		}

		// Announce kick
		$title       = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> kicked $<' . $target->nickname . '$>!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log(Formatter::stripCodes($chatMessage));
	}

	/**
	 * Ban a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param string $message
	 */
	public function banPlayer($adminLogin, $targetLogin, $message = '') {
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkPermission($admin, self::SETTING_PERMISSION_BAN_PLAYER)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$target) {
			return;
		}

		if ($target->isFakePlayer()) {
			$this->maniaControl->chat->sendError('It is not possible to Ban a bot', $admin->login);
			return;
		}

		$this->maniaControl->client->ban($target->login, $message);

		// Announce ban
		$title       = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> banned $<' . $target->nickname . '$>!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Grands the Player an Authorization Level
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int    $authLevel
	 */
	public function grandAuthLevel($adminLogin, $targetLogin, $authLevel) {
		$admin  = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$admin || !$target) {
			return;
		}

		$authLevelName = $this->maniaControl->authenticationManager->getAuthLevelName($authLevel);
		if (!$this->maniaControl->authenticationManager->checkRight($admin, $authLevel + 1)) {
			$this->maniaControl->chat->sendError("You don't have the permission to add a {$authLevelName}!", $admin->login);
			return;
		}

		if ($this->maniaControl->authenticationManager->checkRight($target, $authLevel)) {
			$this->maniaControl->chat->sendError("This Player is already {$authLevelName}!", $admin->login);
			return;
		}

		$success = $this->maniaControl->authenticationManager->grantAuthLevel($target, $authLevel);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred.', $admin->login);
			return;
		}

		// Announce granting
		$title       = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> added $<' . $target->nickname . '$> as $< ' . $authLevelName . '$>!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Revokes all Rights from the Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 */
	public function revokeAuthLevel($adminLogin, $targetLogin) {
		$admin  = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$admin || !$target) {
			return;
		}

		if (!$this->maniaControl->authenticationManager->checkRight($admin, $target->authLevel + 1)) {
			$title = $this->maniaControl->authenticationManager->getAuthLevelName($target->authLevel);
			$this->maniaControl->chat->sendError("You can't revoke the Rights of a {$title}!", $admin->login);
			return;
		}

		if ($this->maniaControl->authenticationManager->checkRight($target, AuthenticationManager::AUTH_LEVEL_MASTERADMIN)) {
			$this->maniaControl->chat->sendError("MasterAdmins can't be removed!", $admin->login);
			return;
		}

		$success = $this->maniaControl->authenticationManager->grantAuthLevel($target, AuthenticationManager::AUTH_LEVEL_PLAYER);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred.', $admin->login);
			return;
		}

		// Announce revoke
		$title       = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> revoked the Rights of $<' . $target->nickname . '$>!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Check if a Player is muted
	 *
	 * @param string $login
	 * @return bool
	 */
	public function isPlayerMuted($login) {
		$ignoreList = $this->maniaControl->client->getIgnoreList(100, 0);
		foreach ($ignoreList as $ignoredPlayers) {
			if ($ignoredPlayers->login == $login) {
				return true;
			}
		}
		return false;
	}
}
