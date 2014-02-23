<?php
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;

/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 23.02.14
 * Time: 14:03
 */
class KarmaTest implements Plugin {
	const MX_KARMA_URL             = 'http://karma.mania-exchange.com/api2/';
	const MX_KARMA_STARTSESSION    = 'startSession';
	const MX_KARMA_ACTIVATESESSION = 'activateSession';
	const MX_KARMA_SAVEVOTES = 'saveVotes';

	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

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
		$this->openSession();
	}

	private function openSession() {
		//$serverLogin           = $this->maniaControl->server->login;
		$serverLogin           = ".escsiege";
		$applicationIdentifier = 'ManiaControl v' . ManiaControl::VERSION;
		$testMode              = 'true';

		$query = self::MX_KARMA_URL . self::MX_KARMA_STARTSESSION;
		$query .= '?serverLogin=' . $serverLogin;
		$query .= '&applicationIdentifier=' . urlencode($applicationIdentifier);
		$query .= '&game=sm';
		$query .= '&testMode=' . $testMode;


		$this->maniaControl->fileReader->loadFile($query, function ($data, $error) {
			var_dump($error);
			if (!$error) {
				$data = json_decode($data);
				if ($data->success) {
					$this->activateSession($data->data->sessionKey, $data->data->sessionSeed);
				} else {
					$this->maniaControl->log("Error while authenticating on Mania-Exchange Karma");
				}
			} else {
				$this->maniaControl->log($error);
			}
		}, "application/json", 1000);
	}

	private function activateSession($sessionKey, $sessionSeed) {
		$hash = $this->buildActivationHash($sessionSeed, "XNTOWRNVQCKEUBBKXNJSCPYOAA");

		$query = self::MX_KARMA_URL . self::MX_KARMA_ACTIVATESESSION;
		$query .= '?sessionKey=' . urlencode($sessionKey);
		$query .= '&activationHash=' . urlencode($hash);

		var_dump($query);

		$this->maniaControl->fileReader->loadFile($query, function ($data, $error) use ($sessionKey) {
			if (!$error) {
				$data = json_decode($data);
				var_dump($data);
				if ($data->success && $data->data->activated) {
					$this->maniaControl->log("Successfully authenticated on Mania-Exchange Karma");

					$this->saveVotes($sessionKey);
				} else {
					$this->maniaControl->log("Error while authenticating on Mania-Exchange Karma");
				}
			} else {
				$this->maniaControl->log($error);
			}
		}, "application/json", 1000);

	}

	private function saveVotes($sessionKey){
		$gameMode = $this->maniaControl->server->getGameMode(true);

		$properties = array();
		if($gameMode == 'Script'){
			$scriptName               = $this->maniaControl->client->getScriptName();
			$properties['gamemode'] = $scriptName["CurrentValue"];
		}else{
			$properties['gamemode'] = $gameMode;
		}

		$properties['titleid'] = $this->maniaControl->server->titleId;

		$map = $this->maniaControl->mapManager->getCurrentMap();
		$properties['mapname'] = $map->name;
		$properties['mapuid'] = $map->uid;
		$properties['mapauthor'] = $map->authorLogin;
		$properties['votes'] = array();

		$content = json_encode($properties);

		$this->maniaControl->fileReader->postData(self::MX_KARMA_URL.self::MX_KARMA_SAVEVOTES . "?sessionKey=" . $sessionKey , function ($data, $error) use ($sessionKey) {
			if (!$error) {
				$data = json_decode($data);
				var_dump($data);
				if ($data->success && $data->data->activated) {
					$this->maniaControl->log("Successfully authenticated on Mania-Exchange Karma");

					$this->saveVotes($sessionKey);
				} else {
					$this->maniaControl->log("Error while authenticating on Mania-Exchange Karma");
				}
			} else {
				$this->maniaControl->log($error);
			}
		}, $content, false, 'application/json');



	}

	private function buildActivationHash($sessionSeed, $mxKey) {
		return hash('sha512', $mxKey . $sessionSeed);
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
		return "karmatest";
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