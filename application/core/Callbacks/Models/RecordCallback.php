<?php

namespace ManiaControl\Callbacks\Models;

/**
 * Base Model Class for Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class RecordCallback extends BaseCallback {
	/*
	 * Constants
	 */
	const CHECKPOINT = 'RecordCallback.Checkpoint';
	const FINISH     = 'RecordCallback.Finish';

	/*
	 * Public Properties
	 */
	public $time = null;
}
