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
 * Document representation.
 *
 * @package    core_search
 * @copyright  2015 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Respresents a document.
 *
 * @copyright  2015 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class document implements renderable {

    /**
     * @var array $data The document data.
     */
    protected $data = array();

    /**
     * @var \moodle_url Link to the document.
     */
    protected $link = null;

    /**
     * All required fields any doc should contain.
     *
     * We have to choose a format to specify field types, using solr format as we have to choose one and solr is the
     * default search engine.
     *
     * Search engine plugins are responsible of setting their appropriate field types and map these naming to whatever format
     * they need.
     *
     * @var array
     */
    protected $requiredfields = array(
        'id' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => false
        ),
        'title' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'content' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'userfullname' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'contextid' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => true
        ),
        'component' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'type' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => true
        ),
        'courseid' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => false
        ),
        'userid' => array(
            'type' => 'int',
            'stored' => true,
            'indexed' => false
        ),
        'created' => array(
            'type' => 'tdate',
            'stored' => true,
            'indexed' => false
        ),
        'modified' => array(
            'type' => 'tdate',
            'stored' => true,
            'indexed' => false
        ),
    );

    /**
     * All optional fields docs can contain.
     *
     * Although it matches solr fields format, this is just to define the field types. Search
     * engine plugins are responsible of setting their appropriate field types and map these
     * naming to whatever format they need.
     *
     * @var array
     */
    protected $optionalfields = array(
        'name' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        ),
        'intro' => array(
            'type' => 'string',
            'stored' => true,
            'indexed' => true
        )
    );

    /**
     * We ensure that the document has a unique id.
     *
     * @param int $id
     * @param string $component
     * @return void
     */
    public function __construct($id, $component) {
        $this->data['id'] = $component . '-' . $id;
        $this->data['component'] = $component;
    }

    public function get_component_dependant_id() {

    }

    /**
     * Setter.
     *
     * Basic checkings to prevent common issues.
     *
     * We don't check that the field is part of required or optional fields as each
     * component can add their own extra fields.
     *
     * @throws \coding_exception
     * @param string $fieldname The field name
     * @param string|int $value The value to store
     * @return string|int The stored value
     */
    public function set($fieldname, $value) {

        if (!empty($this->requiredfields[$fieldname])) {
            if ($this->requiredfields[$fieldname]['type'] === 'int' && !is_numeric($value)) {
                throw new \coding_exception($fieldname . ' value should be an integer and it is ' . $value);
            }
        } else if (!empty($this->optionalfields[$fieldname])) {
            if ($this->optionalfields[$fieldname]['type'] === 'int' && !is_numeric($value)) {
                throw new \coding_exception($fieldname . ' value should be an integer and it is ' . $value);
            }
        }

        $this->data[$fieldname] = $value;

        return $this->data[$fieldname];
    }

    /**
     * Getter.
     *
     * @param string $field
     * @return string|int
     */
    public function get($field) {
        return $this->data->{$field};
    }

    /**
     * Returns all default fields definitions.
     *
     * @return array
     */
    public function get_default_fields_definition() {
        return $this->requiredfields + $this->optionalfields;
    }

    /**
     * Returns the document ready to submit to the search engine.
     *
     * @throws \coding_exception
     * @return array
     */
    public function get_plain_doc() {

        // We accept an empty time modified and we fallback to created.
        if (empty($this->data['modified'])) {
            if (empty($this->data['created'])) {
                throw new \coding_exception('Missing created field in document with id ' . $this->data['id']);
            }
            $this->data['modified'] = $this->data['created'];
        }

        foreach ($this->requiredfields as $fieldname => $field) {
            // They all need a value, !isset is not enough.
            if (empty($this->data[$fieldname])) {
                throw new \coding_exception('Missing ' . $fieldname . ' field in document with id ' . $this->data['id']);
            }
        }

        return $this->data;
    }

    public function set_from_plain_doc($docdata) {
        foreach ($this->requiredfields as $fieldname => $field) {
            $this->set($fieldname, $docdata[$fieldname]);
        }

        foreach ($this->optionalfields as $fieldname => $field) {
            $this->set($fieldname, $docdata[$fieldname]);
        }
    }

    public function set_link(\moodle_url $url) {
        $this->link = $url;
    }

    /**
     * Gets the link to the doc.
     * @throws \moodle_exception
     * @return \moodle_url
     */
    public function get_link() {
        if (empty($this->link)) {
            throw new \moodle_exception('docwithoutlink', 'search');
        }

        return $this->link;
    }
}
