<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Class managing Icons
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class IconManager implements CallbackListener, UsageInformationAble {
	use UsageInformationTrait;
	
	/*
	 * Constants
	 */
	const DEFAULT_IMG_URL = 'https://images.maniacontrol.com/icon/';
	const PRELOAD_MLID    = 'IconManager.Preload.MLID';

	/*
	 * Default icons
	 */
	const MX_ICON       = 'ManiaExchange.png';
	const MX_ICON_MOVER = 'ManiaExchange_logo_press.png';

	const MX_ICON_GREEN       = 'ManiaExchangeGreen.png';
	const MX_ICON_GREEN_MOVER = 'ManiaExchange_logo_pressGreen.png';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $icons        = array();

	/**
	 * Construct a new icon manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->addDefaultIcons();

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::AFTERINIT, $this, 'handleAfterInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
	}

	/**
	 * Add the set of default icons
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
	 * Adds an Icon by it's full URL
	 *
	 * @param $iconName
	 * @param $iconUrl
	 */
	public function addIconFullUrl($iconName, $iconUrl) {
		$this->icons[$iconName] = $iconUrl;
	}

	/**
	 * Get an Icon by its Name
	 *
	 * @param string $iconName
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
		$maniaLink->addChild($frame);
		$frame->setPosition(500, 500);

		foreach ($this->icons as $iconUrl) {
			$iconQuad = new Quad();
			$iconQuad->setImageUrl($iconUrl);
			$iconQuad->setSize(1, 1);
			$frame->addChild($iconQuad);
		}

		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $player);
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
