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

/**
 * The property's value is stored as a serialized array of the form
 * array(
 *     [array('id' => <field name>, 'value' => <field value>)]      (one or more elements)
 *
 * The components the property can have are of the form
 * array(
 *     [array('id' => <component name>, 'name' => <component label>)]      (one or more elements)
 * Default components displayed are: street, street2, city, region, postal_code, country
 * These are given by $this->display_address_components and can be configured
 *
 * The property has a notion of country and will display a country listing  when it encounters a field called "country"
 * The default layout displays all the fields in a column one below the other in the order configured
 * The layout "country" displays the fields in a country specific layout. In this case the fields must have names
 * that the property recognizes, and these names can vary according to the country template.
 * If the layout is defined as "country" the property will try and use the specific display associated with the
 * value of the country field, or fall back to the default display if no such display exists.
 *
 * The difference between the value arrays for country specific and default layouts stems primarily from the fact that
 * in the case of the latter, the sequence of fields is given by the order in which they appear in the configuration.
 */

sys::import('modules.base.xarproperties.textbox');

class AddressProperty extends TextBoxProperty
{
    public $id         = 30033;
    public $name       = 'address';
    public $desc       = 'Address';
    public $reqmodules = [];

    public $display_address_components;
    public $display_address_default_country = '';
    public $display_country_layout          = false;
    public $validation_ignore_validations   = true;
    public $validation_allowempty = true;

    public $specified_countries       = ['ch','us'];   // The countries that have non-default layout templates in this property

    public function __construct(ObjectDescriptor $descriptor)
    {
        $this->display_address_components = 'street,' . xarML('Street') .
                                            ';street2,' . xarML('Street') .
                                            ';city,' . xarML('City') .
                                            ';postal_code,' . xarML('Postal Code') .
                                            ';region,' . xarML('Region') .
                                            ';country,' . xarML('Country') . ';';

        parent::__construct($descriptor);
        $this->tplmodule = 'auto';
        $this->template =  'address';
        $this->filepath   = 'auto';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        $valid = true;
        $invalid = [];
        $value = [];   // We don't allow a value to be passed to this method

        if (!empty($this->display_address_components)) {
            $textbox = DataPropertyMaster::getProperty(['name' => 'textbox']);
            $textbox->validation_allowempty = $this->validation_allowempty;
            $address_components = $this->getAddressComponents($this->display_address_components);
            if (!$this->validation_ignore_validations) {
                $textbox->validation_min_length = 2;
            }
            foreach ($address_components as $field) {
                $isvalid = $textbox->checkInput($name . '["' . $field['id'] . '"]');
                $valid = $valid && $isvalid;
                if ($isvalid) {
                    $value[] = ['id' => $field['id'], 'value' => $textbox->value];
                } else {
                    if (empty($field['name'])) {
                        $invalid[] = strtolower($field['id']);
                    } else {
                        $invalid[] = strtolower($field['name']);
                    }
                }
            }
        }

        if ($valid) {
            $this->setValue($value);
        } else {
            $this->value = null;
            $count = count($invalid);
            $invalid = implode(',', $invalid);
            if ($count == 1) {
                $this->invalid = xarML('The field #(1) is not valid', $invalid);
            } else {
                $this->invalid = xarML('The fields #(1) are not valid', $invalid);
            }
        }
        return $valid;
    }

    public function validateValue($value = null)
    {
        // Dummy method
        xarLog::message("DataProperty::validateValue: Validating property " . $this->name, xarLog::LEVEL_INFO);
        return true;
    }

    public function getValue()
    {
        $valuearray = $this->getValueArray();
        $value = '';
        foreach ($valuearray as $part) {
            try {
                if ($part['id'] == 'country') {
                    $country = DataPropertyMaster::getProperty(['name' => 'countrylisting']);
                    $country->validation_override = true;
                    $country->value = $part['value'];
                    $part['value'] = $country->getOption();
                }
                $tempvalue = trim($part['value']);
                if (empty($tempvalue)) {
                    continue;
                }
                if (empty($value)) {
                    $value = $tempvalue;
                } else {
                    $value .= ', ' . $tempvalue;
                }
            } catch (Exception $e) {
            }
        }
        return $value;
    }

    public function setValue($value=null)
    {
        if (empty($value)) {
            $value = [];
        }
        $this->value = serialize($value);
    }

    public function showInput(array $data = [])
    {
        if (isset($data['module'])) {
            $this->module = $data['module'];
        } else {
            $info = xarController::$request->getInfo();
            $this->module = $info[0];
            $data['module'] = $this->module;
        }
        if (empty($data['address_components'])) {
            $data['address_components'] = $this->display_address_components;
        } else {
            $this->display_address_components = $data['address_components'];
        }
        $data['address_components'] = $this->getAddressComponents($data['address_components']);

        if (isset($data['value'])) {
            $this->value = $data['value'];
        }
        $data['value'] = $this->getValueArray();

        // Pass the raw value in case we need to debug
        $data['rawvalue'] = $this->value;

        // Cater to values as simple strings (errors, old versions etc.)
        if (!is_array($data['value'])) {
            $data['value'] = [['id' => 'street', 'value' => $data['value']]];
        }

        // Check if we should use country layouts
        if ($this->display_country_layout) {
            $data['layout'] = 'country';
        }
        // The setting can be overridden
        if (empty($data['layout'])) {
            $data['layout'] = $this->display_layout;
        } else {
            $this->display_layout = $data['layout'];
        }

        // For country specific layouts we need to reformat the value array
        if ($data['layout'] == 'country') {
            $newvalue = [];
            foreach ($data['value'] as $value) {
                $newvalue[$value['id']] = $value;
            }
            foreach ($data['address_components'] as $component) {
                $newvalue[$component['id']]['label'] = $component['name'];
            }
            $data['value'] = $newvalue;
            if (!empty($data['value']['country']['value']) && file_exists(sys::code() . 'properties/address/xartemplates/includes/' . $data['value']['country']['value'] . '-input.xt')) {
                $data['country_template'] = $data['value']['country']['value'] . '-input';
            } else {
                $data['country_template'] = 'default-input';
            }
        }

        // Get an instance of hte country dropdown for the template
        $data['countrylisting'] = DataPropertyMaster::getProperty(['name' => 'countrylisting']);

        if (!empty($this->display_address_default_country)) {
            // Assign the default value to the property's country dropdown
            foreach ($data['value'] as $key => $value) {
                if (($value['id'] == 'country') && empty($value['value'])) {
                    $data['value'][$key]['value'] = $this->display_address_default_country;
                }
            }
        } else {
            $data['countrylisting']->validation_override = true;
        }

        // Send this value to the template
        $data['default_country'] = $this->display_address_default_country;

        return DataProperty::showInput($data);
    }

    public function showOutput(array $data = [])
    {
        if (isset($data['module'])) {
            $this->module = $data['module'];
        } else {
            $info = xarController::$request->getInfo();
            $this->module = $info[0];
            $data['module'] = $this->module;
        }
        if (empty($data['address_components'])) {
            $data['address_components'] = $this->display_address_components;
        } else {
            $this->display_address_components = $data['address_components'];
        }
        $data['address_components'] = $this->getAddressComponents($data['address_components']);

        if (isset($data['value'])) {
            $this->value = $data['value'];
        }

        // Check if we should use country layouts
        if ($this->display_country_layout) {
            $data['layout'] = 'country';
        }
        // The setting can be overridden
        if (empty($data['layout'])) {
            $data['layout'] = $this->display_layout;
        } else {
            $this->display_layout = $data['layout'];
        }

        // For country specific layouts we need to reformat the value array
        if ($data['layout'] == 'country') {
            $data['value'] = $this->getValueArray();
            $newvalue = [];
            foreach ($data['value'] as $value) {
                $newvalue[$value['id']]['value'] = $value['value'];
            }
            foreach ($data['address_components'] as $component) {
                $newvalue[$component['id']]['label'] = $component['name'];
            }
            $data['value'] = $newvalue;
            if (!empty($data['value']['country']['value']) && file_exists(sys::code() . 'properties/address/xartemplates/includes/' . $data['value']['country']['value'] . '-output.xt')) {
                $data['country_template'] = $data['value']['country']['value'] . '-output';
            } else {
                $data['country_template'] = 'default-output';
            }
        } else {
            $data['value'] = $this->getValue();
        }

        return DataProperty::showOutput($data);
    }

    public function getValueArray()
    {
        $value = @unserialize($this->value);

        if (!is_array($value)) {
            return [];
        }

        // Cater to old array definitions
        reset($value);
        $first_row = current($value);
        if (!is_array($first_row)) {
            $reworked_array = [];
            foreach ($value as $k => $v) {
                $reworked_array[] = ['id' => $k, 'value' => $v];
            }
            $value = $reworked_array;
        }
        // Rework old definitions of street address components
        foreach ($value as $k => $v) {
            if ($v['id'] == 'line1') {
                $v['id'] = 'street';
            }
            if ($v['id'] == 'line2') {
                $v['id'] = 'street2';
            }
            if ($v['id'] == 'line_1') {
                $v['id'] = 'street';
            }
            if ($v['id'] == 'line_2') {
                $v['id'] = 'street2';
            }
            $value[$k] = $v;
        }

        $components = $this->getAddressComponents($this->display_address_components);
        $valuearray = [];
        foreach ($components as $v) {
            $found = false;
            foreach ($value as $part) {
                if (isset($part['id']) && ($part['id'] == $v['id'])) {
                    if (empty($part['value'])) {
                        $part['value'] = '';
                    }
                    $valuearray[] = ['id' => $v['id'], 'value' => $part['value']];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $valuearray[] = ['id' => $v['id'], 'value' => ''];
            }
        }

        return $valuearray;
    }

    public function getAddressComponents($componentstring)
    {
        $components = explode(';', $componentstring);
        // remove the last (empty) element
        array_pop($components);
        $componentarray = [];
        foreach ($components as $component) {
            // allow escaping \, for values that need a comma
            if (preg_match('/(?<!\\\),/', $component)) {
                // if the component contains a , we'll assume it's an name/displayname combination
                [$name, $displayname] = preg_split('/(?<!\\\),/', $component);
                $name = trim(strtr($name, ['\,' => ',']));
                $displayname = trim(strtr($displayname, ['\,' => ',']));
                $componentarray[] = ['id' => $name, 'name' => $displayname];
            } else {
                // otherwise we'll use the component for both name and displayname
                $component = trim(strtr($component, ['\,' => ',']));
                $componentarray[] = ['id' => $component, 'name' => $component];
            }
        }
        return $componentarray;
    }

    public function showHidden(array $data = [])
    {
        if (isset($data['module'])) {
            $this->module = $data['module'];
        } else {
            $info = xarController::$request->getInfo();
            $this->module = $info[0];
            $data['module'] = $this->module;
        }
        if (empty($data['address_components'])) {
            $data['address_components'] = $this->display_address_components;
        } else {
            $this->display_address_components = $data['address_components'];
        }
        $data['address_components'] = $this->getAddressComponents($data['address_components']);

        if (isset($data['value'])) {
            $this->value = $data['value'];
        }
        $data['value'] = $this->getValueArray();

        // Pass the raw value in case we need to debug
        $data['rawvalue'] = $this->value;

        // Cater to values as simple strings (errors, old versions etc.)
        if (!is_array($data['value'])) {
            $data['value'] = [['id' => 'street', 'value' => $data['value']]];
        }

        // For country specific layouts we need to reformat the value array
        if (empty($data['layout'])) {
            $data['layout'] = $this->display_layout;
        } else {
            $this->display_layout = $data['layout'];
        }
        if ($data['layout'] == 'country') {
            $newvalue = [];
            foreach ($data['value'] as $value) {
                $newvalue[$value['id']] = $value;
            }
            foreach ($data['address_components'] as $component) {
                $newvalue[$component['id']]['label'] = $component['name'];
            }
            $data['value'] = $newvalue;
            if (!empty($data['value']['country']['value']) && file_exists(sys::code() . 'properties/address/xartemplates/includes/' . $data['value']['country']['value'] . '-input.xt')) {
                $data['country_template'] = $data['value']['country']['value'] . '-input';
            } else {
                $data['country_template'] = 'default-input';
            }
        }
        if (!empty($this->display_address_default_country)) {
            foreach ($data['value'] as $key => $value) {
                if (($value['id'] == 'country') && empty($value['value'])) {
                    $data['value'][$key]['value'] = $this->display_address_default_country;
                }
            }
        }
        return DataProperty::showHidden($data);
    }
}
