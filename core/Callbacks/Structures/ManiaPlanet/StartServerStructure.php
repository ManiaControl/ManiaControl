<?php

namespace ManiaControl\Callbacks\Structures\ManiaPlanet;


use ManiaControl\Callbacks\Structures\BaseStructure;
use ManiaControl\ManiaControl;

class StartServerStartStructure extends BaseStructure {
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::setManiaControl($maniaControl);
		parent::setJson($data);

	}
}