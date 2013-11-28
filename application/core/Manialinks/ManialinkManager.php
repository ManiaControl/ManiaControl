<?php

namespace ManiaControl\Manialinks;

use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;

require_once __DIR__ . '/StyleManager.php';
require_once __DIR__ . '/../FML/autoload.php';

/**
 * Manialink manager class
 *
 * @author steeffeen & kremsy
 */
class ManialinkManager implements CallbackListener {
	/**
	 * Public properties
	 */
	public $styleManager = null;
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $pageAnswerListeners = array();
	private $maniaLinkIdCount = 0;

	/**
	 * Create a new manialink manager
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->styleManager = new StyleManager($maniaControl);
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 
				'handleManialinkPageAnswer');
	}

	/**
	 * Register a new manialink page answer listener
	 *
	 * @param string $actionId        	
	 * @param ManialinkPageAnswerListener $listener        	
	 * @param string $method        	
	 * @return bool
	 */
	public function registerManialinkPageAnswerListener($actionId, ManialinkPageAnswerListener $listener, $method) {
		if (!method_exists($listener, $method)) {
			trigger_error("Given listener for actionId '{$actionId}' doesn't have callback method '{$method}'!");
			return false;
		}
		if (!array_key_exists($actionId, $this->pageAnswerListeners) || !is_array($this->pageAnswerListeners[$actionId])) {
			// Init listeners array
			$this->pageAnswerListeners[$actionId] = array();
		}
		// Register page answer listener
		array_push($this->pageAnswerListeners[$actionId], array($listener, $method));
		return true;
	}

	/**
	 * Reserve manialink ids
	 *
	 * @param int $count        	
	 * @return array
	 */
	public function reserveManiaLinkIds($count) {
		$manialinkIds = array();
		for ($i = 0; $i < $count; $i++) {
			array_push($manialinkIds, $this->maniaLinkIdCount++);
		}
		return $manialinkIds;
	}

	/**
	 * Handle ManialinkPageAnswer callback
	 *
	 * @param array $callback        	
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		$login = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!array_key_exists($actionId, $this->pageAnswerListeners) || !is_array($this->pageAnswerListeners[$actionId])) {
			// No page answer listener registered
			return;
		}
		// Inform page answer listeners
		foreach ($this->pageAnswerListeners[$actionId] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback, $player);
		}
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
