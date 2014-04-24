<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 24.04.14
 * Time: 16:57
 */

namespace ManiaControl\Callbacks;


use ManiaControl\ManiaControl;

class LibXmlRpcCallbackManager implements CallbackListener{
	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new ShootMania Callbacks Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl, CallbackManager $callbackManager) {
		$this->maniaControl = $maniaControl;
		$callbackManager->registerCallbackListener(Callbacks::ScriptCallback, $this, 'handleScriptCallbacks');
	}

	public function handleScriptCallbacks($name, $data){
		switch($name){
			case 'LibXmlRpc_BeginMatch':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::LibXmlRpc_BeginMatch, $data[0]);
				break;
			case 'LibXmlRpc_LoadingMap':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::LibXmlRpc_LoadingMap, $data[0]);
				break;
			case 'LibXmlRpc_BeginMap':
				$this->maniaControl->callbackManager->triggerCallback(Callbacks::LibXmlRpc_BeginMap, $data[0]);
				break;


		}
		var_dump($name);
		var_dump($data);
	}



} 