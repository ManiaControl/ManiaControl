<?php
namespace Dedimania;

require_once "DedimaniaData.php";
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;

class Dedimania implements CallbackListener, TimerListener, Plugin {
	/**
	 * Constants
	 */
	const XMLRPC_MULTICALL              = 'system.multicall';
	const DEDIMANIA_URL                 = 'http://dedimania.net:8081/Dedimania';
	const DEDIMANIA_OPENSESSION         = 'dedimania.OpenSession';
	const DEDIMANIA_CHECKSESSION        = 'dedimania.CheckSession';
	const DEDIMANIA_GETRECORDS          = 'dedimania.GetChallengeRecords';
	const DEDIMANIA_PLAYERCONNECT       = 'dedimania.PlayerConnect';
	const DEDIMANIA_PLAYERDISCONNECT    = 'dedimania.PlayerDisconnect';
	const DEDIMANIA_UPDATESERVERPLAYERS = 'dedimania.UpdateServerPlayers';
	const DEDIMANIA_SETCHALLENGETIMES   = 'dedimania.SetChallengeTimes';
	const DEDIMANIA_WARNINGSANDTTR2     = 'dedimania.WarningsAndTTR2';

	/**
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var DedimaniaData $dedimaniaData */
	private $dedimaniaData = null;

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		// TODO: Implement prepare() method.
	}

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		return;

		$this->openDedimaniaSession(true);
	}

	private function openDedimaniaSession($init = false) {
		// Open session
		$serverInfo          = $this->maniaControl->server->getInfo();
		$serverVersion       = $this->maniaControl->client->getVersion();
		$packMask            = substr($this->maniaControl->server->titleId, 2);
		$this->dedimaniaData = new DedimaniaData("abc", "cde", $serverInfo->path, $packMask, $serverVersion);


		$url = self::DEDIMANIA_URL;

		//TODO make postFile method in FileReader
		$urlData = parse_url($url);

		$content = gzcompress($this->encode_request(self::DEDIMANIA_OPENSESSION, array($this->dedimaniaData->toArray())));

		$query = 'POST ' . $urlData['path'] . ' HTTP/1.1' . PHP_EOL;
		$query .= 'Host: ' . $urlData['host'] . PHP_EOL;
		$query .= 'Accept-Charset: utf-8;' . PHP_EOL;
		$query .= 'Accept-Encoding: gzip;' . PHP_EOL;
		$query .= 'Content-Type: text/xml; charset=utf-8;' . PHP_EOL;
		$query .= 'Keep-Alive: 300;' . PHP_EOL;
		$query .= 'User-Agent: ManiaControl v' . ManiaControl::VERSION . PHP_EOL;
		$query .= 'Content-Length: ' . strlen($content) . PHP_EOL . PHP_EOL;
		$query .= $content . PHP_EOL;


		$this->maniaControl->fileReader->loadFile($url, function ($data, $error) {
			var_dump($data);
			var_dump($error);

		}, 'UTF-8', $query);

	}

	/**
	 * Encodes the given xml rpc method and params
	 *
	 * @param string $method
	 * @param array  $params
	 * @return string
	 */
	private function encode_request($method, $params) {
		$paramArray = array(array('methodName' => $method, 'params' => $params), array('methodName' => self::DEDIMANIA_WARNINGSANDTTR2, 'params' => array()));
		return xmlrpc_encode_request(self::XMLRPC_MULTICALL, array($paramArray), array('encoding' => 'UTF-8', 'escaping' => 'markup'));
	}

	/**
	 * Decodes xml rpc response
	 *
	 * @param string $response
	 * @return mixed
	 */
	private function decode($response) {
		return xmlrpc_decode($response, 'utf-8');
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
		unset($this->maniaControl);
	}

	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId() {
		// TODO: Implement getId() method.
	}

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName() {
		// TODO: Implement getName() method.
	}

	/**
	 * Get Plugin Version
	 *
	 * @return float
	 */
	public static function getVersion() {
		// TODO: Implement getVersion() method.
	}

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor() {
		// TODO: Implement getAuthor() method.
	}

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription() {
		// TODO: Implement getDescription() method.
	}
}