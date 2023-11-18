<?php

/**
 * Language Property
 *
 * @package properties
 * @subpackage language property
 * @category Third Party Xaraya Property
 * @version 1.0.0
 * @copyright (C) 2011 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.dynamicdata.xarproperties.objectref');

class LanguagesProperty extends ObjectRefProperty
{
    public $id         = 30039;
    public $name       = 'languages';
    public $desc       = 'Languages';
    public $reqmodules = [];

    public $initialization_refobject    = 'languages';
    public $initialization_store_prop   = 'locale';
    public $initialization_display_prop = 'name';

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'auto';
    }

    public function showInput(array $data=[])
    {
        if (!empty($data['store_prop'])) {
            $this->initialization_store_prop = $data['store_prop'];
        }
        if (!empty($data['display_prop'])) {
            $this->initialization_display_prop = $data['display_prop'];
        }
        return parent::showInput($data);
    }

    public function showOutput(array $data=[])
    {
        if (!empty($data['store_prop'])) {
            $this->initialization_store_prop = $data['store_prop'];
        }
        if (!empty($data['display_prop'])) {
            $this->initialization_display_prop = $data['display_prop'];
        }
        return parent::showOutput($data);
    }

    public function getOptions()
    {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) {
                $this->options = array_merge($options, $this->options);
            }
            return $this->options;
        }

        sys::import('modules.dynamicdata.class.properties.master');
        /** @var ObjectRefProperty $property */
        $property = DataPropertyMaster::getProperty(['name' => 'objectref']);
        $property->initialization_refobject = 'languages';
        $property->initialization_store_prop = $this->initialization_store_prop;
        $property->initialization_display_prop = $this->initialization_display_prop;
        $options = $property->getOptions();
        return $options;
    }
}
