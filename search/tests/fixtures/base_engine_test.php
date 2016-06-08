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
 * Search engine unit tests base to extend by search engines.
 *
 * Provides a base for search engines to test their basic functionality.
 *
 * @package     core_search
 * @category    phpunit
 * @copyright   2016 David Monllao {@link http://www.davidmonllao.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');
require_once($CFG->dirroot . '/search/tests/fixtures/mock_search_area.php');
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Search engines basic unit tests.
 *
 * @package     core_search
 * @category    phpunit
 * @copyright   2016 David Monllao {@link http://www.davidmonllao.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class base_engine_test extends advanced_testcase {


    /**
     * @var \core_search::manager
     */
    protected $search = null;

    /**
     * @var Instace of core_search_generator.
     */
    protected $generator = null;

    /**
     * @var Instace of testable_engine.
     */
    protected $engine = null;

    /**
     * Init search testing stuff.
     * - Enables search
     * - Inits generator
     * - Inits testable search manager, search engine and the mock search area.
     *
     * @return void
     */
    public function init_engine_testing() {
        set_config('enableglobalsearch', true);

        $this->generator = self::getDataGenerator()->get_plugin_generator('core_search');
        $this->generator->setup();

        // The engine needs to be set by the child class.
        if (empty($this->engine)) {
            $class = get_called_class();
            throw new coding_exception($class . ' should set ' . $class . '::$engine attribute before calling ' .
                $class . '::init_engine_testing()');
        }

        $this->search = testable_core_search::instance($this->engine);
        $areaid = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');
        $this->search->add_search_area($areaid, new core_mocksearch\search\mock_search_area());

    }

    /**
     * base_test_connection
     *
     * @return void
     */
    public function base_test_connection() {
        $this->assertTrue($this->engine->is_server_ready());
    }

    /**
     * base_test_index
     *
     * @return void
     */
    protected function base_test_index() {
        $record = new \stdClass();
        $record->timemodified = time() - 1;
        $this->generator->create_record($record);

        // Data gets into the search engine.
        $this->assertTrue($this->search->index());

        // Not anymore as everything was already added.
        sleep(1);
        $this->assertFalse($this->search->index());

        $this->generator->create_record();

        // Indexing again once there is new data.
        $this->assertTrue($this->search->index());
    }

    /**
     * base_test_search
     *
     * @return void
     */
    protected function base_test_search() {
        global $USER, $DB;

        $this->generator->create_record();
        $record = new \stdClass();
        $record->title = "Special title";
        $this->generator->create_record($record);

        $this->search->index();

        $querydata = new stdClass();
        $querydata->q = 'message';
        $results = $this->search->search($querydata);
        $this->assertCount(2, $results);

        // Based on core_mocksearch\search\indexer.
        $this->assertEquals($USER->id, $results[0]->get('userid'));
        $this->assertEquals(\context_system::instance()->id, $results[0]->get('contextid'));

        // Do a test to make sure we aren't searching non-query fields, like areaid.
        $querydata->q = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');
        $this->assertCount(0, $this->search->search($querydata));
        $querydata->q = 'message';

        sleep(1);
        $beforeadding = time();
        sleep(1);
        $this->generator->create_record();
        $this->search->index();

        // Timestart.
        $querydata->timestart = $beforeadding;
        $this->assertCount(1, $this->search->search($querydata));

        // Timeend.
        unset($querydata->timestart);
        $querydata->timeend = $beforeadding;
        $this->assertCount(2, $this->search->search($querydata));

        // Title.
        unset($querydata->timeend);
        $querydata->title = 'Special title';
        $this->assertCount(1, $this->search->search($querydata));

        // Course IDs.
        unset($querydata->title);
        $querydata->courseids = array(SITEID + 1);
        $this->assertCount(0, $this->search->search($querydata));

        $querydata->courseids = array(SITEID);
        $this->assertCount(3, $this->search->search($querydata));

        // Now try some area-id combinations.
        unset($querydata->courseids);
        $forumpostareaid = \core_search\manager::generate_areaid('mod_forum', 'post');
        $mockareaid = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');

        $querydata->areaids = array($forumpostareaid);
        $this->assertCount(0, $this->search->search($querydata));

        $querydata->areaids = array($forumpostareaid, $mockareaid);
        $this->assertCount(3, $this->search->search($querydata));

        $querydata->areaids = array($mockareaid);
        $this->assertCount(3, $this->search->search($querydata));

        $querydata->areaids = array();
        $this->assertCount(3, $this->search->search($querydata));

        // Check that index contents get updated.
        $this->generator->delete_all();
        $this->search->index(true);
        unset($querydata->title);
        $querydata->q = '*';
        $this->assertCount(0, $this->search->search($querydata));
    }

    /**
     * base_test_delete
     *
     * @return void
     */
    protected function base_test_delete() {

        $this->generator->create_record();
        $this->generator->create_record();
        $this->search->index();

        $querydata = new stdClass();
        $querydata->q = 'message';

        $this->assertCount(2, $this->search->search($querydata));

        $areaid = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');
        $this->search->delete_index($areaid);
        $this->assertCount(0, $this->search->search($querydata));
    }

    /**
     * base_test_alloweduserid
     *
     * @return void
     */
    protected function base_test_alloweduserid() {

        $area = new core_mocksearch\search\mock_search_area();

        $record = $this->generator->create_record();

        // Get the doc and insert the default doc.
        $doc = $area->get_document($record);
        $this->engine->add_document($doc);

        $users = array();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        // Add a record that only user 100 can see.
        $originalid = $doc->get('id');

        // Now add a custom doc for each user.
        foreach ($users as $user) {
            $doc = $area->get_document($record);
            $doc->set('id', $originalid.'-'.$user->id);
            $doc->set('owneruserid', $user->id);
            $this->engine->add_document($doc);
        }

        $this->engine->area_index_complete($area->get_area_id());

        $querydata = new stdClass();
        $querydata->q = 'message';
        $querydata->title = $doc->get('title');

        // We are going to go through each user and see if they get the original and the owned doc.
        foreach ($users as $user) {
            $this->setUser($user);

            $results = $this->search->search($querydata);
            $this->assertCount(2, $results);

            $owned = 0;
            $notowned = 0;

            // We don't know what order we will get the results in, so we are doing this.
            foreach ($results as $result) {
                $owneruserid = $result->get('owneruserid');
                if (empty($owneruserid)) {
                    $notowned++;
                    $this->assertEquals(0, $owneruserid);
                    $this->assertEquals($originalid, $result->get('id'));
                } else {
                    $owned++;
                    $this->assertEquals($user->id, $owneruserid);
                    $this->assertEquals($originalid.'-'.$user->id, $result->get('id'));
                }
            }

            $this->assertEquals(1, $owned);
            $this->assertEquals(1, $notowned);
        }

        // Now test a user with no owned results.
        $otheruser = $this->getDataGenerator()->create_user();
        $this->setUser($otheruser);

        $results = $this->search->search($querydata);
        $this->assertCount(1, $results);

        $this->assertEquals(0, $results[0]->get('owneruserid'));
        $this->assertEquals($originalid, $results[0]->get('id'));

    }

    /**
     * base_test_index_file
     *
     * @return void
     */
    protected function base_test_index_file() {
        // Very simple test.
        $file = $this->generator->create_file();

        $record = new \stdClass();
        $record->attachfileids = array($file->get_id());
        $this->generator->create_record($record);

        $this->search->index();
        $querydata = new stdClass();
        $querydata->q = '"File contents"';

        $this->assertCount(1, $this->search->search($querydata));
    }

    /**
     * base_test_reindexing_files
     *
     * @return void
     */
    protected function base_test_reindexing_files() {
        // Get area to work with.
        $areaid = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');
        $area = \core_search\manager::get_search_area($areaid);

        $record = $this->generator->create_record();

        $doc = $area->get_document($record);

        // Now we are going to make some files.
        $fs = get_file_storage();
        $syscontext = \context_system::instance();

        $files = array();

        $filerecord = new \stdClass();
        // We make enough so that we pass the 500 files threashold. That is the boundary when getting files.
        $boundary = 500;
        $top = (int)($boundary * 1.1);
        for ($i = 0; $i < $top; $i++) {
            $filerecord->filename  = 'searchfile'.$i;
            $filerecord->content = 'Some FileContents'.$i;
            $file = $this->generator->create_file($filerecord);
            $doc->add_stored_file($file);
            $files[] = $file;
        }

        // Add the doc with lots of files, then commit.
        $this->engine->add_document($doc, true);
        $this->engine->area_index_complete($area->get_area_id());

        // Indexes we are going to check. 0 means we will delete, 1 means we will keep.
        $checkfiles = array(
            0 => 0,                        // Check the begining of the set.
            1 => 1,
            2 => 0,
            ($top - 3) => 0,               // Check the end of the set.
            ($top - 2) => 1,
            ($top - 1) => 0,
            ($boundary - 2) => 0,          // Check at the boundary between fetch groups.
            ($boundary - 1) => 0,
            $boundary => 0,
            ($boundary + 1) => 0,
            ((int)($boundary * 0.5)) => 1, // Make sure we keep some middle ones.
            ((int)($boundary * 1.05)) => 1
        );

        $querydata = new stdClass();

        // First, check that all the files are currently there.
        foreach ($checkfiles as $key => $unused) {
            $querydata->q = 'FileContents'.$key;
            $this->assertCount(1, $this->search->search($querydata));
            $querydata->q = 'searchfile'.$key;
            $this->assertCount(1, $this->search->search($querydata));
        }

        // Remove the files we want removed from the files array.
        foreach ($checkfiles as $key => $keep) {
            if (!$keep) {
                unset($files[$key]);
            }
        }

        // And make us a new file to add.
        $filerecord->filename  = 'searchfileNew';
        $filerecord->content  = 'Some FileContentsNew';
        $files[] = $this->generator->create_file($filerecord);
        $checkfiles['New'] = 1;

        $doc = $area->get_document($record);
        foreach($files as $file) {
            $doc->add_stored_file($file);
        }

        // Reindex the document with the changed files.
        $this->engine->add_document($doc, true);
        $this->engine->area_index_complete($area->get_area_id());

        // Go through our check array, and see if the file is there or not.
        foreach ($checkfiles as $key => $keep) {
            $querydata->q = 'FileContents'.$key;
            $this->assertCount($keep, $this->search->search($querydata));
            $querydata->q = 'searchfile'.$key;
            $this->assertCount($keep, $this->search->search($querydata));
        }

        // Now check that we get one result when we search from something in all of them.
        $querydata->q = 'Some';
        $this->assertCount(1, $this->search->search($querydata));
    }

    /**
     * base_test_external_get_results
     *
     * @return void
     */
    protected function base_test_external_get_results() {
        global $USER, $DB;

        $this->setAdminUser();

        // Filters with defaults.
        $filters = array(
            'title' => null,
            'areaids' => array(),
            'courseids' => array(),
            'timestart' => 0,
            'timeend' => 0
        );

        // We need to execute the return values cleaning process to simulate the web service server.
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('one', $filters));
        $this->assertEquals(0, $return['totalcount']);


        // 2 new records, both will contain message.
        $this->generator->create_record();
        $record = new \stdClass();
        $record->title = "Special title";
        $this->generator->create_record($record);
        $this->search->index();

        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(2, $return['totalcount']);
        $this->assertEquals($USER->id, $return['results'][0]['userid']);
        $this->assertEquals(\context_system::instance()->id, $return['results'][0]['contextid']);

        sleep(1);
        $beforeadding = time();
        sleep(1);
        $this->generator->create_record();
        $this->search->index();

        // Timestart.
        $filters['timestart'] = $beforeadding;
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(1, $return['totalcount']);

        // Timeend.
        $filters['timestart'] = 0;
        $filters['timeend'] = $beforeadding;
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(2, $return['totalcount']);

        // Title.
        $filters['timeend'] = 0;
        $filters['title'] = 'Special title';
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(1, $return['totalcount']);

        // Course IDs.
        $filters['title'] = null;
        $filters['courseids'] = array(SITEID + 1);
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(0, $return['totalcount']);

        $filters['courseids'] = array(SITEID);
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(3, $return['totalcount']);

        // Reset filters once again.
        $filters['courseids'] = array();

        // Now try some area-id combinations.
        $forumpostareaid = \core_search\manager::generate_areaid('mod_forum', 'post');
        $mockareaid = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');

        $filters['areaids'] = array($forumpostareaid);
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(0, $return['totalcount']);

        $filters['areaids'] = array($forumpostareaid, $mockareaid);
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(3, $return['totalcount']);

        $filters['areaids'] = array($mockareaid);
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(3, $return['totalcount']);

        // All records now.
        $filters['areaids'] = array();
        $return = external_api::clean_returnvalue(core_search_external::get_results_returns(),
            core_search_external::get_results('message', $filters));
        $this->assertEquals(3, $return['totalcount']);
    }
}
