<?php

namespace ManiaControl\Configurators;

use ManiaControl\ManiaControl;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Script\Pages;
use FML\Script\Tooltips;
use FML\Controls\Control;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Entry;
use ManiaControl\Players\Player;

/**
 * Class offering a configurator for current script settings
 *
 * @author steeffeen & kremsy
 */
// TODO: boolean script settings not as entries
class ScriptSettings implements ConfiguratorMenu {
	/**
	 * Constants
	 */
	const ACTION_PREFIX_SETTING = 'ScriptSetting.';
	
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new script settings instance
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
		return 'Script Settings';
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Pages $pages, Tooltips $tooltips) {
		$frame = new Frame();
		
		$this->maniaControl->client->query('GetModeScriptInfo');
		$scriptInfo = $this->maniaControl->client->getResponse();
		$scriptParams = $scriptInfo['ParamDescs'];
		
		$this->maniaControl->client->query('GetModeScriptSettings');
		$scriptSettings = $this->maniaControl->client->getResponse();
		
		// Config
		$pagerSize = 9.;
		$settingHeight = 5.;
		$labelTextSize = 2;
		$pageMaxCount = 13;
		
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
		
		$pageCountLabel = new Label();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign(Control::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44, 1);
		$pageCountLabel->setStyle('TextTitle1');
		$pageCountLabel->setTextSize(2);
		
		// Setting pages
		$pageFrames = array();
		$y = 0.;
		foreach ($scriptParams as $index => $scriptParam) {
			$settingName = $scriptParam['Name'];
			if (!isset($scriptSettings[$settingName])) continue;
			
			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				array_push($pageFrames, $pageFrame);
				$y = $height * 0.41;
			}
			
			$settingFrame = new Frame();
			$pageFrame->add($settingFrame);
			$settingFrame->setY($y);
			
			$nameLabel = new Label_Text();
			$settingFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.46);
			$nameLabel->setSize($width * 0.4, $settingHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setText($settingName);
			
			$entry = new Entry();
			$settingFrame->add($entry);
			$entry->setHAlign(Control::RIGHT);
			$entry->setX($width * 0.44);
			$entry->setSize($width * 0.4, $settingHeight);
			$entry->setName(self::ACTION_PREFIX_SETTING . $settingName);
			$settingValue = $scriptSettings[$settingName];
			if ($settingValue === false) {
				$settingValue = 0;
			}
			$entry->setDefault($settingValue);
			
			$descriptionLabel = new Label();
			$pageFrame->add($descriptionLabel);
			$descriptionLabel->setHAlign(Control::LEFT);
			$descriptionLabel->setPosition($width * -0.45, $height * -0.44);
			$descriptionLabel->setSize($width * 0.7, $settingHeight);
			$descriptionLabel->setTranslate(true);
			$descriptionLabel->setTextPrefix('Desc: ');
			$descriptionLabel->setText($scriptParam['Desc']);
			$tooltips->add($nameLabel, $descriptionLabel);
			
			$y -= $settingHeight;
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
		$this->maniaControl->client->query('GetModeScriptSettings');
		$scriptSettings = $this->maniaControl->client->getResponse();
		// var_dump($configData);
		// var_dump($scriptSettings);
		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);
		foreach ($configData[3] as $dataName => $dataValue) {
			if (substr($dataName, 0, $prefixLength) != self::ACTION_PREFIX_SETTING) continue;
			
			$settingName = substr($dataName, $prefixLength);
			
			// TODO: apply new script settings
		}
	}
}

?>
