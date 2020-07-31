<?php

namespace ManiaControl\Players;

use FML\Controls\Quads\Quad_Icons128x32_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Server\Server;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;
use Maniaplanet\DedicatedServer\Xmlrpc\UnavailableFeatureException;

/**
 * Class offering various Admin Commands related to Players
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerCommands implements CommandListener, ManialinkPageAnswerListener, CallbackListener {
	/*
	 * Constants
	 */
	const ACTION_BALANCE_TEAMS            = 'PlayerCommands.BalanceTeams';
	const ACTION_OPEN_PLAYERLIST          = 'PlayerCommands.OpenPlayerList';
	const SETTING_PERMISSION_ADD_BOT      = 'Add Bot';
	const SETTING_PERMISSION_TEAM_BALANCE = 'Balance Teams';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Create a new Player Commands Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Admin commands
		$this->maniaControl->getCommandManager()->registerCommandListener(array('balance', 'teambalance', 'autoteambalance'), $this, 'command_TeamBalance', true, 'Balances the teams.');
		$this->maniaControl->getCommandManager()->registerCommandListener('warn', $this, 'command_Warn', true, 'Warns a player from the server.');
		$this->maniaControl->getCommandManager()->registerCommandListener('kick', $this, 'command_Kick', true, 'Kicks player from the server.');
		$this->maniaControl->getCommandManager()->registerCommandListener('ban', $this, 'command_Ban', true, 'Bans a player from the server.');
		$this->maniaControl->getCommandManager()->registerCommandListener('unban', $this, 'command_UnBan', true, 'Unbans a player from the server.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('forcespec', 'forcespectator'), $this, 'command_ForceSpectator', true, 'Forces player into spectator.');
		$this->maniaControl->getCommandManager()->registerCommandListener('forceplay', $this, 'command_ForcePlay', true, 'Forces player into Play mode.');
		$this->maniaControl->getCommandManager()->registerCommandListener('forceblue', $this, 'command_ForceBlue', true, 'Forces player into blue team.');
		$this->maniaControl->getCommandManager()->registerCommandListener('forcered', $this, 'command_ForceRed', true, 'Forces player into red team.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('addbots', 'addbot'), $this, 'command_AddFakePlayers', true, 'Adds bots to the game.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('removebot', 'removebots'), $this, 'command_RemoveFakePlayers', true, 'Removes bots from the game.');
		$this->maniaControl->getCommandManager()->registerCommandListener('mute', $this, 'command_MutePlayer', true, 'Mutes a player (prevents player from chatting).');
		$this->maniaControl->getCommandManager()->registerCommandListener('unmute', $this, 'command_UnmutePlayer', true, 'Unmute a player (enables player to chat again).');

		// Player commands
		$this->maniaControl->getCommandManager()->registerCommandListener(array('player', 'players'), $this, 'command_playerList', false, 'Shows players currently on the server.');

		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_ADD_BOT, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_TEAM_BALANCE, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Server::CB_TEAM_MODE_CHANGED, $this, 'teamStatusChanged');

		// Action Open PlayerList
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_OPEN_PLAYERLIST, $this, 'command_playerList');
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Author);
		$itemQuad->setAction(self::ACTION_OPEN_PLAYERLIST);
		$this->maniaControl->getActionsMenu()->addMenuItem($itemQuad, true, 15, 'Open PlayerList');
	}

	/**
	 * Handle TeamStatusChanged
	 *
	 * @param bool $teamMode
	 */
	public function teamStatusChanged($teamMode) {
		//Add Balance Team Icon if it's a teamMode
		if ($teamMode) {
			// Action Balance Teams
			$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_BALANCE_TEAMS, $this, 'command_TeamBalance');
			$itemQuad = new Quad_Icons128x32_1();
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_RT_Team);
			$itemQuad->setAction(self::ACTION_BALANCE_TEAMS);
			$this->maniaControl->getActionsMenu()->addMenuItem($itemQuad, false, 40, 'Balance Teams');
		}
	}

	/**
	 * Handle //teambalance command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_TeamBalance(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_TEAM_BALANCE)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		try {
			$this->maniaControl->getClient()->autoTeamBalance();
		} catch (GameModeException $exception) {
			$this->maniaControl->getChat()->sendException($exception, $player);
			return;
		}

		$message = $this->maniaControl->getChat()->formatMessage(
			'%s balanced Teams!',
			$player
		);
		$this->maniaControl->getChat()->sendInformation($message);
	}

	/**
	 * Handle //kick command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_Kick(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_KICK_PLAYER)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chat[1][2], 3);
		if (count($params) <= 1) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//kick login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $params[1];
		$message     = '';
		if (isset($params[2])) {
			$message = $params[2];
		}
		$this->maniaControl->getPlayerManager()->getPlayerActions()->kickPlayer($player, $targetLogin, $message);
	}

	/**
	 * Handle //ban command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_Ban(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_BAN_PLAYER)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chat[1][2], 3);
		if (count($params) <= 1) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//ban login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $params[1];
		$message     = '';
		if (isset($params[2])) {
			$message = $params[2];
		}
		$this->maniaControl->getPlayerManager()->getPlayerActions()->banPlayer($player, $targetLogin, $message);
	}

	/**
	 * Handle //unban command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_UnBan(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_BAN_PLAYER)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chat[1][2], 3);
		if (count($params) <= 1) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//unban login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $params[1];
		$this->maniaControl->getPlayerManager()->getPlayerActions()->unBanPlayer($player, $targetLogin);
	}

	/**
	 * Handle //warn Command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_Warn(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_WARN_PLAYER)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chatCallback[1][2], 3);
		if (count($params) <= 1) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//warn login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $params[1];
		$this->maniaControl->getPlayerManager()->getPlayerActions()->warnPlayer($player, $targetLogin);
	}

	/**
	 * Handle //forcespec command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_ForceSpectator(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_SPEC)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chat[1][2]);
		if (count($params) <= 1) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//forcespec login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $params[1];
		if (isset($params[2]) && is_numeric($params[2])) {
			$type = intval($params[2]);
			$this->maniaControl->getPlayerManager()->getPlayerActions()->forcePlayerToSpectator($player, $targetLogin, $type);
		} else {
			$this->maniaControl->getPlayerManager()->getPlayerActions()->forcePlayerToSpectator($player, $targetLogin);
		}
	}

	/**
	 * Handle //forceplay command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_ForcePlay(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_PLAY)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chat[1][2]);
		if (!isset($params[1])) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//forceplay login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $params[1];
		$type = 2;
		if (isset($params[2]) && is_numeric($params[2])) {
			$type = intval($params[2]);
		}
		$selectable = ($type === 2);

		$this->maniaControl->getPlayerManager()->getPlayerActions()->forcePlayerToPlay($player, $targetLogin, $selectable);
	}

	/**
	 * Handle //forceblue command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_ForceBlue(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_TEAM)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chat[1][2]);
		if (!isset($params[1])) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//forceblue login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $params[1];
		$this->maniaControl->getPlayerManager()->getPlayerActions()->forcePlayerToTeam($player, $targetLogin, PlayerActions::TEAM_BLUE);
	}

	/**
	 * Handle //forcered command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_ForceRed(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_FORCE_PLAYER_TEAM)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chat[1][2]);
		if (!isset($params[1])) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//forcered login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $params[1];
		$this->maniaControl->getPlayerManager()->getPlayerActions()->forcePlayerToTeam($player, $targetLogin, PlayerActions::TEAM_RED);
	}

	/**
	 * Handle //addfakeplayers command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_AddFakePlayers(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_ADD_BOT)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$amount       = 1;
		$messageParts = explode(' ', $chatCallback[1][2]);
		if (isset($messageParts[1]) && is_numeric($messageParts[1])) {
			$amount = intval($messageParts[1]);
		}

		try {
			for ($i = 0; $i < $amount; $i++) {
				$this->maniaControl->getClient()->connectFakePlayer();
			}
			$this->maniaControl->getChat()->sendSuccess('Fake players connected!', $player);
		} catch (UnavailableFeatureException $e) {
			$this->maniaControl->getChat()->sendError('Error while connecting a Fake-Player.', $player);
		}
	}

	/**
	 * Handle //removefakeplayers command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_RemoveFakePlayers(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_ADD_BOT)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$this->maniaControl->getClient()->disconnectFakePlayer('*');
		$this->maniaControl->getChat()->sendSuccess('Fake players disconnected!', $player);
	}

	/**
	 * Handle //mute Command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_MutePlayer(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_MUTE_PLAYER)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$commandParts = explode(' ', $chatCallback[1][2]);
		if (count($commandParts) <= 1) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//mute login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $commandParts[1];
		$this->maniaControl->getPlayerManager()->getPlayerActions()->mutePlayer($player, $targetLogin);
	}

	/**
	 * Handle //unmute Command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_UnmutePlayer(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, PlayerActions::SETTING_PERMISSION_MUTE_PLAYER)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$commandParts = explode(' ', $chatCallback[1][2]);
		if (count($commandParts) <= 1) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'No Login given! Example: %s',
				'//unmute login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$targetLogin = $commandParts[1];
		$this->maniaControl->getPlayerManager()->getPlayerActions()->unMutePlayer($player, $targetLogin);
	}

	/**
	 * Handle Player list command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_playerList(array $chatCallback, Player $player) {
		$this->maniaControl->getPlayerManager()->getPlayerList()->addPlayerToShownList($player, PlayerList::SHOWN_MAIN_WINDOW);
		$this->maniaControl->getPlayerManager()->getPlayerList()->showPlayerList($player);
	}
}
