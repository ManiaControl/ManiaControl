<?php

namespace ManiaControl\Plugins;

use FML\Controls\Quad;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Configurators\ConfiguratorMenu;
use FML\Script\Pages;
use FML\Script\Tooltips;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Control;

/**
 * Configurator for enabling and disabling plugins
 *
 * @author steeffeen
 */
class PluginMenu implements ConfiguratorMenu {
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
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getTitle()
	 */
	public function getTitle() {
		return "Plugins";
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Pages $pages, Tooltips $tooltips) {
		$frame = new Frame();

		$pluginClasses = $this->maniaControl->pluginManager->getPluginClasses();
		//$labelStyleSetting = $this->maniaControl->settingManager->getSetting($this, self::SETTING_STYLE_SETTING);
		$labelStyleSetting = Label_Text::STYLE_TextStaticSmall;
	//	$pagerSize = 9.;
		$entryHeight = 5.;
		$labelTextSize = 2;
	//	$pageMaxCount = 13;
		$pageFrames = array();
		$y = 0.;
		foreach ($pluginClasses as $pluginClass) {
			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				array_push($pageFrames, $pageFrame);
				$y = $height * 0.41;
			}

			//check if plugin is aktiv
			$active = $this->maniaControl->pluginManager->getPluginStatus($pluginClass);


			$settingFrame = new Frame();
			$pageFrame->add($settingFrame);
			$settingFrame->setY($y);

			//TODO: Red or Green quad to see if the plugin is aktiv (not working yet)
			$activeQuad = new Quad();
			$settingFrame->add($active);
			if($active)
				$activeQuad->setStyles("Icons64x64_1", "LvlGreen");
			else
				$activeQuad->setStyles("Icons64x64_1", "LvlRed");
			$activeQuad->setHeight(5);
			$activeQuad->setWidth(5);
			$activeQuad->setX($width * -0.455);
			//TODO handle z position automatically in fml pls

			$nameLabel = new Label();
			$settingFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.4);
			$nameLabel->setSize($width * 0.4, $entryHeight);
			$nameLabel->setStyle($labelStyleSetting);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setText($pluginClass);

			//TODO description
			$descriptionLabel = new Label();
			$pageFrame->add($descriptionLabel);
			$descriptionLabel->setHAlign(Control::LEFT);
			$descriptionLabel->setPosition($width * -0.45, $height * -0.44);
			//$descriptionLabel->setSize($width * 0.7, $settingHeight);
			//$descriptionLabel->setStyle($labelStyleDescription);
			$descriptionLabel->setTranslate(true);
			$descriptionLabel->setTextPrefix('Desc: ');
			//$descriptionLabel->setText($scriptParam['Desc']);
			$tooltips->add($nameLabel, $descriptionLabel);

			//TODO set aktive button
			/*$aktivButton = new Quad();
			$aktivButton->setBgColor("F00");
			$aktivButton->setHeight(10);
			$aktivButton->setWidth(10);
			$aktivButton->setX($width * 0.2);
			$settingFrame->add($aktivButton);*/
			//$aktivButton = new Labels\Label_Button();


			$y -= $entryHeight;
		//	if ($index % $pageMaxCount == $pageMaxCount - 1) {
			//	unset($pageFrame);
			//}
		}
		//TODO multiple pages
		//$pages->add(array(-1 => $pagerPrev, 1 => $pagerNext), $pageFrames, $pageCountLabel);

		return $frame;
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
	}
}

?>
