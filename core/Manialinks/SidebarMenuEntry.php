<?php

namespace ManiaControl\Manialinks;


use ManiaControl\Callbacks\Listening;

class SidebarMenuEntry extends Listening {
		private $id;

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id) {
		$this->id = $id;
	}
}