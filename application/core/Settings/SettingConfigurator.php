<?php

namespace ManiaControl\Settings;

use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;

/**
 * Ingame setting configurator class
 *
 * @author kremsy & steeffeen
 */
class SettingConfigurator implements CallbackListener {
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Construct setting configurator
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'onInit');
		
		$this->maniaControl->manialinkIdHandler->reserveManiaLinkIds(100);
	}

	/**
	 * Handle OnInit callback
	 *
	 * @param array $callback        	
	 */
	public function onInit(array $callback) {
		// TODO: handle callback
		// $this->maniaControl->manialinkUtil->
		// $this->maniaControl->chat->sendChat("test");
	}
} 