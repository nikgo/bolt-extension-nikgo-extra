<?php

/*
 * Problem:
 * - Maximum Depth Of An Expression Tree for SQLite
 *  see: https://www.sqlite.org/limits.html
 *  SQLite parses expressions into a tree for processing. 
 *  During code generation, SQLite walks this tree recursively. 
 *  The depth of expression trees is therefore limited in order to avoid using 
 *  too much stack space. 
 *  The SQLITE_MAX_EXPR_DEPTH parameter determines the maximum expression tree 
 *  depth. If the value is 0, then no limit is enforced. The current 
 *  implementation has a default value of 1000. 
 * Fix:
 * - set LIMIT 500 in SQL Statement
 * 
 * Problem:
 * - Search result with more words
 * Fix:
 * - combined all fields to match all words in a query for better results
 *   => Is one word missing, result is not show.
 * Example SQL Query:
 *  ( ( bolt_pages.title LIKE '%search%' OR bolt_pages.body LIKE '%search%' )
 *   AND ( bolt_pages.title LIKE '%all%' OR bolt_pages.body LIKE '%all%' )
 *   AND ( bolt_pages.title LIKE '%words%' OR bolt_pages.body LIKE '%words%' ) )
 * 
 * 
 * Problem:
 * - Search Query too long
 * Fix: 
 * - only 5 search words in a query allowed
 * 
 */

namespace Bolt\Extension\NikGo\Extra;

use Bolt;
use utilphp\util;

class Storage extends Bolt\Storage {

    /** @var Application */
    private $app;

    public function __construct(Bolt\Application $app) {
        parent::__construct($app);
        $this->app = $app;
    }

    /**
     * Search through a single contenttype.
     *
     * Search, weigh and return the results.
     *
     * @param       $query
     * @param       $contenttype
     * @param       $fields
     * @param array $filter
     *
     * @return \Bolt\Content
     */
    private function searchSingleContentType($query, $contenttype, $fields, array $filter = null) {
        // This could be even more configurable
        // (see also Content->getFieldWeights)
        $searchableTypes = array('text', 'textarea', 'html', 'markdown');

        $table = $this->getContenttypeTablename($contenttype);

        // Build fields 'WHERE'
        $fieldsWhere = array();
        $textsWhere = array();

        foreach ($query['words'] as $word) {

            $wordsWhere = array();
            foreach ($fields as $field => $fieldconfig) {

                if (in_array($fieldconfig['type'], $searchableTypes)) {
                    $wordsWhere[] = sprintf('%s.%s LIKE %s', $table, $field, $this->app['db']->quote('%' . $word . '%'));
                }
            }

            $wordWhere = '( ' . implode(' OR ', $wordsWhere) . ' ) ';
            $textsWhere[] = $wordWhere;
        }
        
        $textWhere = '( ' . implode(' AND ', $textsWhere) . ' ) ';
        $fieldsWhere[] = $textWhere;

        // make taxonomies work
        $taxonomytable = $this->getTablename('taxonomy');
        $taxonomies = $this->getContentTypeTaxonomy($contenttype);
        $tagsWhere = array();
        $tagsQuery = '';
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy['behaves_like'] == 'tags') {
                foreach ($query['words'] as $word) {
                    $tagsWhere[] = sprintf('%s.slug LIKE %s', $taxonomytable, $this->app['db']->quote('%' . $word . '%'));
                }
            }
        }
        // only add taxonomies if they exist
        if (!empty($taxonomies) && !empty($tagsWhere)) {
            $tagsQueryA = sprintf("%s.contenttype = '%s'", $taxonomytable, $contenttype);
            $tagsQueryB = implode(' OR ', $tagsWhere);
            $tagsQuery = sprintf(' OR (%s AND (%s))', $tagsQueryA, $tagsQueryB);
        }

        // Build filter 'WHERE"
        // @todo make relations work as well
        $filterWhere = array();
        if (!is_null($filter)) {
            foreach ($fields as $field => $fieldconfig) {
                if (isset($filter[$field])) {
                    $filterWhere[] = $this->parseWhereParameter($table . '.' . $field, $filter[$field]);
                }
            }
        }

        // Build actual where
        $where = array();
        $where[] = sprintf("%s.status = 'published'", $table);
        $where[] = '(( ' . implode(' OR ', $fieldsWhere) . ' ) ' . $tagsQuery . ' )';
        $where = array_merge($where, $filterWhere);

        // Build SQL query
        $select = sprintf(
                'SELECT %s.id FROM %s LEFT JOIN %s ON %s.id = %s.content_id WHERE %s GROUP BY %s.id LIMIT 500', $table, $table, $taxonomytable, $table, $taxonomytable, implode(' AND ', $where), $table
        );

        // Run Query
        $results = $this->app['db']->fetchAll($select);

        if (!empty($results)) {
            $ids = implode(' || ', util::array_pluck($results, 'id'));

            $results = $this->getContent($contenttype, array('id' => $ids, 'returnsingle' => false));

            // Convert and weight
            foreach ($results as $result) {
                $result->weighSearchResult($query);
            }
        }

        return $results;
    }

    /**
     * Search through actual content.
     *
     * Unless the query is invalid it will always return a 'result array'. It may
     * complain in the log but it won't abort.
     *
     * @param string  $q            Search string
     * @param array   $contenttypes Contenttype names to search for:
     *                              - string: Specific contenttype
     *                              - null:   Every searchable contenttype
     * @param array   $filters      Additional filters for contenttypes
     *                              - key is contenttype
     *                              - value is filter
     * @param integer $limit        limit the number of results
     * @param integer $offset       skip this number of results
     *
     * @return mixed false if query is invalid, an array with results if query was executed
     */
    public function searchContent($q, array $contenttypes = null, array $filters = null, $limit = 9999, $offset = 0) {
        $query = $this->decodeSearchQuery($q);

        if (count($query['words']) > 5) {
            return false;
        }

        if (!$query['valid']) {
            return false;
        }

        $appCt = $this->app['config']->get('contenttypes');

        // By default we only search through searchable contenttypes
        if (is_null($contenttypes)) {
            $contenttypes = array_keys($appCt);
            $contenttypes = array_filter(
                    $contenttypes, function ($ct) use ($appCt) {
                if ((isset($appCt[$ct]['searchable']) && $appCt[$ct]['searchable'] === false) ||
                        (isset($appCt[$ct]['viewless']) && $appCt[$ct]['viewless'] === true)
                ) {
                    return false;
                }

                return true;
            }
            );
            $contenttypes = array_map(
                    function ($ct) use ($appCt) {
                return $appCt[$ct]['slug'];
            }, $contenttypes
            );
        }

        // Build our search results array
        $results = array();
        foreach ($contenttypes as $contenttype) {
            $ctconfig = $this->getContentType($contenttype);

            $fields = $ctconfig['fields'];
            $filter = null;

            if (is_array($filters) && isset($filters[$contenttype])) {
                $filter = $filters[$contenttype];
            }

            $subResults = $this->searchSingleContentType($query, $contenttype, $fields, $filter);

            $results = array_merge($results, $subResults);
        }

        // Sort the results
        usort($results, array($this, 'compareSearchWeights'));

        $noOfResults = count($results);

        $pageResults = array();
        if ($offset < $noOfResults) {
            $pageResults = array_slice($results, $offset, $limit);
        }

        return array(
            'query' => $query,
            'no_of_results' => $noOfResults,
            'results' => $pageResults
        );
    }
    
    // Private Methods
    
    /**
     * Compare by search weights.
     *
     * Or fallback to dates or title
     *
     * @param \Bolt\Content $a
     * @param \Bolt\Content $b
     *
     * @return int
     */
    private function compareSearchWeights(\Bolt\Content $a, \Bolt\Content $b)
    {
        if ($a->getSearchResultWeight() > $b->getSearchResultWeight()) {
            return -1;
        }
        if ($a->getSearchResultWeight() < $b->getSearchResultWeight()) {
            return 1;
        }
        if ($a['datepublish'] > $b['datepublish']) {
            // later is more important
            return -1;
        }
        if ($a['datepublish'] < $b['datepublish']) {
            // earlier is less important
            return 1;
        }

        return strcasecmp($a['title'], $b['title']);
    }

    /**
     * Helper function to set the proper 'where' parameter,
     * when getting values like '<2012' or '!bob'.
     *
     * @param string $key
     * @param string $value
     * @param mixed  $fieldtype
     *
     * @return string
     */
    private function parseWhereParameter($key, $value, $fieldtype = false)
    {
        $value = trim($value);

        // check if we need to split.
        if (strpos($value, " || ") !== false) {
            $values = explode(" || ", $value);
            foreach ($values as $index => $value) {
                $values[$index] = $this->parseWhereParameter($key, $value, $fieldtype);
            }

            return "( " . implode(" OR ", $values) . " )";
        } elseif (strpos($value, " && ") !== false) {
            $values = explode(" && ", $value);
            foreach ($values as $index => $value) {
                $values[$index] = $this->parseWhereParameter($key, $value, $fieldtype);
            }

            return "( " . implode(" AND ", $values) . " )";
        }

        // Set the correct operator for the where clause
        $operator = "=";

        $first = substr($value, 0, 1);

        if ($first == "!") {
            $operator = "!=";
            $value = substr($value, 1);
        } elseif (substr($value, 0, 2) == "<=") {
            $operator = "<=";
            $value = substr($value, 2);
        } elseif (substr($value, 0, 2) == ">=") {
            $operator = ">=";
            $value = substr($value, 2);
        } elseif ($first == "<") {
            $operator = "<";
            $value = substr($value, 1);
        } elseif ($first == ">") {
            $operator = ">";
            $value = substr($value, 1);
        } elseif ($first == "%" || substr($value, -1) == "%") {
            $operator = "LIKE";
        }

        // Use strtotime to allow selections like "< last monday" or "this year"
        if (in_array($fieldtype, array('date', 'datetime')) && ($timestamp = strtotime($value)) !== false) {
            $value = date('Y-m-d H:i:s', $timestamp);
        }

        $parameter = sprintf("%s %s %s", $this->app['db']->quoteIdentifier($key), $operator, $this->app['db']->quote($value));

        return $parameter;
    }
    
     /**
     * Decode search query into searchable parts.
     */
    private function decodeSearchQuery($q) {
        $words = preg_split('|[\r\n\t ]+|', trim($q));

        $words = array_map(
                function ($word) {
            return mb_strtolower($word, mb_detect_encoding($word));
        }, $words
        );
        $words = array_filter(
                $words, function ($word) {
            return strlen($word) >= 2;
        }
        );

        return array(
            'valid' => count($words) > 0,
            'in_q' => $q,
            'use_q' => implode(' ', $words),
            'words' => $words
        );
    }
    
}
