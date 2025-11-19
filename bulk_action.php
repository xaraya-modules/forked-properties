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

use Xaraya\Services\xar;

function listing_bulk_action(array $args = [], $context = null)
{
    $xar = xar::getServicesClass();
    // Get parameters
    if (!$xar->var()->find('idlist', $idlist, 'isset', '')) {
        return;
    }
    if (!$xar->var()->find('operation', $operation, 'isset', null)) {
        return;
    }
    if (!$xar->var()->find('redirecttarget', $redirecttarget, 'isset', null)) {
        return;
    }
    if (!$xar->var()->find('returnurl', $returnurl, 'str', '')) {
        return;
    }
    if (!$xar->var()->find('objectname', $objectname, 'str', null)) {
        return;
    }
    if (!$xar->var()->find('module', $module, 'str', 'listings')) {
        return;
    }

    // Must have an object defined
    if (empty($objectname)) {
        $xar->ctl()->redirect($returnurl);
    }
    // Must have some records defined
    if (empty($idlist)) {
        $xar->ctl()->redirect($returnurl);
    }
    // Must have an operation defined
    if (empty($operation)) {
        $xar->ctl()->redirect($returnurl);
    }

    $listing = DataObjectFactory::getObject(['name' => $objectname]);
    if (!empty($listing->filepath) && $listing->filepath != 'auto') {
        include_once(sys::code() . $listing->filepath);
    }
    switch ($operation) {
        case 1: /* reject item */
        case 2: /* processed */
        case 3: /* item is active, ready */
            $idlist = explode(',', $idlist);
            foreach ($idlist as $id => $val) {
                if (empty($val)) {
                    continue;
                }
                //get the listing and update
                $item = $listing->getItem(['itemid' => $val]);
                if (!$listing->updateItem(['state' => $operation])) {
                    return;
                }
            }
            break;
        case 10: /* physically delete each item */
            $idlist = explode(',', $idlist);
            foreach ($idlist as $id => $val) {
                if (empty($val)) {
                    continue;
                }
                //delete the listing
                if (!$listing->deleteItem(['itemid' => $val])) {
                    return;
                }
            }
            break;
        default: /* custom function */
            // Get the URL corresponding to this custom function
            $urlstring = 'funcurl_' . $operation;
            $xar->var()->find($urlstring, $funcurl, 'str', '');

            // If the URL is empty, bail
            if (empty($funcurl)) {
                $xar->ctl()->redirect($returnurl);
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
                if (empty($typepart)) {
                    $typepart = '';
                }
                unset($callparts[1]);
            } else {
                $typepart = '';
            }
            $funcpart = implode('_', $callparts);

            if ($api) {
                $result = $xar->mod()->apiFunc($modpart, $typepart, $funcpart, ['operation' => $operation]);
            } else {
                $result = $xar->mod()->guiFunc($modpart, $typepart, $funcpart, ['operation' => $operation]);
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

            $xar->ctl()->redirect($returnurl);
            break;
    } // end switch
    $xar->ctl()->redirect($returnurl);
    return true;
}
