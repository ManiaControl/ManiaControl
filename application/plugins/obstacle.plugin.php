<?php

namespace mControl;

/**
 * mControl Obstacle Plugin
 *
 * @author steeffeen
 */
class Plugin_Obstacle extends Plugin {
	/**
	 * Constants
	 */
	const CB_JUMPTO = 'Obstacle.JumpTo';
	const VERSION = '1.0';

	/**
	 * Private properties
	 */
	private $mControl = null;

	private $config = null;

	/**
	 * Constuct obstacle plugin
	 */
	public function __construct($mControl) {
		$this->mControl = $mControl;
		
		// Load config
		$this->config = Tools::loadConfig('obstacle.plugin.xml');
		
		// Check for enabled setting
		if (!Tools::toBool($this->config->enabled)) return;
		
		// Register for jump command
		$this->iControl->commands->registerCommandHandler('jumpto', $this, 'command_jumpto');
		
		error_log('Obstacle Pugin v' . self::VERSION . ' ready!');
	}

	/**
	 * Handle jumpto command
	 */
	public function command_jumpto($chat) {
		$login = $chat[1][1];
		$rightLevel = (string) $this->config->jumps_rightlevel;
		if (!$this->iControl->authentication->checkRight($login, $rightLevel)) {
			// Not allowed
			$this->iControl->authentication->sendNotAllowed($login);
		}
		else {
			// Send jump callback
			$params = explode(' ', $chat[1][2], 2);
			$param = $login . ";" . $params[1] . ";";
			if (!$this->iControl->client->query('TriggerModeScriptEvent', self::CB_JUMPTO, $param)) {
				trigger_error("Couldn't send jump callback for '" . $login . "'. " . $this->iControl->getClientErrorText());
			}
		}
	}
}

?>
