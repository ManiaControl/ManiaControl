<?php

namespace ManiaControl\Configurators;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;

/**
 * Class managing ingame ManiaControl Configuration
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Configurator implements CallbackListener, CommandListener, ManialinkPageAnswerListener {
	/*
	 * Constants
	 */
	const ACTION_TOGGLEMENU                    = 'Configurator.ToggleMenuAction';
	const ACTION_SAVECONFIG                    = 'Configurator.SaveConfigAction';
	const ACTION_SELECTMENU                    = 'Configurator.SelectMenu';
	const SETTING_MENU_POSX                    = 'Menu Widget Position: X';
	const SETTING_MENU_POSY                    = 'Menu Widget Position: Y';
	const SETTING_MENU_WIDTH                   = 'Menu Widget Width';
	const SETTING_MENU_HEIGHT                  = 'Menu Widget Height';
	const SETTING_MENU_STYLE                   = 'Menu Widget BackgroundQuad Style';
	const SETTING_MENU_SUBSTYLE                = 'Menu Widget BackgroundQuad Substyle';
	const SETTING_PERMISSION_OPEN_CONFIGURATOR = 'Open Configurator';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;
	private $scriptSettings = null;
	private $serverSettings = null;
	private $maniaControlSettings = null;
	private $menus = array();
	private $playersMenuShown = array();

	/**
	 * Create a new Configurator
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->addActionsMenuItem();

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_POSX, 0.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_POSY, 3.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_WIDTH, 170.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_HEIGHT, 81.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_STYLE, Quad_BgRaceScore2::STYLE);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MENU_SUBSTYLE, Quad_BgRaceScore2::SUBSTYLE_HandleSelectable);

		//Permission for opening
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_OPEN_CONFIGURATOR, AuthenticationManager::AUTH_LEVEL_ADMIN);

		// Register for page answers
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_TOGGLEMENU, $this, 'handleToggleMenuAction');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SAVECONFIG, $this, 'handleSaveConfigAction');

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');


		// Create server settings
		$this->serverSettings = new ServerSettings($maniaControl);
		$this->addMenu($this->serverSettings);

		// Create script settings
		$this->scriptSettings = new ScriptSettings($maniaControl);
		$this->addMenu($this->scriptSettings);

		// Create Mania Control Settings
		$this->maniaControlSettings = new ManiaControlSettings($maniaControl);
		$this->addMenu($this->maniaControlSettings);

		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('config', $this, 'handleConfigCommand', true, 'Loads Config panel.');
	}

	/**
	 * Add Menu Item to the Actions Menu
	 */
	private function addActionsMenuItem() {
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Tools);
		$itemQuad->setAction(self::ACTION_TOGGLEMENU);
		$this->maniaControl->actionsMenu->addAdminMenuItem($itemQuad, 100, 'Settings');
	}

	/**
	 * Add a configurator menu
	 *
	 * @param ConfiguratorMenu $menu
	 */
	public function addMenu(ConfiguratorMenu $menu) {
		array_push($this->menus, $menu);
	}

	/**
	 * Handle Config Admin Command
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function handleConfigCommand(array $callback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_OPEN_CONFIGURATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$this->showMenu($player);
	}

	/**
	 * Show the Menu to the Player
	 *
	 * @param Player $player
	 * @param int    $menuId
	 */
	public function showMenu(Player $player, $menuId = 0) {
		$manialink = $this->buildManialink($menuId, $player);
		$this->maniaControl->manialinkManager->displayWidget($manialink, $player, "Configurator");
		$this->playersMenuShown[$player->login] = true;
	}

	/**
	 * Build Menu ManiaLink if necessary
	 *
	 * @param int    $menuIdShown
	 * @param Player $player
	 * @return \FML\ManiaLink
	 */
	private function buildManialink($menuIdShown = 0, Player $player = null) {
		$menuPosX     = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MENU_POSX);
		$menuPosY     = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MENU_POSY);
		$menuWidth    = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MENU_WIDTH);
		$menuHeight   = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MENU_HEIGHT);
		$quadStyle    = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MENU_STYLE);
		$quadSubstyle = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MENU_SUBSTYLE);

		$menuListWidth  = $menuWidth * 0.3;
		$menuItemHeight = 10.;
		$subMenuWidth   = $menuWidth - $menuListWidth;
		$subMenuHeight  = $menuHeight;

		$manialink = new ManiaLink(ManialinkManager::MAIN_MLID);

		$frame = new Frame();
		$manialink->add($frame);
		$frame->setPosition($menuPosX, $menuPosY);
		$frame->setZ(10);

		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($menuWidth, $menuHeight);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$menuItemsFrame = new Frame();
		$frame->add($menuItemsFrame);
		$menuItemsFrame->setX($menuWidth * -0.5 + $menuListWidth * 0.5);

		$itemsBackgroundQuad = new Quad();
		$menuItemsFrame->add($itemsBackgroundQuad);
		$itemsBackgroundQuad->setSize($menuListWidth, $menuHeight);
		$itemsBackgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$menusFrame = new Frame();
		$frame->add($menusFrame);
		$menusFrame->setX($menuWidth * -0.5 + $menuListWidth + $subMenuWidth * 0.5);

		// Create script and features
		$script = $manialink->getScript();

		$menuItemY = $menuHeight * 0.42;
		$menuId    = 0;
		foreach ($this->menus as $menu) {
			/** @var ConfiguratorMenu $menu */

			// Add title
			$menuItemLabel = new Label();
			$menuItemsFrame->add($menuItemLabel);
			$menuItemLabel->setY($menuItemY);
			$menuItemLabel->setSize($menuListWidth * 0.9, $menuItemHeight * 0.9);
			$menuItemLabel->setStyle(Label_Text::STYLE_TextCardRaceRank);
			$menuItemLabel->setText('$z' . $menu->getTitle() . '$z');
			$menuItemLabel->setAction(self::ACTION_SELECTMENU . '.' . $menuId);

			//Show a Menu
			if ($menuId == $menuIdShown) {
				$menuControl = $menu->getMenu($subMenuWidth, $subMenuHeight, $script, $player);
				$menusFrame->add($menuControl);
			}

			$menuItemY -= $menuItemHeight * 1.1;
			$menuId++;
		}

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($menuWidth * 0.483, $menuHeight * 0.467, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		// Add close button
		$closeButton = new Label();
		$frame->add($closeButton);
		$closeButton->setPosition($menuWidth * -0.5 + $menuListWidth * 0.29, $menuHeight * -0.43);
		$closeButton->setSize($menuListWidth * 0.3, $menuListWidth * 0.1);
		$closeButton->setStyle(Label_Text::STYLE_TextButtonNavBack);
		$closeButton->setTextPrefix('$999');
		$closeButton->setTranslate(true);
		$closeButton->setText('$zClose$z');
		$closeButton->setAction(self::ACTION_TOGGLEMENU);

		// Add save button
		$saveButton = new Label();
		$frame->add($saveButton);
		$saveButton->setPosition($menuWidth * -0.5 + $menuListWidth * 0.71, $menuHeight * -0.43);
		$saveButton->setSize($menuListWidth * 0.3, $menuListWidth * 0.1);
		$saveButton->setStyle(Label_Text::STYLE_TextButtonNavBack);
		$saveButton->setTextPrefix('$0f5');
		$saveButton->setTranslate(true);
		$saveButton->setText('$zSave$z');
		$saveButton->setAction(self::ACTION_SAVECONFIG);

		return $manialink;
	}

	/**
	 * Reopen the Menu
	 *
	 * @param Player $player
	 * @param int    $menuId
	 */
	public function reopenMenu(Player $player, $menuId = 0) {
		$this->showMenu($player, $menuId);
	}

	/**
	 * Handle toggle menu action
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function handleToggleMenuAction(array $callback, Player $player) {
		$this->toggleMenu($player);
	}

	/**
	 * Toggle the Menu for the Player
	 *
	 * @param Player $player
	 */
	public function toggleMenu(Player $player) {
		if (isset($this->playersMenuShown[$player->login])) {
			$this->hideMenu($player);
		} else {
			$this->showMenu($player);
		}
	}

	/**
	 * Hide the Menu for the Player
	 *
	 * @param Player $player
	 */
	public function hideMenu(Player $player) {
		unset($this->playersMenuShown[$player->login]);
		$this->maniaControl->manialinkManager->closeWidget($player);
	}

	/**
	 * Save the config data received from the manialink
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function handleSaveConfigAction(array $callback, Player $player) {
		foreach ($this->menus as $menu) {
			/** @var ConfiguratorMenu $menu */
			$menu->saveConfigData($callback[1], $player);
		}
	}

	/**
	 * Handle PlayerDisconnect callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerDisconnect(array $callback) {
		$login = $callback[1][0];
		unset($this->playersMenuShown[$login]);
	}

	/**
	 * Unset the player if he opened another Main Widget
	 *
	 * @param Player $player
	 * @param        $openedWidget
	 */
	public function handleWidgetOpened(Player $player, $openedWidget) {
		//unset when another main widget got opened
		if ($openedWidget != 'Configurator') {
			unset($this->playersMenuShown[$player->login]);
		}
	}

	/**
	 * Widget get closed -> unset player
	 *
	 * @param \ManiaControl\Players\Player $player
	 */
	public function closeWidget(Player $player) {
		unset($this->playersMenuShown[$player->login]);
	}

	/**
	 * Gets the Menu Id
	 *
	 * @param string $title
	 * @return int
	 */
	public function getMenuId($title) {
		$i = 0;
		foreach ($this->menus as $menu) {
			/** @var  ConfiguratorMenu $menu */
			if ($menu === $title || $menu->getTitle() === $title) {
				return $i;
			}
			$i++;
		}
		return 0;
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId       = $callback[1][2];
		$boolSelectMenu = (strpos($actionId, self::ACTION_SELECTMENU) === 0);
		if (!$boolSelectMenu) {
			return;
		}

		$login       = $callback[1][1];
		$actionArray = explode(".", $callback[1][2]);

		$player = $this->maniaControl->playerManager->getPlayer($login);
		$this->showMenu($player, intval($actionArray[2]));
	}
}
