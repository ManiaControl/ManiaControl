<?php

namespace ManiaControl\Plugins;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Configurators\ConfiguratorMenu;
use FML\Script\Pages;
use FML\Script\Tooltips;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Control;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Labels\Label_Button;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\CallbackListener;

/**
 * Configurator for enabling and disabling plugins
 *
 * @author steeffeen
 */
class PluginMenu implements CallbackListener, ConfiguratorMenu {
	/**
	 * Constants
	 */
	const ACTION_PREFIX_ENABLEPLUGIN = 'PluginMenu.Enable.';
	const ACTION_PREFIX_DISABLEPLUGIN = 'PluginMenu.Disable.';
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new plugin menu instance
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 
				'handleManialinkPageAnswer');
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getTitle()
	 */
	public function getTitle() {
		return 'Plugins';
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Pages $pages, Tooltips $tooltips) {
		$frame = new Frame();
		
		$pluginClasses = $this->maniaControl->pluginManager->getPluginClasses();
		
		// Config
		$pagerSize = 9.;
		$entryHeight = 5.;
		$labelTextSize = 2;
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
		
		$pageCountLabel = new Label_Text();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign(Control::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44, 1);
		$pageCountLabel->setStyle($pageCountLabel::STYLE_TextTitle1);
		$pageCountLabel->setTextSize(2);
		
		// Plugin pages
		$pageFrames = array();
		$y = 0.;
		foreach ($pluginClasses as $index => $pluginClass) {
			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				array_push($pageFrames, $pageFrame);
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
			}
			else {
				$activeQuad->setSubStyle($activeQuad::SUBSTYLE_LvlRed);
			}
			
			$nameLabel = new Label_Text();
			$pluginFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.5, $entryHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize($labelTextSize);
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
			$tooltips->add($nameLabel, $descriptionLabel);
			
			$statusChangeButton = new Label_Button();
			$pluginFrame->add($statusChangeButton);
			$statusChangeButton->setHAlign(Control::RIGHT);
			$statusChangeButton->setX($width * 0.45);
			$statusChangeButton->setStyle($statusChangeButton::STYLE_CardButtonSmall);
			if ($active) {
				$statusChangeButton->setTextPrefix('$f00');
				$statusChangeButton->setText('Deactivate');
				$statusChangeButton->setAction(self::ACTION_PREFIX_DISABLEPLUGIN . $pluginClass);
			}
			else {
				$statusChangeButton->setTextPrefix('a');
				$statusChangeButton->setText('Activate');
				$statusChangeButton->setAction(self::ACTION_PREFIX_ENABLEPLUGIN . $pluginClass);
			}
			
			$y -= $entryHeight;
			if ($index % $pageMaxCount == $pageMaxCount - 1) {
				unset($pageFrame);
			}
		}
		
		$pages->add(array(-1 => $pagerPrev, 1 => $pagerNext), $pageFrames, $pageCountLabel);
		
		return $frame;
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
	}

	/**
	 * Handle PlayerManialinkPageAnswer callback
	 *
	 * @param array $callback        	
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		$enable = (strpos($actionId, self::ACTION_PREFIX_ENABLEPLUGIN) === 0);
		$disable = (strpos($actionId, self::ACTION_PREFIX_DISABLEPLUGIN) === 0);
		if (!$enable && !$disable) {
			return;
		}
		$login = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		if (!$player) {
			return;
		}
		if ($enable) {
			$pluginClass = substr($actionId, strlen(self::ACTION_PREFIX_ENABLEPLUGIN));
			$activated = $this->maniaControl->pluginManager->activatePlugin($pluginClass);
			if ($activated) {
				$this->maniaControl->chat->sendSuccess($pluginClass::getName() . ' activated!', $player->login);
				$this->maniaControl->configurator->showMenu($player);
			}
			else {
				$this->maniaControl->chat->sendError('Error activating ' . $pluginClass::getName() . '!', $player->login);
			}
		}
		else {
			$pluginClass = substr($actionId, strlen(self::ACTION_PREFIX_DISABLEPLUGIN));
			$deactivated = $this->maniaControl->pluginManager->deactivatePlugin($pluginClass);
			if ($deactivated) {
				$this->maniaControl->chat->sendSuccess($pluginClass::getName() . ' deactivated!', $player->login);
				$this->maniaControl->configurator->showMenu($player);
			}
			else {
				$this->maniaControl->chat->sendError('Error deactivating ' . $pluginClass::getName() . '!', $player->login);
			}
		}
	}
}

?>
