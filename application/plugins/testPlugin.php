<?php

namespace ManiaControl;

class TestPlugin extends Plugin {
    const AUTHOR = 'steeffeen';
    const NAME = 'Test Plugin';
    const VERSION = '1.0';
    const DESCRIPTION = 'Test Plugin';

	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}




    //abstract Methods from ParentClass (do not change them!)
	public function getAuthor() {
		return self::AUTHOR;
	}

	public function getName() {
		return self::NAME;
	}

	public function getVersion() {
		return self::VERSION;
	}

	public function getDescription() {
		return self::DESCRIPTION;
	}
}

?>
