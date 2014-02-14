<?php
/**
 * Currency Property
 *
 * @package properties
 * @subpackage currency property
 * @category Third Party Xaraya Property
 * @version 1.0.0
 * @copyright (C) 2011 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('properties.currency.main');
sys::import('modules.dynamicdata.class.properties.interfaces');

class CurrencyPropertyInstall extends CurrencyProperty implements iDataPropertyInstall
{

    public function install(Array $data=array())
    {
        if (!DataObjectMaster::isObject(array('name' => 'currencies'))) {
            $files[] = sys::code() . 'properties/currency/data/currency-def.xml';
            $files[] = sys::code() . 'properties/currency/data/currency-dat.xml';
            foreach ($files as $file) {
                try {
                    $objectid = xarMod::apiFunc('dynamicdata','util','import', array('file' => $file));
                } catch (Exception $e) {
                    // We only load the object once
                    break;
                }
            }
        }
        return true;
    } 
}

?>