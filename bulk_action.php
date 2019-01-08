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

function listing_bulk_action()
{
    sys::import('modules.dynamicdata.class.objects.base');

    // Get parameters
    if(!xarVarFetch('idlist',         'isset', $idlist,         array(), XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('operation',      'isset', $operation,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('redirecttarget', 'isset', $redirecttarget, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('returnurl',      'str',   $returnurl,      '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('objectname',     'str',   $objectname,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module',         'str',   $module,         'listings', XARVAR_DONT_SET)) {return;}

    // Must have an object defined
    if (empty($objectname)) xarController::redirect($returnurl);
    // Must have some records defined
    if (empty($idlist) || count($idlist) == 0) xarController::redirect($returnurl);
    // Must have an operation defined
    if (empty($operation)) xarController::redirect($returnurl);

    $listing = DataObjectMaster::getObject(array('name' => $objectname));
    if (!empty($listing->filepath) && $listing->filepath != 'auto') include_once(sys::code() . $listing->filepath);
    switch ($operation) {
        case 1: /* reject item */
        case 2: /* processed */
        case 3: /* item is active, ready */
            foreach ($ids as $id => $val) {
                if (empty($val)) continue;
                //get the listing and update
                 $item = $listing->getItem(array('itemid' => $val));
                 if (!$listing->updateItem(array('state' => $operation))) return;
            }
            break;
        case 10: /* physically delete each item */
            foreach ($ids as $id => $val) {
                if (empty($val)) continue;
                //delete the listing
                if (!$listing->deleteItem(array('itemid' => $val))) return;
            }
            break;
        default: /* custom function */
            // Get the URL corresponding to this custom function
            $urlstring = 'funcurl_' . $operation;
            xarVarFetch($urlstring,   'str', $funcurl,  '', XARVAR_NOT_REQUIRED);

            // If the URL is empty, bail
            if (empty($funcurl)) {
                xarController::redirect($returnurl);
                return true;
            }
            
            // Dissect the passed URL
            $callparts = explode('_', $funcurl);
            $modpart = $callparts[0];
            unset($callparts[0]);
            if (isset($callparts[1])) {
                $typepart = $callparts[1];
                // Remove "api" if it's there
                $api = false;
                if (substr($typepart, -3, 3) == 'api') {
                    $typepart = substr($typepart, 0, -3);
                    $api = true;
                }
                if (empty($typepart)) $typepart = '';
                unset($callparts[1]);
            } else {
                $typepart = '';
            }
            $funcpart = implode('_', $callparts);

            if ($api) {
                $result = xarMod::apiFunc($modpart, $typepart, $funcpart, array('operation' => $operation));
            } else {
                $result = xarMod::guiFunc($modpart, $typepart, $funcpart, array('operation' => $operation));
            }
            // Reshape the result into something we cna put in a URL
            $result = serialize($result);
            $result = base64_encode($result);
            
# --------------------------------------------------------
#
# Add the bulk results to the URL for display, and remove any previous results
#
# We are specifically using straight string replacement, rather than parse_url, 
# because we don't know what type of URL scheme is being used (Xaraya, short URLs etc.)
#
            $listing_query = "from_listing";
            
            // Find the part where we have the listing-query string
            $parts = explode($listing_query . "=", $returnurl);
            // The part before it (may be the whole URL) is in any case the beggining of our return URL
            $returnurl = $parts[0];
            $more_exists = false;
            // If the part after it is not empty...
            if (isset($parts[1])) {
                // We need to remove everything up to the next &
                $query_elements = explode("&", $parts[1]);
                // Remove it
                array_shift($query_elements);
                // If there is anything left, reassemble
                if (!empty($query_elements)) {
                    $more_exists = true;
                    $query = implode('&', $query_elements);
                    // Add it to the return URL
                    $returnurl .= $query;
                }
            }

            // Add this bulk operation results to the URL
            // CHECKME: Doesn't seem very robust
            if ($more_exists) {
                $returnurl .= "&" . $listing_query . "=" . $result;
            } else {
                $returnurl .= "&" . $listing_query . "=" . $result;
            }
            
            xarController::redirect($returnurl);
            break;
    } // end switch
    xarController::redirect($returnurl);
    return true;
}
?>