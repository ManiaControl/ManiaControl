<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */
namespace Maniaplanet\DedicatedServer\Structures;

class Map extends AbstractStructure
{
	public $uId;
	public $name;
	public $fileName;
	public $author;
	public $environnement;
	public $mood;
	public $bronzeTime;
	public $silverTime;
	public $goldTime;
	public $authorTime;
	public $copperPrice;
	public $lapRace;
	public $nbLaps;
	public $nbCheckpoints;
	public $mapType;
	public $mapStyle;
}