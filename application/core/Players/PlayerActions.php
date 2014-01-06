<?php

namespace ManiaControl\Players;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;

/**
 * PlayerActions Class
 *
 * @author steeffeen & kremsy
 */
class PlayerActions {
	/**
	 * Constants
	 */
	const BLUE_TEAM = 0;
	const RED_TEAM = 1;
	const SPECTATOR_USER_SELECTABLE = 0;
	const SPECTATOR_SPECTATOR = 1;
	const SPECTATOR_PLAYER = 2;
	const SPECTATOR_BUT_KEEP_SELECTABLE = 3;
	
	/**
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
	}

	/**
	 * Force a Player to a certain Team
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int $teamId
	 */
	public function forcePlayerToTeam($adminLogin, $targetLogin, $teamId) {
		// TODO: get used by playercommands
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$admin || !$target) return;
		
		if ($target->isSpectator) {
			$success = $this->maniaControl->client->query('ForceSpectator', $target->login, self::SPECTATOR_PLAYER);
			if (!$success) {
				$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
				return;
			}
		}
		
		$success = $this->maniaControl->client->query('ForcePlayerTeam', $target->login, $teamId);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		
		$chatMessage = false;
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		if ($teamId == self::BLUE_TEAM) {
			$chatMessage = $title . ' $<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> into the Blue-Team!';
		}
		else if ($teamId == self::RED_TEAM) {
			$chatMessage = $title . ' $<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> into the Red-Team!';
		}
		if (!$chatMessage) return;
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Force a Player to Spectator
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int $spectatorState
	 * @param bool $releaseSlot
	 */
	public function forcePlayerToSpectator($adminLogin, $targetLogin, $spectatorState = self::SPECTATOR_BUT_KEEP_SELECTABLE, $releaseSlot = true) {
		// TODO: get used by playercommands
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkRight($admin, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		
		$success = $this->maniaControl->client->query('ForceSpectator', $target->login, $spectatorState);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> to Spectator!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
		
		if ($releaseSlot) {
			// Free player slot
			$this->maniaControl->client->query('SpectatorReleasePlayerSlot', $target->login);
		}
	}

	/**
	 * UnMute a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int $spectatorState
	 */
	public function unMutePlayer($adminLogin, $targetLogin) {
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkRight($admin, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		
		$success = $this->maniaControl->client->query('UnIgnore', $target->login);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> un-muted $<' . $target->nickname . '$>!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Mute a Player
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int $spectatorState
	 */
	public function mutePlayer($adminLogin, $targetLogin) {
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		if (!$this->maniaControl->authenticationManager->checkRight($admin, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		
		$success = $this->maniaControl->client->query('Ignore', $targetLogin);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
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
		if (!$this->maniaControl->authenticationManager->checkRight($admin, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$target) return;
		
		// Display warning message
		$message = '$s$f00This is an administrative warning.{br}{br}$gWhatever you wrote or you have done is against {br} our server\'s policy.
						{br}Not respecting other players, or{br}using offensive language might result in a{br}$f00kick, or ban $ff0the next time.
						{br}{br}$gThe server administrators.';
		$message = preg_split('/{br}/', $message);
		
		// Build Manialink
		$width = 80;
		$height = 50;
		$quadStyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();
		
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame = new Frame();
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
			$y -= 4;
		}
		
		// Display manialink
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $target);
		
		// Announce warning
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
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
		if (!$this->maniaControl->authenticationManager->checkRight($admin, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$target) return;
		
		if ($target->isFakePlayer()) {
			$success = $this->maniaControl->client->query('DisconnectFakePlayer', $target->login);
		}
		else {
			$success = $this->maniaControl->client->query('Kick', $target->login, $message);
		}
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		
		// Announce kick
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
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
		if (!$this->maniaControl->authenticationManager->checkRight($admin, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($admin);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$target) return;
		
		$success = $this->maniaControl->client->query('Ban', $target->login, $message);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		
		// Announce ban
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		$chatMessage = $title . ' $<' . $admin->nickname . '$> banned $<' . $target->nickname . '$>!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
	}

	/**
	 * Grands the Player an Authorization Level
	 *
	 * @param string $adminLogin
	 * @param string $targetLogin
	 * @param int $authLevel
	 */
	public function grandAuthLevel($adminLogin, $targetLogin, $authLevel) {
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$admin || !$target) return;
		
		$authLevelName = $this->maniaControl->authenticationManager->getAuthLevelName($authLevel);
		if ($this->maniaControl->authenticationManager->checkRight($admin, $authLevel + 1)) {
			$this->maniaControl->chat->sendError("You don't have the permission to add a {$authLevelName}!", $admin->login);
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
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
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
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		if (!$admin || !$target) return;
		
		if ($this->maniaControl->authenticationManager->checkRight($admin, $target->authLevel + 1)) {
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
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
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
		$this->maniaControl->client->query('GetIgnoreList', 100, 0);
		foreach ($this->maniaControl->client->getResponse() as $ignoredPlayers) {
			if ($ignoredPlayers["Login"] == $login) return true;
		}
		return false;
	}
}
