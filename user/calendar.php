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
 * Allows you to edit a users profile
 *
 * @copyright 2015 Shamim Rezaie  http://foodle.org
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_user
 */

require_once('../config.php');
require_once($CFG->libdir.'/gdlib.php');
require_once($CFG->dirroot.'/user/calendar_form.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/user/lib.php');

$userid = optional_param('id', $USER->id, PARAM_INT);    // User id.
$courseid = optional_param('course', SITEID, PARAM_INT);   // Course id (defaults to Site).

$PAGE->set_url('/user/calendar.php', array('id' => $userid, 'course' => $courseid));

list($user, $course) = useredit_setup_preference_page($userid, $courseid);

// Create form.
$calendarform = new user_edit_calendar_form(null, array('userid' => $user->id));
$calendarform->set_data($user);

$redirect = new moodle_url("/user/preferences.php", array('userid' => $user->id));
if ($calendarform->is_cancelled()) {
    redirect($redirect);
} else if ($data = $calendarform->get_data()) {
    $calendartype = $data->calendartype;
    // If the specified calendar type does not exist, use the site default.
    if (!array_key_exists($calendartype, \core_calendar\type_factory::get_list_of_calendar_types())) {
        $calendartype = $CFG->calendartype;
    }

    $user->calendartype = $calendartype;
    // Update user with new calendar type.
    user_update_user($user, false, false);

    // Trigger event.
    \core\event\user_updated::create_from_userid($user->id)->trigger();

    if ($USER->id == $user->id) {
        $USER->calendartype = $calendartype;
    }

    redirect($redirect);
}

// Display page header.
$streditmycalendar = get_string('preferredcalendar', 'calendar');
$userfullname     = fullname($user, true);

$PAGE->navbar->includesettingsbase = true;

$PAGE->set_title("$course->shortname: $streditmycalendar");
$PAGE->set_heading($userfullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($streditmycalendar);

// Finally display THE form.
$calendarform->display();

// And proper footer.
echo $OUTPUT->footer();

