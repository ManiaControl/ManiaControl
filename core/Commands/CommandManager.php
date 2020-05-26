<?php

namespace ManiaControl\Commands;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Listening;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Class for handling Chat Commands
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CommandManager implements CallbackListener, UsageInformationAble {
	use UsageInformationTrait;
	
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var HelpManager $helpManager */
	private $helpManager = array();
	/** @var Listening[][] $commandListenings */
	private $commandListenings = array();
	/** @var CommandListener[][] $disabledCommands */
	private $disabledCommands = array();
	/** @var Listening[][] $adminCommandListenings */
	private $adminCommandListenings = array();
	/** @var CommandListener[][] $disabledAdminCommands */
	private $disabledAdminCommands = array();

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
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERCHAT, $this, 'handleChatCallback');
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
	public function registerCommandListener($commandName, CommandListener $listener, $method, $adminCommand = false, $description = "No Description.") {
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
	 * Disable the command(s) by the given listener.
	 * The specific listener has to also manually reenable the commands, before the command can be used again.
	 * @param mixed           $commandName
	 * @param bool            $adminCommand
	 * @param CommandListener $listener
	 */
	public function disableCommand($commandName, $adminCommand, CommandListener $listener) {
		if (is_array($commandName)) {
			foreach ($commandName as $command) {
				$this->disableCommand($command, $adminCommand, $listener);
			}
			return;
		}

		$command = strtolower(trim($commandName));
		// first, check if the command actually exists
		if (!array_key_exists($command, $this->commandListenings) && !array_key_exists($command, $this->adminCommandListenings)) {
			return;
		}

		$disabledCommands = null;
		if ($adminCommand) {
			$disabledCommands = &$this->disabledAdminCommands;
		} else {
			$disabledCommands = &$this->disabledCommands;
		}

		if (!array_key_exists($command, $disabledCommands)) {
			$disabledCommands[$command] = array();
		}

		if (!in_array($listener, $disabledCommands[$command])) {
			array_push($disabledCommands[$command], $listener);
		}
	}

	/**
	 * Enable the command(s) by the given listener.
	 * @param mixed           $commandName
	 * @param bool            $adminCommand
	 * @param CommandListener $listener
	 */
	public function enableCommand($commandName, $adminCommand, CommandListener $listener) {
		if (is_array($commandName)) {
			foreach ($commandName as $command) {
				$this->enableCommand($command, $adminCommand, $listener);
			}
			return;
		}

		$command = strtolower(trim($commandName));

		$disabledCommands = null;
		if ($adminCommand) {
			$disabledCommands = &$this->disabledAdminCommands;
		} else {
			$disabledCommands = &$this->disabledCommands;
		}

		if (!array_key_exists($command, $disabledCommands)) {
			return;
		}

		if (($key = array_search($listener, $disabledCommands[$command])) !== false) {
			unset($disabledCommands[$command][$key]);
			if (empty($disabledCommands[$command])) {
				unset($disabledCommands[$command]);
			}
		}
	}

	/**
	 * Checks if a command is enabled.
	 * @param mixed $commandName
	 * @param bool  $adminCommand
	 * @return bool|array
	 */
	public function isCommandEnabled($commandName, $adminCommand) {
		if (is_array($commandName)) {
			$results = array();
			foreach ($commandName as $command) {
				array_push($results, $this->isCommandEnabled($command, $adminCommand));
			}
			$resultsUnique = array_unique($results);
			if (count($resultsUnique) === 1) {
				return $resultsUnique[0];
			}

			return $results;
		}

		$command = strtolower(trim($commandName));

		$disabledCommands = null;
		if ($adminCommand) {
			$disabledCommands = &$this->disabledAdminCommands;
		} else {
			$disabledCommands = &$this->disabledCommands;
		}

		if (!array_key_exists($command, $disabledCommands)) {
			return true;
		}

		// if the command is disabled, there should be at least one listener in the array
		assert(!empty($disabledCommands[$command]));
		return false;
	}

	/**
	 * Removes the given CommandListener blocking commands.
	 * 
	 * @param array &$disabledCommands
	 * @param CommandListener $listener
	 * @return bool
	 */
	private function removeDisabledCommandListener(array &$disabledCommands, CommandListener $listener) {
		$removed = false;
		foreach ($disabledCommands as $command => $disableListeners) {
			if (($key = array_search($listener, $disableListeners)) !== false) {
				unset($disabledCommands[$command][$key]);
				$removed = true;

				if (empty($disabledCommands[$command])) {
					unset($disabledCommands[$command]);
				}
			}
		}
		return $removed;
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
		if ($this->removeDisabledCommandListener($this->disabledCommands, $listener)) {
			$removed = true;
		}
		if ($this->removeDisabledCommandListener($this->disabledAdminCommands, $listener)) {
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
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
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
		
		$isAdminCommand = null;
		if (substr($message, 0, 2) === '//' || $command === 'admin') {
			// Admin command
			$isAdminCommand = true;
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
			$isAdminCommand = false;
			$commandListenings = $this->commandListenings;
		}

		if (!array_key_exists($command, $commandListenings) || !is_array($commandListenings[$command])) {
			// No command listener registered
			return;
		}

		if (!$this->isCommandEnabled($command, $isAdminCommand)) {
			$prefix = $isAdminCommand ? '//' : '/';
			$message = $this->maniaControl->getChat()->formatMessage(
				'The command %s%s is currently disabled!',
				$prefix,
				$command
			);
			$this->maniaControl->getChat()->sendError($message, $player);
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
		return (bool) $chatCallback[1][3];
	}
}
