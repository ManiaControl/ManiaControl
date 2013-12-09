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
	 * @return bool
	 */
	public function registerCommandListener($commandName, CommandListener $listener, $method) {
		$command = strtolower($commandName);
		if (!method_exists($listener, $method)) {
			trigger_error("Given listener can't handle command '{$command}' (no method '{$method}')!");
			return false;
		}
		if (!array_key_exists($command, $this->commandListeners) || !is_array($this->commandListeners[$command])) {
			// Init listeners array
			$this->commandListeners[$command] = array();
		}
		// Register command listener
		array_push($this->commandListeners[$command], array($listener, $method));
		return true;
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
		$command = explode(" ", substr($callback[1][2], 1));
		$command = strtolower($command[0]);
		if (!array_key_exists($command, $this->commandListeners) || !is_array($this->commandListeners[$command])) {
			// No command listener registered
			return;
		}
		// Inform command listeners
		foreach ($this->commandListeners[$command] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback, $player);
		}
	}
}

?>
