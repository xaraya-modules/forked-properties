<?php
/**
 * Listing Property
 *
 * @package properties
 * @subpackage listing property
 * @category Third Party Xaraya Property
 * @version 1.0.0
 * @copyright (C) 2011 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.dynamicdata.class.properties.base');

class ListingProperty extends DataProperty
{
    public $id   = 30100;
    public $name = 'listing';
    public $desc = 'Listing';
    public $reqmodules = [];

    public $module;
    public $object      = null;
    public $objectname  = null;
    public $fieldlist   = '';
    public $conditions  = null;
    public $listing = [];

    public $alphabet = [
        'A', 'B', 'C', 'D', 'E', 'F',
        'G', 'H', 'I', 'J', 'K', 'L',
        'M', 'N', 'O', 'P', 'Q', 'R',
        'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z',
    ];

    public $display_show_primary        = false;
    public $display_show_search         = true;
    public $display_show_alphabet       = true;
    public $display_showall_tab         = true;
    public $display_showother_tab       = true;
    public $display_show_items_per_page = false;
    public $display_items_per_page = 20;

    public function __construct(ObjectDescriptor $descriptor)
    {
        $this->tplmodule = 'auto';
        $this->template =  'listing';
        $this->filepath   = 'auto';

        parent::__construct($descriptor);
    }

    public function showInput(array $data = [])
    {
        $data = array_merge($data, $this->runquery($data));
        return parent::showInput($data);
    }

/*
Notes:
- We support 2 search types: by alphabet, by text search
- Text searches and alphabet searches are mutually exclusive
- Sorting direction, ordering field is preserved across the search types
- The itemtype is fundamental in determining if we are in an existing context (and have a current query) or a new context
- As a general rule we try to pass everything that has not changed via session vars.
- The sessionvar listing.settings contains an associative array which contains the settings of every listing the session encounters
- The sessionvar 'listing.' . $objectname contains a serialized array of the items of the last listing displayed.
    This sessionvar is only created when we are exporting (attribute export="1"
- The sessionvar listing.lastkeys contains an associative array of the IDs of the last query of each listing the user has encountered.
  The keys are stored by listing ID.
    This sessionvar is only created when the listing is given an ID: id="somenumberorstring"

    fieldlists attributes in templates have the form
    fieldlist="field1[:formfieldname1:fieldstate1][,field2[:formfieldname2:fieldstate2]]"
    The first part of the name is the name of the field
    The second part of the name is the Label it will be given in the column header
    The third part of the name can have the values input / output (default) / hidden
*/
    public function runquery($data)
    {
        //--- -2. Initial parameters
        if (!isset($data['object'])) {
            $data['object'] = $this->object;
        }
        if (!isset($data['objectname'])) {
            $data['objectname'] = $this->objectname;
        }
        if (!isset($data['fieldlist'])) {
            $data['fieldlist'] = $this->fieldlist;
        }
//        if (!isset($data['tplmodule'])) $data['tplmodule'] = $this->tplmodule;
        if (isset($data['tplmodule'])) {
            $data['module'] = $data['tplmodule'];
        }
        if (!isset($data['layout'])) {
            $data['layout'] = $this->layout;
        }
        if (!isset($data['conditions'])) {
            $data['conditions'] = $this->conditions;
        }
        if (isset($data['module'])) {
            $this->module = $data['module'];
        } else {
            $info = xarController::$request->getInfo();
            $this->module = $info[0];
            $data['module'] = $this->module;
        }

        $ipp = xarModVars::get($this->module, 'items_per_page');
        if (!empty($ipp)) {
            $this->display_items_per_page = $ipp;
        }

        // Send any config settings not overwritten to the template
        if (!isset($data['show_items_per_page'])) {
            $data['show_items_per_page'] = $this->display_show_items_per_page;
        }
        if (!isset($data['items_per_page'])) {
            $data['items_per_page']      = $this->display_items_per_page;
        }
        if (!isset($data['show_primary'])) {
            $data['show_primary']        = $this->display_show_primary;
        }
        if (!isset($data['show_search'])) {
            $data['show_search']         = $this->display_show_search;
        }
        if (!isset($data['show_alphabet'])) {
            $data['show_alphabet']       = $this->display_show_alphabet;
        }
        if (!isset($data['showall_tab'])) {
            $data['showall_tab']         = $this->display_showall_tab;
        }
        if (!isset($data['showother_tab'])) {
            $data['showother_tab']       = $this->display_showother_tab;
        }

        // give the template the alphabet chars
        $data['alphabet'] = $this->alphabet;

        //--- -1. Get the classes we need
        sys::import('xaraya.structures.query');
        sys::import('modules.dynamicdata.class.properties.master');

        //--- 0. Local parameters
        $baddatasources = ['dynamic_data','dummy','modulevars'];

        //--- 1. Get the args passed to this function

        extract($data);

        //--- 2. Get the object for this listing
        // if no object or object name is passed, bail
        if (!isset($object) && !isset($objectname)) {
            throw new Exception('No object passed to the listing property');
        }

        // We accept both object names and objects, but objectname overrides
        if (isset($objectname)) {
            if (is_object($object)) {
                throw new Exception('Object passed to the listing property instead of name');
            }
            sys::import('modules.dynamicdata.class.objects.factory');
            $object = DataObjectFactory::getObjectList(['name' => $objectname]);
        } elseif (isset($object)) {
            if (!is_object($object)) {
                throw new Exception('No object passed to the listing property');
            } else {
                $objectname = $object->name;
                $data['objectname'] = $objectname;
                if (!method_exists($object, 'getItems')) {
                    sys::import('modules.dynamicdata.class.objects.factory');
                    $object = DataObjectFactory::getObjectList(['name' => $objectname]);
                }
            }
        } else {
            throw new Exception('No object passed to the listing property');
        }

        // itemtype 0 means all itemtypes
        $itemtype ??= 0;

        $module ??= xarMod::getName();
        xarMod::apiLoad($module);
        $regid = xarMod::getRegID($module);

        /*    $searchandor = xarModVars::get('listing','searchandor'); //toggle for AND /OR logic in category query, default AND for categories
            if ($searchandor != 1) { //default to AND
                $searchop = 'and';
            } else {
                $searchop = 'or';
            }*/

        //--- 3. Retrieve session vars we work with

        $q = xarSession::getVar('listing.' . $objectname . '.currentquery');

        // Default values for a first time search; possibly overridden below
        // We check for settings from the object's dataquery or just hardcode
        $lastmsg      = '';
        $laststartnum = 1;
        if (!empty($object->dataquery->sorts)) {
            $firstsort = reset($object->dataquery->sorts);
            $lastorder    = $firstsort['name'];
            $lastsort     = $firstsort['order'];
        } else {
            $lastorder    = '';
            $lastsort     = 'ASC';
        }

        // Create a unique internal ID for this query
        $thissearch = md5($object->dataquery->tostring());

        $settings = xarSession::getVar('listing.settings');
        if (!empty($settings) && isset($settings[$thissearch])) {
            // Get the settings of this search if they exist, overriding the above
            $thesesettings = $settings[$thissearch];
            if (isset($thesesettings['lastmsg'])) {
                $lastmsg = $thesesettings['lastmsg'];
            }
            if (isset($thesesettings['lastorder'])) {
                $lastorder = $thesesettings['lastorder'];
            }
            if (isset($thesesettings['lastsort'])) {
                $lastsort = $thesesettings['lastsort'];
            }
            if (isset($thesesettings['laststartnum'])) {
                $laststartnum = $thesesettings['laststartnum'];
            }
        }

        //--- 4. Get all the parameters we need from the form. These can override the sessionvar settings

        if (!xarVar::fetch('startnum', 'int', $startnum, $laststartnum, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('letter', 'str:1', $letter, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('search', 'str:1:100', $search, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('order', 'str', $order, $lastorder, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('sort', 'str', $sort, $lastsort, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('submit', 'str', $submit, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('op', 'str', $op, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('conditions', 'isset', $conditions, null, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('export', 'int', $export, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('store', 'str:1', $store, 'session', xarVar::NOT_REQUIRED)) {
            return;
        }

        //--- 5. Get configuration settings from modvars and tag attributes

        //--- 6. Assemble the fields to be displayed

        // Check if the object has a primary key
        if (empty($object->primary)) {
            throw new Exception(xarML("The listing cannot be displayed, because this object has no primary key"));
        }

        // We'll put fields into the output of the query that have status active or list
        $object->properties[$object->primary]->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE);

        // Check if someone passed a fieldlist attribute or take the one defined in the object
        $nofieldlist = false;
        if (empty($fieldlist)) {
            $fieldlist = $object->getFieldList();
            $nofieldlist = true;
        } elseif (!is_array($fieldlist)) {
            // If a string was passed rather than an array, turn it into an array
            $fieldlist = explode(',', $fieldlist);
        }

        // Someone passed a keyfield attribute
        if (!empty($keyfield)) {
            $defaultkey = $keyfield;
        }

        $data['fieldlabels'] = [];
        $data['fieldnames'] = [];
        $data['formfieldnames'] = [];
        $data['formfieldstates'] = [];
        $data['fields'] = [];                  // Deprecated - remove from templates!!!
        $data['columns'] = [];                 // Deprecated - remove from templates!!!
        $sourcefields = [];

        $primarysource = '';
        $primaryalias = '';
        $indices = [];
        $defaultkeyname = '';
        $tablekeyfield = '';
        $keyfieldalias = '';

        $properties =& $object->getProperties(['status' => [DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE,DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY]]);
        $has_primary = false;
        foreach ($fieldlist as $fielditem) {
            // Explode the single item in the fieldlist
            $parts = explode(':', $fielditem);
            // The name of the field/property
            $fieldname = trim($parts[0]);

            // Ignore items in the fieldlist that don't corresond to properties
            if (!isset($properties[$fieldname])) {
                continue;
            }

            // We have a corresponding property. Check it
            $property = $properties[$fieldname];
            $source = $property->source;
            $alias = $property->name;

            // The name the field will be given on the listing template
            $formfieldname = (!empty($parts[1])) ? trim($parts[1]) : $property->label;
            // The state of the field on the listing template: input/output/hidden
            $formfieldstate = (isset($parts[2])) ? trim($parts[2]) : 'output';

            // Ignore fields with "bad" data sources for now
            if (in_array($property->source, $baddatasources)) {
                continue;
            }

            // if the property source is "None", then only include it if an $items param was passed
            // We don't want to run a query with such property
            // CHECKEME: preList method now takes care of this
            // if (empty($property->source) && !isset($data['items'])) continue;

            // Special treatment if this is the primary key
            if ($property->type == 21) {
                if ($fieldname == $object->primary) {
                    $primarysource = $source;
                    $primaryalias = $alias;
                    $has_primary = true;
                } else {
                    $indices[] = $source;
                }
                // save the field names for later use
                $data['fieldlabels'][$alias] = $property->label;
                $data['fieldnames'][$alias] = $property->name;
                $data['formfieldnames'][$alias] = $formfieldname;
                $data['formfieldstates'][$alias] = $formfieldstate;
                $data['fields'][$alias] = $property->label;                  // Deprecated - remove from templates!!!
                $data['columns'][$alias] = $property->name;                  // Deprecated - remove from templates!!!
                $sourcefields[$alias] = $source;
            }

            // Ignore other fields that don't have active or list status
            if ($nofieldlist) {
                if (($property->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE) &&
                    ($property->getDisplayStatus() != DataPropertyMaster::DD_DISPLAYSTATE_VIEWONLY)
                ) {
                    continue;
                }
            }

            // Do we need this?
            if ($property->name == 'itemtype') {
                $itemtypefield = $source;
            }

            // We found a property that corresponds to the fieldlist entry
            if ($alias != $primaryalias) {
                // save the field names for later use
                $data['fieldlabels'][$alias] = $property->label;
                $data['fieldnames'][$alias] = $property->name;
                $data['formfieldnames'][$alias] = $formfieldname;
                $data['formfieldstates'][$alias] = $formfieldstate;
                $data['fields'][$alias] = $property->label;                  // Deprecated - remove from templates!!!
                $data['columns'][$alias] = $property->name;                  // Deprecated - remove from templates!!!
                $sourcefields[$alias] = $source;
            }

            // Set the keyfield for use with table selects
            // If a keyfield was passed check if this is it
            // If no key was passed take the first columne
            if (empty($defaultkey) || (!empty($defaultkey) && $property->name == $defaultkey)) {
                // Only pick the primary key if it's being shown
                if ($property->type == 21 && !$this->display_show_primary) {
                    continue;
                }
                $defaultkey = $property->name;
                $defaultkeyname = $property->label;
                $tablekeyfield = $source;
                $keyfieldalias = $alias;
            }
        }

        // Check if we had the primary index in the list of fields. Otherwise add it.
        // Its display will be steered by the $show_primary variable
        if (!$has_primary) {
            $property = $properties[$object->primary];
            $alias = $property->name;
            $primarysource = $property->source;
            $primaryalias = $alias;
            // save the field names for later use
            $data['fieldlabels'][$alias] = $property->label;
            $data['fieldnames'][$alias] = $property->name;
            $data['formfieldnames'][$alias] = $property->name;
            $data['formfieldstates'][$alias] = 'output';
            $data['fields'][$alias] = $property->label;                  // Deprecated - remove from templates!!!
            $data['columns'][$alias] = $property->name;                  // Deprecated - remove from templates!!!
            $sourcefields[$alias] = $property->source;

            // If we got here, then we need to check for a default key
            if (empty($defaultkey) && $this->display_show_primary) {
                $defaultkey = $property->name;
                $defaultkeyname = $property->label;
                $tablekeyfield = $property->source;
                $keyfieldalias = $alias;
            }
        }

        // Sanity check to make sure we got a key
        if (empty($defaultkey)) {
            throw new Exception(xarML("The listing cannot be displayed, because no select key was found"));
        }

        // Check whether the order which may have been passed is still valid (we may have changed the display settings
        // We'll support both full names and aliases here
        if (!in_array($order, array_values($sourcefields)) && !in_array($order, array_keys($sourcefields))) {
            // It's either empty of not being displayed, so get a new order
            $order = reset($data['fieldnames']);
            $thesesettings['lastorder'] = $order;
        }

        //--- 7. Figure out the operation we are performing

        $lastsearch = xarSession::getVar('listing.lastsearch');                 // get the ID of the last search
        $firsttime = empty($lastsearch) || ($thissearch != $lastsearch);        // criterium for first time display
        if ($firsttime) {
            $op = 'pagejump';
        }                                       // override if we moved to a new page with a different query

        if ($op == 'column') {
            $operation = 'columnclick';
        }                        // a  column header was clicked
        elseif ($op == 'letter') {
            $startnum =1;
            $operation = 'lettersearch';
        }                   // an alphabet link was clicked
        elseif ($op == 'submit') {
            $startnum =1;
            $operation = 'textsearch';
        }                    // a string was entered into the text field
        elseif ($op == 'page') {
            $operation = 'pagerclick';
        }                     // the pager was clicked
        elseif (!empty($submit) && !$firsttime) {
            $operation = 'categorysearch';
        }  // the submit button was clicked (= any other search)
        else {
            $operation = 'newsearch';
        }                                          // any other operation: we fall back to new search

        $data['params'] = [];

        switch ($operation) {
            //--- 8. Prepare a navigation operation (click on the pager or one of the columns)
            case "pagerclick":
            case "columnclick":

                $q = xarSession::getVar('listing.' . $objectname . '.currentquery');
                if (empty($q) || !isset($q)) {
                    $q = new Query('SELECT');
                    $q->setdistinct();
                } else {
                    $q = unserialize($q);
                    $q->open();
                }
                if (!empty($conditions)) {
                    $object->dataquery->addconditions($conditions);
                }
                $data['msg'] = $lastmsg;
                break;

                //--- 9. First time visit to this page; empty the sessionvars and reset the categories
            case "newsearch":
                // Go back to the persistent settings
                if (isset($thesesettings['lastmsg'])) {
                    $msg = $thesesettings['lastmsg'];
                }
                if (isset($thesesettings['lastorder'])) {
                    $order = $thesesettings['lastorder'];
                }
                if (isset($thesesettings['lastsort'])) {
                    $sort = $thesesettings['lastsort'];
                }
                if (isset($thesesettings['laststartnum'])) {
                    $startnum = $thesesettings['laststartnum'];
                }
                break;

                //--- 10. Any other operation: get the query if it was passed as conditions, or create a new one
            case "lettersearch":
            case "textsearch":
            case "categorysearch":

                if (!empty($conditions)) {
                    $object->dataquery->addconditions($conditions);
                }
                $object->dataquery->setdistinct();

                //--- 11. Filter on the objects and itemtypes we'll be displaying

                $data['msg'] = '';
                break;

            default:
                throw new Exception(xarML('Illegal operation: #(1)', $operation));
                break;
        }

        //--- 12. Add categories to the query if they are active


        //--- 13. Define which field will be sorted on

        // if there is no order defined use the key field
        if (empty($order)) {
            $order = $keyfieldalias;
        }
        if (empty($lastsort) && $operation != 'lettersearch') {
            $sort = 'ASC';
        }
        // change  the sort direction if I clicked one of the column names
        // but only if the column name is the same so it acts like a toggle for that field
        // only change sort if column name is clicked, not a letter which will retain the current settings
        if ($operation == "columnclick") {
            if (isset($order)) {
                if ($order == $lastorder) {
                    if ($lastsort == 'ASC') {
                        $sort = 'DESC';
                    } else {
                        $sort = 'ASC';
                    }
                } else {
                    $sort = 'ASC';
                }
                $data['search'] = $search; //pass along search
            } elseif (empty($letter) && empty($search)) {
                //if order is not set - set it to the default key field but keep it at 'DESC'
                $order = $keyfieldalias;
                $sort = 'ASC';
            }
            $data['msg'] = '';
        }

        //--- 14. Add the order and sort parameters to the query
        if (is_array($order)) {
            $order = trim($order[0]);
        }
        if (is_array($sort)) {
            $sort = trim($sort[0]);
        }
        if (isset($sourcefields[$order])) {
            $object->dataquery->setorder($sourcefields[$order], $sort);
        } else {
            // Support multiple orders/sort
            $order = explode(',', $order);
            $sort = explode(',', $sort);
            for ($i=0;$i<count($order);$i++) {
                if (!isset($sort[$i])) {
                    $sort[$i] = 'ASC';
                }
                $object->dataquery->addorder(trim($order[$i]), trim($sort[$i]));
            }
        }

        switch ($operation) {
            case "lettersearch":
                //--- 16. Operation filters: we clicked one of the alphabet links

                if ($letter == 'Other') {
                    // In this case we create a bunch of SQL conditions
                    // this is better than the 1x way: no using SQL functions, and we can accomodate any type of 'alphabet'
                    //foreach ($alphabet as $let) $q->notlike('r.name', $let.'%');
                    foreach ($alphabet as $let) {
                        $q->notlike($tablekeyfield, $let.'%');
                    }
                    $data['msg'] = xarML(
                        'Listing where #(1) begins with character not listed in alphabet above (labeled as "Other")',
                        $defaultkeyname
                    );
                } elseif ($letter == 'All') {
                    $data['msg'] = xarML("All items");
                } else {
                    // TODO: handle case-sensitive databases
                    //$q->like('r.name', $letter.'%');
                    $object->dataquery->regex($tablekeyfield, '^(\\\%)*' . $letter);
                    $data['msg'] = xarML('Listing where #(1) begins with "#(2)"', $defaultkeyname, $letter);
                }

                //Adjust session vars and parameters
                $data['params']['letter'] = '';
                $startnum = 1;
                break;

            case "newsearch":
            case "textsearch":

                //--- 17. Operation filters: we are submitting a search text

                if (!empty($search)) {
                    $qsearch = '%'.$search.'%';
                    // Dynamically set on active fields - must have roles id - Search conditions _OR_
                    $i = 0;
                    $msg = '';
                    foreach ($sourcefields as $sourcefield => $value) {
                        if (!empty($value)) {
                            if ($i >0) {
                                $msg .= ' or';
                            }
                            $c[$i]= $object->dataquery->plike($value, $qsearch);
                            $msg .= ' '.$data['fieldlabels'][$sourcefield].' ';
                            $i++;
                        }
                    }
                    if (!empty($msg) && $i>0) {
                        if (empty($data['msg'])) {
                            $data['msg'] = xarML('Listing where #(1) contain "#(2)"', $msg, $search);
                        } else {
                            $data['msg'] .= xarML(' and listing where #(1) contain "#(2)"', $msg, $search);
                        }
                    }
                    // take the conditions we decided on above and add them to the query as a bunch of ORs
                    $object->dataquery->qor($c);
                }
                if (empty($data['msg'])) {
                    $data['msg'] = xarML('All items');
                }

                //Adjust session vars and parameters
                $data['params']['letter'] = '';

                break;
            case "pagerclick":
            case "columnclick":
            case "categorysearch":

                //--- 18. Operation filters: likely navigation, take last msg

                $data['msg'] = $lastmsg;
                break;

            default:
                throw new Exception(xarML('Illegal operation: #(1)', $operation));
                break;
        }

        //--- 19. Cache the dd object for reuse (if called)

        if ($export) {
            // Use isset here to check whether a $items param was passed
            if (!isset($items)) {
                // Add the fieldlist defined above and get the raw values of the items
                $exportitems = $object->getItems(['fieldlist' => $data['fieldnames']]);
            } else {
                $exportitems = $items;
            }
            $values = [];

            // Proceed if we have data
            if (is_array(reset($exportitems))) {
                $firstrow = array_keys(reset($exportitems));

                // First get the labels
                $labels = [];
                foreach ($firstrow as $column) {
                    //Check that all columns are among the fields to be displayed
                    if (!isset($data['fieldnames'][$column])) {
                        continue;
                    }
                    $labels[$column] = $properties[$column]->label;
                }
                $values = [$labels];
                // Now add the data
                foreach ($exportitems as $row) {
                    $fields = [];
                    foreach ($firstrow as $column) {
                        //Check that all columns are among the fields to be displayed
                        if (!isset($data['fieldnames'][$column])) {
                            continue;
                        }
                        $properties[$column]->value = $row[$column];

                        // Let formatting of numbers happen downstream
                        switch ($properties[$column]->basetype) {
                            case 'integer':
                            case 'numeric':
                            case 'decimal':
                                $fields[$column] = $properties[$column]->value;
                                break;
                            default:
                                $fields[$column] = $properties[$column]->getValue();
                        }
                    }
                    $values[] = $fields;
                }
            }
            if ($store == 'session') {
                // Store it all in a session var
                xarSession::delVar('listing.' . $objectname);
                xarSession::setVar('listing.' . $objectname, serialize($values));
            } else {
                if (class_exists('xarVariableCache')) {
                    if (xarVariableCache::isCached('listing.' . $objectname)) {
                        xarVariableCache::delCached('listing.' . $objectname);
                    }
                    xarVariableCache::setCached('listing.' . $objectname, serialize($values));
                } else {
                    $message = xarML('Variable Caching needs to be turned on');
                    die($message);
                }
            }
        }

        //--- 20. Set the number of rows to display and the starting point

        if (!empty($data['items_per_page'])) {
            $object->dataquery->setrowstodo($data['items_per_page']);
        }

        // The record to start at needs to come from the template or from the session var
        $object->dataquery->setstartat($startnum);

        // CHECKME: do we need all 3 of these passed to the template
        $data['order'] = $order;
        $data['letter'] = $letter;
        $data['searchstring'] = $search;

        // Debug display
        if (xarModVars::get('dynamicdata', 'debugmode') &&
        in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            echo "ID: " . $thissearch;
            echo "<br />";
            echo "Operation: " . $operation . " [" . $op . "]";
            echo "<br />";
            echo "Properties: " . implode(',', $data['fieldnames']);
            echo "<br />";
            echo "Start at: " . $startnum;
            echo "<br />";
            echo "Items per page: " . $data['items_per_page'];
            echo "<br />";
            echo "Order: ";
            var_dump($order);
            echo "<br />";
            echo "Sort: ";
            var_dump($sort);
            echo "<br />";
            echo "Object: ";
            echo $object->name;
            echo "<br />";
            echo "Query: ";
            $object->dataquery->qecho();
            echo "<br />";
        }

        // Get the total number of rows
        // Use isset here to check whether a $items param was even passed
        if (!isset($items)) {
            $data['total'] = $object->dataquery->getrows();
        } else {
            $data['total'] = count($items);
        }

        // Based on the total of items, reset the startat parameter if need be to make it smaller than the total number of items
        if ($data['total'] <= $object->dataquery->startat) {
            $startat = (int)($data['total']/$object->dataquery->rowstodo * ($object->dataquery->rowstodo - 1));
            if ($startat < 1) {
                $startat = 1;
            }
            $object->dataquery->startat = $startat;
        }

        // Now we run the query, if that is required
        // Use isset here to check whether a $items param was even passed
        if (!isset($items)) {
            // add conditions if they were passed
            if (!empty($conditions)) {
                $object->dataquery->addconditions($conditions);
            }
            // Add the fieldlist defined above and get the records to be displayed
            $items = $object->getItems(['fieldlist' => $data['fieldnames']]);
        // We may need to recalculate the total if we have linked tables
        // Just force it for now
        } else {
            if (!empty($data['items_per_page'])) {
                // items were passed, but we need to get the correct subset
                // first get the total
                $tempitems = [];
                $item_values = array_values($items);
                $startat = $object->dataquery->startat-1;
                $endat = $startat + $object->dataquery->rowstodo;
                for ($i=$startat;$i<$endat;$i++) {
                    if (!isset($item_values[$i])) {
                        break;
                    }
                    $tempitems[] = $item_values[$i];
                }
                $items = $tempitems;
            }
            // Now sort the items according to whatever sort column was defined
            // In this case we need to make sure that the $order var is not compound table + field
            if (is_array($order)) {
                $currentorder = current($order);
            } else {
                $currentorder = $order;
            }

            // Check if the order is a table field (first time) or a property name
            $flipped = array_flip($sourcefields);
            if (isset($flipped[$currentorder])) {
                $thisorder = $flipped[$currentorder];
            } else {
                $thisorder = $currentorder;
            }

            // Prepare to reorder the array
            $temp = [];
            foreach ($items as $key => $row) {
                $temp[$key]  = $row[$thisorder];
            }

            // Add the sort
            if ($sort == 'DESC') {
                $sort_order = SORT_DESC;
            } else {
                $sort_order = SORT_ASC;
            }

            // Reorder
            array_multisort($temp, $sort_order, $items);
        }

        // Save the sequence of items for whoever. Do this only when an ID parameter was passed
        // We save both the keys and the query object, in case we need to recreate the query
        if (isset($data['id'])) {
            $keys = xarSession::getVar('listing.lastkeys');
            if (empty($keys)) {
                $keys = [];
            }
            $thisquerystring = serialize($object->dataquery->tostring());
            $keys[$data['id']] = ['keys' => array_keys($items), 'query' => $thisquerystring];
            xarSession::setVar('listing.lastkeys', $keys);
        }

        // Debug display
        if (xarModVars::get('dynamicdata', 'debugmode') &&
        in_array(xarUser::getVar('id'), xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            echo "Total rows: ";
            echo $data['total'];
            echo "<br />";
            echo "First 10 rows: ";
            $index = 0;
            foreach ($items as $item) {
                var_dump($item);
                echo "<br />";
                if ($index >= 10) {
                    break;
                }
                $index++;
            }
        }

        // Add the filter variable to show a filter form
        if (!isset($data['filter'])) {
            $data['filter'] = 0;
        }

        // Add field definitions to the template variables
        $data['tablekeyfield'] = $tablekeyfield;
        $data['keyfieldalias'] = $keyfieldalias;
        $data['defaultkeyname'] = $defaultkeyname;
        $data['properties'] = $properties;

        // Add the array of items to the template variables
        $data['items'] = $items;

        // A bunch of params the pager will want to see in its target url
        // order and sort are used by the up and down arrows
        // items_per_page is needed because we may be using dynamic items per page
        $data['params']['op']             = 'page';
        $data['params']['order']          = $order;
        $data['params']['sort']           = $sort;
        $data['params']['items_per_page'] = $items_per_page;
        $data['params']['startnum']       = "%%";

        // The startnum parameter needs to be passed directly to the template (pager and such)
        $data['startnum'] = $startnum;

        // Need this in case this code is turned into a dprop
        $data['regid'] = $regid;

        // We also need a reference to the primary column for the template
        $data['primaryalias'] = $primaryalias;

        // Add a reference to the object itself
        $data['object'] = $object;

        // Set the session vars to the latest state
        $thesesettings['lastmsg']            = $data['msg'];
        if (is_array($order)) {
            $order = $order[0];
        }
        $thesesettings['lastorder']          = $order;
        if (is_array($sort)) {
            $sort = $sort[0];
        }
        $thesesettings['lastsort']           = $sort;
        $thesesettings['laststartnum']       = $startnum;
        $thesesettings['lastitemsperpage']   = $items_per_page;
        $settings[$thissearch] = $thesesettings;
        xarSession::setVar('listing.settings', $settings);
        xarSession::setVar('listing.lastsearch', $thissearch);

        // Sort of ugly. How can we do better?
        unset($q->dbconn);
        unset($q->output);
        unset($q->result);

        return $data;
    }

/*
 * Checks whether the AJAX request is an update or not
 * "confirm=1" in the AJAX request signals this is an update
 */
    public function ajaxConfirm($flag='confirm')
    {
        if (xarController::$request->isAjax()) {
            if (!xarVar::fetch($flag, 'int', $confirm, 0, xarVar::NOT_REQUIRED)) {
                return false;
            }
            return $confirm;
        } else {
            return false;
        }
    }

/*
 * Repopulates the output template and sends the output to the browser
 * TODO: allow overrides (module, theme) for the showoutput template.
 * Right now the template in the property is used, although included templates can be overrides
 */
    public function ajaxRefresh($data=[])
    {
        if (xarController::$request->isAjax()) {
            $file = sys::code().'properties/listing/xartemplates/showinput.xt';
            sys::import('xaraya.templating.compiler');
            $compiler = XarayaCompiler::instance();
            $output = $compiler->compileFile($file);
            $data = $this->runquery($data);
            $output = xarTpl::string($output, $data);
            echo $output;
            exit;
        } else {
            return true;
        }
    }
}
