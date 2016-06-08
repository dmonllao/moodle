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
 * Global search external API.
 *
 * @package    core_search
 * @category   external
 * @copyright  2016 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.2
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

/**
 * Badges external functions
 *
 * @package    core_search
 * @category   external
 * @copyright  2016 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.2
 */
class core_search_external extends external_api {

    /**
     * get_results parameters.
     *
     * @since Moodle 3.2
     * @return external_function_parameters
     */
    public static function get_results_parameters() {
        return new external_function_parameters(
            array(
                'q' => new external_value(PARAM_NOTAGS, 'the search query'),
                'filters' => new external_single_structure(
                    array(
                        'title' => new external_value(PARAM_NOTAGS, 'result title', VALUE_OPTIONAL),
                        'areaids' => new external_multiple_structure(
                            new external_value(PARAM_RAW, 'areaid'), 'restrict results to these areas', VALUE_DEFAULT, array()
                        ),
                        'courseids' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'courseid'), 'restrict results to these courses', VALUE_DEFAULT, array()
                        ),
                        'timestart' => new external_value(PARAM_INT, 'result title', VALUE_DEFAULT, 0),
                        'timeend' => new external_value(PARAM_INT, 'result title', VALUE_DEFAULT, 0)
                    ), 'filters to apply', VALUE_OPTIONAL
                ),
                'page' => new external_value(PARAM_INT, 'results page number starting from 0, defaults to the first page',
                    VALUE_DEFAULT)
            )
        );
    }

    /**
     * Gets global search results based on the provided query and filters.
     *
     * @param string $q
     * @param array $filters
     * @param int $page
     * @return array
     */
    public static function get_results($q, $filters = array(), $page = 0) {

        $params = self::validate_parameters(self::get_results_parameters(), array(
            'q' => $q,
            'filters' => $filters,
            'page' => $page)
        );

        $system = context_system::instance();
        external_api::validate_context($system);

        require_capability('moodle/search:query', $system);

        if (\core_search\manager::is_global_search_enabled() === false) {
            throw new \moodle_exception('globalsearchdisabled', 'search');
        }

        $search = \core_search\manager::instance();

        $data = new stdClass();
        $data->q = $params['q'];

        if (!empty($params['filters']['title'])) {
            $data->title = $params['filters']['title'];
        }

        if (!empty($params['filters']['areaids'])) {
            $data->areaids = $params['filters']['areaids'];
        }
        if (!empty($params['filters']['courseids'])) {
            $data->courseids = $params['filters']['courseids'];
        }

        if (!empty($params['filters']['timestart'])) {
            $data->timestart = $params['filters']['timestart'];
        }
        if (!empty($params['filters']['timeend'])) {
            $data->timeend = $params['filters']['timeend'];
        }

        $docs = $search->paged_search($data, $page);

        $return = [
            'totalcount' => $docs->totalcount,
            'warnings' => [],
            'results' => []
        ];

        // Convert results to simple data structures.
        if ($docs) {
            foreach ($docs->results as $doc) {
                $return['results'][] = $doc->export_doc_info();
            }
        }

        return $return;
    }

    /**
     * Returns description of method get_results.
     *
     * @return external_single_structure
     */
    public static function get_results_returns() {

        // The fields depend on the search engine in use. Likely to reuse the previous \core_search\manager instance.
        $search = \core_search\manager::instance();
        $documentclassname = $search->get_engine()->get_document_classname();
        $callable = array($documentclassname, 'get_default_fields_definition');
        $fields = call_user_func($callable);
        $resultstructure = [];
        foreach ($fields as $fieldname => $field) {
            switch ($field['type']) {
                case 'int':
                case 'tdate':
                    // Including tdate as they are stored as docs in core_search\document and children.
                    $type = PARAM_INT;
                    break;
                case 'string':
                case 'text':
                    // It can even contain HTML even if we are only supposed to index plain text because search engines
                    // can add their own stuff (highlighting for example).
                    $type = PARAM_RAW;
                    break;
                default:
                    $type = PARAM_RAW;

            }

            if (call_user_func(array($documentclassname, 'field_is_required'), $fieldname)) {
                $required = VALUE_REQUIRED;
            } else {
                // We consider optional and engine fields to be optional.
                $required = VALUE_OPTIONAL;
            }

            $resultstructure[$fieldname] = new external_value($type, $fieldname . ' search field', $required);
        }

        return new external_single_structure(
            array(
                'totalcount' => new external_value(PARAM_INT, 'Total number of results'),
                'results' => new external_multiple_structure(
                    new external_single_structure($resultstructure),
                    'Search results'
                )
            )
        );
    }
}
