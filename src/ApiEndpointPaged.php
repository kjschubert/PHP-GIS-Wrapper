<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 23.05.16
 * Time: 21:38
 */

namespace GISwrapper;


class APIEndpointPaged extends APIEndpoint implements \Iterator
{
    private $_data;
    private $_loaded;
    private $_pages;
    private $_currentItem;
    private $_pageItems;
    private $_facets;

    // $_currentPage is already declared in the class Endpoint for use in the get method

    function __construct($cache, $auth, $pathParams = array())
    {
        parent::__construct($cache, $auth, $pathParams);
    }

    private function load() {
        $res = $this->get();

        $this->_data = $res->data;

        if(isset($res->facets)) {
            $this->_facets = $res->facets;
        } else {
            $this->_facets = false;
        }

        $this->_currentPage = $res->paging->current_page;
        $this->_pages = $res->paging->total_pages;
        $this->_pageItems = count($res->data);
        $this->_loaded = true;
    }

    public function reset() {
        parent::reset();
        $this->_data = null;
        $this->_pageItems = null;
        $this->_pages = null;
        $this->_currentItem = 0;
        $this->_currentPage = 1;
        $this->_loaded = false;
    }

    public function getFacets() {
        if(!$this->loaded) $this->load();
        return $this->_facets;
    }

    /**
     * Return the current element
     */
    public function current()
    {
        return $this->_data[$this->_currentItem];
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        $this->_currentItem++;
        if ($this->_currentItem >= $this->_pageItems) {
            $this->_currentPage++;
            if ($this->_currentPage <= $this->_pages) {
                $this->load();
                $this->_currentItem = 0;
            }
        }
    }

    /**
     * Return the key of the current element
     */
    public function key()
    {
        if(isset($this->_data[$this->_currentItem]->id)) {
            return $this->_data[$this->_currentItem]->id;
        } else {
            return $this->_currentPage . '_' . $this->_currentItem;
        }
    }

    /**
     * Checks if current position is valid
     * @param null $operation
     * @return bool
     */
    public function valid($operation = null)
    {
        if($operation !== null) {
            return parent::valid($operation);
        } elseif($this->_currentItem < $this->_pageItems && $this->_currentPage <= $this->_pages) {
            return true;
        }
        return false;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        $this->_currentItem = 0;
        $this->_currentPage = 1;
        $this->load();
    }
}