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
 * A scheduled task.
 *
 * @package    core
 * @copyright  2016 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\task;

/**
 * Simple task to run the backup cron.
 *
 * @package    core
 * @copyright  2016 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class related_activities_task extends scheduled_task {

    private $courseid;

    protected $uniquestrs;

    const UNIQUE_CONSTANT = 'ujnasdghujdfssdf';

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return 'Return related activities';
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        $this->courseid = 5282;

        $this->generate_random_mappings();

        $path = '/home/davidm/Desktop/bag-of-words/';
        if (!file_exists($path)) {
            throw new \Exception('Hey, you need to manually create ' . $path . ' this is still a WIP');
        }
        $filepath = $path . '/activities.latest.csv';

        $fh = fopen($filepath, 'wa+');

        $this->add_row($fh, array('cmid', 'section', 'title', 'content', 'description1', 'description2'));

        $activitiesinfo = $this->get_activities_info($this->courseid);
        foreach ($activitiesinfo as $cmid => $activityinfo) {

            // Double weight for title.
            $exampledata = [];
            $exampledata[] = $cmid;
            $exampledata[] = $activityinfo->section;
            $exampledata[] = $activityinfo->title;
            $exampledata[] = $activityinfo->content;
            $exampledata[] = $activityinfo->description1;
            $exampledata[] = $activityinfo->description2;
            $this->add_row($fh, $exampledata);
        }
        fclose($fh);

        $result = $this->cluster_activities($filepath);

        if (is_int($result)) {
            mtrace('Error clustering activities, error code: ' . $result);
            return;
        }

        if ($result->exitcode !== 0) {
            mtrace('Error clustering activities: ' . implode(', ', $result->errors));
            return;
        }

        $this->print_clusters($result->clusters);
    }

    protected function get_activities_info($courseid) {
        global $DB;

        $instancesinfo = [];

        $modinfo = get_fast_modinfo($this->courseid);

        $mods = \core_component::get_plugin_list('mod');
        foreach ($mods as $pluginname => $unused) {

            $plugininstances = $modinfo->get_instances_of($pluginname);

            $plugin = 'mod_' . $pluginname;

            $areaid = \core_search\manager::generate_areaid($plugin, 'activity');
            if (!$searcharea = \core_search\manager::get_search_area($areaid)) {
                continue;
            }

            $activities = $DB->get_recordset($pluginname, array('course' => $this->courseid));
            foreach ($activities as $record) {
                $doc = $searcharea->get_document($record);

                $instance = new \stdClass();
                $instance->cmid = $plugininstances[$record->id]->id;
                $instance->section = $this->uniquestrs[$plugininstances[$record->id]->sectionnum];

                $instance->title = $doc->get('title');
                $instance->content = $doc->get('content');

                if ($doc->is_set('description1')) {
                    $instance->description1 = $doc->get('description1');
                } else {
                    $instance->description1 = '';
                }

                if ($doc->is_set('description2')) {
                    $instance->description2 = $doc->get('description2');
                } else {
                    $instance->description2 = '';
                }

                $instancesinfo[$instance->cmid] = $instance;
            }
            $activities->close();
        }

        return $instancesinfo;
    }

    protected function add_row($fh, $data) {
        fputcsv($fh, $data);
    }

    protected function cluster_activities($filepath) {
        global $CFG;

        $cmd = 'python bag-of-words.py ' .
            escapeshellarg($filepath) . ' ' .

        $output = null;
        $exitcode = null;
        $cwd= getcwd();
        chdir($CFG->dirroot);
        $result = exec($cmd, $output, $exitcode);
        chdir($cwd);

        if (!$result) {
            // TODO lang
            throw new \moodle_exception('noclusterresults', 'insights');
        }


        if (!$resultobj = json_decode($result)) {
            // TODO lang
            throw new \moodle_exception('clustererror', 'insights', json_last_error_msg());
        }

        return $resultobj;
    }

    protected function print_clusters($clusters) {

        $modinfo = get_fast_modinfo($this->courseid);

        mtrace('The following activities have been marked as related');
        foreach ($clusters as $cluster => $cmids) {
            mtrace(PHP_EOL . 'Cluster ' . $cluster);
            foreach ($cmids as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                mtrace($cm->name);
            }
        }
    }

    protected function generate_random_mappings() {
        $chars = str_split('abcdefghijklmnopqrstuvwxyz');

        $this->uniquestrs = [];

        // This should be more than enough if only used for the section number.
        foreach ($chars as $c1) {
            foreach ($chars as $c2) {
                $this->uniquestrs[] = self::UNIQUE_CONSTANT . $c1 . $c2;
            }
        }
    }
}
