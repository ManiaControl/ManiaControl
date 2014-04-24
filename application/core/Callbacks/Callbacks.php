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
	const BeginMatch = "Callbacks.BeginMatch";
	/** LoadingMap Callback, param1 MapNumber */
	const LoadingMap = "Callbacks.LoadingMap";
	/** BeginMap Callback, param1 MapNumber */
	const BeginMap   = "Callbacks.BeginMap";
} 