<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 14.02.14
 * Time: 17:16
 */

namespace Dedimania;

use ManiaControl\ManiaControl;
use Maniaplanet\DedicatedServer\Structures\Version;

/**
 * Dedimania Structure
 *
 * @author kremsy & steeffeen
 */
class DedimaniaData {
	public $game;
	public $path;
	public $packmask;
	public $serverVersion;
	public $serverBuild;
	public $tool;
	public $version;
	public $login;
	public $code;
	public $sessionId = '';
	public $records = array();
	public $directoryAccessChecked = false;

	public function __construct($serverLogin, $dedimaniaCode, $path, $packmask, Version $serverVersion) {
		$this->game          = "TM2";
		$this->login         = $serverLogin;
		$this->code          = $dedimaniaCode;
		$this->version       = ManiaControl::VERSION;
		$this->tool          = "ManiaControl";
		$this->path          = $path;
		$this->packmask      = $packmask;
		$this->serverVersion = $serverVersion->version;
		$this->serverBuild   = $serverVersion->build;
	}

	public function toArray() {
		$array = array();
		foreach(get_object_vars($this) as $key => $value) {
			if ($key == 'records' || $key == 'sessionId' || $key == 'directoryAccessChecked') {
				continue;
			}
			$array[ucfirst($key)] = $value;
		}
		return $array;
	}
} 