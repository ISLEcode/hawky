<?php

namespace Hawky;

class Pages extends \ArrayObject {
    public $hawky;                 // access to API
    public $filterValue;            // current page filter value
    public $paginationNumber;       // current page number in pagination
    public $paginationCount;        // highest page number in pagination

    public function __construct($hawky) {
        parent::__construct(array());
        $this->hawky = $hawky;
    }

    // Filter page collection by page setting
    public function filter($key, $value, $exactMatch = true) {
        $array = array();
        $value = str_replace(" ", "-", mb_strtolower($value));
        $valueLength = mb_strlen($value);
        $this->filterValue = "";
        foreach ($this->getArrayCopy() as $page) {
            if ($page->isExisting($key)) {
                foreach (preg_split("/\s*,\s*/", $page->get($key)) as $pageValue) {
                    $pageValueLength = $exactMatch ? mb_strlen($pageValue) : $valueLength;
                    if ($value==mb_substr(str_replace(" ", "-", mb_strtolower($pageValue)), 0, $pageValueLength)) {
                        if (empty($this->filterValue)) $this->filterValue = mb_substr($pageValue, 0, $pageValueLength);
                        array_push($array, $page);
                        break;
                    }
                }
            }
        }
        $this->exchangeArray($array);
        return $this;
    }

    // Filter page collection by file name
    public function match($regex = "/.*/") {
        $array = array();
        foreach ($this->getArrayCopy() as $page) {
            if (preg_match($regex, $page->fileName)) array_push($array, $page);
        }
        $this->exchangeArray($array);
        return $this;
    }

    // Sort page collection by page setting
    public function sort($key, $ascendingOrder = true) {
        $array = $this->getArrayCopy();
        $sortIndex = 0;
        foreach ($array as $page) {
            $page->set("sortindex", ++$sortIndex);
        }
        $callback = function ($a, $b) use ($key, $ascendingOrder) {
            $result = $ascendingOrder ?
                strnatcasecmp($a->get($key), $b->get($key)) :
                strnatcasecmp($b->get($key), $a->get($key));
            return $result==0 ? $a->get("sortindex") - $b->get("sortindex") : $result;
        };
        usort($array, $callback);
        $this->exchangeArray($array);
        return $this;
    }

    // Sort page collection by settings similarity
    public function similar($page, $ascendingOrder = false) {
        $location = $page->location;
        $keywords = mb_strtolower($page->get("title").",".$page->get("tag").",".$page->get("author"));
        $tokens = array_unique(array_filter(preg_split("/[,\s\(\)\+\-]/", $keywords), "strlen"));
        if (!empty($tokens)) {
            $array = array();
            foreach ($this->getArrayCopy() as $page) {
                $searchScore = 0;
                foreach ($tokens as $token) {
                    if (stristr($page->get("title"), $token)) $searchScore += 10;
                    if (stristr($page->get("tag"), $token)) $searchScore += 5;
                    if (stristr($page->get("author"), $token)) $searchScore += 2;
                }
                if ($page->location!=$location) {
                    $page->set("searchscore", $searchScore);
                    array_push($array, $page);
                }
            }
            $this->exchangeArray($array);
            $this->sort("modified", $ascendingOrder)->sort("searchscore", $ascendingOrder);
        }
        return $this;
    }

    // Calculate union, merge page collection
    public function merge($input) {
        $this->exchangeArray(array_merge($this->getArrayCopy(), (array)$input));
        return $this;
    }

    // Calculate intersection, remove pages that are not present in another page collection
    public function intersect($input) {
        $callback = function ($a, $b) {
            return strcmp(spl_object_hash($a), spl_object_hash($b));
        };
        $this->exchangeArray(array_uintersect($this->getArrayCopy(), (array)$input, $callback));
        return $this;
    }

    // Calculate difference, remove pages that are present in another page collection
    public function diff($input) {
        $callback = function ($a, $b) {
            return strcmp(spl_object_hash($a), spl_object_hash($b));
        };
        $this->exchangeArray(array_udiff($this->getArrayCopy(), (array)$input, $callback));
        return $this;
    }

    // Append to end of page collection
    public function append($page) {
        parent::append($page);
        return $this;
    }

    // Prepend to start of page collection
    public function prepend($page) {
        $array = $this->getArrayCopy();
        array_unshift($array, $page);
        $this->exchangeArray($array);
        return $this;
    }

    // Limit the number of pages in page collection
    public function limit($pagesMax) {
        $this->exchangeArray(array_slice($this->getArrayCopy(), 0, $pagesMax));
        return $this;
    }

    // Reverse page collection
    public function reverse() {
        $this->exchangeArray(array_reverse($this->getArrayCopy()));
        return $this;
    }

    // Randomize page collection
    public function shuffle() {
        $array = $this->getArrayCopy();
        shuffle($array);
        $this->exchangeArray($array);
        return $this;
    }

    // Paginate page collection
    public function pagination($limit, $reverse = true) {
        $this->paginationNumber = 1;
        $this->paginationCount = ceil($this->count() / $limit);
        if ($this->hawky->isRequest("page")) $this->paginationNumber = intval($this->hawky->getRequest("page"));
        if ($this->paginationNumber>$this->paginationCount) $this->paginationNumber = 0;
        if ($this->paginationNumber>=1) {
            $array = $this->getArrayCopy();
            if ($reverse) $array = array_reverse($array);
            $this->exchangeArray(array_slice($array, ($this->paginationNumber - 1) * $limit, $limit));
        }
        return $this;
    }

    // Return current page number in pagination
    public function getPaginationNumber() {
        return $this->paginationNumber;
    }

    // Return highest page number in pagination
    public function getPaginationCount() {
        return $this->paginationCount;
    }

    // Return location for a page in pagination
    public function getPaginationLocation($absoluteLocation = true, $pageNumber = 1) {
        $location = $locationArguments = "";
        if ($pageNumber>=1 && $pageNumber<=$this->paginationCount) {
            $location = $this->hawky->getLocation($absoluteLocation);
            $locationArguments = $this->hawky->w3getargs ("page", $pageNumber>1 ? "$pageNumber" : "");
        }
        return $location.$locationArguments;
    }

    // Return location for previous page in pagination
    public function getPaginationPrevious($absoluteLocation = true) {
        $pageNumber = $this->paginationNumber-1;
        return $this->getPaginationLocation($absoluteLocation, $pageNumber);
    }

    // Return location for next page in pagination
    public function getPaginationNext($absoluteLocation = true) {
        $pageNumber = $this->paginationNumber+1;
        return $this->getPaginationLocation($absoluteLocation, $pageNumber);
    }

    // Return current page number in collection
    public function getPageNumber($page) {
        $pageNumber = 0;
        foreach ($this->getIterator() as $key=>$value) {
            if ($page->getLocation()==$value->getLocation()) {
                $pageNumber = $key+1;
                break;
            }
        }
        return $pageNumber;
    }

    // Return page in collection, null if none
    public function getPage($pageNumber = 1) {
        return ($pageNumber>=1 && $pageNumber<=$this->count()) ? $this->offsetGet($pageNumber-1) : null;
    }

    // Return previous page in collection, null if none
    public function getPagePrevious($page) {
        $pageNumber = $this->getPageNumber($page)-1;
        return $this->getPage($pageNumber);
    }

    // Return next page in collection, null if none
    public function getPageNext($page) {
        $pageNumber = $this->getPageNumber($page)+1;
        return $this->getPage($pageNumber);
    }

    // Return current page filter
    public function getFilter() {
        return $this->filterValue;
    }

    // Return page collection modification date, Unix time or HTTP format
    public function getModified($httpFormat = false) {
        $modified = 0;
        foreach ($this->getIterator() as $page) {
            $modified = max($modified, $page->getModified());
        }
        return $httpFormat ? $this->hawky->getHttpDateFormatted($modified) : $modified;
    }

    // Check if there is a pagination
    public function isPagination() {
        return $this->paginationCount>1;
    }
}

// vim: nospell
