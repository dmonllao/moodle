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
 * This file contains the definition for the class assignment
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package   local_mformdemo
 * @copyright 2015 Adrian
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/mformdemo/testedit_form.php');

class local_mformdemo_temp_thing {

    public function __construct() {
        // Nothing yet.
    }

    static public function create_thing_form() {
        global $PAGE, $CFG;

        $context = context_system::instance();
        $PAGE->set_context($context);
        $PAGE->set_url('/local/mformdemo/index.php');
        $PAGE->set_requirements_for_fragments();

        $testform = new testedit_form();

        if ($data = $testform->get_data()) {
            print_object($data);
            return;
        } else {
            $mformoutput = $testform->render();
        }

        return $mformoutput;
    }
}