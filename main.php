<?php
/**
 * Province Property
 * @package math
 * @copyright (C) 2010 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */
sys::import('modules.base.xarproperties.dropdown');

/**
 * Handle the Province property
 *
 * Show a dropdown of provinces for a given country
 */
class ProvinceProperty extends SelectProperty
{
    public $id         = 30105;
    public $name       = 'province';
    public $desc       = 'Province';
    public $reqmodules = array();

    public $initialization_province_country = 'us';
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);

        $this->filepath   = 'auto';
    }

    public function showInput(Array $data = array())
    {
        if (empty($data['country'])) $data['country'] = $this->initialization_province_country;
        $data['options'] = $this->getFirstline();
        try {
            sys::import('properties.province.data.' . $data['country']);
            $data['options'] = array_merge($data['options'], getProvinces());
        } catch (Exception $e) {}
        
        return parent::showInput($data);
    }
 }

?>