<?php

namespace ManiaControl;

/**
 * Chat utility class
 *
 * @author steeffeen & kremsy
 */
class Chat {
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Construct chat utility
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Get prefix
	 *
	 * @param string|bool $prefix        	
	 * @return string
	 */
	private function getPrefix($prefix) {
		if (is_string($prefix)) {
			return $prefix;
		}
		if ($prefix === true) {
			return $this->maniaControl->settingManager->getSetting($this, 'DefaultPrefix', 'ManiaControl>');
		}
		return '';
	}

	/**
	 * Send a chat message to the given login
	 *
	 * @param string $message        	
	 * @param string $login        	
	 * @param string|bool $prefix        	
	 * @return bool
	 */
	public function sendChat($message, $login = null, $prefix = false) {
		if (!$this->maniaControl->client) {
			return false;
		}
		$client = $this->maniaControl->client;
		$chatMessage = '$z' . $this->getPrefix($prefix) . $message . '$z';
		if ($login === null) {
			return $client->query('ChatSendServerMessage', $chatMessage);
		}
		return $client->query('ChatSendServerMessageToLogin', $chatMessage, $login);
	}

	/**
	 * Send an information message to the given login
	 *
	 * @param string $message        	
	 * @param string $login        	
	 * @param string|bool $prefix        	
	 * @return bool
	 */
	public function sendInformation($message, $login = null, $prefix = false) {
		$format = $this->maniaControl->settingManager->getSetting($this, 'ErrorFormat', '$fff');
		return $this->sendChat($format . $message, $login);
	}

	/**
	 * Send a success message to the given login
	 *
	 * @param string $message        	
	 * @param string $login        	
	 * @param string|bool $prefix        	
	 * @return bool
	 */
	public function sendSuccess($message, $login = null, $prefix = false) {
		$format = $this->maniaControl->settingManager->getSetting($this, 'ErrorFormat', '$0f0');
		return $this->sendChat($format . $message, $login);
	}

	/**
	 * Send an error message to the given login
	 *
	 * @param string $message        	
	 * @param string $login        	
	 * @param string|bool $prefix        	
	 * @return bool
	 */
	public function sendError($message, $login = null, $prefix = false) {
		$format = $this->maniaControl->settingManager->getSetting($this, 'ErrorFormat', '$f00');
		return $this->sendChat($format . $message, $login);
	}
}

?>
