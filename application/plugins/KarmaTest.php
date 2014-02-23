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

		$serverLogin = $this->maniaControl->server->login;

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