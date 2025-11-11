<?php

/**
 * CodeMirror Property
 *
 * @package properties
 * @subpackage codemirror property
 * @category Third Party Xaraya Property
 * @version 1.0.0
 * @copyright (C) 2011 Netspan AG
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author M. Lutolf (mfl@netspan.ch)
 */

/**
 * Handle the codemirror property
 * Utilizes JavaScript based  Editor: CodeMirror
 *
 */


class CodeMirrorProperty extends TextAreaProperty
{
    public $id         = 30101;
    public $name       = 'codemirror';
    public $desc       = 'CodeMirror';
    public $reqmodules = [];

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'auto';
        $this->template =  'codemirror';
        $this->filepath   = 'auto';
    }

    public function showInput(array $data = [])
    {
        $data['basepath'] = sys::code() . 'properties/codemirror/xartemplates/includes/codemirror/';
        return parent::showInput($data);
    }
}
