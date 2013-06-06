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

class globalsearch_forum_testcase extends advanced_testcase {
    public function test_forum_basic() {
        global $USER, $CFG, $DB;
        require_once($CFG->dirroot . '/search/' . $CFG->search_engine . '/connection.php');
        require_once($CFG->dirroot . '/search/lib.php');
        require_once($CFG->dirroot . '/mod/forum/tests/generator/lib.php');
        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $this->resetAfterTest(true);
        $client->delete_by_query('*:*');
        $client->commit();

        $search_function = $CFG->search_engine . '_execute_query';

        //create users
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        //create course
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();

        //create forum1
        $record = new stdClass();
        $record->course = $course1->id;
        $forum1 = self::getDataGenerator()->create_module('forum', $record);

        //create discussion1
        $record = new stdClass();
        $record->course = $course1->id;
        $record->userid = $user1->id;
        $record->forum = $forum1->id;
        $record->message = 'Moodle Global Search';
        $discussion1 = self::getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        //create post1 in discussion1
        $record = new stdClass();
        $record->discussion = $discussion1->id;
        $record->parent = $discussion1->firstpost;
        $record->userid = $user2->id;
        $record->message = 'This is the PHP UNIT test';
        $discussion1reply1 = self::getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        //check forum1 creation
        $this->assertEquals(1, $DB->count_records_select('forum', 'id = :forum1',
                                                            array('forum1' => $forum1->id)));

        //check discussion1 creation
        $this->assertEquals(1, $DB->count_records_select('forum_discussions', 'forum = :forum1',
                                                            array('forum1' => $forum1->id)));
        //check posts creation
        $this->assertEquals(2, $DB->count_records_select('forum_posts', 'discussion = :discussion1',
                                                            array('discussion1' => $discussion1->id)));

        //enrol user1 in course1
        $enrol = enrol_get_plugin('manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

        //get SolrInputDocument of post1
        $post1 = forum_search_get_documents(1);

        //add the returned SolrInputDocument to solr object $client
        foreach ($post1 as $post) {
            $client->add_document($post);
        }

        //commit
        $client->commit();

        //set user1 as testing user
        $this->setUser($user1);
        $this->assertEquals(3, $user1->id);

        //create a query
        $query = new stdClass();
        $query->queryfield = "Moodle";
        $results = $search_function($client, $query);
        is_array($results) ? $x=1 : $x=0;   //this is because in {search_engine}_execute_query() function (defined in {search_engine}/search.php), if the search is success
                                            //then the results are returned as an array, otherwise the $results is a string denoting a message that
                                            //'no results were found'. (line #237 in solr/search.php)
        $this->assertEquals(1, $x); //because the search was a success

        //now enrol user2 in course2. this user wouldn't be able to search as discussion1 was in course1
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id);

        //set user2 as testing user
        $this->setUser($user2);
        $this->assertEquals(4, $user2->id);
        $query = new stdClass();
        $query->queryfield = "Moodle";
        $results = $search_function($client, $query);
        is_array($results) ? $x=1 : $x=0;
        $this->assertEquals(0, $x);//because, this time as there were 0 results, hence a string was returned in $results

        //delete this forum post record from index
        $client->delete_by_id('forum_1');
        $client->commit();

        //set user1 again as testing user
        $this->setUser($user1);
        $this->assertEquals(3, $user1->id);

        $query = new stdClass();
        $query->queryfield = "Moodle";
        $results = $search_function($client, $query);
        is_array($results) ? $x=1 : $x=0;
        $this->assertEquals(0, $x); //because the post was deleted from the index

    }
}
