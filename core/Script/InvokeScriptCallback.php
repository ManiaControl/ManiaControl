<?php

namespace ManiaControl\Script;


use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Structures\Common\BaseResponseStructure;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * Class For Invoking Callbacks on Script Methods
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InvokeScriptCallback implements CallbackListener, UsageInformationAble {
	use UsageInformationTrait;

	/**
	 * @var \ManiaControl\ManiaControl $maniaControl
	 */
	private $maniaControl;
	private $callbackName;
	private $responseId;

	/**
	 * InvokeScriptCallback constructor.
	 *
	 * @param $maniaControl
	 * @param $callbackName
	 * @param $responseId
	 */
	public function __construct($maniaControl, $callbackName, $responseId) {
		$this->maniaControl = $maniaControl;
		$this->callbackName = $callbackName;
		$this->responseId   = $responseId;
	}

	/**
	 * Sets a Callable to be called back with the Information
	 *
	 * @api
	 * @param  callable $function async Function to Call back
	 */
	public function setCallable(callable $function) {
		$this->maniaControl->getCallbackManager()->registerCallbackListener($this->callbackName, $this, function (BaseResponseStructure $callBackData) use (&$function) {
			if ($callBackData->getResponseId() == $this->responseId) {
				call_user_func_array($function, array($callBackData));
			}
		});
	}

	/**
	 * You can set a Method in your Class to be called with this Variant
	 *
	 * @api
	 * @param \ManiaControl\Callbacks\CallbackListener $callbackListener
	 * @param                                          $methodName
	 */
	public function setCallableMethod(CallbackListener $callbackListener, $methodName) {
		$this->maniaControl->getCallbackManager()->registerCallbackListener($this->callbackName, $this, function (BaseResponseStructure $callBackData) use (&$callbackListener, &$methodName) {
			if ($callBackData->getResponseId() == $this->responseId) {
				call_user_func_array(array($callbackListener, $methodName), array($callBackData));
			}
		});
	}

	/**
	 * Returns the Generated ResponseId
	 *
	 * @api
	 * @return mixed
	 */
	public function getResponseId() {
		return $this->responseId;
	}
}