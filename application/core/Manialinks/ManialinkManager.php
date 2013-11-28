<?php

namespace ManiaControl\Manialinks;

use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;

require_once __DIR__ . '/ManialinkPageAnswerListener.php';
require_once __DIR__ . '/../FML/autoload.php';

/**
 * Manialink manager class
 *
 * @author steeffeen & kremsy
 */
class ManialinkManager implements CallbackListener {
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $pageAnswerListeners = array();

	/**
	 * Create a new manialink manager
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 
				'handleManialinkPageAnswer');
	}

	/**
	 * Register a new manialink page answer listener
	 *
	 * @param string $manialinkId        	
	 * @param ManialinkPageAnswerListener $listener        	
	 * @param string $method        	
	 * @return bool
	 */
	public function registerManialinkPageAnswerListener($manialinkId, ManialinkPageAnswerListener $listener, $method) {
		if (!method_exists($listener, $method)) {
			trigger_error("Given listener for manialinkId '{$manialinkId}' doesn't have callback method '{$method}'.");
			return false;
		}
		if (!array_key_exists($manialinkId, $this->pageAnswerListeners) || !is_array($this->pageAnswerListeners[$manialinkId])) {
			// Init listeners array
			$this->pageAnswerListeners[$manialinkId] = array();
		}
		// Register page answer listener
		array_push($this->pageAnswerListeners[$manialinkId], array($listener, $method));
		return true;
	}

	/**
	 * Handle ManialinkPageAnswer callback
	 *
	 * @param array $callback        	
	 */
	public function handleManialinkPageAnswer(array $callback) {
		var_dump($callback);
	}

	/**
	 * Send the given manialink to players
	 *
	 * @param string $manialinkText        	
	 * @param mixed $logins        	
	 * @param int $timeout        	
	 * @param bool $hideOnClick        	
	 * @return bool
	 */
	public function sendManialink($manialinkText, $logins = null, $timeout = 0, $hideOnClick = false) {
		if (!$logins) {
			return $this->maniaControl->client->query('SendDisplayManialinkPage', $manialinkText, $timeout, $hideOnClick);
		}
		if (is_string($logins)) {
			return $this->maniaControl->client->query('SendDisplayManialinkPageToLogin', $logins, $manialinkText, $timeout, 
					$hideOnClick);
		}
		if (is_array($logins)) {
			$success = true;
			foreach ($logins as $login) {
				$subSuccess = $this->maniaControl->client->query('SendDisplayManialinkPageToLogin', $login, $manialinkText, $timeout, 
						$hideOnClick);
				if (!$subSuccess) {
					$success = false;
				}
			}
			return $success;
		}
		return false;
	}
}

?>
