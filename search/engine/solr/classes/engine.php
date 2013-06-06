<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Solr engine.
 *
 * @package search_solr
 * @copyright 2015 Daniel Neis Araujo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_solr;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot.'/course/lib.php');  // needed for course search

class engine  extends \core_search\engine {

    /**
     * Completely prepares a solr query request and executes it.
     * @param object $data containing query and filters.
     * @return mixed array $results containing search results, if found, or
     *              string $results containing an error message.
     */
    public function execute_query($data) {
        global $USER, $CFG;
        $this->client = $this->get_search_client();

        if (!$this->check_server()) {
            return 'Solr Jetty Server is not running!';
        }

        // check cache through MUC
        $cache = cache::make_from_params(cache_store::MODE_SESSION, 'globalsearch', 'search');
        if (time() - $cache->get('time_' . $USER->id) < SEARCH_CACHE_TIME and $cache->get('query_' . $USER->id) == serialize($data)) {
            return $results = unserialize($cache->get('results_' . $USER->id));
        } else { // fire a new search request to server and store its cache
            $cache->set('query_' . $USER->id, serialize($data));
        }

        $query = new SolrQuery();
        $this->set_query($query, $data);
        $this->prepare_filter($data);
        $this->add_fields($query);

        // search filters applied
        if (!empty($data->titlefilterqueryfield)) {
            $query->addFilterQuery($data->titlefilterqueryfield);
        }
        if (!empty($data->authorfilterqueryfield)) {
            $query->addFilterQuery($data->authorfilterqueryfield);
        }
        if (!empty($data->modulefilterqueryfield)) {
            $query->addFilterQuery($data->modulefilterqueryfield);
        }
        if (!empty($data->searchfromtime) or !empty($data->searchtilltime)) {
            if (empty($data->searchfromtime)) {
                $data->searchfromtime = '*';
            } else {
                $data->searchfromtime = gmdate('Y-m-d\TH:i:s\Z', $data->searchfromtime);
            }
            if (empty($data->searchtilltime)) {
                $data->searchtilltime = '*';
            } else {
                $data->searchtilltime = gmdate('Y-m-d\TH:i:s\Z', $data->searchtilltime);
            }

            $query->addFilterQuery('modified:[' . $data->searchfromtime . ' TO ' . $data->searchtilltime . ']');
        }

        try {
            return $this->query_response($this->client->query($query));
        } catch (SolrClientException $ex) {
            return 'Bad query request!';
        }
    }

    /**
     * Prepares a new query by setting the query, start offset and rows to return.
     * @param SolrQuery $query object.
     * @param object $data containing query and filters.
     */
    public function set_query($query, $data) {
        $this->set_highlight($query);
        $query->setQuery($data->queryfield);
        $query->setStart(SEARCH_SET_START);
        $query->setRows(SEARCH_SET_ROWS);
    }

    /**
     * Sets highlighting properties.
     * @param SolrQuery $query object.
     */
    public function set_highlight($query) {
        $query->setHighlight(true);
        $highlightfields = array('content', 'user', 'author', 'name', 'title', 'intro');
        foreach ($highlightfields as $field) {
            $query->addHighlightField($field);
        }
        $query->setHighlightFragsize(SEARCH_SET_FRAG_SIZE);
        $query->setHighlightSimplePre('<span class="highlight">');
        $query->setHighlightSimplePost('</span>');
    }

    /**
     * Prepares filter to be applied to query.
     * @param object $data containing query and filters.
     */
    public function prepare_filter($data) {
        if (!empty($data->titlefilterqueryfield)) {
            $data->titlefilterqueryfield = 'title:' . $data->titlefilterqueryfield;
        }
        if (!empty($data->authorfilterqueryfield)) {
            $data->authorfilterqueryfield = 'author:' . $data->authorfilterqueryfield;
        }
        if (!empty($data->modulefilterqueryfield)) {
            $data->modulefilterqueryfield = 'module:' . $data->modulefilterqueryfield;
        }
    }

    /**
     * Sets fields to be returned in the result.
     * @param SolrQuery $query object.
     */
    public function add_fields($query) {
        $fields = array('id', 'user', 'created', 'modified', 'author', 'name', 'title', 'intro', 'content',
                        'courseid', 'mime', 'contextlink', 'directlink', 'modulelink', 'module');

        foreach ($fields as $field) {
            $query->addField($field);
        }
    }

    /**
     * Finds the key common to both highlighing and docs array returned from response.
     * @param object $response containing results.
     */
    public function add_highlight_content($response) {
        $highlightedobject = $response->highlighting;
        foreach ($response->response->docs as $doc) {
            $x = $doc->id;
            $highlighteddoc = $highlightedobject->$x;
            $this->merge_highlight_field_values($doc, $highlighteddoc);
        }
    }

    /**
     * Adds the highlighting array values to docs array values.
     * @param object $doc containing the results.
     * @param object $highlighteddoc containing the highlighted results values.
     */
    public function merge_highlight_field_values($doc, $highlighteddoc) {
        $fields = array('content', 'user', 'author', 'name', 'title', 'intro');

        foreach ($fields as $field) {
            if (!empty($doc->$field)) {
                switch ($field) {
                    case 'author':
                        if (!empty($highlighteddoc->$field)) {
                            $doc->$field = $highlighteddoc->$field;
                        }
                        break;

                    default:
                        if (empty($highlighteddoc->$field)) {
                            $doc->$field = substr($doc->$field, 0, SEARCH_SET_FRAG_SIZE);
                        } else {
                            $doc->$field = reset($highlighteddoc->$field);
                        }
                        break;
                }
            }
        }
    }

    /**
     * Filters the response on Moodle side.
     * @param object $query_response containing the response return from solr server.
     * @return object $results containing final results to be displayed.
     */
    public function query_response($query_response) {
        global $CFG, $USER;

        $cache = cache::make_from_params(cache_store::MODE_SESSION, 'globalsearch', 'search');

        $response = $query_response->getResponse();
        $totalnumfound = $response->response->numFound;
        $docs = $response->response->docs;
        $numgranted = 0;

        if (!empty($totalnumfound)) {
            $this->add_highlight_content($response);
            foreach ($docs as $key => $value) {
                $solr_id = explode('_', $value->id);
                $modname = 'gs_support_' . $solr_id[0];
                // Check whether the module belonging to search response's Solr Document is gs_supported or not.
                if (!empty($CFG->$modname)) {
                    $access_func = $solr_id[0] . '_search_access';
                    $acc = $access_func($solr_id[1]);
                    switch ($acc) {
                        case SEARCH_ACCESS_DELETED:
                            search_delete_index_by_id($value->id);
                            unset($docs[$key]);
                            break;
                        case SEARCH_ACCESS_DENIED:
                            unset($docs[$key]);
                            break;
                        case SEARCH_ACCESS_GRANTED:
                            $numgranted++;
                            break;
                    }
                } else {
                    unset($docs[$key]);
                }

                if ($numgranted == SEARCH_MAX_RESULTS) {
                    $docs = array_slice($docs, 0, SEARCH_MAX_RESULTS, true);
                    break;
                }
            }
        }
        // set cache through MUC
        $cache->set('results_' . $USER->id, serialize($docs));
        $cache->set('time_' . $USER->id, time());
        return $docs;
    }

    /**
     * Builds the cURL object's url for indexing Rich Documents
     * @return string $url
     */
    public function post_file($file, $posturl) {
        global $CFG;
        $filename = urlencode($file->get_filename());
        $curl = new curl();
        $url = $CFG->solr_server_hostname . ':' . $CFG->solr_server_port . '/solr/update/extract?';
        $url .= $posturl;
        $params = array();
        $params[$filename] = $file;
        $curl->post($url, $params);
    }

    public function get_more_like_this_text($text) {
        global $CFG;

        $query = new \SolrQuery();
        $this->add_fields($query);
        $query->setMlt(true);
        $query->setMltCount(5);
        $query->addMltField('content');
        $query->setQuery('"'.$text.'"');
        $query->setStart(0);
        $query->setRows(10);
        $query->setMltMinDocFrequency(1);
        $query->setMltMinTermFrequency(1);
        $query->setMltMinWordLength(4);
        $query->setOmitHeader(5);
        $query_response = $this->client->query($query);
        $response = $query_response->getResponse();
        if ($mlt = (array) $response->moreLikeThis) {
            $mlt = array_pop($mlt);

            $cleanresults = array();
            if ($mlt->numFound > 0) {
                foreach ($mlt->docs as $r){
                    $link = substr($r->contextlink, 0, strpos($r->contextlink, '#'));
                    $discussion = substr($link, strpos($link, '=') + 1);
                    $cleanresults[$discussion] = array('name' => $r->title, 'link' => $link);
                }
            }
            return $cleanresults;
        }
    }

    public function add_document($doc) {
        $solrdoc = new SolrInputDocument();
        foreach ($doc as $field => $value) {
            $doc->addField($field, $value);
        }
        return $this->client->addDocument($doc);
    }

    public function commit() {
        return $this->client->commit();
    }

    public function optimize() {
        return $this->client->optimize();
    }

    public function delete_by_id($id) {
        return $this->client->deleteById($id);
    }

    public function delete($module = null) {
        if ($module) {
            $this->delete_by_query('module:' . $module);
        } else {
            $this->delete_by_query('*:*');
        }
    }
    private function delete_by_query($query) {
        return $this->client->deleteByQuery($query);
    }

    public function check_server() {
        try {
            $this->client->ping();
            return 1;
        } catch (SolrClientException $ex) {
            return 0;
        }
    }

    public function is_installed() {
        function_exists('solr_get_version') ? $x = 1 : $x = 0;
        return $x;
    }

    public function get_search_client() {
        global $CFG;

        if (function_exists('solr_get_version')) {
            // Solr connection options.
            $options = array(
                'hostname' => isset($CFG->solr_server_hostname) ? $CFG->solr_server_hostname : '',
                'login'    => isset($CFG->solr_server_username) ? $CFG->solr_server_username : '',
                'password' => isset($CFG->solr_server_password) ? $CFG->solr_server_password : '',
                'port'     => isset($CFG->solr_server_port) ? $CFG->solr_server_port : '',
                'issecure' => isset($CFG->solr_secure) ? $CFG->solr_secure : '',
                'ssl_cert' => isset($CFG->solr_ssl_cert) ? $CFG->solr_ssl_cert : '',
                'ssl_cert_only' => isset($CFG->solr_ssl_cert_only) ? $CFG->solr_ssl_cert_only : '',
                'ssl_key' => isset($CFG->solr_ssl_key) ? $CFG->solr_ssl_key : '',
                'ssl_password' => isset($CFG->solr_ssl_keypassword) ? $CFG->solr_ssl_keypassword : '',
                'ssl_cainfo' => isset($CFG->solr_ssl_cainfo) ? $CFG->solr_ssl_cainfo : '',
                'ssl_capath' => isset($CFG->solr_ssl_capath) ? $CFG->solr_ssl_capath : '',
                'path' => isset($path) ? $path : '', // a way to use more than one collection/core
            );

            // If php solr extension 1.0.3-alpha installed, one may choose 3.x or 4.x solr from admin settings page.
            return new SolrClient($options);
        }
        return null;
    }
}
