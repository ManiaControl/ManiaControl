<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Class managing Icons
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class IconManager implements CallbackListener {
	/*
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
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_AFTERINIT, $this, 'handleAfterInit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
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
		if (!isset($this->icons[$iconName])) {
			return null;
		}
		return $this->icons[$iconName];
	}

	/**
	 * Handle OnInit Callback
	 */
	public function handleAfterInit() {
		$this->preloadIcons();
	}

	/**
	 * Preload Icons
	 *
	 * @param Player $player
	 */
	public function preloadIcons($player = null) {
		$maniaLink = new ManiaLink(self::PRELOAD_MLID);
		$frame     = new Frame();
		$maniaLink->add($frame);
		$frame->setPosition(500, 500);

		foreach ($this->icons as $iconUrl) {
			$iconQuad = new Quad();
			$iconQuad->setImage($iconUrl);
			$iconQuad->setSize(1, 1);
			$frame->add($iconQuad);
		}

		// Send manialink
		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $player);
	}

	/**
	 * Handle PlayerConnect Callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		$this->preloadIcons($player);
	}
}