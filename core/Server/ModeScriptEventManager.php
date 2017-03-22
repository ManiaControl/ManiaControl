<?php
/**
 * Manager for Mode Script Events
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
namespace ManiaControl\Server;

//TODO maybe own folder

use ManiaControl\ManiaControl;

class ModeScriptEventManager {
	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct a new ranking manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Enables XmlRpc Callbacks
	 */
	public function enableCallbacks() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.EnableCallbacks', array('true'));
	}

	/**
	 * Disables XmlRpc Callbacks
	 */
	public function disableCallbacks() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.EnableCallbacks', array('false'));
	}
}