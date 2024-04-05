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

sys::import('properties.datetime.main');
sys::import('modules.dynamicdata.class.properties.interfaces');

class DateTimePropertyInstall extends DateTimeProperty implements iDataPropertyInstall
{
    public function install(array $data = [])
    {
        $dat_file = sys::code() . 'properties/datetime/data/configurations-dat.xml';
        $data = ['file' => $dat_file];
        try {
            $objectid = xarMod::apiFunc('dynamicdata', 'util', 'import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }
}
