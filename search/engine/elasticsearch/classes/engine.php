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

require_once($CFG->dirroot . '/lib/filelib.php');

class engine extends \core_search\engine {

    protected $serverhostname = null;

    protected $instancename = null;

    public function __construct() {
        global $CFG;

        if (!$this->serverhostname = get_config('search_elasticsearch', 'server')) {
            return false;
        }
        if (!$this->instancename = get_config('search_elasticsearch', 'instancename')) {
            $this->instancename = 'moodle';
        }
    }

    public function is_installed() {
        // Elastic Search only needs curl, and Moodle already requires it, so it is ok to just return true.
        return true;
    }

    public function is_server_ready() {
        $url = $this->serverhostname.'/?pretty';
        $c = new \curl();
        if ($response = json_decode($c->get($url))) {
            return $response->status == 200;
        } else {
            return false;
        }
    }

    public function add_document($doc) {
        $url = $this->serverhostname.'/' . $this->instancename . '/' . $doc['component'];

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

    public function post_file($file, $posturl) {
    }

    public function execute_query($data, $typescontexts) {


        $search = array('query' => array('bool' => array('must' => array(array('match' => array('content' => $data->queryfield))))));

        if (!empty($data->titlefilterqueryfield)) {
            $search['query']['bool']['must'][] = array('match' => array('title' => $data->titlefilterqueryfield));
        }
        if (!empty($data->authorfilterqueryfield)) {
            $search['query']['bool']['should'][] = array('match' => array('author' => $data->authorfilterqueryfield));
            $search['query']['bool']['should'][] = array('match' => array('user' => $data->authorfilterqueryfield));
        }

        // All components by default.
        $selectedtype = false;
        if (!empty($data->componentname)) {
            $selectedtype = $data->componentname;
        }

        // Valid contexts associated with types.
        if ($typescontexts) {
            $search['filter'] = array();
            foreach ($typescontexts as $type => $contextids) {
                // No need to include all filters if only 1 type is selected.
                if ($selectedtype === false || $type === $selectedtype) {
                    $filter = array(
                        'and' => array(
                            array('terms' => array('contextid' => $contextids)),
                            array('type' => array('value' => $type))
                        )
                    );

                    // Adding it to the list of filters.
                    $search['filter']['or'][] = $filter;
                    // TODO The OR node should contain a _cached true to force caching of all this stuff
                    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-and-filter.html
                }
            }
        }

        return $this->make_request($search, $selectedtype);
    }

    private function make_request($search, $type = false) {
        global $CFG;

        $url = $this->serverhostname.'/' . $this->instancename;
        if ($type !== false) {
            $url .= '/' . $type;
        }
        $url .= '/_search?pretty';

        // Components search instances.
        $componentsearch = array();

        $c = new \curl();
        $results = json_decode($c->post($url, json_encode($search)));
        $docs = array();
        if (isset($results->hits))  {
            $numgranted = 0;
            foreach ($results->hits->hits as $r) {

                $componentname = $r->_source->component;

                if (isset($componentsearch[$componentname]) && $componentsearch[$componentname] === false) {
                    // We already got that component and it is not available.
                    continue;
                }

                if (!isset($componentsearch[$componentname])) {
                    // First result that matches this component.

                    $componentsearch[$componentname] = \core_search::get_search_component($componentname);
                    if ($componentsearch[$componentname] === false) {
                        // The component does not support search or it is not available any more.
                        continue;
                    }
                    if (!$componentsearch[$componentname]->is_enabled()) {
                        // We skip the component if it is not enabled.
                        $componentsearch[$componentname] = false;
                        continue;
                    }
                }

                $sourceid = explode('-', $r->_source->id);

                $access = $componentsearch[$componentname]->search_access($sourceid[1]);
                switch ($access) {
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

            $url = $this->serverhostname.'/' . $this->instancename . '/?pretty';
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
