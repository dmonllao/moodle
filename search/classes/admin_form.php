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
 * Global Search admin form definition
 *
 * @package   Global Search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class core_search_admin_form extends moodleform {

    function definition() {
        $mform = & $this->_form;
        $checkboxarray = array();
        $checkboxarray[] =& $mform->createElement('checkbox', 'delete', '', get_string('delete', 'search'));
        $mform->addGroup($checkboxarray, 'indexcheckbox', '', array(' '), false);
        $mform->closeHeaderBefore('indexcheckbox');

        $modcheckboxarray = array();
        $globalsearch = new core_search();
        $mods = $globalsearch->get_modules();
        $modcheckboxarray[] =& $mform->createElement('advcheckbox', 'all', '', 'Entire Index', array('group' => 1));
        $modcheckboxarray[] =& $mform->createElement('advcheckbox', 'course', '', get_string('course'), array('group' => 2));  // add course
        foreach ($mods as $mod) {
            $modcheckboxarray[] =& $mform->createElement('advcheckbox', $mod->name, '', ucfirst($mod->name), array('group' => 2));
        }
        $mform->addGroup($modcheckboxarray, 'modadvcheckbox', '', array(' '), false);
        $mform->closeHeaderBefore('modadvcheckbox');

        $mform->disabledIf('modadvcheckbox', 'delete', 'notchecked');

        $mform->addElement('checkbox', 'reindex', '', get_string('reindex', 'search'));

        $this->add_action_buttons($cancel = false);
        $mform->setDefault('action', '');
    }
}
