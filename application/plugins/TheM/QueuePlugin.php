<?php

namespace TheM;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerActions;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

/**
 * Queue plugin
 *
 * @author TheM
 */

// TODO: worst kick function
// TODO: idlekick function (?)

class QueuePlugin implements CallbackListener, ManialinkPageAnswerListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const ID                 = 22;
	const VERSION            = 0.12;
	const AUTHOR             = 'TheM';
	const NAME               = 'Queue Plugin';
	const ML_ID              = 'Queue.Widget';
	const ML_ADDTOQUEUE      = 'Queue.Add';
	const ML_REMOVEFROMQUEUE = 'Queue.Remove';

	const QUEUE_MAX            = 'Maximum number in the queue';
	const QUEUE_WIDGET_POS_X   = 'X position of the widget';
	const QUEUE_WIDGET_POS_Y   = 'Y position of the widget';
	const QUEUE_ACTIVE_ON_PASS = 'Activate queue when there is a play password';
	const QUEUE_CHATMESSAGES   = 'Activate chat messages on queue join/leave/move to play';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $queue = array();
	private $spectators = array();
	private $showPlay = array();
	private $maxPlayers = 0;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
		// TODO: Implement prepare() method.
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::NAME;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::AUTHOR;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Plugin offers the known AutoQueue/SpecJam options.';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->timerManager->registerTimerListening($this, 'handleEverySecond', 1000);
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERINFOCHANGED, $this, 'handlePlayerInfoChanged');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleBeginMap');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ML_ADDTOQUEUE, $this, 'handleManiaLinkAnswerAdd');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ML_REMOVEFROMQUEUE, $this, 'handleManiaLinkAnswerRemove');

		$this->maniaControl->settingManager->initSetting($this, self::QUEUE_MAX, 8);
		$this->maniaControl->settingManager->initSetting($this, self::QUEUE_WIDGET_POS_X, 0);
		$this->maniaControl->settingManager->initSetting($this, self::QUEUE_WIDGET_POS_Y, -46);
		$this->maniaControl->settingManager->initSetting($this, self::QUEUE_ACTIVE_ON_PASS, false);
		$this->maniaControl->settingManager->initSetting($this, self::QUEUE_CHATMESSAGES, true);

		$maxPlayers       = $this->maniaControl->client->getMaxPlayers();
		$this->maxPlayers = $maxPlayers['CurrentValue'];

		if (!($this->maniaControl->client->getServerPassword() != false && $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_ACTIVE_ON_PASS) == false)) {
			foreach ($this->maniaControl->playerManager->getPlayers() as $player) {
				if ($player->isSpectator) {
					$this->spectators[$player->login] = $player->login;
					$this->maniaControl->client->forceSpectator($player->login, 1);
					$this->showJoinQueueWidget($player);
				}
			}
		}
	}

	/**
	 * Function shows the join queue widget to a player.
	 *
	 * @param Player $player
	 */
	private function showJoinQueueWidget(Player $player) {
		$maniaLink = new ManiaLink(self::ML_ID);

		$quadStyle    = $this->maniaControl->manialinkManager->getStyleManager()->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->getStyleManager()->getDefaultMainWindowSubStyle();
		$maxQueue     = $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_MAX);

		// Main frame
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize(60, 6);
		$xPos = $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_WIDGET_POS_X);
		$yPos = $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_WIDGET_POS_Y);
		$frame->setPosition($xPos, $yPos, 0);

		// Background
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setPosition(0, 0, 0);
		$backgroundQuad->setSize(70, 10);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$cameraQuad = new Quad_Icons64x64_1();
		$frame->add($cameraQuad);
		$cameraQuad->setPosition(-29, 0.4, 2);
		$cameraQuad->setSize(9, 9);
		$cameraQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_Camera);

		$statusLabel = new Label_Text();
		$frame->add($statusLabel);
		$statusLabel->setPosition(4.5, 2.8, 1);
		$statusLabel->setSize(66, 4);
		$statusLabel->setAlign('center', 'center');
		$statusLabel->setScale(0.8);
		$statusLabel->setStyle(Label_Text::STYLE_TextStaticSmall);

		$messageLabel = new Label_Button();
		$frame->add($messageLabel);
		$messageLabel->setPosition(4.5, -1.6, 1);
		$messageLabel->setSize(56, 4);
		$messageLabel->setAlign('center', 'center');
		$messageLabel->setScale(1.0);

		$inQueue = false;
		foreach ($this->queue as $queuedPlayer) {
			if ($queuedPlayer->login == $player->login) {
				$inQueue = true;
			}
		}

		if ($inQueue) {
			$message = '$fff$sYou\'re in the queue (click to unqueue).';

			$position = 0;
			foreach (array_values($this->queue) as $i => $queuePlayer) {
				if ($player->login == $queuePlayer->login) {
					$position = ($i + 1);
				}
			}

			$statusLabel->setText('$aaaStatus: In queue (' . $position . '/' . count($this->queue) . ')      Waiting: ' . count($this->queue) . '/' . $maxQueue . '');
			$messageLabel->setAction(self::ML_REMOVEFROMQUEUE);
			$backgroundQuad->setAction(self::ML_REMOVEFROMQUEUE);
			$statusLabel->setAction(self::ML_REMOVEFROMQUEUE);
			$cameraQuad->setAction(self::ML_REMOVEFROMQUEUE);
		} else {
			if (count($this->queue) < $maxQueue) {
				$message = '$0ff$sClick to join spectator waiting list.';
				$messageLabel->setAction(self::ML_ADDTOQUEUE);
				$backgroundQuad->setAction(self::ML_ADDTOQUEUE);
				$statusLabel->setAction(self::ML_ADDTOQUEUE);
				$cameraQuad->setAction(self::ML_ADDTOQUEUE);
			} else {
				$message = '$f00The waiting list is full!';
			}

			$statusLabel->setText('$aaaStatus: Not queued spectator      Waiting: ' . count($this->queue) . '/' . $maxQueue . '');
		}

		$messageLabel->setText($message);
		$messageLabel->setStyle(Label_Text::STYLE_TextStaticSmall);

		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $player->login);
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		foreach ($this->spectators as $spectator) {
			$this->maniaControl->client->forceSpectator($spectator, 3);
			$this->maniaControl->client->forceSpectator($spectator, 0);
		}

		$this->maniaControl->manialinkManager->hideManialink(self::ML_ID);

		$this->queue      = array();
		$this->spectators = array();
		$this->showPlay   = array();
	}

	/**
	 * Function handling on the connection of a player.
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		if ($this->maniaControl->client->getServerPassword() != false && $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_ACTIVE_ON_PASS) == false) {
			return;
		}

		$maxPlayers       = $this->maniaControl->client->getMaxPlayers();
		$this->maxPlayers = $maxPlayers['CurrentValue'];

		if ($player->isSpectator) {
			$this->spectators[$player->login] = $player->login;
			$this->maniaControl->client->forceSpectator($player->login, 1);
			$this->showJoinQueueWidget($player);
		} else {
			if (count($this->queue) != 0) {
				$this->maniaControl->client->forceSpectator($player->login, 1);
				$this->spectators[$player->login] = $player->login;
				$this->showJoinQueueWidget($player);
			}
		}
	}

	/**
	 * Function handling on the disconnection of a player.
	 *
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) {
		if ($this->maniaControl->client->getServerPassword() != false && $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_ACTIVE_ON_PASS) == false) {
			return;
		}

		$maxPlayers       = $this->maniaControl->client->getMaxPlayers();
		$this->maxPlayers = $maxPlayers['CurrentValue'];

		if (isset($this->spectators[$player->login])) {
			unset($this->spectators[$player->login]);
		}
		$this->removePlayerFromQueue($player->login);
		$this->moveFirstPlayerToPlay();
	}

	/**
	 * Remove a Player from the Queue
	 *
	 * @param string $login
	 */
	private function removePlayerFromQueue($login) {
		$count    = 0;
		$newQueue = array();
		foreach ($this->queue as $queuePlayer) {
			if ($queuePlayer->login != $login) {
				$newQueue[$count] = $queuePlayer;
				$count++;
			}
		}

		$this->queue = $newQueue;
		$this->showQueueWidgetSpectators();
	}

	/**
	 * Show the Queue Widgets to Spectators
	 */
	public function showQueueWidgetSpectators() {
		foreach ($this->spectators as $login) {
			$player = $this->maniaControl->playerManager->getPlayer($login);
			$this->showJoinQueueWidget($player);
		}
	}

	/**
	 * Function used to move the first queued player to the
	 */
	private function moveFirstPlayerToPlay() {
		if (count($this->queue) > 0) {
			$firstPlayer = $this->maniaControl->playerManager->getPlayer($this->queue[0]->login);
			$this->forcePlayerToPlay($firstPlayer);
			$this->showQueueWidgetSpectators();
		}
	}

	/**
	 * Function to force a player to play status.
	 *
	 * @param Player $player
	 */
	private function forcePlayerToPlay(Player $player) {
		if ($this->maniaControl->client->getServerPassword() != false && $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_ACTIVE_ON_PASS) == false) {
			return;
		}

		if ($this->maxPlayers > $this->maniaControl->playerManager->getPlayerCount(true)) {
			try {
				$this->maniaControl->client->forceSpectator($player->login, PlayerActions::SPECTATOR_PLAYER);
			} catch (Exception $e) {
				// TODO: only possible valid exception should be "wrong login" - throw others (like connection error)
				$this->maniaControl->chat->sendError("Error while leaving the Queue", $player->login);
				return;
			}

			try {
				$this->maniaControl->client->forceSpectator($player->login, PlayerActions::SPECTATOR_USER_SELECTABLE);
			} catch (Exception $e) {
				// TODO: only possible valid exception should be "wrong login" - throw others (like connection error)
			}

			$teams = array();

			//Initialize at least two teams with 0 players
			$teams[0] = 0;
			$teams[1] = 0;

			/** @var  Player $playerObj */
			foreach ($this->maniaControl->playerManager->getPlayers() as $playerObj) {
				if ($playerObj->isSpectator) {
					continue;
				}

				if (!isset($teams[$playerObj->teamId])) {
					$teams[$playerObj->teamId] = 1;
				} else {
					$teams[$playerObj->teamId]++;
				}
			}

			$smallestTeam = null;
			$smallestSize = 999;
			foreach ($teams as $team => $size) {
				if ($size < $smallestSize) {
					$smallestTeam = $team;
					$smallestSize = $size;
				}
			}

			try {
				if ($smallestTeam !== null) {
					$this->maniaControl->client->forcePlayerTeam($player->login, $smallestTeam);
				}
			} catch (Exception $e) {
				$this->maniaControl->errorHandler->triggerDebugNotice("QueuePlugin line 383 Exception" . $e->getTraceAsString());
			}

			if (isset($this->spectators[$player->login])) {
				unset($this->spectators[$player->login]);
			}
			$this->removePlayerFromQueue($player->login);
			$this->showPlayWidget($player);
			$this->sendChatMessage('$<$fff' . $player->nickname . '$> has a free spot and is now playing!');
		}

	}

	/**
	 * Function shows the "You got a free spot, enjoy playing!" widget.
	 *
	 * @param Player $player
	 */
	private function showPlayWidget(Player $player) {
		$maniaLink = new ManiaLink(self::ML_ID);

		$quadStyle    = $this->maniaControl->manialinkManager->getStyleManager()->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->getStyleManager()->getDefaultMainWindowSubStyle();

		// Main frame
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize(60, 6);
		$xPos = $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_WIDGET_POS_X);
		$yPos = $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_WIDGET_POS_Y);
		$frame->setPosition($xPos, $yPos, 0);

		// Background
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setPosition(0, 0, 0);
		$backgroundQuad->setSize(70, 10);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$cameraQuad = new Quad_Icons64x64_1();
		$frame->add($cameraQuad);
		$cameraQuad->setPosition(-29, 0.4, 2);
		$cameraQuad->setSize(9, 9);
		$cameraQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_Camera);

		$messageLabel = new Label_Button();
		$frame->add($messageLabel);
		$messageLabel->setPosition(4.5, 0.6, 1);
		$messageLabel->setSize(56, 4);
		$messageLabel->setAlign('center', 'center');
		$messageLabel->setScale(1.0);
		$messageLabel->setText('$090You got a free spot, enjoy playing!');
		$messageLabel->setStyle(Label_Text::STYLE_TextStaticSmall);

		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $player->login);
		$this->showPlay[$player->login] = array('time' => time(), 'player' => $player);
	}

	/**
	 * Function sends (or not depending on setting) chat messages for the queue.
	 *
	 * @param string $message
	 */
	private function sendChatMessage($message) {
		if ($this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_CHATMESSAGES)) {
			$this->maniaControl->chat->sendChat('$090[Queue] ' . $message);
		}
	}

	/**
	 * Function handling the change of player information.
	 *
	 * @param array $callback
	 */
	public function handlePlayerInfoChanged(array $callback) {
		if ($this->maniaControl->client->getServerPassword() != false && $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_ACTIVE_ON_PASS) == false) {
			return;
		}

		$login  = $callback[1][0]['Login'];
		$player = $this->maniaControl->playerManager->getPlayer($login);

		if (!is_null($player)) {
			if ($player->isSpectator) {
				if (!isset($this->spectators[$player->login])) {
					$this->maniaControl->client->forceSpectator($player->login, 1);
					$this->spectators[$player->login] = $player->login;
					$this->showJoinQueueWidget($player);
					$this->showQueueWidgetSpectators();
				}
			} else {
				$this->removePlayerFromQueue($player->login);
				if (isset($this->spectators[$player->login])) {
					unset($this->spectators[$player->login]);
				}

				$found = false;
				foreach ($this->showPlay as $showPlay) {
					if ($showPlay['player']->login == $player->login) {
						$found = true;
					}
				}

				if (!$found) {
					$this->hideQueueWidget($player);
				}
			}
		}
	}

	/**
	 * Function hides the queue widget from the player.
	 *
	 * @param Player $player
	 */
	private function hideQueueWidget(Player $player) {
		$this->maniaControl->manialinkManager->hideManialink(self::ML_ID, $player);
	}

	/**
	 * Function called on every second.
	 */
	public function handleEverySecond() {
		if ($this->maniaControl->client->getServerPassword() != false && $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_ACTIVE_ON_PASS) == false) {
			return;
		}

		if ($this->maxPlayers > ($this->maniaControl->playerManager->getPlayerCount() - count($this->spectators))) {
			$this->moveFirstPlayerToPlay();
			$this->showQueueWidgetSpectators();
		}

		foreach ($this->showPlay as $showPlay) {
			if (($showPlay['time'] + 5) < time()) {
				$this->hideQueueWidget($showPlay['player']);
				unset($this->showPlay[$showPlay['player']->login]);
			}
		}
	}

	/**
	 * Checks for being of new map to retrieve maximum number of players.
	 */
	public function handleBeginMap() {
		if ($this->maniaControl->client->getServerPassword() != false && $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_ACTIVE_ON_PASS) == false) {
			return;
		}

		$maxPlayers       = $this->maniaControl->client->getMaxPlayers();
		$this->maxPlayers = $maxPlayers['CurrentValue'];
	}

	/**
	 * Function handling the click of the widget to add them to the queue.
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handleManiaLinkAnswerAdd(array $chatCallback, Player $player) {
		$this->addPlayerToQueue($player);
	}

	/**
	 * Function adds a player to the queue.
	 *
	 * @param Player $player
	 * @return bool
	 */
	private function addPlayerToQueue(Player $player) {
		if ($this->maniaControl->client->getServerPassword() != false && $this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_ACTIVE_ON_PASS) == false) {
			return false;
		}

		foreach ($this->queue as $queuedPlayer) {
			if ($queuedPlayer->login == $player->login) {
				$this->maniaControl->chat->sendError('You\'re already in the queue!', $player->login);
				return false;
			}
		}

		if ($this->maniaControl->settingManager->getSettingValue($this, self::QUEUE_MAX) > count($this->queue)) {
			$this->queue[count($this->queue)] = $player;
			$this->sendChatMessage('$<$fff' . $player->nickname . '$> just joined the queue!');
		}

		$this->showQueueWidgetSpectators();

		return true;
	}

	/**
	 * Function handling the click of the widget to remove them from the queue.
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function handleManiaLinkAnswerRemove(array $chatCallback, Player $player) {
		$this->removePlayerFromQueue($player->login);
		$this->showJoinQueueWidget($player);
		$this->sendChatMessage('$<$fff' . $player->nickname . '$> has left the queue!');
	}
}
