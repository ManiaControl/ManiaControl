<?php

namespace ManiaControl\Commands;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Listening;
use ManiaControl\ManiaControl;

/**
 * Class for handling Chat Commands
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CommandManager implements CallbackListener {
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var HelpManager $helpManager */
	private $helpManager = array();
	/** @var Listening[][] $commandListenings */
	private $commandListenings = array();
	/** @var Listening[][] $adminCommandListenings */
	private $adminCommandListenings = array();

	/**
	 * Construct a new Commands Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Children
		$this->helpManager = new HelpManager($this->maniaControl);

		// Callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCHAT, $this, 'handleChatCallback');
	}

	/**
	 * Return the help manager instance
	 *
	 * @return HelpManager
	 */
	public function getHelpManager() {
		return $this->helpManager;
	}

	/**
	 * Register a Command Listener
	 *
	 * @param string          $commandName
	 * @param CommandListener $listener
	 * @param string          $method
	 * @param bool            $adminCommand
	 * @param string          $description
	 * @return bool
	 */
	public function registerCommandListener($commandName, CommandListener $listener, $method, $adminCommand = false,
	                                        $description = null) {
		if (is_array($commandName)) {
			$success = false;
			foreach ($commandName as $command) {
				if ($this->registerCommandListener($command, $listener, $method, $adminCommand, $description)) {
					$success = true;
				}
			}
			return $success;
		}

		if (!Listening::checkValidCallback($listener, $method)) {
			$listenerClass = get_class($listener);
			trigger_error("Given Listener '{$listenerClass}' can't handle Command '{$commandName}': No callable Method '{$method}'!");
			return false;
		}

		$command   = strtolower($commandName);
		$listening = new Listening($listener, $method);

		if ($adminCommand) {
			$this->addListening($this->adminCommandListenings, $listening, $command);
		} else {
			$this->addListening($this->commandListenings, $listening, $command);
		}

		// TODO: description(?)
		if ($description) {
			$this->helpManager->registerCommand($command, $adminCommand, $description, get_class($listener) . '\\' . $method);
		}

		return true;
	}

	/**
	 * Add a Listening to the given Listenings Array
	 *
	 * @param array     $listeningsArray
	 * @param Listening $listening
	 * @param string    $command
	 */
	private function addListening(array &$listeningsArray, Listening $listening, $command) {
		if (!array_key_exists($command, $listeningsArray) || !is_array($listeningsArray[$command])) {
			// Init listenings array
			$listeningsArray[$command] = array();
		}

		// Register command listening
		array_push($listeningsArray[$command], $listening);
	}

	/**
	 * Unregister a Command Listener
	 *
	 * @param CommandListener $listener
	 * @return bool
	 */
	public function unregisterCommandListener(CommandListener $listener) {
		$removed = false;
		if ($this->removeCommandListener($this->commandListenings, $listener)) {
			$removed = true;
		}
		if ($this->removeCommandListener($this->adminCommandListenings, $listener)) {
			$removed = true;
		}
		return $removed;
	}

	/**
	 * Remove the Command Listener from the given Listenings Array
	 *
	 * @param array           $listeningsArray
	 * @param CommandListener $listener
	 * @return bool
	 */
	private function removeCommandListener(array &$listeningsArray, CommandListener $listener) {
		$removed = false;
		foreach ($listeningsArray as &$listenings) {
			foreach ($listenings as $key => &$listening) {
				if ($listening->listener === $listener) {
					unset($listenings[$key]);
					$removed = true;
				}
			}
		}
		return $removed;
	}

	/**
	 * Handle Chat Callback
	 *
	 * @param array $callback
	 */
	public function handleChatCallback(array $callback) {
		// Check for command
		if (!$this->isCommandMessage($callback)) {
			return;
		}

		// Check for valid player
		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player) {
			return;
		}

		// Parse command
		$message      = $callback[1][2];
		$commandArray = explode(' ', $message);
		$command      = ltrim(strtolower($commandArray[0]), '/');
		if (!$command) {
			return;
		}

		if (substr($message, 0, 2) === '//' || $command === 'admin') {
			// Admin command
			$commandListenings = $this->adminCommandListenings;

			if ($command === 'admin') {
				// Strip 'admin' keyword
				if (isset($commandArray[1])) {
					$command = $commandArray[1];
					unset($commandArray[1]);
				}
			}
			unset($commandArray[0]);

			// Compose uniformed message
			$message        = '//' . $command . ' ' . implode(' ', $commandArray);
			$callback[1][2] = $message;
		} else {
			// User command
			$commandListenings = $this->commandListenings;
		}

		if (!array_key_exists($command, $commandListenings) || !is_array($commandListenings[$command])) {
			// No command listener registered
			return;
		}

		// Inform command listeners
		foreach ($commandListenings[$command] as $listening) {
			/** @var Listening $listening */
			$listening->triggerCallback($callback, $player);
		}
	}

	/**
	 * Check if the given Chat Callback is a Command Message
	 *
	 * @param array $chatCallback
	 * @return bool
	 */
	private function isCommandMessage(array $chatCallback) {
		return (bool)$chatCallback[1][3];
	}
}
