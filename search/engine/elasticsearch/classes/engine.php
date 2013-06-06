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
 * Elasticsearch engine.
 *
 * @package search_elasticsearch
 * @copyright 2015 Daniel Neis Araujo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_elasticsearch;

defined('MOODLE_INTERNAL') || die();

class engine  extends \core_search\engine {

    private $serverhostname = '';

    public function __construct() {
        global $CFG;
        if (!isset($CFG->elasticsearch_server_hostname)) {
            return false;
        }
        $this->serverhostname = $CFG->elasticsearch_server_hostname;
    }

    public function is_installed() {
        // Elastic Search only needs curl, and Moodle already requires it, so it is ok to just return true.
        return true;
    }

    public function check_server() {
        $url = $this->serverhostname.'/?pretty';
        $c = new \curl();
        if ($response = json_decode($c->get($url))) {
            return $response->status == 200;
        } else {
            return false;
        }
    }

    public function add_document($doc) {
        $url = $this->serverhostname.'/moodle/'.$doc['id'];

        $jsondoc = json_encode($doc);

        $c = new \curl();
        if ($result = json_decode($c->post($url, $jsondoc))) {
            return $result->created == true;
        } else {
            return false;
        }
    }

    public function commit() {
    }

    public function optimize() {
    }

    public function post_file() {
    }

    public function execute_query($data) {


        $search = array('query' => array('bool' => array('must' => array(array('match' => array('content' => $data->queryfield))))));

        if (!empty($data->titlefilterqueryfield)) {
            $search['query']['bool']['must'][] = array('match' => array('title' => $data->titlefilterqueryfield));
        }
        if (!empty($data->authorfilterqueryfield)) {
            $search['query']['bool']['should'][] = array('match' => array('author' => $data->authorfilterqueryfield));
            $search['query']['bool']['should'][] = array('match' => array('user' => $data->authorfilterqueryfield));
        }
        if (!empty($data->modulefilterqueryfield)) {
            $search['query']['bool']['must'][] = array('match' => array('module' => $data->modulefilterqueryfield));
        }

        return $this->make_request($search);
    }

    private function make_request($search) {
        global $CFG;
        $url = $this->serverhostname.'/moodle/_search?pretty';

        $c = new \curl();
        $results = json_decode($c->post($url, json_encode($search)));
        $docs = array();
        if (isset($results->hits))  {
            $numgranted = 0;
            foreach ($results->hits->hits as $r) {
                $sourceid = explode('_', $r->_source->id);
                $modname = $sourceid[0];
                $modgssupport = 'gs_support_' . $modname;
                if (!empty($CFG->$modgssupport)) {
                    include_once($CFG->dirroot.'/mod/'.$modname.'/db/search.php');
                    $access_func = $modname . '_search_access';
                    $acc = $access_func($sourceid[1]);
                    switch ($acc) {
                        case SEARCH_ACCESS_DELETED:
                            $this->delete_index_by_id($value->id);
                            break;
                        case SEARCH_ACCESS_DENIED:
                            break;
                        case SEARCH_ACCESS_GRANTED:
                            if (!isset($r->_source->author)) {
                                $r->_source->author = array($r->_source->user);
                            }
                            $docs[] = $r->_source;
                            $numgranted++;
                            break;
                    }
                }
            }
        } else {
            if (!$results) {
                return false;
            }
            return $results->error;
        }
        return $docs;
    }

    public function get_more_like_this_text($text) {

        $search = array('query' =>
                            array('more_like_this' =>
                                      array('fields' => array('content'),
                                            'like_text' => $text,
                                            'min_term_freq' => 1,
                                            'max_query_terms' => 12)));
        return $this->make_request($search);
    }

    public function delete($module = null) {
        if ($module) {
            // TODO
        } else {

            $url = $this->serverhostname.'/moodle/?pretty';
            $c = new \curl();
            if ($response = json_decode($c->delete($url))) {
                if ( (isset($response->acknowledged) && ($response->acknowledged == true)) ||
                     ($response->status == 404)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        }
    }
}
