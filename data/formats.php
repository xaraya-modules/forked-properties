<?php

/**
 * Date Property
 *
 * @package properties
 * @subpackage date property
 * @category Third Party Xaraya Property
 * @version 1.0.0
 * @copyright (C) 2011 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

function date_formats()
{
    $formats[1] = ['id' => 1, 'name' => 'Universal - short', 'format' => 'Y-m-d', 'useoffset' => 0];
    $formats[2] = ['id' => 2, 'name' => 'US - short', 'format' => 'm-d-Y', 'useoffset' => 0];
    $formats[3] = ['id' => 3, 'name' => 'Euro - short', 'format' => 'd.m.Y', 'useoffset' => 0];
    $formats[4] = ['id' => 4, 'name' => 'Long', 'format' => 'r', 'useoffset' => 0];

    return $formats;
}
