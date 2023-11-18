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

sys::import('modules.dynamicdata.class.properties.base');
sys::import('xaraya.structures.datetime');
sys::import('properties.date.data.formats');

class DateProperty extends DataProperty
{
    public $id         = 30022;
    public $name       = 'date';
    public $desc       = 'Date';
    public $reqmodules = [];
    public $basetype   = 'date';

    // The default display is short uniersal display
    public $display_date_format_type   = 2;
    public $display_date_format_predef = 1;
    public $display_date_format_custom = 'c';
    public $initialization_encrypt     = false;
    public $initialization_timezone;
    public $display_start_year;
    public $display_end_year;

    // Allow for dropdowns or calendar (datetime-local) in HTML5
    public $input_type                     = 'dropdown';

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'auto';
        $this->template  = 'date';
        $this->filepath  = 'auto';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;

        // Get the input type flag from the template so we know how to proceed
        if (!xarVar::fetch('input_type_' . $name, 'str:1:100', $input_type, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (empty($input_type)) {
            $input_type = $this->input_type;
        }

        // Anything that is not explicitly 'calendar' is considered 'dropdown' (the default)
        if ($input_type == 'dropdown') {
            // store the fieldname for validations who need them (e.g. file uploads)
            $this->fieldname = $name;
            if (!isset($value)) {
                [$isvalid, $years] = $this->fetchValue($name . '["year"]');
                [$isvalid, $months] = $this->fetchValue($name . '["month"]');
                [$isvalid, $days] = $this->fetchValue($name . '["day"]');
            }
            if (!isset($years) || !isset($months) || !isset($days)) {
                $this->objectref->missingfields[] = $this->name;
                return null;
            }
            $value = mktime(0, 0, 0, $months, $days, $years);
        } else {
            // Get the date value from a datetime-local input
            if (!xarVar::fetch($name, 'str:1:100', $template_value, '', xarVar::NOT_REQUIRED)) {
                return;
            }
            if ($template_value != '') {
                $value = strtotime($template_value);
            } else {
                $value = $template_value;
            }
        }

        // Adjust the value for a timezone offset, if it exists
        $value -= $this->getOffset();

        return $this->validateValue($value);
    }

    public function showInput(array $data = [])
    {
        // Send this value to the template so it knows what to display
        if (!isset($data['input_type'])) {
            $data['input_type'] = $this->input_type;
        }

        if (!isset($data['onchange'])) {
            $data['onchange'] = null;
        } // let tpl decide what to do
        $data['extraparams'] = !empty($extraparams) ? $extraparams : "";

        // Anything that is not explicitly 'calendar' is considered 'dropdown' (the default)
        if ($data['input_type'] == 'dropdown') {
            $data['value'] = $this->getvaluearray($data);
            // Adjust for timezone
            $data['value']['timestamp'] += $this->getOffset();

            if ($this->display_start_year == null) {
                $this->display_start_year =  min($data['value']['year'], date("Y")) - 5;
            }

            if ($this->display_end_year == null) {
                $this->display_end_year = max($data['value']['year'], date("Y")) + 5;
            }

            $data['start_year'] ??= $this->display_start_year;
            $data['end_year'] ??= $this->display_end_year;
        } else {
            // Use the datetime-local input
            if (!isset($data['value'])) {
                $data['value'] = $this->value;
            }
            // Adjust for timezone
            $data['value'] += $this->getOffset();
            // The format is important here:
            $data['value'] = date('Y-m-d', $data['value']);
        }

        return DataProperty::showInput($data);
    }

    public function showOutput(array $data = [])
    {
        if (isset($data['format_type'])) {
            $this->display_date_format_type   = $data['format_type'];
        }
        if (isset($data['format_predef'])) {
            $this->display_date_format_predef = $data['format_predef'];
        }
        if (isset($data['format_custom'])) {
            $this->display_date_format_custom = $data['format_custom'];
        }

        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }
        if (empty($value)) {
            $value = time();
        }
        if (is_array($value)) {
            // An array was passed
            $timestamp = mktime(0, 0, 0, $value['month'], $value['day'], $value['year']);

            // Adjust for timezone
            $timestamp += $this->getOffset();

            $value['date'] = $this->format($timestamp);
            $data['value'] = $value;
        } else {
            // A timestamp was passed
            $date = new XarDateTime();

            // Adjust for timezone
            $value += $this->getOffset();

            $date->settimestamp($value);
            $valuearray['day'] = $date->getDay();
            $valuearray['month'] = $date->getMonth();
            $valuearray['year'] = $date->getYear();
            $valuearray['date'] = $this->format($value);
            $data['value'] = $valuearray;
        }

        return DataProperty::showOutput($data);
    }

    public function getValue()
    {
        $value = $this->value;
        $value = !empty($value) ? $value : 0;

        // Adjust for timezone
        $value += $this->getOffset();
        return $this->format($value);
    }

    public function getvaluearray($data)
    {
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }

        // This is already a time array
        if (is_array($value)) {
            return $value;
        }

        if (empty($value)) {
            $value = 0;
        }
        // Not a good ideea to force to time()
        //        $value = $value == 0 ? time() : $value;
        if (empty($value)) {
            $value = 0;
        }
        $date = new XarDateTime();
        $date->settimestamp($value);
        $valuearray['day'] = $date->getDay();
        $valuearray['month'] = $date->getMonth();
        $valuearray['year'] = $date->getYear();
        $valuearray['timestamp'] = $value;

        return $valuearray;
    }

    public function format($value)
    {
        switch($this->display_date_format_type) {
            case 1:
            default:
                $value = xarLocale::getFormattedDate('short', $value, false);
                break;
            case 2:
                // If no format chosen, just return the raw value
                if (!empty($this->display_date_format_predef)) {
                    // Import the predefined display formats here
                    sys::import('properties.date.data.formats');
                    $formats = date_formats();
                    $value = date($formats[$this->display_date_format_predef]['format'], $value);
                }
                break;
            case 3:
                $value = date($this->display_date_format_custom, $value);
                break;
        }
        return $value;
    }

    public function showHidden(array $data = [])
    {
        // Send this value to the template so it knows what to display
        if (!isset($data['input_type'])) {
            $data['input_type'] = $this->input_type;
        }

        // Anything that is not explicitly 'calendar' is considered 'dropdown' (the default)
        if ($data['input_type'] == 'dropdown') {
            $data['value'] = $this->getvaluearray($data);
            $data['value']['timestamp'] += $this->getOffset();
        } else {
            // Use the datetime-local input
            if (!isset($data['value'])) {
                $data['value'] = $this->value;
            }
            // Adjust for timezone
            $data['value'] = !empty($value) ? $value : 0;
            $data['value'] += $this->getOffset();
            // The format is important here:
            $data['value'] = date('YYYY-mm-dd', $data['value']);
        }
        return parent::showHidden($data);
    }

    public function daymonth_isgt($timestamp = null)
    {
        $this_date = $this->get_daymonth_timestamp();
        $that_date = $this->get_daymonth_timestamp($timestamp);
        return $that_date > $this_date;
    }

    public function daymonth_islt($timestamp = null)
    {
        $this_date = $this->get_daymonth_timestamp();
        $that_date = $this->get_daymonth_timestamp($timestamp);
        return $that_date < $this_date;
    }

    public function daymonth_iseq($timestamp = null)
    {
        $this_date = $this->get_daymonth_timestamp();
        $that_date = $this->get_daymonth_timestamp($timestamp);
        return $that_date == $this_date;
    }

    private function get_daymonth_timestamp($timestamp = null)
    {
        $date = new XarDateTime();
        $this_date = $this->getvaluearray($timestamp);
        $date->day = $this_date['day'];
        $date->month = $this_date['month'];
        $this_timestamp = $date->getTimestamp();
        return $this_timestamp;
    }
    /*
     *  Support for time zone if it exists
     * This function gets the offset in seconds to universal time
     */
    public function getOffset()
    {
        if (empty($this->initialization_timezone)) {
            return 0;
        } else {
            // Check for a xar function
            if (strpos($this->initialization_timezone, 'xar') === 0) {
                @eval('$timezone_code = ' . $this->initialization_timezone .';');
            } else {
                // Do nothing nothing else for now
                $timezone_code = $this->initialization_timezone;
            }

            try {
                $timezone = new DateTimeZone($timezone_code);
                $time = new DateTime("now", $timezone);
                $offset = $timezone->getOffset($time);
                return $offset;
            } catch (Exception $e) {
                return 0;
            }
        }

    }
}
