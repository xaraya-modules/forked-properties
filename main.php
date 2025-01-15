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

sys::import('modules.dynamicdata.class.properties.base');
sys::import('xaraya.structures.datetime');
sys::import('properties.datetime.data.formats');

class DateTimeProperty extends DataProperty
{
    public $id         = 30059;
    public $name       = 'datetime';
    public $desc       = 'DateTime';
    public $reqmodules = [];
    public $basetype   = 'time';

    // The default display is short uniersal display
    public $display_datetime_format_type   = 2;
    public $display_datetime_format_predef = 1;
    public $display_datetime_format_custom = 'c';
    public $initialization_encrypt         = false;
    public $initialization_timezone;
    public $initialization_start_year;
    public $initialization_end_year;

    // Allow for dropdowns or calendar (datetime-local) in HTML5
    public $input_type                     = 'dropdown';

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'auto';
        $this->template  = 'datetime';
        $this->filepath  = 'auto';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_' . $this->id : $name;

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
                [$isvalid, $hours] = $this->fetchValue($name . '["hour"]');
                [$isvalid, $minutes] = $this->fetchValue($name . '["minute"]');
                [$isvalid, $seconds] = $this->fetchValue($name . '["second"]');
            }
            if (!isset($years) || !isset($months) || !isset($days) || !isset($hours) || !isset($minutes) || !isset($seconds)) {
                $this->objectref->missingfields[] = $this->name;
                return null;
            }
            $value = mktime($hours, $minutes, $seconds, $months, $days, $years);
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
        $data['extraparams'] ??= "";

        // Anything that is not explicitly 'calendar' is considered 'dropdown' (the default)
        if ($data['input_type'] == 'dropdown') {

            $value = $this->getvaluearray($data);
            // Adjust for timezone
            $value['timestamp'] += $this->getOffset();
            $data['value'] = $value['timestamp'];
            $data['value'] = $this->getvaluearray($data);

            if($this->initialization_start_year == null) {
                $this->initialization_start_year =  min($data['value']['year'], date("Y")) - 5;
            }

            if($this->initialization_end_year == null) {
                $this->initialization_end_year = max($data['value']['year'], date("Y")) + 5;
            }

            $data['start_year'] ??= $this->initialization_start_year;
            $data['end_year'] ??= $this->initialization_end_year;

        } else {
            // Use the datetime-local input
            if (!isset($data['value'])) {
                $data['value'] = $this->value;
            }
            // Adjust for timezone
			if (empty($data['value'])) $data['value'] = 0;
            $data['value'] += $this->getOffset();
            // The format is important here: no timezones allowed, and set the seconds to 00
            $data['value'] = date('Y-m-d\TH:i:00', $data['value']);
        }
        return DataProperty::showInput($data);
    }

    /*
     *  This function outputs the following:
     *  date: The formated output string for display
     *  time: an array containing seconds, minutes, hours etc. of this date/time for further manipulation
     */
    public function showOutput(array $data = [])
    {
        if (isset($data['format_type'])) {
            $this->display_datetime_format_type   = $data['format_type'];
        }
        if (isset($data['format_predef'])) {
            $this->display_datetime_format_predef = $data['format_predef'];
        }
        if (isset($data['format_custom'])) {
            $this->display_datetime_format_custom = $data['format_custom'];
        }
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }
        if (empty($value)) {
            $value = time();
        }
        if (!is_array($value)) {
            // Adjust for timezone
            $value += $this->getOffset();

            $valuearray['date'] = $this->format($value);
            $valuearray['time'] = $this->getvaluearray(['value' => $value]);
        } else {
            // Adjust for timezone
            $value['second'] += $this->getOffset();
            $value['timestamp'] += $this->getOffset();

            $valuearray['date'] = $this->format($value['timestamp']);
            $valuearray['time'] = $value;
        }
        $data['value'] = $valuearray;
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

        // Not a good idea to force to time()
        //        $value = $value == 0 ? time() : $value;
        if (empty($value)) {
            $value = 0;
        }
        $date = new XarDateTime();
        $date->settimestamp($value);
        $valuearray['second']    = $date->getSecond();
        $valuearray['minute']    = $date->getMinute();
        $valuearray['hour']      = $date->getHour();
        $valuearray['day']       = $date->getDay();
        $valuearray['month']     = $date->getMonth();
        $valuearray['year']      = $date->getYear();
        $valuearray['timestamp'] = $value;

        return $valuearray;
    }

    public function format($value)
    {
        switch($this->display_datetime_format_type) {
            case 1:
            default:
                $date = xarLocale::getFormattedDate('short', $value, false);
                $time = xarLocale::getFormattedTime('short', $value, false);
                $value = $date . " " . $time;
                break;
            case 2:
                // If no format chosen, just return the raw value
                if (!empty($this->display_datetime_format_predef)) {
                    // Import the predefined display formats here
                    sys::import('properties.datetime.data.formats');
                    $formats = datetime_formats();
                    $value = date($formats[$this->display_datetime_format_predef]['format'], $value);
                }
                break;
            case 3:
                $value = date($this->display_datetime_format_custom, $value);
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
            $data['value']['second'] += $this->getOffset();
            $data['value']['timestamp'] += $this->getOffset();
        } else {
            // Use the datetime-local input
            if (!isset($data['value'])) {
                $data['value'] = $this->value;
            }
            // Adjust for timezone
            $data['value'] += $this->getOffset();
            // The format is important here: no timezones allowed, and set the seconds to 00
            $data['value'] = date('Y-m-d\TH:i:00', $data['value']);
        }
        return parent::showHidden($data);
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
                @eval('$timezone_code = ' . $this->initialization_timezone . ';');
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


    public function showConfiguration(array $data = [])
    {
        sys::import('properties.datetime.data.formats');
        return parent::showConfiguration($data);
    }
}
