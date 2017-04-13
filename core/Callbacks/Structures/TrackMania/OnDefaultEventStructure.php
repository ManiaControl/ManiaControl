<?php

namespace ManiaControl\Callbacks\Structures\TrackMania;


use ManiaControl\Callbacks\Structures\Common\BaseStructure;
use ManiaControl\Callbacks\Structures\Common\BaseTimeStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the Default Event Structure Callback
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class OnDefaultEventStructure extends BaseTimeStructure {
	private $type;

	/**
	 * OnDefaultEventStructure constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @param                            $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->type = $this->getPlainJsonObject()->type;
	}


	/**
	 * Returns the type of event
	 *
	 * @api
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
}