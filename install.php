<?php
/**
 * Address Property
 *
 * @package properties
 * @subpackage address property
 * @category Third Party Xaraya Property
 * @version 1.0.0
 * @copyright (C) 2011 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('properties.address.main');
sys::import('modules.dynamicdata.class.properties.interfaces');

class AddressPropertyInstall extends AddressProperty implements iDataPropertyInstall
{
    public function install(array $data = [])
    {
        $dat_file = sys::code() . 'properties/address/data/configurations-dat.xml';
        $data = ['file' => $dat_file];
        try {
            $objectid = xarMod::apiFunc('dynamicdata', 'util', 'import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }
}
