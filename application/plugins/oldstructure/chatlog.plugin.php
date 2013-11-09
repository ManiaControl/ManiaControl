<?php

namespace ManiaControl;

/**
 * ManiaControl Chatlog Plugin
 *
 * @author steeffeen
 */
class Plugin_Chatlog extends Plugin{
	/**
	 * Constants
	 */
	const VERSION = '1.0';

	/**
	 * Private properties
	 */
	private $mControl = null;

	private $config = null;

	private $settings = null;

	/**
	 * Constuct chatlog plugin
	 */
	public function __construct($mControl) {
		$this->mControl = $mControl;
		
		// Load config
		$this->config = Tools::loadConfig('chatlog.plugin.xml');
		
		// Check for enabled setting
		if (!Tools::toBool($this->config->enabled)) return;
		
		// Load settings
		$this->loadSettings();
		
		// Register for callbacksc
		$this->iControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCHAT, $this, 'handlePlayerChatCallback');
		
		error_log('Chatlog Pugin v' . self::VERSION . ' ready!');
	}

	/**
	 * Load settings from config
	 */
	private function loadSettings() {
		$this->settings = new \stdClass();
		
		// File name
		$fileName = (string) $this->config->filename;
		$this->settings->fileName = ManiaControlDir . '/' . $fileName;
		
		// log_server_messages
		$log_server_messages = $this->config->xpath('log_server_messages');
		$this->settings->log_server_messages = ($log_server_messages ? (Tools::toBool($log_server_messages[0])) : false);
	}

	/**
	 * Handle PlayerChat callback
	 */
	public function handlePlayerChatCallback($callback) {
		$data = $callback[1];
		if ($data[0] <= 0 && !$this->settings->log_server_messages) {
			// Skip server message
			return;
		}
		$this->logText($data[2], $data[1]);
	}

	/**
	 * Log the given message
	 *
	 * @param string $message        	
	 * @param string $login        	
	 */
	private function logText($text, $login = null) {
		$message = date(ManiaControl::DATE) . '>> ' . ($login ? $login . ': ' : '') . $text . PHP_EOL;
		file_put_contents($this->settings->fileName, $message, FILE_APPEND);
	}
}

?>
