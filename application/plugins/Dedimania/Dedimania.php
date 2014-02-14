<?php

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Plugins\Plugin;

class Dedimania implements CallbackListener, TimerListener, Plugin {
	/**
	 * Private Properties
	 */
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
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
		// TODO: Implement unload() method.
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
}}