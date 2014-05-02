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
use Maniaplanet\DedicatedServer\Xmlrpc\LoginUnknownException;
use Maniaplanet\DedicatedServer\Xmlrpc\NotInScriptModeException;

/**
 * Manialink Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManialinkManager implements ManialinkPageAnswerListener, CallbackListener {
	/*
	 * Constants
	 */
	const MAIN_MLID             = 'Main.ManiaLinkId';
	const ACTION_CLOSEWIDGET    = 'ManiaLinkManager.CloseWidget';
	const CB_MAIN_WINDOW_CLOSED = 'ManialinkManagerCallback.MainWindowClosed';
	const CB_MAIN_WINDOW_OPENED = 'ManialinkManagerCallback.MainWindowOpened';

	/*
	 * Public Properties
	 */
	public $styleManager = null;
	public $customUIManager = null;
	public $iconManager = null;

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $pageAnswerListeners = array();
	private $pageAnswerRegexListener = array();

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
	 * Register a new manialink page answer reg ex listener
	 *
	 * @param string                      $actionIdRegex
	 * @param ManialinkPageAnswerListener $listener
	 * @param string                      $method
	 * @return bool
	 */
	public function registerManialinkPageAnswerRegexListener($actionIdRegex, ManialinkPageAnswerListener $listener, $method) {
		if (!method_exists($listener, $method)) {
			trigger_error("Given listener for actionIdRegex '{$actionIdRegex}' doesn't have callback method '{$method}'!");
			return false;
		}

		if (!array_key_exists($actionIdRegex, $this->pageAnswerRegexListener) || !is_array($this->pageAnswerRegexListener[$actionIdRegex])) {
			// Init regex listeners array
			$this->pageAnswerRegexListener[$actionIdRegex] = array();
		}

		// Register page answer reg exlistener
		array_push($this->pageAnswerRegexListener[$actionIdRegex], array($listener, $method));

		return true;
	}

	/**
	 * Remove a Manialink Page Answer Listener
	 *
	 * @param ManialinkPageAnswerListener $listener
	 * @return bool
	 */
	public function unregisterManialinkPageAnswerListener(ManialinkPageAnswerListener $listener) {
		$removed      = false;
		$allListeners = array_merge($this->pageAnswerListeners, $this->pageAnswerRegexListener);
		foreach ($allListeners as &$listeners) {
			foreach ($listeners as $key => &$listenerCallback) {
				if ($listenerCallback[0] !== $listener) {
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

		if (array_key_exists($actionId, $this->pageAnswerListeners) && is_array($this->pageAnswerListeners[$actionId])) {
			// Inform page answer listeners
			foreach ($this->pageAnswerListeners[$actionId] as $listener) {
				call_user_func($listener, $callback, $player);
			}
		}

		// Check regex listeners
		foreach ($this->pageAnswerRegexListener as $actionIdRegex => $pageAnswerRegexListeners) {
			if (preg_match($actionIdRegex, $actionId)) {
				// Inform page answer regex listeners
				foreach ($pageAnswerRegexListeners as $listener) {
					call_user_func($listener, $callback, $player);
				}
			}
		}
	}

	/**
	 * Hide the Manialink with the given Id
	 *
	 * @param mixed $manialinkId
	 * @param mixed $logins
	 */
	public function hideManialink($manialinkId, $logins = null) {
		if (is_array($manialinkId)) {
			foreach ($manialinkId as $mlId) {
				$this->hideManialink($mlId, $logins);
			}
		} else {
			$emptyManialink = new ManiaLink($manialinkId);
			$this->sendManialink($emptyManialink, $logins);
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
	public function sendManialink($manialinkText, $logins = null, $timeout = 0, $hideOnClick = false) {
		$manialinkText = (string)$manialinkText;

		if (!$manialinkText) {
			return true;
		}

		try {
			if (!$logins) {
				return $this->maniaControl->client->sendDisplayManialinkPage(null, $manialinkText, $timeout, $hideOnClick);
			}
			if (is_string($logins)) {
				$success = $this->maniaControl->client->sendDisplayManialinkPage($logins, $manialinkText, $timeout, $hideOnClick);
				return $success;
			}
			if ($logins instanceof Player) {
				$success = $this->maniaControl->client->sendDisplayManialinkPage($logins->login, $manialinkText, $timeout, $hideOnClick);
				return $success;
			}
			if (is_array($logins)) {
				$success = true;
				foreach ($logins as $login) {
					$subSuccess = $this->maniaControl->client->sendDisplayManialinkPage($login, $manialinkText, $timeout, $hideOnClick);
					if (!$subSuccess) {
						$success = false;
					}
				}

				return $success;
			}
		} catch (LoginUnknownException $e) {
			return false;
		}

		return true;
	}

	/**
	 * Displays a ManiaLink Widget to a certain Player (Should only be used on Main Widgets)
	 *
	 * @param mixed  $maniaLink
	 * @param Player $player
	 * @param string $widgetName
	 */
	public function displayWidget($maniaLink, Player $player, $widgetName = '') {
		// render and display xml
		$this->sendManialink($maniaLink, $player->login);

		if ($widgetName != '') {
			// TODO make check by manialinkId, getter is needed to avoid uses on non main widgets
			$this->disableAltMenu($player);
			// Trigger callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_MAIN_WINDOW_OPENED, $player, $widgetName);
		}
	}

	/**
	 * Disable the alt menu for the player
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function disableAltMenu(Player $player) {
		try {
			$success = $this->maniaControl->client->triggerModeScriptEvent('LibXmlRpc_DisableAltMenu', $player->login);
		} catch (NotInScriptModeException $e) {
			return false;
		}
		return $success;
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
		if (!$widgetId) {
			$emptyManialink = new ManiaLink(self::MAIN_MLID);
			$this->sendManialink($emptyManialink, $player->login);
			$this->enableAltMenu($player);

			// Trigger callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_MAIN_WINDOW_CLOSED, $player);
		} else {
			$emptyManialink = new ManiaLink($widgetId);
			$this->sendManialink($emptyManialink, $player->login);
		}
	}

	/**
	 * Enable the alt menu for the player
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function enableAltMenu(Player $player) {
		try {
			$success = $this->maniaControl->client->triggerModeScriptEvent('LibXmlRpc_EnableAltMenu', $player->login);
		} catch (NotInScriptModeException $e) {
			return false;
		}
		return $success;
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
		// define standard properties
		$hAlign    = (isset($properties['hAlign']) ? $properties['hAlign'] : Control::LEFT);
		$style     = (isset($properties['style']) ? $properties['style'] : Label_Text::STYLE_TextCardSmall);
		$textSize  = (isset($properties['textSize']) ? $properties['textSize'] : 1.5);
		$textColor = (isset($properties['textColor']) ? $properties['textColor'] : 'FFF');
		$profile   = (isset($properties['profile']) ? $properties['profile'] : false);

		$labels = array();
		foreach ($labelStrings as $text => $x) {
			$label = new Label_Text();
			$frame->add($label);
			$label->setHAlign($hAlign);
			$label->setX($x);
			$label->setStyle($style);
			$label->setTextSize($textSize);
			$label->setText($text);
			$label->setTextColor($textColor);

			if ($profile) {
				$label->addPlayerProfileFeature($profile);
			}

			$labels[] = $label; // add Label to the labels array
		}

		return $labels;
	}
}
