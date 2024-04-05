<?php
/**
 * DateTime Property
 *
 * @package properties
 * @subpackage datetime property
 * @category Third Party Xaraya Property
 * @version 1.0.0
 * @copyright (C) 2011 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

function datetime_hours()
{
    $options = [];
    for ($i = 0;$i < 24;$i++) {
        $hour1 = "00" . $i;
        $hour1 = substr($hour1, strlen($hour1) - 2);
        $hour2 = "00" . ($i + 1);
        $hour2 = substr($hour2, strlen($hour2) - 2);
        $options[] = ['id' => $i * 60, 'name' => $hour1 . ':00 - ' . $hour2 . ':00'];
    }
    return $options;
}
