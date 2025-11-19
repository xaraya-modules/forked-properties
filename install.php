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


class LanguagesPropertyInstall extends LanguagesProperty implements iDataPropertyInstall
{
    public function install(array $data = [])
    {
        if (!DataObjectFactory::isObject(['name' => 'languages'])) {
            $files[] = sys::code() . 'properties/languages/data/language-def.xml';
            $files[] = sys::code() . 'properties/languages/data/language-dat.xml';
            foreach ($files as $file) {
                try {
                    $objectid = $this->mod()->apiFunc('dynamicdata', 'util', 'import', ['file' => $file]);
                } catch (Exception $e) {
                    // We only load the object once
                    break;
                }
            }
        }
        return true;
    }
}
