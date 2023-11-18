<?php

/**
 * QR Code Property
 *
 * @package properties
 * @subpackage qrcode property
 * @category Third Party Xaraya Property
 * @version 1.0.0
 * @copyright (C) 2022 Luetolf-Carroll GmbH
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 */

sys::import('modules.base.xarproperties.textbox');
sys::import('properties.qrcode.QR_BarCode');

class QRCodeProperty extends TextBoxProperty
{
    public $id         = 30151;
    public $name       = 'qrcode';
    public $desc       = 'QR Code';
    public $reqmodules = [];

    public $initialization_code_size  = 500;
    public $initialization_code_color  = '#000000';
    public $display_url;

//    public $name;
    public $email;
    public $subject;
    public $message;
    public $phone;
    public $msg;
    public $address;
    public $type;
    public $size;
    public $content;
    public $filename;
    public $qr;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'auto';
        $this->template =  'qrcode';
        $this->filepath   = 'auto';
    }

    public function showOutput(array $data = [])
    {
        if (empty($data['code_size'])) {
            $data['code_size'] = $this->initialization_code_size;
        }
        if (empty($data['code_color'])) {
            $data['code_color'] = $this->initialization_code_color;
        }
        $this->qr = new QR_BarCode($data['code_size'], $data['code_color']);
        if (empty($data['url'])) {
            $data['url'] = $this->display_url;
        }
        $this->qr->url($data['url']);
        $data['qrimage'] = base64_encode($this->qr->qrCode());

        return parent::showOutput($data);
    }
}
