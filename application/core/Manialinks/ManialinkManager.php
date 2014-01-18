<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

require_once __DIR__ . '/StyleManager.php';
require_once __DIR__ . '/IconManager.php';
require_once __DIR__ . '/CustomUIManager.php';
require_once __DIR__ . '/../FML/autoload.php';

/**
 * Manialink manager class
 *
 * @author steeffeen & kremsy
 */
class ManialinkManager implements ManialinkPageAnswerListener, CallbackListener {

	/**
	 * Constants
	 */
	const MAIN_MLID             = 'Main.ManiaLinkId';
	const ACTION_CLOSEWIDGET    = 'ManiaLinkManager.CloseWidget';
	const CB_MAIN_WINDOW_CLOSED = 'ManialinkManagerCallback.MainWindowClosed';
	const CB_MAIN_WINDOW_OPENED = 'ManialinkManagerCallback.MainWindowOpened';

	/**
	 * Public properties
	 */
	public $styleManager = null;
	public $customUIManager = null;
	public $iconManager = null;

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
		$this->maniaControl    = $maniaControl;
		$this->styleManager    = new StyleManager($maniaControl);
		$this->customUIManager = new CustomUIManager($maniaControl);
		$this->iconManager     = new IconManager($maniaControl);

		// Register for callbacks
		$this->registerManialinkPageAnswerListener(self::ACTION_CLOSEWIDGET, $this, 'closeWidgetCallback');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
	}

	/**
	 * Register a new manialink page answer listener
	 *
	 * @param string                      $actionId
	 * @param ManialinkPageAnswerListener $listener
	 * @param string                      $method
	 * @return bool
	 */
	public function registerManialinkPageAnswerListener($actionId, ManialinkPageAnswerListener $listener, $method) {
		if(!method_exists($listener, $method)) {
			trigger_error("Given listener for actionId '{$actionId}' doesn't have callback method '{$method}'!");

			return false;
		}
		if(!array_key_exists($actionId, $this->pageAnswerListeners) || !is_array($this->pageAnswerListeners[$actionId])) {
			// Init listeners array
			$this->pageAnswerListeners[$actionId] = array();
		}
		// Register page answer listener
		array_push($this->pageAnswerListeners[$actionId], array($listener, $method));

		return true;
	}

	/**
	 * Remove a Manialink Page Answer Listener
	 *
	 * @param ManialinkPageAnswerListener $listener
	 * @return bool
	 */
	public function unregisterManialinkPageAnswerListener(ManialinkPageAnswerListener $listener) {
		$removed = false;
		foreach($this->pageAnswerListeners as &$listeners) {
			foreach($listeners as $key => &$listenerCallback) {
				if($listenerCallback[0] != $listener) {
					continue;
				}
				unset($listeners[$key]);
				$removed = true;
			}
		}
		return $removed;
	}

	/**
	 * Handle ManialinkPageAnswer callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		$login    = $callback[1][1];
		$player   = $this->maniaControl->playerManager->getPlayer($login);
		if(!array_key_exists($actionId, $this->pageAnswerListeners) || !is_array($this->pageAnswerListeners[$actionId])) {
			// No page answer listener registered
			return;
		}
		// Inform page answer listeners
		foreach($this->pageAnswerListeners[$actionId] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback, $player);
		}
	}

	/**
	 * Send the given manialink to players
	 *
	 * @param string $manialinkText
	 * @param mixed  $logins
	 * @param int    $timeout
	 * @param bool   $hideOnClick
	 * @return bool
	 */
	public function sendManialink($manialinkText, $logins = null, $timeout = 0, $hideOnClick = false) { //TODO imrpvoe
		$manialinkText = (string)$manialinkText;

		try {
			if(!$logins) {
				return $this->maniaControl->client->sendDisplayManialinkPage(null, $manialinkText, $timeout, $hideOnClick);
			}
			if(is_string($logins)) {
				return $this->maniaControl->client->sendDisplayManialinkPage($logins, $manialinkText, $timeout, $hideOnClick);
			}
			if(is_array($logins)) {
				$success = true;
				foreach($logins as $login) {
					$subSuccess = $this->maniaControl->client->sendDisplayManialinkPage($login, $manialinkText, $timeout, $hideOnClick);
					if(!$subSuccess) {
						$success = false;
					}
				}

				return $success;
			}
		} catch(Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * Enable the alt menu for the player
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function enableAltMenu(Player $player) {
		return $this->maniaControl->client->triggerModeScriptEvent('LibXmlRpc_EnableAltMenu', $player->login);
	}

	/**
	 * Disable the alt menu for the player
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function disableAltMenu(Player $player) {
		return $this->maniaControl->client->triggerModeScriptEvent('LibXmlRpc_DisableAltMenu', $player->login);
	}

	/**
	 * Displays a ManiaLink Widget to a certain Player
	 *
	 * @param mixed  $maniaLink
	 * @param Player $player
	 * @param string $widgetName
	 */
	public function displayWidget($maniaLink, Player $player, $widgetName = '') {
		// render and display xml
		$this->maniaControl->manialinkManager->sendManialink($maniaLink, $player->login);
		$this->disableAltMenu($player);

		if($widgetName != '') {
			// Trigger callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_MAIN_WINDOW_OPENED, array(self::CB_MAIN_WINDOW_OPENED, $player, $widgetName));
		}
	}

	/**
	 * Closes a widget via the callback
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function closeWidgetCallback(array $callback, Player $player) {
		$this->closeWidget($player);
	}

	/**
	 * Closes a Manialink Widget
	 *
	 * @param Player $player
	 * @param bool   $widgetId
	 */
	public function closeWidget(Player $player, $widgetId = false) {
		if(!$widgetId) {
			$emptyManialink = new ManiaLink(self::MAIN_MLID);
			$manialinkText  = $emptyManialink->render()->saveXML();
			$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
			$this->enableAltMenu($player);

			// Trigger callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_MAIN_WINDOW_CLOSED, array(self::CB_MAIN_WINDOW_CLOSED, $player));
		} else {
			$emptyManialink = new ManiaLink($widgetId);
			$manialinkText  = $emptyManialink->render()->saveXML();
			$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
		}
	}

	/**
	 * Adds a line of labels
	 *
	 * @param Frame $frame
	 * @param array $labelStrings
	 * @param array $properties
	 * @return array Returns the labels (to add special Properties later)
	 */
	public function labelLine(Frame $frame, array $labelStrings, array $properties = array()) {
		// TODO overwrite standard properties with properties from array

		// define standard properties
		$hAlign    = Control::LEFT;
		$style     = Label_Text::STYLE_TextCardSmall;
		$textSize  = 1.5;
		$textColor = 'FFF';
		$profile   = (isset($properties['profile']) ? $properties['profile'] : false);
		$script    = (isset($properties['script']) ? $properties['script'] : null);

		$labels = array();
		foreach($labelStrings as $text => $x) {
			$label = new Label_Text();
			$frame->add($label);
			$label->setHAlign($hAlign);
			$label->setX($x);
			$label->setStyle($style);
			$label->setTextSize($textSize);
			$label->setText($text);
			$label->setTextColor($textColor);

			if($profile) {
				$script->addProfileButton($label, $profile);
			}

			$labels[] = $label; // add Label to the labels array
		}

		return $labels;
	}
}
