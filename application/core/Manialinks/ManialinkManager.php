<?php

namespace ManiaControl\Manialinks;

use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use FML\ManiaLink;
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;

require_once __DIR__ . '/StyleManager.php';
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
	const MAIN_MLID = 'Main.ManiaLinkId';
	const ACTION_CLOSEWIDGET = 'ManiaLinkManager.CloseWidget';
	const CB_MAIN_WINDOW_CLOSED = 'ManialinkManagerCallback.MainWindowClosed';
	
	/**
	 * Public properties
	 */
	public $styleManager = null;
	public $customUIManager = null;
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $pageAnswerListeners = array();
	private $maniaLinkIdCount = 0;

	/**
	 * Create a new manialink manager
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->styleManager = new StyleManager($maniaControl);
		$this->customUIManager = new CustomUIManager($maniaControl);
		
		// Register for callbacks
		$this->registerManialinkPageAnswerListener(self::ACTION_CLOSEWIDGET, $this, 'closeWidgetCallback');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 
				'handleManialinkPageAnswer');
	}

	/**
	 * Register a new manialink page answer listener
	 *
	 * @param string $actionId        	
	 * @param ManialinkPageAnswerListener $listener        	
	 * @param string $method        	
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
	 * Remove a Manialink Page Answer Listener
	 *
	 * @param ManialinkPageAnswerListener $listener        	
	 * @return bool
	 */
	public function unregisterManialinkPageAnswerListener(ManialinkPageAnswerListener $listener) {
		$keys = array_keys($this->pageAnswerListeners, $listener);
		foreach ($keys as $key) {
			unset($this->pageAnswerListeners[$key]);
		}
		return true;
	}

	/**
	 * Reserve manialink ids
	 *
	 * @param int $count        	
	 * @return array
	 */
	public function reserveManiaLinkIds($count) {
		$manialinkIds = array();
		for ($i = 0; $i < $count; $i++) {
			array_push($manialinkIds, $this->maniaLinkIdCount++);
		}
		return $manialinkIds;
	}

	/**
	 * Handle ManialinkPageAnswer callback
	 *
	 * @param array $callback        	
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		$login = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!array_key_exists($actionId, $this->pageAnswerListeners) || !is_array($this->pageAnswerListeners[$actionId])) {
			// No page answer listener registered
			return;
		}
		// Inform page answer listeners
		foreach ($this->pageAnswerListeners[$actionId] as $listener) {
			call_user_func(array($listener[0], $listener[1]), $callback, $player);
		}
	}

	/**
	 * Send the given manialink to players
	 *
	 * @param string $manialinkText        	
	 * @param mixed $logins        	
	 * @param int $timeout        	
	 * @param bool $hideOnClick        	
	 * @return bool
	 */
	public function sendManialink($manialinkText, $logins = null, $timeout = 0, $hideOnClick = false) {
		if (!$logins) {
			return $this->maniaControl->client->query('SendDisplayManialinkPage', $manialinkText, $timeout, $hideOnClick);
		}
		if (is_string($logins)) {
			return $this->maniaControl->client->query('SendDisplayManialinkPageToLogin', $logins, $manialinkText, $timeout, 
					$hideOnClick);
		}
		if (is_array($logins)) {
			$success = true;
			foreach ($logins as $login) {
				$subSuccess = $this->maniaControl->client->query('SendDisplayManialinkPageToLogin', $login, $manialinkText, $timeout, 
						$hideOnClick);
				if (!$subSuccess) {
					$success = false;
				}
			}
			return $success;
		}
		return false;
	}

	/**
	 * Enable the alt menu for the player
	 *
	 * @param Player $player        	
	 * @return bool
	 */
	public function enableAltMenu(Player $player) {
		return $this->maniaControl->client->query('TriggerModeScriptEvent', 'LibXmlRpc_EnableAltMenu', $player->login);
	}

	/**
	 * Disable the alt menu for the player
	 *
	 * @param Player $player        	
	 * @return bool
	 */
	public function disableAltMenu(Player $player) {
		return $this->maniaControl->client->query('TriggerModeScriptEvent', 'LibXmlRpc_DisableAltMenu', $player->login);
	}

	/**
	 * Displays a ManiaLink Widget to a certain Player
	 *
	 * @param String $maniaLink        	
	 * @param Player $player        	
	 */
	public function displayWidget($maniaLink, Player $player) {
		// render and display xml
		$maniaLinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($maniaLinkText, $player->login);
		$this->disableAltMenu($player);
	}

	/**
	 * Closes a widget via the callback
	 *
	 * @param array $callback        	
	 * @param Player $player        	
	 */
	public function closeWidgetCallback(array $callback, Player $player) {
		$this->closeWidget($player);
	}

	/**
	 * Closes the Manialink Widget and enables the Alt Menu
	 *
	 * @param Player $player        	
	 */
	public function closeWidget(Player $player) {
		$emptyManialink = new ManiaLink(self::MAIN_MLID);
		$manialinkText = $emptyManialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
		$this->enableAltMenu($player);
		
		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_MAIN_WINDOW_CLOSED, array(self::CB_MAIN_WINDOW_CLOSED, $player));
	}

	/**
	 * Adds a line of labels
	 *
	 * @param Frame $frame        	
	 * @param array $labelStrings        	
	 * @param array $properties        	
	 * @return array Returns the frames (to add special Properties later)
	 */
	public function labelLine(Frame $frame, array $labelStrings, array $properties = array()) {
		// TODO overwrite standard properties with properties from array
		
		// define standard properties
		$hAlign = Control::LEFT;
		$style = Label_Text::STYLE_TextCardSmall;
		$textSize = 1.5;
		$textColor = 'FFF';
		
		$frames = array();
		foreach ($labelStrings as $text => $x) {
			$label = new Label_Text();
			$frame->add($label);
			$label->setHAlign($hAlign);
			$label->setX($x);
			$label->setStyle($style);
			$label->setTextSize($textSize);
			$label->setText($text);
			$label->setTextColor($textColor);
			
			$frames[] = $frame; // add Frame to the frames array
		}
		return $frames;
	}
}
