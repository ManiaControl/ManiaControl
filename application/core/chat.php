<?php

namespace ManiaControl;

/**
 * Class for chat methods
 *
 * @author steeffeen
 */
class Chat {

	/**
	 * Private properties
	 */
	private $mc = null;

	private $config = null;

	private $prefix = 'ManiaControl>';

	/**
	 * Construct ManiaControl chat
	 */
	public function __construct($mc) {
		$this->mc = $mc;
		
		// Load config
		$this->config = Tools::loadConfig('chat.ManiaControl.xml');
	}

	/**
	 * Send a chat message to the given login
	 *
	 * @param string $login        	
	 * @param string $message        	
	 * @param bool $prefix        	
	 */
	public function sendChat($message, $login = null, $prefix = false) {
		if (!$this->mc->client) return false;
		if ($login === null) {
			return $this->mc->client->query('ChatSendServerMessage', '$z' . ($prefix ? $this->prefix : '') . $message . '$z');
		}
		else {
			return $this->mc->client->query('ChatSendServerMessageToLogin', '$z' . ($prefix ? $this->prefix : '') . $message . '$z', $login);
		}
	}

	/**
	 * Send an information message to the given login
	 *
	 * @param string $login        	
	 * @param string $message        	
	 * @param bool $prefix        	
	 */
	public function sendInformation($message, $login = null, $prefix = false) {
		$format = (string) $this->config->messages->information;
		return $this->sendChat($format . $message, $login);
	}

	/**
	 * Send a success message to the given login
	 *
	 * @param string $message        	
	 * @param string $login        	
	 * @param bool $prefix        	
	 */
	public function sendSuccess($message, $login = null, $prefix = false) {
		$format = (string) $this->config->messages->success;
		return $this->sendChat($format . $message, $login);
	}

	/**
	 * Send an error message to the given login
	 *
	 * @param string $login        	
	 * @param string $message        	
	 * @param bool $prefix        	
	 */
	public function sendError($message, $login = null, $prefix = false) {
		$format = (string) $this->config->messages->error;
		return $this->sendChat($format . $message, $login);
	}
}

?>
