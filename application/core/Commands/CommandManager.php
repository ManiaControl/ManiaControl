<?php

namespace ManiaControl\Commands;

use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;

/**
 * Class for handling chat commands
 *
 * @author steeffeen & kremsy
 */
class CommandManager implements CallbackListener {
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $commandListeners = array();
	private $adminCommandListeners = array();

	/**
	 * Construct commands manager
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCHAT, $this, 'handleChatCallback');
	}

	/**
	 * Register a command listener
	 *
	 * @param string $commandName        	
	 * @param CommandListener $listener        	
	 * @param string $method        	
	 * @param bool $adminCommand        	
	 * @return bool
	 */
	public function registerCommandListener($commandName, CommandListener $listener, $method, $adminCommand = false) {
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
		}
		else {
			if (!array_key_exists($command, $this->commandListeners) || !is_array($this->commandListeners[$command])) {
				// Init listeners array
				$this->commandListeners[$command] = array();
			}
			// Register command listener
			array_push($this->commandListeners[$command], array($listener, $method));
		}
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
	 * Handle chat callback
	 *
	 * @param array $callback        	
	 */
	public function handleChatCallback(array $callback) {
		// Check for command
		if (!$callback[1][3]) {
			return;
		}
		// Check for valid player
		$player = $this->maniaControl->playerManager->getPlayer($callback[1][1]);
		if (!$player) {
			return;
		}
		// Handle command
		$commandArray = explode(" ", substr($callback[1][2], 1));
		$command = strtolower($commandArray[0]);
		
		if (substr($command, 0, 1) == "/" || $command == "admin") { // admin command
			$commandListeners = $this->adminCommandListeners;
			if ($command == "admin") {
				$command = strtolower($commandArray[1]);
			}
			else {
				$command = substr($command, 1); // remove /
			}
		}
		else { // user command
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
