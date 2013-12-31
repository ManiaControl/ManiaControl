<?php
/**
 * Class managing Icon
 *
 * @author steeffeen & kremsy
 */

namespace ManiaControl\Manialinks;


use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\PlayerManager;

class IconManager implements CallbackListener {
	/**
	 * Constants
	 */
	const DEFAULT_IMG_URL = "http://images.maniacontrol.com/icons/";
	const PRELOAD_ML_ID   = "IconManager.Preload";

	/**
	 * Some Default icons
	 */
	const MX_ICON = 'ManiaExchange.png';

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

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerConnect');
	}


	/**
	 * Adds an Icon
	 *
	 * @param string $iconName
	 * @param string $iconLink
	 */
	public function addIcon($iconName, $iconLink = self::DEFAULT_IMG_URL) {
		$this->icons[$iconName] = $iconLink . "/" . $iconName;
	}

	/**
	 * Gets an Icon by its name
	 *
	 * @param $iconName
	 * @return string
	 */
	public function getIcon($iconName) {
		return $this->icons[$iconName];
	}

	/**
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		$this->preloadIcons();
	}

	/**
	 * @param array $callback
	 */
	public function handlePlayerConnect(array $callback) {
		$this->preloadIcons($callback[1]);
	}

	/**
	 * Preload Icons
	 */
	private function preloadIcons($login = false) {
		$maniaLink = new ManiaLink(self::PRELOAD_ML_ID);

		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setPosition(500, 500);

		foreach ($this->icons as $iconUrl) {
			$iconQuad = new Quad();
			$iconQuad->setImage($iconUrl);
			$iconQuad->setSize(10, 10);
			$frame->add($iconQuad);
		}

		// Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);
	}
} 