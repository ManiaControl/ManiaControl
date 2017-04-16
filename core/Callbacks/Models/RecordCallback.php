<?php

namespace ManiaControl\Callbacks\Models;

/**
 * Base Model Class for Callbacks
 *
 * @deprecated
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class RecordCallback extends BaseCallback {
	/*
	 * Constants
	 */
	const CHECKPOINT = 'RecordCallback.Checkpoint';
	const FINISH     = 'RecordCallback.Finish';
	const LAPFINISH  = 'RecordCallback.LapFinish';

	/*
	 * Public Properties
	 */
	public $time = null;
	public $player = null;
	public $racetime = null;
	public $laptime = null;
	public $stuntsscore = null;
	public $checkpointinrace = null;
	public $checkpointinlap = null;
	public $isendrace = null;
	public $isendlap = null;
	public $blockid = null;
	public $speed = null;
	public $distance = null;
}
