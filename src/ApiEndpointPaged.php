<?php
namespace GISwrapper;

/**
 * Class APIEndpointPaged
 * representing an part of the path returning data in pages
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package GISwrapper
 * @version 0.2
 */
class APIEndpointPaged extends APIEndpoint implements \Iterator, \Countable
{
    /**
     * @var object containing the returned elements
     */
    private $_data;

    /**
     * @var bool indicating if there is loaded data
     */
    private $_loaded;

    /**
     * @var int number of total pages
     */
    private $_pages;

    /**
     * @var int
     */
    private $_currentItem;

    /**
     * @var int number of elements on the current page
     */
    private $_pageItems;

    /**
     * @var object containing the facets returned by some paged endpoints
     */
    private $_facets;

    /**
     * @var int number of total elements in the last request
     */
    private $_count;

    // $_currentPage is already declared in the class Endpoint for use in the get method
    // $_perPage is already declared in the class Endpoint for use in the get method

    /**
     * @var int page to start on with foreach loop
     */
    private $_startPage;

    /**
     * APIEndpointPaged constructor.
     * @param array $cache parsed swagger file for this api
     * @param AuthProvider $auth
     * @param array $pathParams array with values for dynamic parts of the path
     */
    function __construct($cache, $auth, $pathParams = array())
    {
        parent::__construct($cache, $auth, $pathParams);
        $this->_loaded = false;
    }

    /**
     * load the current page
     * @throws OperationNotAvailableException
     * @throws ParameterRequiredException
     */
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
        $this->_count = $res->paging->total_items;
        $this->_loaded = true;
    }

    /**
     * @return object containing the facets of the endpoint
     */
    public function getFacets() {
        if(!$this->_loaded) $this->load();
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
                $expectedPage = $this->_currentPage;
                $this->load();
                if($this->_currentPage != $expectedPage) {
                    throw new InvalidAPIResponseException("Got page " . $this->_currentPage . ' but expected page' . $expectedPage);
                }
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
     * Checks if current position is valid if $operation is null and else if the parameters of this endpoint are valid for the http method
     * @param null|string $operation null or the http method
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
        if($this->_startPage != null) {
            $this->_currentPage = $this->_startPage;
        } else {
            $this->_currentPage = 1;
        }
        $this->load();
    }

    /**
     * @return int number of elements with the current parameters
     * @throws \Exception
     */
    public function count() {
        $p = $this->_currentPage;
        $this->_currentPage = 1;
        try {
            $res = $this->get();
        } catch(\Exception $e) {
            $this->_currentPage = $p;
            throw $e;
        }
        $this->_currentPage = $p;
        return $res->paging->total_items;
    }

    /**
     * @return int number of elements in the last request
     */
    public function lastCount() {
        return $this->_count;
    }

    /**
     * @return int number of the current page
     */
    public function currentPage() {
        if($this->_currentPage != null) {
            return $this->_currentPage;
        } else {
            return 1;
        }
    }

    /**
     * @param int $page page to start foreach loop with
     */
    public function setStartPage($page) {
        $this->_startPage = $page;
    }

    /**
     * @param int $number number of items per page
     */
    public function setPerPage($number) {
        $this->_perPage = $number;
    }
}