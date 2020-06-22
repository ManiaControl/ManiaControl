<?php

namespace ManiaControl\Communication;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\Listening;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\ConnectionException;
use React\Socket\Server;

/**
 * Class for managing Socket Callbacks
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CommunicationManager implements CallbackListener, UsageInformationAble {
	use UsageInformationTrait;
	
	/** Constants */
	const SETTING_SOCKET_ENABLED  = "Activate Socket";
	const SETTING_SOCKET_PASSWORD = "Password for the Socket Connection";
	const SETTING_SOCKET_PORT     = "Socket Port for Server ";

	const ENCRYPTION_IV     = "kZ2Kt0CzKUjN2MJX";
	const ENCRYPTION_METHOD = "aes-192-cbc";

	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/** @var LoopInterface $loop */
	private $loop = null;

	/** @var Listening[] $communicationListenings */
	private $communicationListenings = array();

	/** @var Server $socket */
	private $socket = null;
	/** @var Communication[] $communcations */
	private $communications = array();

	/**
	 * Create a new Communication Handler Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->getCallbackManager()->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'updateSettings');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::AFTERINIT, $this, 'initCommunicationManager');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONRESTART, $this, 'onShutDown');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONSHUTDOWN, $this, 'onShutDown');
	}

	/**
	 * Creates a Communication to another ManiaControl
	 *
	 * @param $ip
	 * @param $port
	 * @return \ManiaControl\Communication\Communication
	 */
	public function createCommunication($ip, $port, $encryptionKey) {
		$communication = new Communication($ip, $port, $encryptionKey);
		$communication->createConnection();

		$this->communications[] = $communication;
		return $communication;
	}


	/**
	 * Closes a opened Communication
	 * Does not necessarily need be called, all connections get destroyed on ManiaControl Shutdown
	 *
	 * @param Communication $communication
	 * @return bool
	 */
	public function closeCommunication($communication) {
		$key = array_search($communication, $this->communications);
		if (isset($this->communications[$key])) {
			$this->communications[$key]->closeConnection();
			unset($this->communications[$key]);
			return true;
		}
		return false;
	}

	/** Close all Sockets on maniaControl Shutdown */
	public function onShutDown() {
		if ($this->socket && $this->socket->master) {
			//Stop the Socket Listening
			$this->socket->shutdown();
			$this->socket = null;
		}

		foreach ($this->communications as $communication) {
			$this->closeCommunication($communication);
		}
	}

	/**
	 * Register a new Communication Listener
	 *
	 * @param string                $communicationName
	 * @param CommunicationListener $listener
	 * @param string                $method
	 * @return bool
	 */
	public function registerCommunicationListener($communicationName, CommunicationListener $listener, $method) {
		if (!Listening::checkValidCallback($listener, $method)) {
			$listenerClass = get_class($listener);
			trigger_error("Given Listener '{$listenerClass}' can't handle Callback '{$communicationName}': No callable Method '{$method}'!");
			return false;
		}

		if (!array_key_exists($communicationName, $this->communicationListenings)) {
			$this->communicationListenings[$communicationName] = new Listening($listener, $method);
		} else {
			//TODO say which is already listening and other stuff
			trigger_error("Only one Listener can listen on a specific Communication Message");
		}

		return true;
	}

	/**
	 * Trigger a specific Callback
	 *
	 * @param mixed $callback
	 */
	public function triggerCommuncationCallback($callbackName) {
		if (!array_key_exists($callbackName, $this->communicationListenings)) {
			return null;
		}

		$params = func_get_args();
		$params = array_slice($params, 1, null, true);

		$listening = $this->communicationListenings[$callbackName];
		/** @var Listening $listening */
		return $listening->triggerCallbackWithParams($params);
	}


	/**
	 * Unregister a Communication Listener
	 *
	 * @param CommunicationListener $listener
	 * @return bool
	 */
	public function unregisterCommunicationListener(CommunicationListener $listener) {
		return $this->removeCommunicationListener($this->communicationListenings, $listener);
	}


	/**
	 * Remove the Communication Listener from the given Listeners Array
	 *
	 * @param Listening[]           $listeningsArray
	 * @param CommunicationListener $listener
	 * @return bool
	 */
	private function removeCommunicationListener(array &$listeningsArray, CommunicationListener $listener) {
		$removed = false;

		foreach ($listeningsArray as $key => &$listening) {
			if($listening->listener === $listener){
				unset($listeningsArray[$key]);
			}
		}
		return $removed;
	}

	/**
	 * Inits the Communication Manager after ManiaControl Startup
	 */
	public function initCommunicationManager() {
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SOCKET_ENABLED, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SOCKET_PASSWORD, "");

		$servers = $this->maniaControl->getServer()->getAllServers();
		foreach ($servers as $server) {
			$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SOCKET_PORT . $server->login, 31500 + $server->index);
		}


		$this->createListeningSocket();
	}

	/**
	 * Update Setting
	 *
	 * @param Setting $setting
	 */
	public function updateSettings(Setting $setting) {
		if (!$setting->belongsToClass($this)) {
			return;
		}

		$socketEnabled = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SOCKET_ENABLED);

		if ($socketEnabled && !$this->socket) {
			$this->createListeningSocket();
		}

		if (!$socketEnabled) {
			$this->socket = null;
		}
	}

	/**
	 * Creates The Socket
	 */
	private function createListeningSocket() {
		$socketEnabled = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SOCKET_ENABLED);
		if ($socketEnabled) {

			Logger::log("[CommunicationManager] Trying to create Socket");

			// Check for MySQLi
			$message = '[CommunicationManager] Checking for installed openssl ... ';
			if (!extension_loaded('openssl')) {
				Logger::log($message . 'NOT FOUND!');
				Logger::log(" -- You don't have openssl installed! Check: http://www.php.net/manual/en/openssl.installation.php");
				return;
			} else {
				Logger::log($message . 'FOUND!');
			}

			$serverLogin = $this->maniaControl->getServer()->login;
			$socketPort  = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SOCKET_PORT . $serverLogin);
			$password    = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SOCKET_PASSWORD);

			try {
				$this->loop   = Factory::create();
				$this->socket = new Server($this->loop);

				$this->socket->on('error', function ($e) {
					Logger::log("[CommunicationManager] Socket Error" . $e);
				});

				$this->socket->on('connection', function (Connection $connection) use ($password) {
					$buffer = '';
					$connection->on('data', function ($data) use (&$buffer, &$connection, $password) {
						$buffer .= $data;
						$arr    = explode("\n", $buffer, 2);
						while (count($arr) == 2 && strlen($arr[1]) >= (int) $arr[0]) {
							// received full message
							$len    = (int) $arr[0];
							$msg    = substr($arr[1], 0, $len); // clip msg
							$buffer = substr($buffer, strlen((string) $len) + 1 /* newline */ + $len); // clip buffer

							// Decode Message
							$data = openssl_decrypt($msg, self::ENCRYPTION_METHOD, $password, OPENSSL_RAW_DATA, self::ENCRYPTION_IV);
							$data = json_decode($data);

							if ($data == null) {
								Logger::log("[CommunicationManager] Error: Data is not provided as an valid AES-196 encrypted JSON");
								$data = array("error" => true, "data" => "Data is not provided as an valid AES-196 encrypted JSON");
							} else if (!property_exists($data, "method") || !property_exists($data, "data")) {
								Logger::log("[CommunicationManager] Invalid Communication Message Received");
								$data = array("error" => true, "data" => "Invalid Message");
							} else {
								$answer = $this->triggerCommuncationCallback($data->method, $data->data);
								//Prepare Response
								if (!$answer) {
									$data = new CommunicationAnswer("No listener or response on the given Message", true);
								} else {
									$data = $answer;
								}
							}

							//Encode, Encrypt and Send Response
							$data = json_encode($data);

							$data = openssl_encrypt($data, self::ENCRYPTION_METHOD, $password, OPENSSL_RAW_DATA, self::ENCRYPTION_IV);
							$connection->write(strlen($data) . "\n" . $data);

							// next msg
							$arr = explode("\n", $buffer, 2);
						}
					});
				});
				//TODO check if port is closed
				$this->socket->listen($socketPort, $this->maniaControl->getServer()->ip);

				Logger::log("[CommunicationManager] Socket " . $this->maniaControl->getServer()->ip . ":" . $this->socket->getPort() . " Successfully created!");
			} catch (ConnectionException $e) {
				Logger::log("[CommunicationManager] Exception: " . $e->getMessage());
			}
		}
	}


	/**
	 * Processes Data on every ManiaControl Tick, don't call this Method
	 */
	public function tick() {
		if ($this->loop) {
			$this->loop->tick();
		}

		foreach ($this->communications as $communication) {
			$communication->tick();
		}
	}
}