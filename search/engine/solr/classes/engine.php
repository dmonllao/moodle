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

class engine extends \core_search\engine {

    protected $client = null;

    /**
     * Completely prepares a solr query request and executes it.
     * @param object $data containing query and filters.
     * @return mixed array $results containing search results, if found, or
     *              string $results containing an error message.
     */
    public function execute_query($data, $typescontexts) {
        global $USER, $CFG;

        if (!$this->client = $this->get_search_client()) {
            throw new moodle_exception('errorconnection', 'search_solr');
        }

        if (!$this->is_server_ready()) {
            throw new moodle_exception('engineserverstatus', 'search', $CFG->searchengine);
        }

        // Check cache through MUC.
        //$cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'globalsearch', 'search');
        //if (time() - $cache->get('time_' . $USER->id) < SEARCH_CACHE_TIME and $cache->get('query_' . $USER->id) == serialize($data)) {
            //return $results = unserialize($cache->get('results_' . $USER->id));
        //} else { // fire a new search request to server and store its cache
            //$cache->set('query_' . $USER->id, serialize($data));
        //}

        $query = new \SolrQuery();
        $this->set_query($query, $data->queryfield);
        $this->add_fields($query);

        // Search filters applied.
        if (!empty($data->titlefilterqueryfield)) {
            $query->addFilterQuery('title:' . $data->titlefilterqueryfield);
        }
        if (!empty($data->authorfilterqueryfield)) {
            $query->addFilterQuery('user:' . $data->authorfilterqueryfield);
        }
        if (!empty($data->componentname)) {
            $query->addFilterQuery('component:' . $data->componentname);
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
     * @param SolrQuery $query
     * @param object $queryfield Containing query and filters.
     */
    public function set_query($query, $queryfield) {

        // Set hightlighting.
        $query->setHighlight(true);
        $highlightfields = array('content', 'user', 'name', 'title', 'intro');
        foreach ($highlightfields as $field) {
            $query->addHighlightField($field);
        }
        $query->setHighlightFragsize(SEARCH_SET_FRAG_SIZE);
        $query->setHighlightSimplePre('<span class="highlight">');
        $query->setHighlightSimplePost('</span>');

        $query->setQuery($queryfield);
        $query->setStart(SEARCH_SET_START);
        $query->setRows(SEARCH_SET_ROWS);
    }

    /**
     * Sets fields to be returned in the result.
     *
     * These fields should be the same fields specified as 'stored'.
     *
     * @todo We must allow components to add other stuff here.
     *
     * @param SolrQuery $query object.
     */
    public function add_fields($query) {
        $fields = array('id', 'title', 'content', 'userfullname', 'contextid', 'component', 'type', 'courseid', 'userid', 'created', 'modified', 'name', 'intro');

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
        $fields = array('title', 'content', 'userfullname', 'name', 'intro');

        foreach ($fields as $field) {
            if (!empty($doc->$field)) {
                switch ($field) {
                    case 'userfullname':
                        if (!empty($highlighteddoc->$field)) {
                            $doc->$field = $highlighteddoc->$field;
                        }
                        break;

                    default:
                        if (empty($highlighteddoc->$field)) {
                            $doc->$field = substr($doc->{$field}, 0, \core_search::SEARCH_SET_FRAG_SIZE);
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

        $cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'globalsearch', 'search');

        $response = $query_response->getResponse();
        $totalnumfound = $response->response->numFound;
        $docs = $response->response->docs;
        $numgranted = 0;

        // Components search instances.
        $componentsearch = array();

        if (!empty($totalnumfound)) {
            $this->add_highlight_content($response);
            foreach ($docs as $key => $docdata) {
                $componentname = $docdata->component;

                if (isset($componentsearch[$componentname]) && $componentsearch[$componentname] === false) {
                    // We already got that component and it is not available.
                    unset($docs[$key]);
                    continue;
                }

                if (!isset($componentsearch[$componentname])) {
                    // First result that matches this component.

                    $componentsearch[$componentname] = \core_search::get_search_component($componentname);
                    if ($componentsearch[$componentname] === false) {
                        // The component does not support search or it is not available any more.
                        unset($docs[$key]);
                        continue;
                    }
                    if (!$componentsearch[$componentname]->is_enabled()) {
                        // We skip the component if it is not enabled.
                        unset($docs[$key]);
                        $componentsearch[$componentname] = false;
                        continue;
                    }
                }

                $docid = explode('-', $docdata->id);
                $access = $componentsearch[$componentname]->search_access($docid[1]);
                switch ($access) {
                    case SEARCH_ACCESS_DELETED:
                        $this->delete_by_id($docdata->id);
                        unset($docs[$key]);
                        break;
                    case SEARCH_ACCESS_DENIED:
                        unset($docs[$key]);
                        break;
                    case SEARCH_ACCESS_GRANTED:
                        $numgranted++;

                        // Prepare the doc.
                        $doc = new \core\search\document($docid[1], $componentname);
                        $doc->set_from_plain_doc($docdata);
                        $componentsearch[$componentname]->prepare_doc($doc);

                        $docs[$key] = $doc;
                        break;
                }

                if ($numgranted == SEARCH_MAX_RESULTS) {
                    $docs = array_slice($docs, 0, SEARCH_MAX_RESULTS, true);
                    break;
                }
            }
        }
        // set cache through MUC
        // TODO This should have plain objects instead of \core\search\document classes.
        //$cache->set('results_' . $USER->id, serialize($docs));
        //$cache->set('time_' . $USER->id, time());
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
        $url = $this->config->server_hostname . ':' . $this->config->server_port . '/solr/update/extract?';
        $url .= $posturl;
        $params = array();
        $params[$filename] = $file;
        $curl->post($url, $params);
    }

    public function get_more_like_this_text($text) {
        global $CFG;

        if (!$this->client = $this->get_search_client()) {
            throw new moodle_exception('errorconnection', 'search_solr');
        }

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

        if (!$this->client = $this->get_search_client()) {
            throw new moodle_exception('errorconnection', 'search_solr');
        }

        // TODO new add_documents API function please.
        $solrdoc = new \SolrInputDocument();
        foreach ($doc as $field => $value) {
            $solrdoc->addField($field, $value);
        }
        try {
            $result = $this->client->addDocument($solrdoc);
        } catch (\SolrClientException $e) {
            debugging('Solr client error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        } catch (\SolrServerException $e) {
            debugging('Solr server error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    public function commit() {

        if (!$this->client = $this->get_search_client()) {
            throw new moodle_exception('errorconnection', 'search_solr');
        }

        return $this->client->commit();
    }

    public function optimize() {

        if (!$this->client = $this->get_search_client()) {
            throw new moodle_exception('errorconnection', 'search_solr');
        }

        return $this->client->optimize();
    }

    public function delete_by_id($id) {

        if (!$this->client = $this->get_search_client()) {
            throw new moodle_exception('errorconnection', 'search_solr');
        }

        $this->client->deleteById($id);
    }

    public function delete($componentname = null) {
        if ($componentname) {
            $this->delete_by_query('componentname:' . $componentname);
        } else {
            $this->delete_by_query('*:*');
        }
    }
    private function delete_by_query($query) {

        if (!$this->client = $this->get_search_client()) {
            throw new moodle_exception('errorconnection', 'search_solr');
        }

        $this->client->deleteByQuery($query);
    }

    public function is_server_ready() {

        if (!$this->client = $this->get_search_client()) {
            debugging('Error connecting to solr server, ensure that the hostname and the collection you specified are correct', DEBUG_DEVELOPER);
            return false;
        }

        try {
            $this->client->ping();
            return true;
        } catch (\SolrClientException $ex) {
            debugging('Solr client error: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        } catch (\SolrServerException $ex) {
            debugging('Solr server error: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
        // Let other exceptions be triggered as usual.
    }

    public function is_installed() {
        return function_exists('solr_get_version');
    }

    public function get_search_client() {
        global $CFG;

        if ($this->client !== null) {
            return $this->client;
        }

        if (function_exists('solr_get_version')) {

            if (empty($this->config->server_hostname) || empty($this->config->collectionname)) {
                throw new moodle_exception('errorconnection', 'search_solr');
            }

            // Solr connection options.
            $options = array(
                'hostname' => $this->config->server_hostname,
                'path'     => '/solr/' . $this->config->collectionname,
                'login'    => isset($this->config->server_username) ? $this->config->server_username : '',
                'password' => isset($this->config->server_password) ? $this->config->server_password : '',
                'port'     => isset($this->config->server_port) ? $this->config->server_port : '',
                'issecure' => isset($this->config->secure) ? $this->config->secure : '',
                'ssl_cert' => isset($this->config->ssl_cert) ? $this->config->ssl_cert : '',
                'ssl_cert_only' => isset($this->config->ssl_cert_only) ? $this->config->ssl_cert_only : '',
                'ssl_key' => isset($this->config->ssl_key) ? $this->config->ssl_key : '',
                'ssl_password' => isset($this->config->ssl_keypassword) ? $this->config->ssl_keypassword : '',
                'ssl_cainfo' => isset($this->config->ssl_cainfo) ? $this->config->ssl_cainfo : '',
                'ssl_capath' => isset($this->config->ssl_capath) ? $this->config->ssl_capath : '',
            );

            // If php solr extension 1.0.3-alpha installed, one may choose 3.x or 4.x solr from admin settings page.
            $this->client = new \SolrClient($options);

            return $this->client;
        }

        return null;
    }
}
