<?php

namespace ManiaControl;

class TestPlugin extends Plugin {

	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	public function getAuthor() {
		return 'steeffeen';
	}

	public function getName() {
		return 'Test Plugin';
	}

	public function getVersion() {
		return '1.0';
	}

	public function getDescription() {
		return 'Dummy plugin for testing plugin handling';
	}
}

?>
