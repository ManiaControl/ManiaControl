<?php

namespace ManiaControl\Communication;

/**
 * Class for Communicating with other ManiaControls
 * to call @see \ManiaControl\Communication\CommunicationManager::createCommunication()
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Communication {
	private $socket;
	private $ip;
	private $port;
	private $encryptionPassword;

	private $buffer       = "";
	private $messageQueue = array();

	public function __construct($ip, $port, $encryptionPassword) {
		$this->ip                 = $ip;
		$this->port               = $port;
		$this->encryptionPassword = $encryptionPassword;
	}

	/** Create an Connection */
	public function createConnection() {
		$errno        = null;
		$errstr       = null;
		$this->socket = @fsockopen($this->ip, $this->port, $errno, $errstr, 2);

		//socket_set_nonblock($this->socket);
		stream_set_blocking($this->socket, 0);

		if ($errno != 0 || !$this->socket) {
			var_dump($errstr);
			return false;
		}
		return true;
	}

	/**
	 * Call an Method Asynchronously
	 *
	 * @param callable $function
	 * @param          $method
	 * @param string   $data
	 */
	public function call(callable $function, $method, $data = "") {
		if (!$this->socket) {
			call_user_func($function, true, "You need to create an Communication before using it");
			return null;
		}

		$data = json_encode(array("method" => $method, "data" => $data));

		$data = openssl_encrypt($data, CommunicationManager::ENCRYPTION_METHOD, $this->encryptionPassword, OPENSSL_RAW_DATA, CommunicationManager::ENCRYPTION_IV);

		array_push($this->messageQueue, $function);

		// Write Request on Socket
		fwrite($this->socket, strlen($data) . "\n" . $data);
	}

	/**
	 * Process data on every Tick
	 */
	public function tick() {
		//Check if the connection is enabled
		if (!$this->socket) {
			return;
		}

		$data = fgets($this->socket, 1024); // reads as much as possible OR nothing at all
		if (strlen($data) > 0) { // got new data
			$this->buffer .= $data; // append new data to buffer
			// handle the data the exact same way
			$arr = explode("\n", $this->buffer, 2);
			while (count($arr) == 2 && strlen($arr[1]) >= (int) $arr[0]) {
				// received full message
				$len          = (int) $arr[0];
				$msg          = substr($arr[1], 0, $len); // clip msg
				$this->buffer = substr($this->buffer, strlen((string) $len) + 1 /* newline */ + $len); // clip buffer

				// Decode Message
				$data = openssl_decrypt($msg, CommunicationManager::ENCRYPTION_METHOD, $this->encryptionPassword, OPENSSL_RAW_DATA, CommunicationManager::ENCRYPTION_IV);
				$data = json_decode($data);

				// Received something!
				//Call Function with Data
				call_user_func(array_shift($this->messageQueue), $data->error, $data->data);

				// next msg
				$arr = explode("\n", $this->buffer, 2);
			}
		}
	}

	/** Closes the connection, don't call yourself, let it do the Communication Manager */
	public function closeConnection() {
		if ($this->socket) {
			fclose($this->socket);
		}
	}
}