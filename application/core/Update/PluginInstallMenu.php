<?php

namespace ManiaControl\Update;

use FML\Controls\Control;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_Icons128x32_1;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Configurators\ConfiguratorMenu;
use ManiaControl\Formatter;
use ManiaControl\Files\FileUtil;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;

/**
 * Configurator for enabling and disabling Plugins
 *
 * @author steeffeen
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PluginInstallMenu implements CallbackListener, ConfiguratorMenu, ManialinkPageAnswerListener {
	const SETTING_PERMISSION_INSTALL_PLUGINS  = 'Install plugins';
	const ACTION_PREFIX_INSTALLPLUGIN         = 'PluginInstallMenu.Install.';

	/**
	 * Create a new plugin menu instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_INSTALL_PLUGINS, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
	}

	/**
	 * Get the Menu Title
	 *
	 * @return string
	 */
	public function getTitle() {
		return 'Install Plugins';
	}

	/**
	 * Get the Configurator Menu Frame
	 *
	 * @param float  $width
	 * @param float  $height
	 * @param Script $script
	 * @return \FML\Controls\Frame
	 */
	public function getMenu($width, $height, Script $script) {
		$pagesId = 'PluginInstallPages';
		$frame   = new Frame();

		$pluginClasses = $this->maniaControl->pluginManager->getPluginClasses();

		// Config
		$pagerSize     = 9.;
		$entryHeight   = 5.;
		$labelTextSize = 2;
		$pageMaxCount  = 10;
		$y             = 0.;
		$pageFrames    = array();

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

		$script->addPager($pagerPrev, -1, $pagesId);
		$script->addPager($pagerNext, 1, $pagesId);

		$pageCountLabel = new Label_Text();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign(Control::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44, 1);
		$pageCountLabel->setStyle($pageCountLabel::STYLE_TextTitle1);
		$pageCountLabel->setTextSize(2);

		$script->addPageLabel($pageCountLabel, $pagesId);

		$url            = ManiaControl::URL_WEBSERVICE . 'plugins';
		$dataJson       = FileUtil::loadFile($url);
		$pluginList     = json_decode($dataJson);
		$index          = 0;
		if ($pluginList && isset($pluginList[0])) {
			$pluginClasses = $this->maniaControl->pluginManager->getPluginClasses();
			$pluginIds = array();
			/** @var  Plugin $class */
			foreach($pluginClasses as $class) {
				$pluginIds[] = $class::getId();
			}

			foreach($pluginList as $plugin) {
				if(!in_array($plugin->id, $pluginIds)) {
					if (!isset($pageFrame)) {
						$pageFrame = new Frame();
						$frame->add($pageFrame);
						if (!empty($pageFrames)) {
							$pageFrame->setVisible(false);
						}

						array_push($pageFrames, $pageFrame);
						$script->addPage($pageFrame, count($pageFrames), $pagesId);
						$y = $height * 0.41;
					}

					$pluginFrame = new Frame();
					$pageFrame->add($pluginFrame);
					$pluginFrame->setY($y);

					$nameLabel = new Label_Text();
					$pluginFrame->add($nameLabel);
					$nameLabel->setHAlign(Control::LEFT);
					$nameLabel->setX($width * -0.4);
					$nameLabel->setSize($width * 0.5, $entryHeight);
					$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
					$nameLabel->setTextSize($labelTextSize);
					$nameLabel->setText($plugin->name);

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
					$description = "Author: {$plugin->author}\nVersion: {$plugin->currentVersion->version}\nDesc: {$plugin->description}";
					$descriptionLabel->setText($description);
					$script->addTooltip($nameLabel, $descriptionLabel);

					$installButton = new Label_Button();
					$pluginFrame->add($installButton);
					$installButton->setHAlign(Control::RIGHT);
					$installButton->setX($width * 0.45);
					$installButton->setStyle($installButton::STYLE_CardButtonSmall);
					$installButton->setTextPrefix('$f00');
					$installButton->setText('Install');
					$installButton->setAction(self::ACTION_PREFIX_INSTALLPLUGIN . $plugin->id);

					$y -= $entryHeight;
					if ($index % $pageMaxCount == $pageMaxCount - 1) {
						unset($pageFrame);
					}

					$index++;
				}
			}
		}

		return $frame;
	}

	/**
	 * Save the Config Data
	 *
	 * @param array  $configData
	 * @param Player $player
	 */
	public function saveConfigData(array $configData, Player $player) {
		// TODO: Implement saveConfigData() method.
	}
}