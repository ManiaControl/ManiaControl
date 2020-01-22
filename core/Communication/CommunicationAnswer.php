<?php

namespace ManiaControl\Communication;

/**
 * Class for Answer of Communication Request
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CommunicationAnswer {
	/** Properties are Public for serialization */
	public $error;
	public $data;

	/**
	 * @param string $data
	 * @param bool   $error
	 */
	public function __construct($data = "", $error = false) {
		$this->data  = $data;
		$this->error = $error;
	}
}