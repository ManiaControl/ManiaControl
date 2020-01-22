<?php

namespace ManiaControl\Callbacks\Structures\XmlRpc;

use ManiaControl\Callbacks\Structures\Common\BaseResponseStructure;
use ManiaControl\ManiaControl;

/**
 * Structure Class for the ApiVersion Structure Callback
 *
 * @api
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ApiVersionStructure extends BaseResponseStructure {
	private $version;

	/**
	 * Construct a new Callbacks Version Structure
	 *
	 * @param ManiaControl $maniaControl
	 * @param array        $data
	 */
	public function __construct(ManiaControl $maniaControl, $data) {
		parent::__construct($maniaControl, $data);

		$this->version = $this->getPlainJsonObject()->version;
	}

	/**
	 * Gets the API Version
	 *
	 * @api
	 * @return string version
	 */
	public function getVersion() {
		return $this->version;
	}

}