<?php
/**
 * Pager Property
 *
 * @package properties
 * @subpackage pager property
 * @category Xaraya Property
 * @version 1.0.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 *
 * Equivalent of pnHTML()'s Pager function (to get rid of pnHTML calls in modules while waiting for widgets)
 *
 * @author Jason Judge
 * @since 1.13 - 2003/10/09
 * @access public
 * @param integer $startnum     start item
 * @param integer $total        total number of items present
 * @param string  $urltemplate  template for url, will replace '%%' with item number
 * @param integer $perpage      number of links to display (default=10)
 * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
 * @param integer $template     alternative template name within base/user (default 'pager')
 *
 */

sys::import('modules.base.xarproperties.textbox');

/**
 * Pager Property
 */
class PagerProperty extends TextBoxProperty
{
    public $id         = 30099;
    public $name       = 'pager';
    public $desc       = 'Pager';
    public $reqmodules = [];

    public $itemstotal = 0;
    public $items_per_page = 20;
    public $startnum = 1;
    public $order = null;

    public $blocksize = 1;
    public $urltemplate = '';
    public $blockoptions = [];
    public $module;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'auto';
        $this->template =  'pager';
        $this->filepath   = 'auto';

        $args = $descriptor->getArgs();
        extract($args);

        if (!empty($startnum)) {
            $this->startnum = $startnum;
        }
        if (!empty($url)) {
            $this->urltemplate = $url;
        } else {
            $addons = [
                'startnum' => "%%",
                'items_per_page' => $this->items_per_page,
            ];
            $this->urltemplate = xarServer::getCurrentURL($addons);
        }
        $this->module = xarMod::getName();
    }

    public function checkInput($name = '', $value = null)
    {
        if (!empty($name)) {
            $name = $name . "_";
        }
        if (!xarVar::fetch($name . 'startnum', 'int', $this->startnum, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch($name . 'order', 'str', $this->order, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch($name . 'items_per_page', 'int', $this->items_per_page, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch($name . 'itemstotal', 'int', $this->itemstotal, null, xarVar::DONT_SET)) {
            return;
        }
        return true;
    }

    public function getArgs(array $args=[])
    {
        $position = [
                        'startnum' => $this->startnum,
                        'order'    => $this->order,
                        'items_per_page'     => $this->items_per_page,
                     ];
        foreach ($args as $key => $value) {
            $position[$key] = $value;
        }
        return $position;
    }

    public function validate()
    {
        $validated = true;
        if (empty($this->itemstotal) ||
            ($this->itemstotal < 1)
        ) {
            $validated = false;
        }
        return $validated;
    }

    public function showInput(array $data = [])
    {
        extract($data);

        if (isset($localmodule)) {
            $this->module = $localmodule;
        }

        if (isset($items_per_page)) {
            $this->items_per_page = $items_per_page;
        } else {
            $moduleitems_per_page = xarModVars::get($this->module, 'items_per_page');
            if (!empty($moduleitems_per_page)) {
                $this->items_per_page = $moduleitems_per_page;
            }
        }

        if (isset($itemstotal)) {
            $this->itemstotal = $itemstotal;
        }
        if (isset($urltemplate)) {
            $this->urltemplate = $urltemplate;
        }
        if ($this->validate()) {
            $data = $this->getPagerInfo($this->startnum);
        } else {
            $data = [];
        }
        return parent::showInput($data);
    }

    public function showOutput(array $data = [])
    {
        return parent::showInput([]);
    }

/**
 * Creates pager information with no assumptions to output format.
 *
 * @author Jason Judge
 * @since 2003/10/09
 * @access public
 * @param integer $currentItem     start item
 *
 * @todo  Move this somewhere else, preferably transparent and a widget (which might be mutually exclusive)
 */
    public function getPagerInfo($currentItem)
    {
        // Default block options.
        if (is_numeric($this->blockoptions)) {
            $pageBlockSize = $this->blockoptions;
        }

        if (is_array($this->blockoptions)) {
            if (!empty($this->blockoptions['blocksize'])) {
                $blockSize = $this->blockoptions['blocksize'];
            }
            if (!empty($this->blockoptions['firstitem'])) {
                $firstItem = $this->blockoptions['firstitem'];
            }
            if (!empty($this->blockoptions['firstpage'])) {
                $firstPage = $this->blockoptions['firstpage'];
            }
            if (!empty($this->blockoptions['urltemplate'])) {
                $this->urltemplate = $this->blockoptions['urltemplate'];
            }
            if (!empty($this->blockoptions['urlitemmatch'])) {
                $urlItemMatch = $this->blockoptions['urlitemmatch'];
            } else {
                $urlItemMatch = '%%';
            }
            $urlItemMatchEnc = rawurlencode($urlItemMatch);
        }

        // Default values.
        if (empty($blockSize) || $blockSize < 1) {
            $blockSize = 10;
        }
        if (empty($firstItem)) {
            $firstItem = 1;
        }
        if (empty($firstPage)) {
            $firstPage = 1;
        }

        // The last item may be offset if the first item is not 1.
        $lastItem = ($this->itemstotal + $firstItem - 1);

        // Sanity check on arguments.
        if ($currentItem < $firstItem) {
            $currentItem = $firstItem;
        }
        if ($currentItem > $lastItem) {
            $currentItem = $lastItem;
        }

        // If this request was the same as the last one, then return the cached pager details.
        // TODO: is there a better way of caching for each unique request?
        $request = md5($currentItem . ':' . $lastItem . ':' . $this->items_per_page . ':' . serialize($this->blockoptions));
        if (xarCore::getCached('Pager.core', 'request') == $request) {
            return xarCore::getCached('Pager.core', 'details');
        }

        // Record the values in this request.
        xarCore::setCached('Pager.core', 'request', $request);

        // Max number of items in a block of pages.
        $itemsPerBlock = ($blockSize * $this->items_per_page);

        // Get the start and end items of the page block containing the current item.
        $blockFirstItem = $currentItem - (($currentItem - $firstItem) % $itemsPerBlock);
        $blockLastItem = $blockFirstItem + $itemsPerBlock - 1;
        if ($blockLastItem > $lastItem) {
            $blockLastItem = $lastItem;
        }

        // Current/Last page numbers.
        $currentPage = (int)ceil(($currentItem-$firstItem+1) / $this->items_per_page) + $firstPage - 1;
        $totalPages = (int)ceil($this->itemstotal / $this->items_per_page);

        // First/Current/Last block numbers
        $firstBlock = 1;
        $currentBlock = (int)ceil(($currentItem-$firstItem+1) / $itemsPerBlock);
        $totalBlocks = (int)ceil($this->itemstotal / $itemsPerBlock);

        // Get start and end items of the current page.
        $pageFirstItem = $currentItem - (($currentItem-$firstItem) % $this->items_per_page);
        $pageLastItem = $pageFirstItem + $this->items_per_page - 1;
        if ($pageLastItem > $lastItem) {
            $pageLastItem = $lastItem;
        }

        // Initialise data array.
        $data = [];

        $data['middleitems'] = [];
        $data['middleurls'] = [];
        $pageNum = (int)ceil(($blockFirstItem - $firstItem + 1) / $this->items_per_page) + $firstPage - 1;
        for ($i = $blockFirstItem; $i <= $blockLastItem; $i += $this->items_per_page) {
            if (!empty($this->urltemplate)) {
                $data['middleurls'][$pageNum] = str_replace([$urlItemMatch,$urlItemMatchEnc], [$i,$i], $this->urltemplate);
            }
            $data['middleitems'][$pageNum] = $i;
            $data['middleitemsfrom'][$pageNum] = $i;
            $data['middleitemsto'][$pageNum] = $i + $this->items_per_page - 1;
            if ($data['middleitemsto'][$pageNum] > $this->itemstotal) {
                $data['middleitemsto'][$pageNum] = $this->itemstotal;
            }
            $pageNum += 1;
        }

        $data['currentitem'] = $currentItem;
        $data['totalitems'] = $this->itemstotal;
        $data['lastitem'] = $lastItem;
        $data['firstitem'] = $firstItem;
        $data['items_per_page'] = $this->items_per_page;
        $data['itemsperblock'] = $itemsPerBlock;
        $data['pagesperblock'] = $blockSize;

        $data['currentblock'] = $currentBlock;
        $data['totalblocks'] = $totalBlocks;
        $data['firstblock'] = $firstBlock;
        $data['lastblock'] = $totalBlocks;
        $data['blockfirstitem'] = $blockFirstItem;
        $data['blocklastitem'] = $blockLastItem;

        $data['currentpage'] = $currentPage;
        $data['currentpagenum'] = $currentPage;
        $data['totalpages'] = $totalPages;
        $data['pagefirstitem'] = $pageFirstItem;
        $data['pagelastitem'] = $pageLastItem;

        // These two are item numbers. The naming is historical.
        $data['firstpage'] = $firstItem;
        $data['lastpage'] = $lastItem - (($lastItem-$firstItem) % $this->items_per_page);

        if (!empty($this->urltemplate)) {
            // These two links are for first and last pages.
            $data['firsturl'] = str_replace([$urlItemMatch,$urlItemMatchEnc], [$data['firstpage'],$data['firstpage']], $this->urltemplate);
            $data['lasturl'] = str_replace([$urlItemMatch,$urlItemMatchEnc], [$data['lastpage'],$data['lastpage']], $this->urltemplate);
        }

        $data['firstpagenum'] = $firstPage;
        $data['lastpagenum'] = ($totalPages + $firstPage - 1);

        // Data for previous page of items.
        if ($currentPage > $firstPage) {
            $data['prevpageitems'] = $this->items_per_page;
            $data['prevpage'] = ($pageFirstItem - $this->items_per_page);
            if (!empty($this->urltemplate)) {
                $data['prevpageurl'] = str_replace([$urlItemMatch,$urlItemMatchEnc], [$data['prevpage'],$data['prevpage']], $this->urltemplate);
            }
        } else {
            $data['prevpageitems'] = 0;
        }

        // Data for next page of items.
        if ($pageLastItem < $lastItem) {
            $nextPageLastItem = ($pageLastItem + $this->items_per_page);
            if ($nextPageLastItem > $lastItem) {
                $nextPageLastItem = $lastItem;
            }
            $data['nextpageitems'] = ($nextPageLastItem - $pageLastItem);
            $data['nextpage'] = ($pageLastItem + 1);
            if (!empty($this->urltemplate)) {
                $data['nextpageurl'] = str_replace([$urlItemMatch,$urlItemMatchEnc], [$data['nextpage'],$data['nextpage']], $this->urltemplate);
            }
        } else {
            $data['nextpageitems'] = 0;
        }

        // Data for previous block of pages.
        if ($currentBlock > $firstBlock) {
            $data['prevblockpages'] = $blockSize;
            $data['prevblock'] = ($blockFirstItem - $itemsPerBlock);
            if (!empty($this->urltemplate)) {
                $data['prevblockurl'] = str_replace([$urlItemMatch,$urlItemMatchEnc], [$data['prevblock'],$data['prevblock']], $this->urltemplate);
            }
        } else {
            $data['prevblockpages'] = 0;
        }

        // Data for next block of pages.
        if ($currentBlock < $totalBlocks) {
            $nextBlockLastItem = ($blockLastItem + $itemsPerBlock);
            if ($nextBlockLastItem > $lastItem) {
                $nextBlockLastItem = $lastItem;
            }
            $data['nextblockpages'] = ceil(($nextBlockLastItem - $blockLastItem) / $this->items_per_page);
            $data['nextblock'] = ($blockLastItem + 1);
            if (!empty($this->urltemplate)) {
                $data['nextblockurl'] = str_replace([$urlItemMatch,$urlItemMatchEnc], [$data['nextblock'],$data['nextblock']], $this->urltemplate);
            }
        } else {
            $data['nextblockpages'] = 0;
        }

        // Cache all the pager details.
        xarCore::setCached('Pager.core', 'details', $data);

        return $data;
    }
}
