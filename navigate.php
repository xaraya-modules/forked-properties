<?php
/**
 * Listing Property
 *
 * @package properties
 * @subpackage listing property
 * @copyright (C) 2010 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <mfl@netspan.ch>
 */

function navigate($listing_id, $itemid, $direction, $index_name, $url)
{
    // Only 2 directions allowed
    if (!in_array($direction, ['forward','backward'])) {
        return false;
    }

    // Get the keys
    $data = xarSession::getVar('listing.lastkeys');
    if (!isset($data[$listing_id])) {
        return false;
    }

    $current_place = array_search($itemid, $data[$listing_id]['keys']);
    $next_key = false;
    if ($current_place !== false) {
        if ($direction == 'forward') {
            if ($current_place < count($data[$listing_id]['keys']) - 1) {
                $next_place = $current_place + 1;
            } else {
                $next_place = false;
            }
        } elseif ($direction == 'backward') {
            if ($current_place > 0) {
                $next_place = $current_place - 1;
            } else {
                $next_place = false;
            }
        }
        if ($next_place !== false) {
            $next_key = $data[$listing_id]['keys'][$next_place];
        }
    }
    if ($next_key == false) {
        return false;
    }

    if (empty($url)) {
        $url = xarServer::getCurrentURL([$index_name => $next_key]);
    } else {
        $url_parts = parse_url($url);
        $cleanstring = html_entity_decode($url_parts['query']);
        parse_str($cleanstring, $queryparts);
        $queryparts[$index_name] = $next_key;
        $querystring = '';
        foreach ($queryparts as $key => $value) {
            $querystring .= "&" . $key . "=" . $value;
        }
        $querystring = ltrim($querystring, "&");
        $url_parts['query'] = $querystring;
        $url = http_build_url($url_parts);
    }
    return $url;
}

// From http://fuelforthefire.ca/free/php/http_build_url/
/**
 * http_build_url
 * Stand alone version of http_build_url (http://php.net/manual/en/function.http-build-url.php)
 * Based on buggy and inefficient version I found at http://www.mediafire.com/?zjry3tynkg5 by tycoonmaster[at]gmail[dot]com
 * @author Chris Nasr (chris[at]fuelforthefire[dot]ca)
 * @copyright Fuel for the Fire
 * @package http
 * @version 0.1
 * @created 2012-07-26
 */

// If the function doesn't already exist
if (!function_exists('http_build_url')) {
        // Define constants
    define('HTTP_URL_REPLACE', 0x0001);	// Replace every part of the first URL when there's one of the second URL
    define('HTTP_URL_JOIN_PATH', 0x0002);	// Join relative paths
    define('HTTP_URL_JOIN_QUERY', 0x0004);	// Join query strings
    define('HTTP_URL_STRIP_USER', 0x0008);	// Strip any user authentication information
    define('HTTP_URL_STRIP_PASS', 0x0010);	// Strip any password authentication information
    define('HTTP_URL_STRIP_PORT', 0x0020);	// Strip explicit port numbers
    define('HTTP_URL_STRIP_PATH', 0x0040);	// Strip complete path
    define('HTTP_URL_STRIP_QUERY', 0x0080);	// Strip query string
    define('HTTP_URL_STRIP_FRAGMENT', 0x0100);	// Strip any fragments (#identifier)

    // Combination constants
    define('HTTP_URL_STRIP_AUTH', HTTP_URL_STRIP_USER | HTTP_URL_STRIP_PASS);
    define('HTTP_URL_STRIP_ALL', HTTP_URL_STRIP_AUTH | HTTP_URL_STRIP_PORT | HTTP_URL_STRIP_QUERY | HTTP_URL_STRIP_FRAGMENT);

    /**
     * HTTP Build URL
     * Combines arrays in the form of parse_url() into a new string based on specific options
     * @name http_build_url
     * @param string|array $url		The existing URL as a string or result from parse_url
     * @param string|array $parts	Same as $url
     * @param int $flags			URLs are combined based on these
     * @param array &$new_url		If set, filled with array version of new url
     * @return string
     */
    function http_build_url(/*string|array*/ $url, /*string|array*/ $parts = [], /*int*/ $flags = HTTP_URL_REPLACE, /*array*/ &$new_url = false)
    {
        // If the $url is a string
        if (is_string($url)) {
            $url = parse_url($url);
        }

        // If the $parts is a string
        if (is_string($parts)) {
            $parts	= parse_url($parts);
        }

        // Scheme and Host are always replaced
        if (isset($parts['scheme'])) {
            $url['scheme']	= $parts['scheme'];
        }
        if (isset($parts['host'])) {
            $url['host']	= $parts['host'];
        }

        // (If applicable) Replace the original URL with it's new parts
        if (HTTP_URL_REPLACE & $flags) {
            // Go through each possible key
            foreach (['user','pass','port','path','query','fragment'] as $key) {
                // If it's set in $parts, replace it in $url
                if (isset($parts[$key])) {
                    $url[$key]	= $parts[$key];
                }
            }
        } else {
            // Join the original URL path with the new path
            if (isset($parts['path']) && (HTTP_URL_JOIN_PATH & $flags)) {
                if (isset($url['path']) && $url['path'] != '') {
                    // If the URL doesn't start with a slash, we need to merge
                    if ($url['path'][0] != '/') {
                        // If the path ends with a slash, store as is
                        if ('/' == $parts['path'][strlen($parts['path'])-1]) {
                            $sBasePath	= $parts['path'];
                        }
                        // Else trim off the file
                        else {
                            // Get just the base directory
                            $sBasePath	= dirname($parts['path']);
                        }

                        // If it's empty
                        if ('' == $sBasePath) {
                            $sBasePath	= '/';
                        }

                        // Add the two together
                        $url['path']	= $sBasePath . $url['path'];

                        // Free memory
                        unset($sBasePath);
                    }

                    if (false !== strpos($url['path'], './')) {
                        // Remove any '../' and their directories
                        while (preg_match('/\w+\/\.\.\//', $url['path'])) {
                            $url['path']	= preg_replace('/\w+\/\.\.\//', '', $url['path']);
                        }

                        // Remove any './'
                        $url['path']	= str_replace('./', '', $url['path']);
                    }
                } else {
                    $url['path']	= $parts['path'];
                }
            }

            // Join the original query string with the new query string
            if (isset($parts['query']) && (HTTP_URL_JOIN_QUERY & $flags)) {
                if (isset($url['query'])) {
                    $url['query']	.= '&' . $parts['query'];
                } else {
                    $url['query']	= $parts['query'];
                }
            }
        }

        // Strips all the applicable sections of the URL
        if (HTTP_URL_STRIP_USER & $flags) {
            unset($url['user']);
        }
        if (HTTP_URL_STRIP_PASS & $flags) {
            unset($url['pass']);
        }
        if (HTTP_URL_STRIP_PORT & $flags) {
            unset($url['port']);
        }
        if (HTTP_URL_STRIP_PATH & $flags) {
            unset($url['path']);
        }
        if (HTTP_URL_STRIP_QUERY & $flags) {
            unset($url['query']);
        }
        if (HTTP_URL_STRIP_FRAGMENT & $flags) {
            unset($url['fragment']);
        }

        // Store the new associative array in $new_url
        $new_url	= $url;

        // Combine the new elements into a string and return it
        return
             ((isset($url['scheme'])) ? $url['scheme'] . '://' : '')
            .((isset($url['user'])) ? $url['user'] . ((isset($url['pass'])) ? ':' . $url['pass'] : '') .'@' : '')
            .((isset($url['host'])) ? $url['host'] : '')
            .((isset($url['port'])) ? ':' . $url['port'] : '')
            .((isset($url['path'])) ? $url['path'] : '')
            .((isset($url['query'])) ? '?' . $url['query'] : '')
            .((isset($url['fragment'])) ? '#' . $url['fragment'] : '')
        ;
    }
}
