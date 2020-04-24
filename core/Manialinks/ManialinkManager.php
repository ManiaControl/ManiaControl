<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\ManiaLink;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;
use Maniaplanet\DedicatedServer\Xmlrpc\MessageException;
use Maniaplanet\DedicatedServer\Xmlrpc\UnknownPlayerException;

/**
 * Manialink Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManialinkManager implements ManialinkPageAnswerListener, CallbackListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Constants
	 */
	const MAIN_MLID              = 'Main.ManiaLinkId';
	const ACTION_CLOSEWIDGET     = 'ManiaLinkManager.CloseWidget';
	const CB_MAIN_WINDOW_CLOSED  = 'ManialinkManagerCallback.MainWindowClosed';
	const CB_MAIN_WINDOW_OPENED  = 'ManialinkManagerCallback.MainWindowOpened';
	const MAIN_MANIALINK_Z_VALUE = 150;


	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/** @var StyleManager $styleManager */
	private $styleManager = null;

	/** @var CustomUIManager $customUIManager */
	private $customUIManager = null;

	/** @var IconManager $iconManager */
	private $iconManager = null;

	/** @var SidebarMenuManager $sidebarMenuManager */
	private $sidebarMenuManager = null;

	/** @var ElementBuilder $elementBuilder */
	private $elementBuilder = null;

	// TODO: use listening class
	private $pageAnswerListeners     = array();
	private $pageAnswerRegexListener = array();

	/**
	 * Construct a new manialink manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Children
		$this->styleManager       = new StyleManager($maniaControl);
		$this->customUIManager    = new CustomUIManager($maniaControl);
		$this->iconManager        = new IconManager($maniaControl);
		$this->sidebarMenuManager = new SidebarMenuManager($maniaControl);
		$this->elementBuilder     = new ElementBuilder($maniaControl);

		// Callbacks
		$this->registerManialinkPageAnswerListener(self::ACTION_CLOSEWIDGET, $this, 'closeWidgetCallback');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		//Set ManiaLink version 3 as Default Version in FML
		ManiaLink::setDefaultVersion(ManiaLink::VERSION_3);
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
	 * Return the style manager
	 *
	 * @return StyleManager
	 */
	public function getStyleManager() {
		return $this->styleManager;
	}

	/**
	 * Return the custom UI manager
	 *
	 * @return CustomUIManager
	 */
	public function getCustomUIManager() {
		return $this->customUIManager;
	}

	/**
	 * Return the icon manager
	 *
	 * @return IconManager
	 */
	public function getIconManager() {
		return $this->iconManager;
	}

	/**
	 * Return the element builder
	 * 
	 * @return ElementBuilder
	 */
	public function getElementBuilder() {
		return $this->elementBuilder;
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

		// Register page answer regex listener
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
		$player   = $this->maniaControl->getPlayerManager()->getPlayer($login);

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
	 * Displays a ManiaLink Widget to a certain Player (Should only be used on Main Widgets)
	 *
	 * @param mixed  $maniaLink
	 * @param mixed  $player
	 * @param string $widgetName
	 */
	public function displayWidget($maniaLink, $player, $widgetName = null) {
		// render and display xml
		$this->sendManialink($maniaLink, $player);

		if ($widgetName) {
			$this->disableAltMenu($player);
			// Trigger callback
			$player = $this->maniaControl->getPlayerManager()->getPlayer($player);
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_MAIN_WINDOW_OPENED, $player, $widgetName);
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
		//Add Toggle Feature
		if($manialinkText instanceof ManiaLink){
			/*$toggleInterfaceF9 = new \FML\Script\Features\ToggleInterface("F9");
			$manialinkText->getScript()
			          ->addFeature($toggleInterfaceF9); (not working yet) */
		}

		$manialinkText = (string) $manialinkText;

		if (!$manialinkText) {
			return true;
		}

		try {
			if (!$logins) {
				return $this->maniaControl->getClient()->sendDisplayManialinkPage(null, $manialinkText, $timeout, $hideOnClick, true);
			}
			if (is_string($logins)) {
				return $this->maniaControl->getClient()->sendDisplayManialinkPage($logins, $manialinkText, $timeout, $hideOnClick, true);
			}
			if ($logins instanceof Player) {
				return $this->maniaControl->getClient()->sendDisplayManialinkPage($logins->login, $manialinkText, $timeout, $hideOnClick, true);
			}
			if (is_array($logins)) {
				$loginList = array();
				foreach ($logins as $login) {
					if ($login instanceof Player) {
						$loginList[] = $login->login;
					} else {
						$loginList[] = $login;
					}
				}
				return $this->maniaControl->getClient()->sendDisplayManialinkPage(implode(',', $loginList), $manialinkText, $timeout, $hideOnClick, true);
			}
		} catch (UnknownPlayerException $e) {
			return false;
		} catch (FaultException $e) {
			return false;
		} catch (MessageException $e) {
			//TODO verify why this can happen
			Logger::logError("Request too large during opening Directory Browser");
		}

		return true;
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
	 * @param mixed $player
	 * @param bool  $widgetId
	 */
	public function closeWidget($player, $widgetId = false) {
		if (!$widgetId) {
			$this->hideManialink(self::MAIN_MLID, $player);
			$this->enableAltMenu($player);

			// Trigger callback
			$player = $this->maniaControl->getPlayerManager()->getPlayer($player);
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_MAIN_WINDOW_CLOSED, $player);
		} else {
			$this->hideManialink($widgetId, $player);
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
	 * Enable the alt menu for the player
	 *
	 * @api
	 * @param mixed $player
	 */
	public function enableAltMenu($player) {
		$this->maniaControl->getModeScriptEventManager()->displayScoreBoardOnAlt($player);
	}

	/**
	 * Disable the alt menu for the player
	 *
	 * @api
	 * @param mixed $player
	 */
	public function disableAltMenu($player) {
		$this->maniaControl->getModeScriptEventManager()->hideScoreBoardOnAlt($player);
	}

	/**
	 * Adds a line of labels
	 * LabelLine should be an array with the following structure: array(array(positions), array(texts))
	 * or array($text1 => $pos1, $text2 => $pos2 ...)
	 *
	 * @param Frame $frame
	 * @param array $labelStrings
	 * @param array $properties
	 * @return Label_Text[]
	 * @deprecated use \ManiaControl\Manialinks\LabelLine instead
	 * @see        \ManiaControl\Manialinks\LabelLine
	 */
	public function labelLine(Frame $frame, array $labelStrings, array $properties = array()) {
		// define standard properties
		$hAlign    = (isset($properties['hAlign']) ? $properties['hAlign'] : Control::LEFT);
		$style     = (isset($properties['style']) ? $properties['style'] : Label_Text::STYLE_TextCardSmall);
		$textSize  = (isset($properties['textSize']) ? $properties['textSize'] : 1.5);
		$textColor = (isset($properties['textColor']) ? $properties['textColor'] : 'FFF');
		$posZ      = (isset($properties['posZ']) ? $properties['posZ'] : 0);

		$labelLine = new LabelLine($frame);
		$labelLine->setHorizontalAlign($hAlign);
		$labelLine->setStyle($style);
		$labelLine->setTextSize($textSize);
		$labelLine->setTextColor($textColor);
		$labelLine->setZ($posZ);

		/**
		 * @var Label_Text $prevLabel
		 */
		$prevLabel = null;

		//If you call LabelLine with array(array(positions), array(texts))
		if (count($labelStrings) == 2 && array_key_exists(0, $labelStrings) && array_key_exists(1, $labelStrings) && array_key_exists(0, $labelStrings[0]) && array_key_exists(0, $labelStrings[1])) {
			$positions = $labelStrings[0];
			$texts     = $labelStrings[1];

			if (count($positions) != count($texts)) {
				trigger_error("LabelLine Position length is not equal to Text Length", E_USER_ERROR);
			}

			foreach ($positions as $key => $x) {
				$labelLine->addLabelEntryText($texts[$key], $x);
			}
		} else {
			foreach ($labelStrings as $text => $x) {
				$labelLine->addLabelEntryText($text, $x);
			}
		}
		$labelLine->render();

		return $labelLine->getEntries();
	}

	/**
	 * @return SidebarMenuManager
	 */
	public function getSidebarMenuManager() {
		return $this->sidebarMenuManager;
	}
}
