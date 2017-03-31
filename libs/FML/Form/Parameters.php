<?php

namespace FML\Form;

/**
 * Parameters Class
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Parameters
{

    /**
     * Get the submitted form value
     *
     * @param string $name Value name
     * @return string
     */
    public static function getValue($name)
    {
        if (array_key_exists($name, $_GET)) {
            return $_GET[$name];
        }
        if (array_key_exists($name, $_POST)) {
            return $_POST[$name];
        }
        return null;
    }

}
