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

class globalsearch_page_testcase extends advanced_testcase {
    public function test_page_basic() {
        global $USER, $CFG, $DB;
        require_once($CFG->dirroot . '/search/' . $CFG->search_engine . '/connection.php');
        require_once($CFG->dirroot . '/search/lib.php');
        require_once($CFG->dirroot . '/mod/page/lib.php');

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

        //create page1
        $record = new stdClass();
        $record->course = $course1->id;
        $record->name = 'Moodle Global Search';
        $page1 = self::getDataGenerator()->create_module('page', $record);

        //check page1 creation
        $this->assertEquals(1, $DB->count_records_select('page', 'id = :page1',
                                                            array('page1' => $page1->id)));

        //enrol user1 in course1
        $enrol = enrol_get_plugin('manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

        //get SolrInputDocument of post1
        $page1 = page_search_get_documents(1);

        //add the returned SolrInputDocument to solr object $client
        foreach ($page1 as $page) {
            $client->add_document($page);
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

        //create another query
        $query = new stdClass();
        $query->queryfield = "Australia";
        $results = $search_function($client, $query);
        is_array($results) ? $x=1 : $x=0;
        $this->assertEquals(0, $x); //because the search wasn't a success

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

        //delete this page from index
        $client->delete_by_id('page_1');
        $client->commit();

        //set user1 again as testing user
        $this->setUser($user1);
        $this->assertEquals(3, $user1->id);

        $query = new stdClass();
        $query->queryfield = "Moodle";
        $results = $search_function($client, $query);
        is_array($results) ? $x=1 : $x=0;
        $this->assertEquals(0, $x); //because the page was deleted from the index
    }
}
