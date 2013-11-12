<?php
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;

/**
 * Basic test plugin
 *
 * @author steeffeen
 */
class TestPlugin extends Plugin {

	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		$this->author = 'steeffeen';
		$this->name = 'Test Plugin';
		$this->version = '1.0';
		$this->description = 'Dummy plugin for testing plugin handling';
	}
}

?>
