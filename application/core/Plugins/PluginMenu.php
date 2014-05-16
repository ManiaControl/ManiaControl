<?php

namespace ManiaControl\Plugins;

use FML\Controls\Control;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_Icons128x32_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Features\Paging;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Configurators\ConfiguratorMenu;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Settings\Setting;

/**
 * Configurator for enabling and disabling Plugins
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PluginMenu implements CallbackListener, ConfiguratorMenu, ManialinkPageAnswerListener {
	/*
	 * Constants
	 */
	const ACTION_PREFIX_ENABLEPLUGIN                = 'PluginMenu.Enable.';
	const ACTION_PREFIX_DISABLEPLUGIN               = 'PluginMenu.Disable.';
	const ACTION_PREFIX_SETTINGS                    = 'PluginMenu.Settings.';
	const ACTION_PREFIX_SETTING                     = 'PluginMenuSetting';
	const ACTION_SETTING_BOOL                       = 'PluginMenuActionBoolSetting.';
	const ACTION_BACK_TO_PLUGINS                    = 'PluginMenu.BackToPlugins';
	const ACTION_PREFIX_UPDATEPLUGIN                = 'PluginMenu.Update.';
	const ACTION_UPDATEPLUGINS                      = 'PluginMenu.Update.All';
	const SETTING_PERMISSION_CHANGE_PLUGIN_SETTINGS = 'Change Plugin Settings';
	const CACHE_SETTING_CLASS                       = 'PluginMenuCache.SettingClass';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new plugin menu instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_BACK_TO_PLUGINS, $this, 'backToPlugins');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_PLUGIN_SETTINGS, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
	}

	/**
	 * Returns Back to the Plugins
	 */
	public function backToPlugins($callback, Player $player) {
		$player->destroyCache($this, self::CACHE_SETTING_CLASS);
		$menuId = $this->maniaControl->configurator->getMenuId($this->getTitle());
		$this->maniaControl->configurator->reopenMenu($player, $menuId);
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getTitle()
	 */
	public function getTitle() {
		return 'Plugins';
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script, Player $player) {
		$paging = new Paging();
		$script->addFeature($paging);
		$frame = new Frame();

		$pluginClasses = $this->maniaControl->pluginManager->getPluginClasses();

		// Config
		$pagerSize    = 9.;
		$entryHeight  = 5.;
		$pageMaxCount = 10;

		// Pagers
		$pagerPrev = new Quad_Icons64x64_1();
		$frame->add($pagerPrev);
		$pagerPrev->setPosition($width * 0.39, $height * -0.44, 2);
		$pagerPrev->setSize($pagerSize, $pagerSize);
		$pagerPrev->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_ArrowPrev);

		$pagerNext = new Quad_Icons64x64_1();
		$frame->add($pagerNext);
		$pagerNext->setPosition($width * 0.45, $height * -0.44, 2);
		$pagerNext->setSize($pagerSize, $pagerSize);
		$pagerNext->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_ArrowNext);

		$paging->addButton($pagerNext);
		$paging->addButton($pagerPrev);

		$pageCountLabel = new Label_Text();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign(Control::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44, 1);
		$pageCountLabel->setStyle($pageCountLabel::STYLE_TextTitle1);
		$pageCountLabel->setTextSize(2);

		$paging->setLabel($pageCountLabel);

		$settingClass = $player->getCache($this, self::CACHE_SETTING_CLASS);
		if ($settingClass) {
			// Show Settings Menu
			return $this->getPluginSettingsMenu($frame, $width, $height, $paging, $player, $settingClass);
		}

		// Display normal Plugin List
		// Plugin pages
		$y             = 0.;
		$pluginUpdates = $this->maniaControl->updateManager->pluginUpdateManager->getPluginsUpdates();

		usort($pluginClasses, function ($a, $b) {
			/** @var Plugin $a */
			/** @var Plugin $b */
			return strcmp($a::getName(), $b::getName());
		});

		$pageFrame = null;
		foreach ($pluginClasses as $index => $pluginClass) {
			/** @var Plugin $pluginClass */
			if ($index % $pageMaxCount === 0) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$paging->addPage($pageFrame);
				$y = $height * 0.41;
			}

			$active = $this->maniaControl->pluginManager->isPluginActive($pluginClass);

			$pluginFrame = new Frame();
			$pageFrame->add($pluginFrame);
			$pluginFrame->setY($y);

			$activeQuad = new Quad_Icons64x64_1();
			$pluginFrame->add($activeQuad);
			$activeQuad->setPosition($width * -0.45, -0.1, 1);
			$activeQuad->setSize($entryHeight * 0.9, $entryHeight * 0.9);
			if ($active) {
				$activeQuad->setSubStyle($activeQuad::SUBSTYLE_LvlGreen);
			} else {
				$activeQuad->setSubStyle($activeQuad::SUBSTYLE_LvlRed);
			}

			$nameLabel = new Label_Text();
			$pluginFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.5, $entryHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize(2);
			$nameLabel->setText($pluginClass::getName());

			$descriptionLabel = new Label();
			$pageFrame->add($descriptionLabel);
			$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
			$descriptionLabel->setPosition($width * -0.45, $height * -0.22);
			$descriptionLabel->setSize($width * 0.7, $entryHeight);
			$descriptionLabel->setTextSize(2);
			$descriptionLabel->setTranslate(true);
			$descriptionLabel->setVisible(false);
			$descriptionLabel->setAutoNewLine(true);
			$descriptionLabel->setMaxLines(5);
			$description = "Author: {$pluginClass::getAuthor()}\nVersion: {$pluginClass::getVersion()}\nDesc: {$pluginClass::getDescription()}";
			$descriptionLabel->setText($description);
			$nameLabel->addTooltipFeature($descriptionLabel);

			$quad = new Quad_Icons128x32_1();
			$pluginFrame->add($quad);
			$quad->setSubStyle($quad::SUBSTYLE_Settings);
			$quad->setX(15);
			$quad->setZ(1);
			$quad->setSize(5, 5);
			$quad->setAction(self::ACTION_PREFIX_SETTINGS . $pluginClass);

			$statusChangeButton = new Label_Button();
			$pluginFrame->add($statusChangeButton);
			$statusChangeButton->setHAlign(Control::RIGHT);
			$statusChangeButton->setX($width * 0.45);
			$statusChangeButton->setStyle($statusChangeButton::STYLE_CardButtonSmall);
			if ($active) {
				$statusChangeButton->setTextPrefix('$f00');
				$statusChangeButton->setText('Deactivate');
				$statusChangeButton->setAction(self::ACTION_PREFIX_DISABLEPLUGIN . $pluginClass);
			} else {
				$statusChangeButton->setTextPrefix('a');
				$statusChangeButton->setText('Activate');
				$statusChangeButton->setAction(self::ACTION_PREFIX_ENABLEPLUGIN . $pluginClass);
			}

			if ($pluginUpdates != false && array_key_exists($pluginClass::getId(), $pluginUpdates)) {
				$quadUpdate = new Quad_Icons128x128_1();
				$pluginFrame->add($quadUpdate);
				$quadUpdate->setSubStyle($quadUpdate::SUBSTYLE_ProfileVehicle);
				$quadUpdate->setX(56);
				$quadUpdate->setZ(2);
				$quadUpdate->setSize(5, 5);
				$quadUpdate->setAction(self::ACTION_PREFIX_UPDATEPLUGIN . $pluginClass);
			}

			$y -= $entryHeight;
		}

		if ($pluginUpdates != false) {
			$updatePluginsButton = new Label_Button();
			$frame->add($updatePluginsButton);
			$updatePluginsButton->setHAlign(Control::RIGHT);
			$updatePluginsButton->setX($width * 0.5);
			$updatePluginsButton->setY(-29);
			$updatePluginsButton->setZ(2);
			$updatePluginsButton->setWidth(10);
			$updatePluginsButton->setStyle($updatePluginsButton::STYLE_CardButtonSmallS);
			$updatePluginsButton->setText(count($pluginUpdates) . ' update(s)');
			$updatePluginsButton->setAction(self::ACTION_UPDATEPLUGINS);
		}

		return $frame;
	}

	/**
	 * Get the Frame with the Plugin Settings
	 *
	 * @param Frame  $frame
	 * @param float  $width
	 * @param float  $height
	 * @param Paging $paging
	 * @param Player $player
	 * @param string $settingClass
	 * @return Frame
	 */
	private function getPluginSettingsMenu(Frame $frame, $width, $height, Paging $paging, Player $player, $settingClass) {
		// TODO: use maniacontrolsettings menu
		$settings = $this->maniaControl->settingManager->getSettingsByClass($settingClass);

		$pageSettingsMaxCount = 12;
		$y                    = 0;
		$index                = 0;
		$settingHeight        = 5.;
		$pageFrame            = null;

		foreach ($settings as $setting) {
			if ($index % $pageSettingsMaxCount === 0) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$paging->addPage($pageFrame);
				$y = $height * 0.41;
			}

			$settingFrame = new Frame();
			$pageFrame->add($settingFrame);
			$settingFrame->setY($y);

			if ($index === 0) {
				//Headline Label
				$headLabel = new Label_Text();
				$settingFrame->add($headLabel);
				$headLabel->setHAlign(Control::LEFT);
				$headLabel->setX($width * -0.46);
				$headLabel->setSize($width * 0.6, $settingHeight);
				$headLabel->setStyle($headLabel::STYLE_TextCardSmall);
				$headLabel->setTextSize(2);
				$headLabel->setText($setting->class);
				$headLabel->setTextColor("F00");

				$y -= $settingHeight;
			}

			$settingFrame = new Frame();
			$pageFrame->add($settingFrame);
			$settingFrame->setY($y);
			// Headline Label finished

			$nameLabel = new Label_Text();
			$settingFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.46);
			$nameLabel->setSize($width * 0.6, $settingHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize(2);
			$nameLabel->setText($setting->setting);
			$nameLabel->setTextColor("FFF");

			$entry = new Entry();
			$settingFrame->add($entry);
			$entry->setStyle(Label_Text::STYLE_TextValueSmall);
			$entry->setHAlign(Control::CENTER);
			$entry->setX($width / 2 * 0.65);
			$entry->setTextSize(1);
			$entry->setSize($width * 0.3, $settingHeight * 0.9);
			$entry->setName(self::ACTION_PREFIX_SETTING . '.' . $setting->index);
			$entry->setDefault($setting->value);


			if ($setting->type === Setting::TYPE_BOOL) {
				if ($setting->value) {
					$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlGreen;
				} else {
					$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlRed;
				}

				$quad = new Quad_Icons64x64_1();
				$settingFrame->add($quad);
				$quad->setX($width / 2 * 0.6);
				$quad->setZ(-0.01);
				$quad->setSubStyle($substyle);
				$quad->setSize(4, 4);
				$quad->setHAlign(Control::CENTER2);
				$quad->setAction(self::ACTION_SETTING_BOOL . $setting->index);
				$entry->setVisible(false);
			}

			$y -= $settingHeight;

			$index++;
		}

		$quad = new Label_Button();
		$frame->add($quad);
		$quad->setStyle($quad::STYLE_CardMain_Quit);
		$quad->setHAlign(Control::LEFT);
		$quad->setScale(0.75);
		$quad->setText("Back");
		$quad->setPosition(-$width / 2 + 7, -$height / 2 + 7);
		$quad->setAction(self::ACTION_BACK_TO_PLUGINS);

		return $frame;
	}

	/**
	 * Handle PlayerManialinkPageAnswer callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player) {
			return;
		}

		$actionId    = $callback[1][2];
		$enable      = (strpos($actionId, self::ACTION_PREFIX_ENABLEPLUGIN) === 0);
		$disable     = (strpos($actionId, self::ACTION_PREFIX_DISABLEPLUGIN) === 0);
		$settings    = (strpos($actionId, self::ACTION_PREFIX_SETTINGS) === 0);
		$boolSetting = (strpos($actionId, self::ACTION_SETTING_BOOL) === 0);
		if (!$enable && !$disable && !$settings && !$boolSetting) {
			return;
		}

		if ($enable) {
			$pluginClass = substr($actionId, strlen(self::ACTION_PREFIX_ENABLEPLUGIN));
			/** @var Plugin $pluginClass */
			$activated = $this->maniaControl->pluginManager->activatePlugin($pluginClass, $player->login);
			if ($activated) {
				$this->maniaControl->chat->sendSuccess($pluginClass::getName() . ' activated!', $player);
				$this->maniaControl->log("{$player->login} activated '{$pluginClass}'!", true);
			} else {
				$this->maniaControl->chat->sendError('Error activating ' . $pluginClass::getName() . '!', $player);
			}
		} else if ($disable) {
			$pluginClass = substr($actionId, strlen(self::ACTION_PREFIX_DISABLEPLUGIN));
			/** @var Plugin $pluginClass */
			$deactivated = $this->maniaControl->pluginManager->deactivatePlugin($pluginClass);
			if ($deactivated) {
				$this->maniaControl->chat->sendSuccess($pluginClass::getName() . ' deactivated!', $player);
				$this->maniaControl->log("{$player->login} deactivated '{$pluginClass}'!", true);
			} else {
				$this->maniaControl->chat->sendError('Error deactivating ' . $pluginClass::getName() . '!', $player);
			}
		} else if ($settings) {
			// Open Settings Menu
			$pluginClass = substr($actionId, strlen(self::ACTION_PREFIX_SETTINGS));
			$player->setCache($this, self::CACHE_SETTING_CLASS, $pluginClass);
		} else if ($boolSetting) {
			$actionArray = explode(".", $actionId);
			$setting     = $actionArray[1];

			// Toggle the Boolean Setting
			$this->toggleBooleanSetting($setting, $player);

			// Save all Changes
			$this->saveConfigData($callback[1], $player);
		}

		// Reopen the Menu
		$menuId = $this->maniaControl->configurator->getMenuId($this->getTitle());
		$this->maniaControl->configurator->reopenMenu($player, $menuId);
	}

	/**
	 * Toggle a Boolean Value
	 *
	 * @param int    $settingIndex
	 * @param Player $player
	 */
	public function toggleBooleanSetting($settingIndex, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_PLUGIN_SETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$oldSetting = $this->maniaControl->settingManager->getSettingByIndex($settingIndex);

		if (!isset($oldSetting)) {
			var_dump('no setting with index: ' . $settingIndex);
			return;
		}

		// Toggle value
		$this->maniaControl->settingManager->setSetting($oldSetting->class, $oldSetting->setting, !$oldSetting->value);
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_PLUGIN_SETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		if (!$configData[3] || strpos($configData[3][0]['Name'], self::ACTION_PREFIX_SETTING) !== 0) {
			return;
		}

		$maniaControlSettings = $this->maniaControl->settingManager->getSettings();

		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		foreach ($configData[3] as $setting) {
			$settingName = substr($setting['Name'], $prefixLength + 1);

			if (!isset($maniaControlSettings[$settingName])) {
				continue;
			}

			$oldSetting = $maniaControlSettings[$settingName];
			if ($setting['Value'] == $oldSetting->value || $oldSetting->type == 'bool') {
				continue;
			}

			$this->maniaControl->settingManager->setSetting($oldSetting->class, $oldSetting->setting, $setting['Value']);
		}

		$this->maniaControl->chat->sendSuccess('Plugin Settings saved!', $player);

		//Reopen the Menu
		$menuId = $this->maniaControl->configurator->getMenuId($this->getTitle());
		$this->maniaControl->configurator->reopenMenu($player, $menuId);
	}
}
