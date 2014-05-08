<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Structures;

class Music extends AbstractStructure
{
	/** var bool */
	public $override;
	/** var string */
	public $url;
	/** var string */
	public $file;
}
