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
		return SELF::AUTHOR;
	}

	public function getName() {
		return SELF::NAME;
	}

	public function getVersion() {
		return SELF::VERSION;
	}

	public function getDescription() {
		return SELF::DESCRIPTION;
	}
}

?>
