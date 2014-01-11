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
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;

require_once __DIR__ . '/ConfiguratorMenu.php';
require_once __DIR__ . '/ScriptSettings.php';
require_once __DIR__ . '/ServerSettings.php';
require_once __DIR__ . '/ManiaControlSettings.php';

/**
 * Class managing ingame ManiaControl configuration
 *
 * @author steeffeen & kremsy
 */
class Configurator implements CallbackListener, CommandListener, ManialinkPageAnswerListener {
	/**
	 * Constants
	 */
	const ACTION_TOGGLEMENU     = 'Configurator.ToggleMenuAction';
	const ACTION_SAVECONFIG     = 'Configurator.SaveConfigAction';
	const ACTION_SELECTMENU     = 'Configurator.SelectMenu';
	const SETTING_MENU_POSX     = 'Menu Widget Position: X';
	const SETTING_MENU_POSY     = 'Menu Widget Position: Y';
	const SETTING_MENU_WIDTH    = 'Menu Widget Width';
	const SETTING_MENU_HEIGHT   = 'Menu Widget Height';
	const SETTING_MENU_STYLE    = 'Menu Widget BackgroundQuad Style';
	const SETTING_MENU_SUBSTYLE = 'Menu Widget BackgroundQuad Substyle';

	/**
	 * Private properties
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
		$this->maniaControl->commandManager->registerCommandListener('config', $this, 'handleConfigCommand', true);
	}

	/**
	 * Handle Config Admin Aommand
	 *
	 * @param array $callback
	 */
	public function handleConfigCommand(array $callback, Player $player) {
		$this->showMenu($player);
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
	 * Reopens the Menu
	 *
	 * @param array $callback
	 */
	public function reopenMenu($menuId = 0) {
		foreach($this->playersMenuShown as $login => $shown) {
			if($shown == true) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				$this->showMenu($player, $menuId);
			}
		}
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
	 * Save the config data received from the manialink
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function handleSaveConfigAction(array $callback, Player $player) {
		foreach($this->menus as $menu) {
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
	 * Show the Menu to the Player
	 *
	 * @param Player $player
	 * @param int    $menuId
	 */
	public function showMenu(Player $player, $menuId = 0) {
		$manialink = $this->buildManialink($menuId);
		$this->maniaControl->manialinkManager->displayWidget($manialink, $player, "Configurator");
		$this->playersMenuShown[$player->login] = true;
	}

	/**
	 * Unset the player if he opened another Main Widget
	 *
	 * @param array $callback
	 */
	public function handleWidgetOpened(array $callback) {
		$player       = $callback[1];
		$openedWidget = $callback[2];
		//unset when another main widget got opened
		if($openedWidget != 'Configurator') {
			unset($this->playersMenuShown[$player->login]);
		}
	}

	/**
	 * Widget get closed -> unset player
	 *
	 * @param array $callback
	 */
	public function closeWidget(array $callback) {
		$player = $callback[1];
		unset($this->playersMenuShown[$player->login]);
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
	 * Toggle the Menu for the Player
	 *
	 * @param Player $player
	 */
	public function toggleMenu(Player $player) {
		if(isset($this->playersMenuShown[$player->login])) {
			$this->hideMenu($player);
		} else {
			$this->showMenu($player);
		}
	}

	/**
	 * Gets the Menu Id
	 *
	 * @param $name
	 * @return int
	 */
	public function getMenuId($name) {
		$i = 0;
		foreach($this->menus as $menu) {
			/** @var  ConfiguratorMenu $menu */
			if($menu->getTitle() == $name) {
				return $i;
			}
			$i++;
		}
		return 0;
	}

	/**
	 * Build menu manialink if necessary
	 *
	 * @param int $menuIdShown
	 * @internal param bool $forceBuild
	 * @return \FML\ManiaLink
	 */
	private function buildManialink($menuIdShown = 0) {
		$menuPosX     = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSX);
		$menuPosY     = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_POSY);
		$menuWidth    = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_WIDTH);
		$menuHeight   = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_HEIGHT);
		$quadStyle    = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_STYLE);
		$quadSubstyle = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MENU_SUBSTYLE);

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
		$script = new Script();
		$manialink->setScript($script);

		$menuItemY = $menuHeight * 0.42;
		$menuId    = 0;
		foreach($this->menus as $index => $menu) {
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
			if($menuId == $menuIdShown) {
				$menuControl = $menu->getMenu($subMenuWidth, $subMenuHeight, $script);
				$menusFrame->add($menuControl);
				$script->addMenu($menuItemLabel, $menuControl);
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
		$closeQuad->setAction(self::ACTION_TOGGLEMENU);

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
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId       = $callback[1][2];
		$boolSelectMenu = (strpos($actionId, self::ACTION_SELECTMENU) === 0);
		if(!$boolSelectMenu) {
			return;
		}

		$login       = $callback[1][1];
		$actionArray = explode(".", $callback[1][2]);

		$player = $this->maniaControl->playerManager->getPlayer($login);
		$this->showMenu($player, intval($actionArray[2]));
	}

	/**
	 * Add Menu Item to the Actions Menu
	 */
	private function addActionsMenuItem() {
		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Tools);
		$itemQuad->setAction(self::ACTION_TOGGLEMENU);
		$this->maniaControl->actionsMenu->addAdminMenuItem($itemQuad, 20, 'Settings');
	}
}
