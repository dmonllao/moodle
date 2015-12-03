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
 * Form to edit a users preferred language
 *
 * @copyright 2015 Shamim Rezaie  http://foodle.org
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_user
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Class user_edit_calendar_form.
 *
 * @copyright 2015 Shamim Rezaie  http://foodle.org
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_edit_calendar_form extends moodleform {

    /**
     * Define the form.
     */
    public function definition () {
        global $CFG, $COURSE, $USER;

        $mform = $this->_form;
        $userid = $USER->id;

        if (is_array($this->_customdata)) {
            if (array_key_exists('userid', $this->_customdata)) {
                $userid = $this->_customdata['userid'];
            }
        }

        // Add some extra hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'course', $COURSE->id);
        $mform->setType('course', PARAM_INT);

        $calendartypes = \core_calendar\type_factory::get_list_of_calendar_types();
        $mform->addElement('select', 'calendartype', get_string('preferredcalendar', 'calendar'), $calendartypes);
        $mform->setDefault('calendartype', $CFG->calendartype);

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Extend the form definition after the data has been parsed.
     */
    public function definition_after_data() {
        global $CFG, $DB, $OUTPUT;

        $mform = $this->_form;

        // If calendar type does not exist, use site default calendar type.
        if ($calsel = $mform->getElementValue('calendartype')) {
            $calendar = reset($calsel);
            // Check calendar type exists.
            if (!array_key_exists($calendar, \core_calendar\type_factory::get_list_of_calendar_types())) {
                $calendartypeel =& $mform->getElement('calendartype');
                $calendartypeel->setValue($CFG->calendartype);
            }
        }
    }
}
