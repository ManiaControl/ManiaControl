<?php
namespace ManiaControl\Callbacks;

/**
 * Callbacks Interface
 *
 * @author    steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Callbacks {
	const ScriptCallback       = 'Callbacks.ScriptCallback';
	/** BeginMatch Callback, param1 MapNumber */
	const LibXmlRpc_BeginMatch = "Callbacks.LibXmlRpc_BeginMatch";
	/** BeginMatch Callback, param1 MapNumber */
	const LibXmlRpc_LoadingMap = "Callbacks.LibXmlRpc_LoadingMap";
	/** BeginMatch Callback, param1 MapNumber */
	const LibXmlRpc_BeginMap   = "Callbacks.LibXmlRpc_BeginMap";
} 