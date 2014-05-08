<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Structures;

class FileDesc extends AbstractStructure
{
	/** @var string */
	public $fileName;
	/** @var string */
	public $checksum;
}
