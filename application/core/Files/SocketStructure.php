<?php
/**
 * Socket Structure
 *
 * @author kremsy & steeffeen
 */
namespace ManiaControl\Files;

/**
 * Socket Structure
 */
class SocketStructure {
	public $streamBuffer;
	public $socket;
	public $function;
	public $url;

	public function __construct($url, $socket, $function) {
		$this->url          = $url;
		$this->socket       = $socket;
		$this->function     = $function;
		$this->streamBuffer = '';
	}
}