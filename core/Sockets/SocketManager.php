<?php
namespace ManiaControl\Sockets;

use ManiaControl\Callbacks\Listening;
use ManiaControl\ManiaControl;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\ConnectionException;
use React\Socket\Server;

/**
 * Class for managing Socket Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2015 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SocketManager {

	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/** @var LoopInterface $loop */
	private $loop = null;

	/** @var Listening[] $socketListenings */
	private $socketListenings = array();

	/** @var Server $socket */
	private $socket = null;


	/**
	 * Create a new Socket Handler Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->createSocket();
	}

	/**
	 * Register a new Socket Listener
	 *
	 * @param string         $callbackName
	 * @param SocketListener $listener
	 * @param string         $method
	 * @return bool
	 */
	public function registerSocketListener($echoName, SocketListener $listener, $method) {
		if (!Listening::checkValidCallback($listener, $method)) {
			$listenerClass = get_class($listener);
			trigger_error("Given Listener '{$listenerClass}' can't handle Callback '{$echoName}': No callable Method '{$method}'!");
			return false;
		}

		if (!array_key_exists($echoName, $this->socketListenings)) {
			$this->socketListenings[$echoName] = new Listening($listener, $method);
		} else {
			//TODO say which is already listening and other stuff
			trigger_error("Only one Listener can listen on a specific Socket Message");
		}

		return true;
	}

	/**
	 * Trigger a specific Callback
	 *
	 * @param mixed $callback
	 */
	public function triggerSocketCallback($callbackName) {
		if (!array_key_exists($callbackName, $this->socketListenings)) {
			return null;
		}

		$params = func_get_args();
		$params = array_slice($params, 1, null, true);

		$listening = $this->socketListenings[$callbackName];
		/** @var Listening $listening */
		return $listening->triggerCallbackWithParams($params);
	}


	/**
	 * Unregister a Socket Listener
	 *
	 * @param SocketListener $listener
	 * @return bool
	 */
	public function unregisterEchoListener(SocketListener $listener) {
		return $this->removeSocketListener($this->socketListenings, $listener);
	}


	/**
	 * Remove the Socket Listener from the given Listeners Array
	 *
	 * @param Listening[]    $listeningsArray
	 * @param SocketListener $listener
	 * @return bool
	 */
	private function removeSocketListener(array &$listeningsArray, SocketListener $listener) {
		$removed = false;
		foreach ($listeningsArray as &$listening) {
			if ($listening->listener === $listener) {
				unset($listening);
				$removed = true;
			}
		}
		return $removed;
	}


	/**
	 * Creates The Socket
	 */
	private function createSocket() {
		try {
			$this->loop   = Factory::create();
			$this->socket = new Server($this->loop);

			$this->socket->on('error', function ($e) {
				//TODO error handling
				var_dump($e);
			});

			$this->socket->on('connection', function (Connection $connection) {
				$buffer = '';
				$connection->on('data', function ($data) use (&$buffer, &$connection) {
					$buffer .= $data;
					$arr = explode("\n", $buffer, 2); // much haxy.
					while (count($arr) == 2 && strlen($arr[1]) >= (int) $arr[0]) {
						// received full message
						$len    = (int) $arr[0];
						$msg    = substr($arr[1], 0, $len); // clip msg
						$buffer = substr($buffer, strlen((string) $len) + 1 /* newline */ + $len); // clip buffer

						//TODO pass and port management
						// Decode Message
						$data = openssl_decrypt($msg, 'aes-192-cbc', 'testpass123', OPENSSL_RAW_DATA, 'kZ2Kt0CzKUjN2MJX');
						$data = json_decode($data);

						if ($data == null) {
							$data = array("error" => true, "data" => "Data is not provided as an valid AES-196-encrypted encrypted JSON");
						} else if (!property_exists($data, "method") || !property_exists($data, "data")) {
							$data = array("error" => true, "data" => "Invalid Message");
						} else {
							$answer = $this->triggerSocketCallback($data->method, $data);
							//Prepare Response
							if (!$answer) {
								$data = array("error" => true, "data" => "No listener or response on the given Message");
							} else {
								$data = array("error" => false, "data" => $answer);
							}
						}

						//Encode, Encrypt and Send Response
						$data = json_encode($data);
						$data = openssl_encrypt($data, 'aes-192-cbc', 'testpass123', OPENSSL_RAW_DATA, 'kZ2Kt0CzKUjN2MJX');
						$connection->write(strlen($data) . "\n" . $data);

						// next msg
						$arr = explode("\n", $buffer, 2);
					}
				});
			});
			//TODO port
			$this->socket->listen(19999, getHostByName(getHostName())); // exceptions are just thrown right? why does it not work with local ip? because you bind to your loopback adapter k

			// so that aint it.. xD^^ maybe because it is not in an apache environemnt or smth
			// this lib should never run in such an env but the periodictimer works? :O thats actually cool xD ye xD
		} catch (ConnectionException $e) {
			//TODO proper handling
			var_dump($e);
		}
	}


	/**
	 * Processes Data on every ManiaControl Tick, don't call this Method
	 */
	public function tick() {
		$this->loop->tick();
	}
}