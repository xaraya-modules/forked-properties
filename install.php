<?php
/**
 * Listing Property
 *
 * @package properties
 * @subpackage listing property
 * @copyright (C) 2010 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('properties.listing.main');
sys::import('modules.dynamicdata.class.properties.interfaces');

class ListingPropertyInstall extends ListingProperty implements iDataPropertyInstall
{
    public function install(array $data = [])
    {
        $dat_file = sys::code() . 'properties/listing/data/configurations-dat.xml';
        $data = ['file' => $dat_file];
        try {
            $objectid = xarMod::apiFunc('dynamicdata', 'util', 'import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }
}
