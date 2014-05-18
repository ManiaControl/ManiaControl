<?php

namespace ManiaControl\Configurators;

use FML\Controls\Control;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Features\Paging;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Settings\Setting;

/**
 * Class offering a Configurator for ManiaControl Settings
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaControlSettings implements ConfiguratorMenu, CallbackListener {
	/*
	 * Constants
	 */
	const TITLE                                 = 'ManiaControl Settings';
	const ACTION_PREFIX_SETTING                 = 'MCSetting.';
	const ACTION_PREFIX_SETTINGCLASS            = 'MCSettingClass.';
	const ACTION_SETTINGCLASS_BACK              = 'MCSettingClassBack';
	const ACTION_SETTING_BOOL                   = 'MCSettings.ActionBoolSetting.';
	const SETTING_PERMISSION_CHANGE_MC_SETTINGS = 'Change ManiaControl Settings';
	const CACHE_CLASS_OPENED                    = 'ClassOpened';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Script Settings Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_MC_SETTINGS, AuthenticationManager::AUTH_LEVEL_ADMIN);
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script, Player $player) {
		$openedClass = $player->getCache($this, self::CACHE_CLASS_OPENED);
		if ($openedClass) {
			return $this->getMenuSettingsForClass($openedClass, $width, $height, $script, $player);
		}
		return $this->getMenuSettingClasses($width, $height, $script, $player);
	}

	/**
	 * Get the Menu showing the Settings for the given Class
	 *
	 * @param string $settingClass
	 * @param float  $width
	 * @param float  $height
	 * @param Script $script
	 * @param Player $player
	 * @return \FML\Controls\Frame
	 */
	private function getMenuSettingsForClass($settingClass, $width, $height, Script $script, Player $player) {
		$settings = $this->maniaControl->settingManager->getSettingsByClass($settingClass);

		$paging = new Paging();
		$script->addFeature($paging);
		$frame = new Frame();

		// Config
		$pagerSize     = 9.;
		$settingHeight = 5.;
		$labelTextSize = 2;
		$pageMaxCount  = 13;

		// Pagers
		$pagerPrev = new Quad_Icons64x64_1();
		$frame->add($pagerPrev);
		$pagerPrev->setPosition($width * 0.39, $height * -0.44, 2);
		$pagerPrev->setSize($pagerSize, $pagerSize);
		$pagerPrev->setSubStyle($pagerPrev::SUBSTYLE_ArrowPrev);

		$pagerNext = new Quad_Icons64x64_1();
		$frame->add($pagerNext);
		$pagerNext->setPosition($width * 0.45, $height * -0.44, 2);
		$pagerNext->setSize($pagerSize, $pagerSize);
		$pagerNext->setSubStyle($pagerNext::SUBSTYLE_ArrowNext);

		$paging->addButton($pagerNext);
		$paging->addButton($pagerPrev);

		$pageCountLabel = new Label_Text();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign($pageCountLabel::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44);
		$pageCountLabel->setStyle($pageCountLabel::STYLE_TextTitle1);
		$pageCountLabel->setTextSize(2);

		$paging->setLabel($pageCountLabel);

		$backLabel = new Label_Button();
		$frame->add($backLabel);
		$backLabel->setPosition($width * -0.49, $height * -0.44);
		$backLabel->setHAlign($backLabel::LEFT);
		$backLabel->setTextSize(2);
		$backLabel->setText('Back');
		$backLabel->setAction(self::ACTION_SETTINGCLASS_BACK);

		$pageFrame = null;
		$index     = 0;
		$y         = 0;
		foreach ($settings as $setting) {
			if (!$pageFrame) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$y = $height * 0.41;
				$paging->addPage($pageFrame);
			}

			$settingFrame = new Frame();
			$pageFrame->add($settingFrame);
			$settingFrame->setY($y);

			// TODO: display currently select setting class

			// Headline Label
			if (false) {
				$headLabel = new Label_Text();
				$settingFrame->add($headLabel);
				$headLabel->setHAlign(Control::LEFT);
				$headLabel->setX($width * -0.46);
				$headLabel->setSize($width * 0.6, $settingHeight);
				$headLabel->setStyle($headLabel::STYLE_TextCardSmall);
				$headLabel->setTextSize($labelTextSize);
				$headLabel->setText($setting->class);
				$headLabel->setTextColor("F00");
			} // Headline

			$nameLabel = new Label_Text();
			$settingFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.46);
			$nameLabel->setSize($width * 0.6, $settingHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setText($setting->setting);
			$nameLabel->setTextColor("FFF");

			if ($setting->type === Setting::TYPE_BOOL) {
				$quad = new Quad_Icons64x64_1();
				$settingFrame->add($quad);
				$quad->setX($width / 2 * 0.6);
				$quad->setZ(-0.01);
				$quad->setSubStyle(($setting->value ? $quad::SUBSTYLE_LvlGreen : $quad::SUBSTYLE_LvlRed));
				$quad->setSize(4, 4);
				$quad->setAction(self::ACTION_SETTING_BOOL . $setting->index);
			} else {
				$entry = new Entry();
				$settingFrame->add($entry);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall);
				$entry->setX($width / 2 * 0.65);
				$entry->setTextSize(1);
				$entry->setSize($width * 0.3, $settingHeight * 0.9);
				$entry->setName(self::ACTION_PREFIX_SETTING . $setting->index);
				$entry->setDefault($setting->value);
			}

			$y -= $settingHeight;
			if ($index % $pageMaxCount == $pageMaxCount - 1) {
				$pageFrame = null;
			}

			$index++;
		}

		return $frame;
	}

	/**
	 * Get the Menu showing all possible Classes
	 *
	 * @param float  $width
	 * @param float  $height
	 * @param Script $script
	 * @param Player $player
	 * @return \FML\Controls\Frame
	 */
	private function getMenuSettingClasses($width, $height, Script $script, Player $player) {
		$settingClasses = $this->maniaControl->settingManager->getSettingClasses(true);

		$paging = new Paging();
		$script->addFeature($paging);
		$frame = new Frame();

		// Config
		$pagerSize     = 9.;
		$settingHeight = 5.;
		$pageMaxCount  = 13;
		$y             = 0;

		// Pagers
		$pagerPrev = new Quad_Icons64x64_1();
		$frame->add($pagerPrev);
		$pagerPrev->setPosition($width * 0.39, $height * -0.44, 2);
		$pagerPrev->setSize($pagerSize, $pagerSize);
		$pagerPrev->setSubStyle($pagerPrev::SUBSTYLE_ArrowPrev);

		$pagerNext = new Quad_Icons64x64_1();
		$frame->add($pagerNext);
		$pagerNext->setPosition($width * 0.45, $height * -0.44, 2);
		$pagerNext->setSize($pagerSize, $pagerSize);
		$pagerNext->setSubStyle($pagerNext::SUBSTYLE_ArrowNext);

		$paging->addButton($pagerNext);
		$paging->addButton($pagerPrev);

		$pageCountLabel = new Label_Text();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign($pageCountLabel::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44, 1);
		$pageCountLabel->setStyle($pageCountLabel::STYLE_TextTitle1);
		$pageCountLabel->setTextSize(2);

		$paging->setLabel($pageCountLabel);

		$pageFrame = null;
		$index     = 0;
		foreach ($settingClasses as $settingClass) {
			if (!$pageFrame) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$y = $height * 0.41;
				$paging->addPage($pageFrame);
			}

			$classLabel = new Label_Text();

			$settingClassArray = explode('\\', $settingClass);
			$className         = "";
			for ($i = 1; $i < count($settingClassArray); $i++) {
				$className .= $settingClassArray[$i] . " - ";
			}
			$className = substr($className, 0, -3);

			$pageFrame->add($classLabel);
			$classLabel->setHAlign($classLabel::LEFT);
			$classLabel->setPosition($width * -0.45, $y);
			$classLabel->setSize($width * 0.9, $settingHeight * 0.9);
			$classLabel->setStyle($classLabel::STYLE_TextCardSmall);
			$classLabel->setTextSize(2);
			$classLabel->setText($className);
			$classLabel->setTextColor('fff');
			$classLabel->setAction(self::ACTION_PREFIX_SETTINGCLASS . $settingClass);

			$y -= $settingHeight;

			if ($index % $pageMaxCount == $pageMaxCount - 1) {
				$pageFrame = null;
			}

			$index++;
		}

		return $frame;
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		if ($actionId === self::ACTION_SETTINGCLASS_BACK) {
			// Back to classes list
			$login  = $callback[1][1];
			$player = $this->maniaControl->playerManager->getPlayer($login);
			$player->destroyCache($this, self::CACHE_CLASS_OPENED);
			$menuId = $this->maniaControl->configurator->getMenuId($this);
			$this->maniaControl->configurator->showMenu($player, $menuId);
		} else if (strpos($actionId, self::ACTION_SETTING_BOOL) === 0) {
			// Bool setting change
			$settingIndex = (int)substr($actionId, strlen(self::ACTION_SETTING_BOOL));

			$login  = $callback[1][1];
			$player = $this->maniaControl->playerManager->getPlayer($login);

			// Toggle the Boolean Setting
			$this->toggleBooleanSetting($settingIndex, $player);

			if ($callback[1][3]) {
				// Save all Changes
				$this->saveConfigData($callback[1], $player);
			} else {
				// Reopen menu directly
				$menuId = $this->maniaControl->configurator->getMenuId($this);
				$this->maniaControl->configurator->reopenMenu($player, $menuId);
			}
		} else if (strpos($actionId, self::ACTION_PREFIX_SETTINGCLASS) === 0) {
			// Setting class selected
			$settingClass = substr($actionId, strlen(self::ACTION_PREFIX_SETTINGCLASS));

			$login  = $callback[1][1];
			$player = $this->maniaControl->playerManager->getPlayer($login);
			$player->setCache($this, self::CACHE_CLASS_OPENED, $settingClass);

			$menuId = $this->maniaControl->configurator->getMenuId($this);
			$this->maniaControl->configurator->showMenu($player, $menuId);
		}
	}

	/**
	 * Toggles a Boolean Value
	 *
	 * @param int    $settingIndex
	 * @param Player $player
	 */
	public function toggleBooleanSetting($settingIndex, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_MC_SETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$oldSetting = $this->maniaControl->settingManager->getSettingByIndex($settingIndex);

		if (!isset($oldSetting)) {
			var_dump('no setting ' . $settingIndex);
			return;
		}

		// Toggle value
		$this->maniaControl->settingManager->setSetting($oldSetting->class, $oldSetting->setting, !$oldSetting->value);
	}

	/**
	 * Save the Config Data
	 *
	 * @param array  $configData
	 * @param Player $player
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_MC_SETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		if (!$configData[3] || strpos($configData[3][0]['Name'], self::ACTION_PREFIX_SETTING) !== 0) {
			// TODO: improve needed, this won't save configData passed by boolean setting change
			return;
		}

		$maniaControlSettings = $this->maniaControl->settingManager->getSettings();

		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		foreach ($configData[3] as $setting) {
			$settingName = substr($setting['Name'], $prefixLength);

			$oldSetting = $maniaControlSettings[$settingName];
			if ($setting['Value'] == $oldSetting->value || $oldSetting->type == 'bool') {
				continue;
			}

			$this->maniaControl->settingManager->setSetting($oldSetting->class, $oldSetting->setting, $setting['Value']);
		}

		$this->maniaControl->chat->sendSuccess('Settings saved!', $player);

		// Reopen the Menu
		$menuId = $this->maniaControl->configurator->getMenuId($this->getTitle());
		$this->maniaControl->configurator->reopenMenu($player, $menuId);
	}

	/**
	 * Get the Menu Title
	 *
	 * @return string
	 */
	public function getTitle() {
		return self::TITLE;
	}
}
