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
 * Solr search engine unit tests.
 *
 * Required params:
 * - define('TEST_SEARCH_SOLR_HOSTNAME', '127.0.0.1');
 * - define('TEST_SEARCH_SOLR_PORT', '8983');
 * - define('TEST_SEARCH_SOLR_INDEXNAME', 'unittest');
 *
 * Optional params:
 * - define('TEST_SEARCH_SOLR_USERNAME', '');
 * - define('TEST_SEARCH_SOLR_PASSWORD', '');
 * - define('TEST_SEARCH_SOLR_SSLCERT', '');
 * - define('TEST_SEARCH_SOLR_SSLKEY', '');
 * - define('TEST_SEARCH_SOLR_KEYPASSWORD', '');
 * - define('TEST_SEARCH_SOLR_CAINFOCERT', '');
 *
 * @package     core_search
 * @category    phpunit
 * @copyright   2015 David Monllao {@link http://www.davidmonllao.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/search/tests/fixtures/base_engine_test.php');

/**
 * Solr search engine unit tests.
 *
 * @package     core_search
 * @category    phpunit
 * @copyright   2015 David Monllao {@link http://www.davidmonllao.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_solr_engine_testcase extends base_engine_test {

    public function setUp() {

        $this->resetAfterTest();

        if (!function_exists('solr_get_version')) {
            $this->markTestSkipped('Solr extension is not loaded.');
        }

        if (!defined('TEST_SEARCH_SOLR_HOSTNAME') || !defined('TEST_SEARCH_SOLR_INDEXNAME') ||
                !defined('TEST_SEARCH_SOLR_PORT')) {
            $this->markTestSkipped('Solr extension test server not set.');
        }

        set_config('server_hostname', TEST_SEARCH_SOLR_HOSTNAME, 'search_solr');
        set_config('server_port', TEST_SEARCH_SOLR_PORT, 'search_solr');
        set_config('indexname', TEST_SEARCH_SOLR_INDEXNAME, 'search_solr');

        if (defined('TEST_SEARCH_SOLR_USERNAME')) {
            set_config('server_username', TEST_SEARCH_SOLR_USERNAME, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_PASSWORD')) {
            set_config('server_password', TEST_SEARCH_SOLR_PASSWORD, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_SSLCERT')) {
            set_config('secure', true, 'search_solr');
            set_config('ssl_cert', TEST_SEARCH_SOLR_SSLCERT, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_SSLKEY')) {
            set_config('ssl_key', TEST_SEARCH_SOLR_SSLKEY, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_KEYPASSWORD')) {
            set_config('ssl_keypassword', TEST_SEARCH_SOLR_KEYPASSWORD, 'search_solr');
        }

        if (defined('TEST_SEARCH_SOLR_CAINFOCERT')) {
            set_config('ssl_cainfo', TEST_SEARCH_SOLR_CAINFOCERT, 'search_solr');
        }

        set_config('fileindexing', 1, 'search_solr');

        // We are only test indexing small string files, so setting this as low as we can.
        set_config('maxindexfilekb', 1, 'search_solr');

        $this->setAdminUser();

        // At this stage we are ready to enable search and initialise all search engine testing stuff.
        parent::setUp();

        // search attribute was set in parent's setUp().
        // Cleanup before doing anything on it as the index it is out of this test control.
        $this->search->delete_index();

        // Add moodle fields if they don't exist.
        $schema = new \search_solr\schema();
        $schema->setup(false);
    }

    public function tearDown() {
        // For unit tests before PHP 7, teardown is called even on skip. So only do our teardown if we did setup.
        if ($this->generator) {
            // Moodle DML freaks out if we don't teardown the temp table after each run.
            $this->generator->teardown();
            $this->generator = null;
        }
    }

    /**
     * Simple data provider to allow tests to be run with file indexing on and off.
     */
    public function file_indexing_provider() {
        return array(
            'file-indexing-on' => array(1),
            'file-indexing-off' => array(0)
        );
    }

    /**
     * Tests if the server connection is ready.
     *
     * @return void
     */
    public function test_connection() {
        $this->base_test_connection();
    }

    /**
     * @dataProvider file_indexing_provider
     */
    public function test_index($fileindexing) {
        $this->engine->test_set_config('fileindexing', $fileindexing);
        $this->base_test_index();
    }

    /**
     * Better keep this not very strict about which or how many results are returned as may depend on solr engine config.
     *
     * @dataProvider file_indexing_provider
     *
     * @return void
     */
    public function test_search($fileindexing) {
        $this->engine->test_set_config('fileindexing', $fileindexing);
        $this->base_test_search();
    }

    /**
     * @dataProvider file_indexing_provider
     */
    public function test_delete($fileindexing) {
        $this->engine->test_set_config('fileindexing', $fileindexing);
        $this->base_test_delete();
    }

    /**
     * @dataProvider file_indexing_provider
     */
    public function test_alloweduserid($fileindexing) {
        $this->engine->test_set_config('fileindexing', $fileindexing);
        $this->base_test_alloweduserid();
    }

    /**
     * @dataProvider file_indexing_provider
     */
    public function test_highlight($fileindexing) {
        global $PAGE;

        $this->engine->test_set_config('fileindexing', $fileindexing);

        $this->generator->create_record();
        $this->search->index();

        $querydata = new stdClass();
        $querydata->q = 'message';

        $results = $this->search->search($querydata);
        $this->assertCount(1, $results);

        $result = reset($results);

        $regex = '|'.\search_solr\engine::HIGHLIGHT_START.'message'.\search_solr\engine::HIGHLIGHT_END.'|';
        $this->assertRegExp($regex, $result->get('content'));

        $searchrenderer = $PAGE->get_renderer('core_search');
        $exported = $result->export_for_template($searchrenderer);

        $regex = '|<span class="highlight">message</span>|';
        $this->assertRegExp($regex, $exported['content']);
    }

    public function test_export_file_for_engine() {
        // Get area to work with.
        $areaid = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');
        $area = \core_search\manager::get_search_area($areaid);

        $record = $this->generator->create_record();

        $doc = $area->get_document($record);
        $filerecord = new stdClass();
        $filerecord->timemodified  = 978310800;
        $file = $this->generator->create_file($filerecord);
        $doc->add_stored_file($file);

        $filearray = $doc->export_file_for_engine($file);

        $this->assertEquals(\core_search\manager::TYPE_FILE, $filearray['type']);
        $this->assertEquals($file->get_id(), $filearray['solr_fileid']);
        $this->assertEquals($file->get_contenthash(), $filearray['solr_filecontenthash']);
        $this->assertEquals(\search_solr\document::INDEXED_FILE_TRUE, $filearray['solr_fileindexstatus']);
        $this->assertEquals($file->get_filename(), $filearray['title']);
        $this->assertEquals(978310800, \search_solr\document::import_time_from_engine($filearray['modified']));
    }

    /**
     * test_index_file
     *
     * @return void
     */
    public function test_index_file() {
        $this->base_test_index_file();
    }

    /**
     * test_reindexing_files
     *
     * @return void
     */
    public function test_reindexing_files() {
        $this->base_test_reindexing_files();
    }

    /**
     * Test indexing a file we don't consider indexable.
     */
    public function test_index_filtered_file() {
        // Get area to work with.
        $areaid = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');
        $area = \core_search\manager::get_search_area($areaid);

        // Get a single record to make a doc from.
        $record = $this->generator->create_record();

        $doc = $area->get_document($record);

        // Now we are going to make some files.
        $fs = get_file_storage();
        $syscontext = \context_system::instance();

        // We need to make a file greater than 1kB in size, which is the lowest filter size.
        $filerecord = new \stdClass();
        $filerecord->filename = 'largefile';
        $filerecord->content = 'Some LargeFindContent to find.';
        for ($i = 0; $i < 200; $i++) {
            $filerecord->content .= ' The quick brown fox jumps over the lazy dog.';
        }

        $this->assertGreaterThan(1024, strlen($filerecord->content));

        $file = $this->generator->create_file($filerecord);
        $doc->add_stored_file($file);

        $filerecord->filename = 'smallfile';
        $filerecord->content = 'Some SmallFindContent to find.';
        $file = $this->generator->create_file($filerecord);
        $doc->add_stored_file($file);

        $this->engine->add_document($doc, true);
        $this->engine->area_index_complete($area->get_area_id());

        $querydata = new stdClass();
        // We shouldn't be able to find the large file contents.
        $querydata->q = 'LargeFindContent';
        $this->assertCount(0, $this->search->search($querydata));

        // But we should be able to find the filename.
        $querydata->q = 'largefile';
        $this->assertCount(1, $this->search->search($querydata));

        // We should be able to find the small file contents.
        $querydata->q = 'SmallFindContent';
        $this->assertCount(1, $this->search->search($querydata));

        // And we should be able to find the filename.
        $querydata->q = 'smallfile';
        $this->assertCount(1, $this->search->search($querydata));
    }

    public function test_delete_file_by_id() {
        // First get files in the index.
        $file = $this->generator->create_file();
        $record = new \stdClass();
        $record->attachfileids = array($file->get_id());
        $this->generator->create_record($record);
        $this->generator->create_record($record);
        $this->search->index();

        $querydata = new stdClass();

        // Then search to make sure they are there.
        $querydata->q = '"File contents"';
        $results = $this->search->search($querydata);
        $this->assertCount(2, $results);

        $first = reset($results);
        $deleteid = $first->get('id');

        $this->engine->delete_by_id($deleteid);

        // Check that we don't get a result for it anymore.
        $results = $this->search->search($querydata);
        $this->assertCount(1, $results);
        $result = reset($results);
        $this->assertNotEquals($deleteid, $result->get('id'));
    }

    /**
     * Test that expected results are returned, even with low check_access success rate.
     *
     * @dataProvider file_indexing_provider
     */
    public function test_solr_filling($fileindexing) {
        $this->engine->test_set_config('fileindexing', $fileindexing);

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // We are going to create a bunch of records that user 1 can see with 2 keywords.
        // Then we are going to create a bunch for user 2 with only 1 of the keywords.
        // If user 2 searches for both keywords, solr will return all of the user 1 results, then the user 2 results.
        // This is because the user 1 results will match 2 keywords, while the others will match only 1.

        $record = new \stdClass();

        // First create a bunch of records for user 1 to see.
        $record->denyuserids = array($user2->id);
        $record->content = 'Something1 Something2';
        $maxresults = (int)(\core_search\manager::MAX_RESULTS * .75);
        for ($i = 0; $i < $maxresults; $i++) {
            $this->generator->create_record($record);
        }

        // Then create a bunch of records for user 2 to see.
        $record->denyuserids = array($user1->id);
        $record->content = 'Something1';
        for ($i = 0; $i < $maxresults; $i++) {
            $this->generator->create_record($record);
        }

        $this->search->index();

        // Check that user 1 sees all their results.
        $this->setUser($user1);
        $querydata = new stdClass();
        $querydata->q = 'Something1 Something2';
        $results = $this->search->search($querydata);
        $this->assertCount($maxresults, $results);

        // Check that user 2 will see theirs, even though they may be crouded out.
        $this->setUser($user2);
        $results = $this->search->search($querydata);
        $this->assertCount($maxresults, $results);
    }

    /**
     * Create 40 docs, that will be return from Solr in 10 hidden, 10 visible, 10 hidden, 10 visible if you query for:
     * Something1 Something2 Something3 Something4, with the specified user set.
     */
    protected function setup_user_hidden_docs($user) {
        // These results will come first, and will not be visible by the user.
        $record = new \stdClass();
        $record->denyuserids = array($user->id);
        $record->content = 'Something1 Something2 Something3 Something4';
        for ($i = 0; $i < 10; $i++) {
            $this->generator->create_record($record);
        }

        // These results will come second, and will  be visible by the user.
        unset($record->denyuserids);
        $record->content = 'Something1 Something2 Something3';
        for ($i = 0; $i < 10; $i++) {
            $this->generator->create_record($record);
        }

        // These results will come third, and will not be visible by the user.
        $record->denyuserids = array($user->id);
        $record->content = 'Something1 Something2';
        for ($i = 0; $i < 10; $i++) {
            $this->generator->create_record($record);
        }

        // These results will come fourth, and will be visible by the user.
        unset($record->denyuserids);
        $record->content = 'Something1 ';
        for ($i = 0; $i < 10; $i++) {
            $this->generator->create_record($record);
        }
    }

    /**
     * Test that counts are what we expect.
     *
     * @dataProvider file_indexing_provider
     */
    public function test_get_query_total_count($fileindexing) {
        $this->engine->test_set_config('fileindexing', $fileindexing);

        $user = self::getDataGenerator()->create_user();
        $this->setup_user_hidden_docs($user);
        $this->search->index();

        $this->setUser($user);
        $querydata = new stdClass();
        $querydata->q = 'Something1 Something2 Something3 Something4';

        // In this first set, it should have determined the first 10 of 40 are bad, so there could be up to 30 left.
        $results = $this->engine->execute_query($querydata, true, 5);
        $this->assertEquals(30, $this->engine->get_query_total_count());
        $this->assertCount(5, $results);

        // To get to 15, it has to process the first 10 that are bad, 10 that are good, 10 that are bad, then 5 that are good.
        // So we now know 20 are bad out of 40.
        $results = $this->engine->execute_query($querydata, true, 15);
        $this->assertEquals(20, $this->engine->get_query_total_count());
        $this->assertCount(15, $results);

        // Try to get more then all, make sure we still see 20 count and 20 returned.
        $results = $this->engine->execute_query($querydata, true, 30);
        $this->assertEquals(20, $this->engine->get_query_total_count());
        $this->assertCount(20, $results);
    }

    /**
     * Test that paged results are what we expect.
     *
     * @dataProvider file_indexing_provider
     */
    public function test_manager_paged_search($fileindexing) {
        $this->engine->test_set_config('fileindexing', $fileindexing);

        $user = self::getDataGenerator()->create_user();
        $this->setup_user_hidden_docs($user);
        $this->search->index();

        // Check that user 1 sees all their results.
        $this->setUser($user);
        $querydata = new stdClass();
        $querydata->q = 'Something1 Something2 Something3 Something4';

        // On this first page, it should have determined the first 10 of 40 are bad, so there could be up to 30 left.
        $results = $this->search->paged_search($querydata, 0);
        $this->assertEquals(30, $results->totalcount);
        $this->assertCount(10, $results->results);
        $this->assertEquals(0, $results->actualpage);

        // On the second page, it should have found the next 10 bad ones, so we no know there are only 20 total.
        $results = $this->search->paged_search($querydata, 1);
        $this->assertEquals(20, $results->totalcount);
        $this->assertCount(10, $results->results);
        $this->assertEquals(1, $results->actualpage);

        // Try to get an additional page - we should get back page 1 results, since that is the last page with valid results.
        $results = $this->search->paged_search($querydata, 2);
        $this->assertEquals(20, $results->totalcount);
        $this->assertCount(10, $results->results);
        $this->assertEquals(1, $results->actualpage);
    }
}
