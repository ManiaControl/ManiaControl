<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\PlayerManager;

/**
 * Class managing Icons
 *
 * @author steeffeen & kremsy
 */
class IconManager implements CallbackListener {
	/**
	 * Constants
	 */
	const DEFAULT_IMG_URL = 'http://images.maniacontrol.com/icons/';
	const PRELOAD_MLID    = 'IconManager.Preload.MLID';

	/**
	 * Some Default icons
	 */
	const MX_ICON       = 'ManiaExchange.png';
	const MX_ICON_MOVER = 'ManiaExchange_logo_press.png';

	const MX_ICON_GREEN       = 'ManiaExchangeGreen.png';
	const MX_ICON_GREEN_MOVER = 'ManiaExchange_logo_pressGreen.png';

	/**
	 * Private Properties
	 */
	private $maniaControl = null;
	private $icons = array();

	/**
	 * Create a new Icon Manager
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->addDefaultIcons();

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerConnect');
	}

	/**
	 * Add the Set of default Icons
	 */
	private function addDefaultIcons() {
		$this->addIcon(self::MX_ICON);
		$this->addIcon(self::MX_ICON_MOVER);
		$this->addIcon(self::MX_ICON_GREEN);
		$this->addIcon(self::MX_ICON_GREEN_MOVER);

	}

	/**
	 * Add an Icon
	 *
	 * @param string $iconName
	 * @param string $iconLink
	 */
	public function addIcon($iconName, $iconLink = self::DEFAULT_IMG_URL) {
		$this->icons[$iconName] = $iconLink . '/' . $iconName;
	}

	/**
	 * Get an Icon by its name
	 *
	 * @param $iconName
	 * @return string
	 */
	public function getIcon($iconName) {
		if(!isset($this->icons[$iconName])) {
			return null;
		}
		return $this->icons[$iconName];
	}

	/**
	 * Handle OnInit Callback
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		$this->preloadIcons();
	}

	/**
	 * Handle PlayerConnect Callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerConnect(array $callback) {
		$login = $callback[1];
		$this->preloadIcons($login);
	}

	/**
	 * Preload Icons
	 *
	 * @param string $login
	 */
	public function preloadIcons($login = false) {
		$maniaLink = new ManiaLink(self::PRELOAD_MLID);
		$frame     = new Frame();
		$maniaLink->add($frame);
		$frame->setPosition(500, 500);

		foreach($this->icons as $iconUrl) {
			$iconQuad = new Quad();
			$iconQuad->setImage($iconUrl);
			$iconQuad->setSize(1, 1);
			$frame->add($iconQuad);
		}

		// Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);
	}
}