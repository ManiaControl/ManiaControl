<?php

namespace FML\Types;

/**
 * Interface for Elements with script event attributes
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Scriptable
{

    /**
     * Get script events
     *
     * @api
     * @return bool
     */
    public function getScriptEvents();

    /**
     * Set script events
     *
     * @api
     * @param bool $scriptEvents If script events should be enabled
     * @return static
     */
    public function setScriptEvents($scriptEvents);

    /**
     * Get script action
     *
     * @api
     * @return string
     */
    public function getScriptAction();

    /**
     * Set script action
     *
     * @api
     * @param string   $scriptAction           Script action to be triggered
     * @param string[] $scriptActionParameters (optional) Script action parameters
     * @return static
     */
    public function setScriptAction($scriptAction, array $scriptActionParameters = null);

    /**
     * Get script action parameters
     *
     * @api
     * @return string[]
     */
    public function getScriptActionParameters();

    /**
     * Set script action parameters
     *
     * @api
     * @param string[] $scriptActionParameters (optional) Script action parameters
     * @return static
     */
    public function setScriptActionParameters(array $scriptActionParameters = null);

}
