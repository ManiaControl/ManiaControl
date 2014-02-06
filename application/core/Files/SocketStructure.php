<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 07.02.14
 * Time: 00:18
 */

namespace ManiaControl\Files;

/**
 * Socket Structure
 */
class SocketStructure {
	public $streamBuffer;
	public $socket;
	public $function;

	public function construct($socket, $function) {
		$this->socket       = $socket;
		$this->function     = $function;
		$this->streamBuffer = '';
	}
}