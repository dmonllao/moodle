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
 * PHPUnit globalsearch tests
 *
 * @package    globalsearch
 * @category   phpunit
 * @copyright  Prateek Sachan {@link http://prateeksachan.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class globalsearch_basic_testcase extends advanced_testcase{
    public function test_basic() {
        global $USER, $CFG, $DB;
        require_once($CFG->dirroot . '/search/' . $CFG->search_engine . '/connection.php');
        require_once($CFG->dirroot . '/search/lib.php');
        require_once($CFG->dirroot . '/mod/forum/tests/generator/lib.php');
        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $this->resetAfterTest(true);
        $search_engine_installed = $CFG->search_engine . '_installed';
        $search_engine_check_server = $CFG->search_engine . '_check_server';

        $this->assertEquals(1, $search_engine_installed());
        $this->assertEquals(1, $search_engine_check_server($client));
    }
}
