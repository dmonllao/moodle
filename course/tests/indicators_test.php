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
 * Unit tests for core_course indicators.
 *
 * @package   core_course
 * @category  analytics
 * @copyright 2017 David Monllaó {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../completion/criteria/completion_criteria_self.php');


/**
 * Unit tests for core_course indicators.
 *
 * @package   core_course
 * @category  analytics
 * @copyright 2017 David Monllaó {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_course_indicators_testcase extends advanced_testcase {

    /**
     * test_no_teacher
     *
     * @return void
     */
    public function test_no_teacher() {
        global $DB;

        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $coursecontext1 = \context_course::instance($course1->id);
        $coursecontext2 = \context_course::instance($course2->id);

        $user = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'teacher');

        $indicator = new \core_course\analytics\indicator\no_teacher();

        $sampleids = array($course1->id => $course1->id, $course2->id => $course2->id);
        $data = array(
            $course1->id => array(
                'context' => $coursecontext1,
                'course' => $course1,
            ),
            $course2->id => array(
                'context' => $coursecontext2,
                'course' => $course2,
            ));
        $indicator->add_sample_data($data);

        $values = $indicator->calculate($sampleids, 'course');
        $this->assertEquals($indicator::get_min_value(), $values[$course1->id][0]);
        $this->assertEquals($indicator::get_max_value(), $values[$course2->id][0]);
    }

    /**
     * test_completion_enabled
     *
     * @return void
     */
    public function test_completion_enabled() {
        global $DB;

        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course(array('enablecompletion' => 0));
        $course2 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $course3 = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Criteria only for the last one.
        $criteriadata = new stdClass();
        $criteriadata->id = $course3->id;
        $criteriadata->criteria_self = 1;
        $criterion = new completion_criteria_self();
        $criterion->update_config($criteriadata);

        $indicator = new \core_course\analytics\indicator\completion_enabled();

        $sampleids = array($course1->id => $course1->id, $course2->id => $course2->id, $course3->id => $course3->id);
        $data = array(
            $course1->id => array(
                'course' => $course1,
            ),
            $course2->id => array(
                'course' => $course2,
            ),
            $course3->id => array(
                'course' => $course3,
            ));
        $indicator->add_sample_data($data);

        // Calculate using course samples.
        $values = $indicator->calculate($sampleids, 'course');
        $this->assertEquals($indicator::get_min_value(), $values[$course1->id][0]);
        $this->assertEquals($indicator::get_min_value(), $values[$course2->id][0]);
        $this->assertEquals($indicator::get_max_value(), $values[$course3->id][0]);

        // Calculate using course_modules samples.
        $indicator->clear_sample_data();
        $data1 = $this->getDataGenerator()->create_module('data', array('course' => $course3->id),
                                                             array('completion' => 0));
        $data2 = $this->getDataGenerator()->create_module('data', array('course' => $course3->id),
                                                             array('completion' => 1));

        $sampleids = array($data1->cmid => $data1->cmid, $data2->cmid => $data2->cmid);
        $cm1 = $DB->get_record('course_modules', array('id' => $data1->cmid));
        $cm2 = $DB->get_record('course_modules', array('id' => $data2->cmid));
        $data = array(
            $cm1->id => array(
                'course' => $course3,
                'course_modules' => $cm1,
            ),
            $cm2->id => array(
                'course' => $course3,
                'course_modules' => $cm2,
            ));
        $indicator->add_sample_data($data);

        $values = $indicator->calculate($sampleids, 'course_modules');
        $this->assertEquals($indicator::get_min_value(), $values[$cm1->id][0]);
        $this->assertEquals($indicator::get_max_value(), $values[$cm2->id][0]);
    }

    /**
     * test_potential_cognitive
     *
     * @return void
     */
    public function test_potential_cognitive() {
        global $DB;

        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();

        $course2 = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', array('course' => $course2->id));

        $course3 = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course3->id));
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course3->id));

        $course4 = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course4->id));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course4->id));

        $indicator = new \core_course\analytics\indicator\potential_cognitive_depth();

        $sampleids = array($course1->id => $course1->id, $course2->id => $course2->id, $course3->id => $course3->id,
            $course4->id => $course4->id);
        $data = array(
            $course1->id => array(
                'course' => $course1,
            ),
            $course2->id => array(
                'course' => $course2,
            ),
            $course3->id => array(
                'course' => $course3,
            ),
            $course4->id => array(
                'course' => $course4,
            ));
        $indicator->add_sample_data($data);

        $values = $indicator->calculate($sampleids, 'course');
        $this->assertEquals($indicator::get_min_value(), $values[$course1->id][0]);

        // page cognitive = 1.
        $this->assertEquals(-0.6, $values[$course2->id][0]);

        // 4.5 average of forum = 4 and assign = 5.
        $this->assertEquals(0.8, $values[$course3->id][0]);

        // 4 average of forum = 4 and forum = 4.
        $this->assertEquals(0.6, $values[$course4->id][0]);

        // Calculate using course_modules samples.
        $course5 = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course5->id));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course5->id));

        $sampleids = array($assign->cmid => $assign->cmid, $forum->cmid => $forum->cmid);
        $cm1 = $DB->get_record('course_modules', array('id' => $assign->cmid));
        $cm2 = $DB->get_record('course_modules', array('id' => $forum->cmid));
        $data = array(
            $cm1->id => array(
                'course' => $course3,
                'course_modules' => $cm1,
            ),
            $cm2->id => array(
                'course' => $course3,
                'course_modules' => $cm2,
            ));
        $indicator->clear_sample_data();
        $indicator->add_sample_data($data);

        $values = $indicator->calculate($sampleids, 'course_modules');
        $this->assertEquals(1, $values[$cm1->id][0]);
        $this->assertEquals(0.6, $values[$cm2->id][0]);

    }
}
