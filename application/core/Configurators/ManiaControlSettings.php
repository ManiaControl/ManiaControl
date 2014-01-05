<?php
/**
 * Class offering a Configurator for Script Settings
 *
 * @author steeffeen & kremsy
 */
namespace ManiaControl\Configurators;


use FML\Controls\Frame;
use FML\Script\Script;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class ManiaControlSettings implements ConfiguratorMenu{
	/**
	 * Constants
	 */
	const TITLE = 'ManiaControl Settings';

	/**
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

	}

	/**
	 * Get the Menu Title
	 *
	 * @return string
	 */
	public function getTitle() {
		self::TITLE;
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
		$pagesId = 'ScriptSettingsPages';
		$frame = new Frame();

		/** @var  ManiaControl/SettingManager $this->maniaControl->settingManager */
		$settings = $this->maniaControl->settingManager->getSettings();

		foreach($settings as $setting){
			var_dump($setting);


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