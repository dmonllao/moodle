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
 * Solr schema manipulation manager.
 *
 * @package   search_solr
 * @copyright 2015 David Monllao
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_solr;

defined('MOODLE_INTERNAL') || die();

/**
 * Schema class to interact with Solr schema.
 *
 * At the moment it only implements create which should be enough for a basic
 * moodle configuration in Solr.
 *
 * @package   search_solr
 * @copyright 2015 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class schema {

    protected $config = null;

    public function __construct() {
        if (!$this->config = get_config('search_solr')) {
            throw new \moodle_exception('solrnotset', 'search_solr');
        }

        if (empty($this->config->server_hostname) || empty($this->config->collectionname)) {
            throw new \moodle_exception('solrnotset', 'search_solr');
        }
    }

    /**
     * Creates the Solr schema required for moodle.
     *
     * @return bool
     */
    public function create() {
        $doc = new \core\search\document();
        $fields = $doc->get_default_fields_definition();

        // Field id is already there.
        unset($fields['id']);

        return $this->add_fields($fields);
    }

    /**
     * Adds the provided fields to Solr schema.
     *
     * Intentionally separated from create(), it can be called to add extra fields.
     * fields separately.
     *
     * @throws coding_exception
     * @throws moodle_exception
     * @param array $fields \core_search\doc::$requiredfields format
     * @return bool
     */
    public function add_fields($fields) {

        $curl = new \curl();
        $curl->setHeader('Content-type: application/json');

        // TODO Bah! Look at all solr config login/password... we can't use SolrClient for this.
        $port = !empty($this->config->server_port) ? $this->config->server_port : '';
        $url = rtrim($this->config->server_hostname, '/') . ':' . $port . '/solr/' . $this->config->collectionname . '/schema';

        // Check that non of them exists.
        foreach ($fields as $fieldname => $data) {
            $fieldurl = $url . '/fields/' . $fieldname;
            $results = $curl->get($fieldurl);
            if (!$results) {
                throw new \moodle_exception('errorcreatingschema', 'search_solr', '', get_string('nodatafromserver', 'search_solr'));
            }
            $results = json_decode($results);

            // The field should not exist.
            if (empty($results->error) || (!empty($results->error) && $results->error->code !== 404)) {
                if (!empty($results->error)) {
                    $errormsg = $results->error->msg;
                } else {
                    $errormsg = get_string('schemafieldalreadyexists', 'search_solr', $fieldname);
                }
                throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $errormsg);
            }
        }

        // Add all fields.
        foreach ($fields as $fieldname => $data) {

            if (!isset($data['type']) || !isset($data['stored']) || !isset($data['indexed'])) {
                var_dump($data);
                throw new \coding_exception($fieldname . ' does not define all required field params.');
            }
            // Changing default multiValued value to false as we want to match values easily.
            $params = array(
                'add-field' => array(
                    'name' => $fieldname,
                    'type' => $data['type'],
                    'stored' => $data['stored'],
                    'multiValued' => false,
                    'indexed' => $data['indexed']
                )
            );
            $results = $curl->post($url, json_encode($params));
            $this->check_results($results);
        }

        return true;
    }

    /**
     * Checks that the results do not contain errors.
     *
     * @throws moodle_exception
     * @param string $results curl response body
     * @return void
     */
    public function check_results($result) {
        if (!$result) {
            throw new \moodle_exception('errorcreatingschema', 'search_solr', '', get_string('nodatafromserver', 'search_solr'));
        }
        $results = json_decode($result);
        if (!$results) {
            throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $result);
        }

        // It comes as error when fetching fields data.
        if (!empty($results->error)) {
            throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $results->error);
        }

        // It comes as errors when adding fields.
        if (!empty($results->errors)) {

            // We treat this error separately.
            $errorstr = '';
            foreach ($results->errors as $error) {
                $errorstr .= implode(', ', $error->errorMessages);
            }
            throw new \moodle_exception('errorcreatingschema', 'search_solr', '', $errorstr);
        }

    }
}
