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
 * Global Search Block page.
 *
 * @package    block
 * @subpackage globalsearch
 * @copyright  Prateek Sachan {@link http://prateeksachan.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The Global Search block class
 */
class block_globalsearch extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_globalsearch');
    }

    function get_content() {
        global $CFG, $OUTPUT;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content =  new stdClass;
        $this->content->footer = '';

        if (\core_search::is_global_search_enabled() === false) {
            $this->content->text = get_string('globalsearchdisabled', 'search');
            return $this->content;
        }

        // Getting the global search enabled components.
        $components = \core_search::get_search_components_list(true);

        $url = new moodle_url('/search/index.php');
        $this->content->footer .= html_writer::link($url, get_string('advancequeries', 'search'));

        $this->content->text  = html_writer::start_tag('div', array('class' => 'searchform'));
        $this->content->text .= html_writer::start_tag('form', array('action' => $url->out()));
        $this->content->text .= html_writer::start_tag('fieldset', array('action' => 'invisiblefieldset'));
        $this->content->text .= html_writer::tag('label', get_string('search', 'block_globalsearch'), array('for' => 'searchform_search', 'class' => 'accesshide'));
        $this->content->text .= html_writer::empty_tag('input', array('id' => 'searchform_search', 'name' => 'search', 'type' => 'text', 'size' => '15'));
        $this->content->text .= $OUTPUT->help_icon('globalsearch', 'search');
        $this->content->text .= html_writer::tag('label', get_string('searchin', 'block_globalsearch'),
            array('for' => 'id_globalsearch_component'));

        $options = array();
        foreach ($components as $componentname => $componentsearch) {
            $options[$componentname] = $componentsearch->get_component_visible_name();
        }
        $this->content->text .= html_writer::select($options, 'component', '',
            array('' => get_string('allcomponents', 'block_globalsearch')), array('id' => 'id_globalsearch_component'));
        $this->content->text .= html_writer::tag('button', get_string('search', 'block_globalsearch'),
            array('id' => 'searchform_button', 'type' => 'submit', 'title' => 'globalsearch'));
        $this->content->text .= html_writer::end_tag('fieldset');
        $this->content->text .= html_writer::end_tag('form');
        $this->content->text .= html_writer::end_tag('div');

        return $this->content;
    }
}
