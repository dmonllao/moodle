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

        $search = new core_search();

        $this->content         =  new stdClass;
        $this->content->footer = '';

        // Getting the global search supported mods list.
        $mods = $search->get_modules();
        $modules = array();
        $modules [] = "All modules";
        foreach ($mods as $mod) {
            $modules[$mod->name] = ucfirst($mod->name);
        }

        $url = new moodle_url('/search/index.php');
        $this->content->footer .= html_writer::link($url, get_string('advancequeries', 'search'));

        $this->content->text  = html_writer::start_tag('div', array('class' => 'searchform'));
        $this->content->text .= '<form action="'.$CFG->wwwroot.'/search/index.php" style="display:inline">';
        $this->content->text .= '<fieldset class="invisiblefieldset">';
        $this->content->text .= '<label for="searchform_search">Search: </label>'.
                                '<input id="searchform_search" name="search" type="text" size="15" />';
        $this->content->text .= $OUTPUT->help_icon('globalsearch', 'search');
        $this->content->text .= '<label for="searchform_fq_module">Search in: </label><br>' .
                                '<select name="fq_module" id="searchform_fq_module">';
        foreach ($modules as $key => $value) {
            $this->content->text .= '<option value="' . $key . '">' . $value . '</option>';
        }
        $this->content->text .= '</select>';
        $this->content->text .= '<button id="searchform_button" type="submit" title="globalsearch">Go!</button><br />';
        $this->content->text .= '</fieldset>';
        $this->content->text .= '</form>';
        $this->content->text .= html_writer::end_tag('div');

        return $this->content;
    }

    // Running the cron job for indexing.
    function cron() {
        global $CFG;

        if ($CFG->enableglobalsearch) {
            include($CFG->dirroot . '/search/cron.php');
        }
    }

}
