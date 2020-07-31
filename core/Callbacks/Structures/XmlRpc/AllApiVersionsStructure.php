<?php

namespace ManiaControl\Callbacks\Structures\XmlRpc;

use ManiaControl\Callbacks\Structures\Common\BaseResponseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the AllApiVersions Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AllApiVersionsStructure extends BaseResponseStructure {
	private $latest;
	private $versions;

	/**
	 * Construct a new Callbacks Version Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->latest   = $this->getPlainJsonObject()->latest;
		$this->versions = $this->getPlainJsonObject()->versions;
	}

	/**
	 * Get the Latest Version
	 *
	 * @api
	 * @return string
	 */
	public function getLatest() {
		return $this->latest;
	}

	/**
	 * Get all Versions
	 *
	 * @api
	 * @return array
	 */
	public function getVersions() {
		return $this->versions;
	}


}