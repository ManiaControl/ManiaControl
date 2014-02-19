<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace Maniaplanet\DedicatedServer\Xmlrpc;

class FatalException extends Exception 
{
	const NOT_INITIALIZED = 1;
	const INTERRUPTED = 2;
	const OTHER = 999;
}

?>