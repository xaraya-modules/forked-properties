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

class DateTimeProperty extends DataProperty
{
    public $id         = 30059;
    public $name       = 'datetime';
    public $desc       = 'DateTime';
    public $reqmodules = array();
    public $basetype   = 'time';

    // The default display is short uniersal display
    public $display_datetime_format_type   = 2;
    public $display_datetime_format_predef = 1;
    public $display_datetime_format_custom = 'c';
    public $initialization_encrypt         = false;
    public $initialization_start_year;
    public $initialization_end_year;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'auto';
        $this->template =  'datetime';
        $this->filepath   = 'auto';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            list($isvalid, $years) = $this->fetchValue($name . '_year');
            list($isvalid, $months) = $this->fetchValue($name . '_month');
            list($isvalid, $days) = $this->fetchValue($name . '_day');
            list($isvalid, $hours) = $this->fetchValue($name . '_hour');
            list($isvalid, $minutes) = $this->fetchValue($name . '_minute');
            list($isvalid, $seconds) = $this->fetchValue($name . '_second');
        }
        if (!isset($years) ||!isset($months) ||!isset($days) ||!isset($hours) ||!isset($minutes) ||!isset($seconds)) {
            $this->objectref->missingfields[] = $this->name;
            return null;
        }
        $value = mktime($hours,$minutes,$seconds,$months,$days,$years);
        return $this->validateValue($value);
    }

    public function showInput(Array $data = array())
    {
        $data['value'] = $this->getvaluearray($data);
        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do
        $data['extraparams'] =!empty($extraparams) ? $extraparams : "";
        
        if($this->initialization_start_year == null)            
            $this->initialization_start_year =  min($data['value']['year'], date("Y")) - 5;
        
        if($this->initialization_end_year == null)          
            $this->initialization_end_year = max($data['value']['year'], date("Y")) + 5;
                
        $data['start_year'] = isset($data['start_year'])? $data['start_year'] : $this->initialization_start_year;
        $data['end_year'] = isset($data['end_year'])? $data['end_year'] : $this->initialization_end_year;        
        
        return DataProperty::showInput($data);
    }

/*
 *  This function outputs the following:
 *  date: The formated output string for display
 *  time: an array containing seconds, minutes, hours etc. of this date/time for further manipulation
 */
    public function showOutput(Array $data = array())
    {
        if (isset($data['format_type']))   $this->display_datetime_format_type   = $data['format_type'];
        if (isset($data['format_predef'])) $this->display_datetime_format_predef = $data['format_predef'];
        if (isset($data['format_custom'])) $this->display_datetime_format_custom = $data['format_custom'];
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }
        if (empty($value)) $value = time();
        if (!is_array($value)) {
            $valuearray['date'] = $this->format($value);
            $valuearray['time'] = $this->getvaluearray(array('value' => $value));
        } else {
            $valuearray['date'] = $this->format($value['timestamp']);
            $valuearray['time'] = $value;
        }
        $data['value'] = $valuearray;
        return DataProperty::showOutput($data);
    }

    // Review this
    public function getValue_x()
    {
        return $this->format($this->value);
    }
    
    function getvaluearray($data)
    {
        if (!isset($data['value'])) $value = $this->value;
        else $value = $data['value'];

        // This is already a time array
        if (is_array($value)) return $value;

// Not a good ideea to force to time()
//        $value = $value == 0 ? time() : $value;
        if (empty($value)) $value = 0;
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

    function format($value)
    {
        switch($this->display_datetime_format_type) {
            case 1:
            default:
                $date = xarLocaleGetFormattedDate('short', $value, false);
                $time = xarLocaleGetFormattedTime('short', $value, false);
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

    function showHidden(Array $data = array())
    {
        $data['value'] = $this->getvaluearray($data);
        return parent::showHidden($data);
    }
}

?>