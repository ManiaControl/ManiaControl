<?php

use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

/**
 * ManiaControl Chat-Message Plugin
 *
 * @author kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ChatMessagePlugin implements CommandListener, Plugin {
	/**
	 * Constants
	 */
	const PLUGIN_ID              = 4;
	const PLUGIN_VERSION         = 0.1;
	const PLUGIN_NAME            = 'ChatMessagePlugin';
	const PLUGIN_AUTHOR          = 'kremsy';
	const SETTING_AFK_FORCE_SPEC = 'AFK command forces spec';

	/**
	 * Private properties
	 */
	/**
	 * @var maniaControl $maniaControl
	 */
	private $maniaControl = null;

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		//do nothing
	}

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->commandManager->registerCommandListener('me', $this, 'chat_me');
		$this->maniaControl->commandManager->registerCommandListener('hi', $this, 'chat_hi');
		$this->maniaControl->commandManager->registerCommandListener('bye', $this, 'chat_bye');
		$this->maniaControl->commandManager->registerCommandListener('bb', $this, 'chat_bye');
		$this->maniaControl->commandManager->registerCommandListener('thx', $this, 'chat_thx');
		$this->maniaControl->commandManager->registerCommandListener('gg', $this, 'chat_gg');
		$this->maniaControl->commandManager->registerCommandListener('gl', $this, 'chat_gl');
		$this->maniaControl->commandManager->registerCommandListener('hf', $this, 'chat_hf');
		$this->maniaControl->commandManager->registerCommandListener('glhf', $this, 'chat_glhf');
		$this->maniaControl->commandManager->registerCommandListener('ns', $this, 'chat_ns');
		$this->maniaControl->commandManager->registerCommandListener('n1', $this, 'chat_n1');
		$this->maniaControl->commandManager->registerCommandListener('lol', $this, 'chat_lol');
		$this->maniaControl->commandManager->registerCommandListener('lool', $this, 'chat_lool');
		$this->maniaControl->commandManager->registerCommandListener('brb', $this, 'chat_brb');
		$this->maniaControl->commandManager->registerCommandListener('bgm', $this, 'chat_bgm');
		$this->maniaControl->commandManager->registerCommandListener('afk', $this, 'chat_afk');
		$this->maniaControl->commandManager->registerCommandListener('bootme', $this, 'chat_bootme');
		$this->maniaControl->commandManager->registerCommandListener('ragequit', $this, 'chat_ragequit');
		$this->maniaControl->commandManager->registerCommandListener('rq', $this, 'chat_ragequit');
		//TODO block commandlistener for muted people
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_AFK_FORCE_SPEC, true);

		return true;
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
		$this->maniaControl->commandManager->unregisterCommandListener($this);
		unset($this->maniaControl);
	}

	/**
	 * Builds a chat message starting with the player's nickname, can used to express emotions
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_me(array $chat, Player $player) {
		$message = substr($chat[1][2], 4);

		$msg = '$<' . $player->nickname . '$>$s$i$fa0 ' . $message;
		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Hello Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_hi(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iHello $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iHello All!';
		}
		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Bye Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_bye(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iBye $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iI have to go... Bye All!';
		}

		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Thx Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_thx(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iThanks $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iThanks All!';
		}

		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Good Game Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_gg(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iGood Game $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iGood Game All!';
		}

		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Good Luck Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_gl(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iGood Luck $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iGood Luck All!';
		}

		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Have Fun Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_hf(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iHave Fun $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iHave Fun All!';
		}

		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Good Luck and Have Fun Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_glhf(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iGood Luck and Have Fun $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iGood Luck and Have Fun All!';
		}

		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Nice Shot Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_ns(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iNice Shot $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iNice Shot!';
		}

		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Nice one Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_n1(array $chat, Player $player) {
		$command = explode(" ", $chat[1][2]);

		if (isset($command[1])) {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iNice One $z$<' . $this->getTarget($command[1]) . '$>$i!';
		} else {
			$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iNice One!';
		}

		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Lol! Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_lol(array $chat, Player $player) {
		$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iLoL!';
		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * LooOOooL! Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_lool(array $chat, Player $player) {
		$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iLooOOooL!';
		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Be right back Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_brb(array $chat, Player $player) {
		$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iBe Right Back!';
		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Bad game for me Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_bgm(array $chat, Player $player) {
		$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iBad Game for me :(';
		$this->maniaControl->chat->sendChat($msg, null, false);
	}

	/**
	 * Leave the server with an Bootme Message
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_bootme(array $chat, Player $player) {
		$msg = '$i$ff0 $<' . $player->nickname . '$>$s$39f chooses to boot back to the real world!';
		$this->maniaControl->chat->sendChat($msg, null, true);

		$message = '$39F Thanks for Playing, see you around!$z';
		try {
			$this->maniaControl->client->kick($player->login, $message);
		} catch(Exception $e) {
			$this->maniaControl->errorHandler->triggerDebugNotice("ChatMessagePlugin Debug Line 316: " . $e->getMessage());
			// TODO: only possible valid exception should be "wrong login" - throw others (like connection error)
			$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
			return;
		}
	}

	/**
	 * Leave the server with an Ragequit
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_ragequit(array $chat, Player $player) {
		$msg = '$i$ff0 $<' . $player->nickname . '$>$s$f00 said: "@"#!" and ragequitted!';
		$this->maniaControl->chat->sendChat($msg, null, true);

		$message = '$39F Thanks for Playing, please come back soon!$z ';
		try {
			$this->maniaControl->client->kick($player->login, $message);
		} catch(Exception $e) {
			$this->maniaControl->errorHandler->triggerDebugNotice("ChatMessagePlugin Debug Line " . $e->getLine() . ": " . $e->getMessage());
			// TODO: only possible valid exception should be "wrong login" - throw others (like connection error)
			$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
			return;
		}
	}

	/**
	 * Afk Message and force player to spec
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function chat_afk(array $chat, Player $player) {
		$msg = '$g[$<' . $player->nickname . '$>$s] $ff0$iAway From Keyboard!';
		$this->maniaControl->chat->sendChat($msg, null, false);

		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_AFK_FORCE_SPEC)) {
			if ($player->isSpectator) {
				return;
			}

			// force into spec
			try {
				$this->maniaControl->client->forceSpectator($player->login, 3);
			} catch(Exception $e) {
				$this->maniaControl->errorHandler->triggerDebugNotice("ChatMessagePlugin Debug Line " . $e->getLine() . ": " . $e->getMessage());
				// TODO: only possible valid exception should be "wrong login" - throw others (like connection error)
				$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
				return;
			}

			// free player slot
			try {
				$this->maniaControl->client->spectatorReleasePlayerSlot($player->login);
			} catch(Exception $e) {
				if ($e->getMessage() != 'The player is not a spectator') {
					$this->maniaControl->errorHandler->triggerDebugNotice("ChatMessagePlugin Debug Line " . $e->getLine() . ": " . $e->getMessage());
					// TODO: only possible valid exception should be "wrong login" - throw others (like connection error)
					//to nothing
				}
			}
		}
	}

	/**
	 * Checks if a Player is in the PlayerList and returns the nickname if he is, can be called per login, pid or nickname or lj for
	 * (last joined)
	 *
	 * @param $login
	 * @return mixed
	 */
	private function getTarget($login) {
        /** @var Player $player */
        $player = null;
		foreach($this->maniaControl->playerManager->getPlayers() as $player) {
			if ($login == $player->login || $login == $player->pid || $login == $player->nickname) {
				return $player->nickname;
			}
		}

		if ($player && $login == 'lj') {
			return $player->nickname;
		}

		//returns the text given if nothing matches
		return $login;
	}

	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId() {
		return self::PLUGIN_ID;
	}

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName() {
		return self::PLUGIN_NAME;
	}

	/**
	 * Get Plugin Version
	 *
	 * @return float,,
	 */
	public static function getVersion() {
		return self::PLUGIN_VERSION;
	}

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor() {
		return self::PLUGIN_AUTHOR;
	}

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription() {
		return "Plugin offers various Chat-Commands like /gg /hi /afk /rq...";
	}
}