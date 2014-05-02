<?php

namespace ManiaControl\Commands;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
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
	 * Private Properties
	 */
	private $maniaControl = null;
	private $helpManager = array();
	private $adminCommandListeners = array();
	private $commandListeners = array();

	/**
	 * Construct a new Commands Manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		//Create help manager instance
		$this->helpManager = new HelpManager($this->maniaControl);

		// Register for callback
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCHAT, $this, 'handleChatCallback');
	}

	/**
	 * Register a command listener
	 *
	 * @param string          $commandName
	 * @param CommandListener $listener
	 * @param string          $method
	 * @param bool            $adminCommand
	 * @param string          $description
	 * @return bool
	 */
	public function registerCommandListener($commandName, CommandListener $listener, $method, $adminCommand = false, $description = '') {
		if (is_array($commandName)) {
			$success = true;
			foreach ($commandName as $command) {
				if (!$this->registerCommandListener($command, $listener, $method, $adminCommand, $description)) {
					$success = false;
				}
			}
			return $success;
		}
		$command = strtolower($commandName);
		if (!method_exists($listener, $method)) {
			trigger_error("Given listener can't handle command '{$command}' (no method '{$method}')!");
			return false;
		}
		if ($adminCommand) {
			if (!array_key_exists($command, $this->adminCommandListeners) || !is_array($this->adminCommandListeners[$command])) {
				// Init admin listeners array
				$this->adminCommandListeners[$command] = array();
			}
			// Register admin command listener
			array_push($this->adminCommandListeners[$command], array($listener, $method));
		} else {
			if (!array_key_exists($command, $this->commandListeners) || !is_array($this->commandListeners[$command])) {
				// Init listeners array
				$this->commandListeners[$command] = array();
			}
			// Register command listener
			array_push($this->commandListeners[$command], array($listener, $method));
		}

		//TODO description
		$this->helpManager->registerCommand($command, $adminCommand, $description, get_class($listener) . '\\' . $method);

		return true;
	}

	/**
	 * Remove a Command Listener
	 *
	 * @param CommandListener $listener
	 * @return bool
	 */
	public function unregisterCommandListener(CommandListener $listener) {
		$removed = false;
		foreach ($this->commandListeners as &$listeners) {
			foreach ($listeners as $key => &$listenerCallback) {
				if ($listenerCallback[0] == $listener) {
					unset($listeners[$key]);
					$removed = true;
				}
			}
		}
		foreach ($this->adminCommandListeners as &$listeners) {
			foreach ($listeners as $key => &$listenerCallback) {
				if ($listenerCallback[0] == $listener) {
					unset($listeners[$key]);
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
		if (!$callback[1][3]) {
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

		if (substr($message, 0, 2) == '//' || $command == 'admin') {
			// Admin command
			$commandListeners = $this->adminCommandListeners;

			if ($command == 'admin') {
				// Strip 'admin' keyword
				if (isset($commandArray[1])) {
					$command = $commandArray[1];
					unset($commandArray[1]);
				}
			}
			unset($commandArray[0]);

			// Compose uniformed message
			$message = '//' . $command;
			foreach ($commandArray as $commandPart) {
				$message .= ' ' . $commandPart;
			}
			$callback[1][2] = $message;
		} else {
			// User command
			$commandListeners = $this->commandListeners;
		}

		if (!array_key_exists($command, $commandListeners) || !is_array($commandListeners[$command])) {
			// No command listener registered
			return;
		}

		// Inform command listeners
		foreach ($commandListeners[$command] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback, $player);
		}
	}
}
