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
 * Global Search search form definition
 *
 * @package   Global Search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class core_search_search_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('header', 'search', get_string('search', 'search'));

        $mform->addElement('text', 'queryfield', get_string('query', 'search'));
        $mform->addHelpButton('queryfield', 'globalsearch', 'search');
        $mform->setType('queryfield', PARAM_TEXT);
        $mform->addRule('queryfield', get_string('emptyqueryfield', 'search'), 'required', null, 'client');

        $mform->addElement('header', 'filterquerysection', get_string('filterqueryheader', 'search'));
        $mform->addElement('text', 'titlefilterqueryfield', get_string('titlefilterquery', 'search'));
        $mform->setType('titlefilterqueryfield', PARAM_TEXT);

        $mform->addElement('text', 'authorfilterqueryfield', get_string('authorfilterquery', 'search'));
        $mform->setType('authorfilterqueryfield', PARAM_TEXT);

        $search = new core_search();

        $mods = $search->get_modules();
        $modules = array();
        $modules [] = "All modules";
        $modules ['course'] = get_string('course');  // add course
        foreach ($mods as $mod) {
            $modules[$mod->name] = ucfirst($mod->name);
        }
        $mform->addElement('select', 'modulefilterqueryfield', get_string('modulefilterquery', 'search'), $modules);

        $mform->addElement('header', 'filtertimesection', get_string('filtertimesection', 'search'));

        $mform->addElement('date_time_selector', 'searchfromtime', get_string('searchfromtime', 'search'), array('optional'=>true));
        $mform->setDefault('searchfromtime', 0);

        $mform->addElement('date_time_selector', 'searchtilltime', get_string('searchtilltime', 'search'), array('optional'=>true));
        $mform->setDefault('searchtilltime', 0);

        $this->add_action_buttons(false, get_string('search', 'search'));
    }
}
