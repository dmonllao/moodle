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
 * Forum search manager.
 *
 * @package    mod_forum
 * @copyright  2015 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_forum;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/forum/lib.php');

/**
 * Forum search manager.
 *
 * @copyright  2015 David Monllao
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search extends \core\search\base_mod {

    public function search_iterator($from = 0) {
        global $DB;

        $sql = "SELECT id, modified FROM {forum_posts} WHERE modified > ? ORDER BY modified ASC";

        return $DB->get_recordset_sql($sql, array($from));
    }


    public function search_get_documents($id) {
        global $DB;

        $docs = array();
        try {
            if ($post = forum_get_post_full($id)) {
                $forum = $DB->get_record('forum', array('id' => $post->forum), '*', MUST_EXIST);
                $cm = get_coursemodule_from_instance('forum', $forum->id, $forum->course);
                $context = \context_module::instance($cm->id);
                $user = $DB->get_record('user', array('id' => $post->userid));
            } else {
                return $docs;
            }
        } catch (dml_missing_record_exception $ex) {
            return $docs;
        }

        $contextlink = '/mod/forum/discuss.php?d=' . $post->discussion . '#p' . $post->id;
        $modulelink = '/mod/forum/view.php?id=' . $cm->id;

        // Prepare associative array with data from DB.
        $doc = new \core\search\document($post->id, $this->componentname);
        $doc->set('title', $post->subject);
        $doc->set('content', strip_tags($post->message));
        $doc->set('userfullname', fullname($user));
        $doc->set('contextid', $context->id);
        $doc->set('type', SEARCH_TYPE_HTML);
        $doc->set('courseid', $forum->course);
        $doc->set('userid', $user->id);
        $doc->set('created', gmdate('Y-m-d\TH:i:s\Z', $post->created));
        $doc->set('modified', gmdate('Y-m-d\TH:i:s\Z', $post->modified));
        $doc->set('name', $forum->name);
        $doc->set('intro', strip_tags($forum->intro));
        $docs[] = $doc->get_plain_doc();

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_forum', 'attachment', $id, "timemodified", false);

        $numfile = 1;
        foreach ($files as $file) {
            if (strpos($mime = $file->get_mimetype(), 'image') === false) {
                $filename = urlencode($file->get_filename());
                $directlink = '/pluginfile.php/' . $context->id . '/mod_forum/attachment/' . $id . '/' . $filename;
                $url = 'literal.id=' . 'forum_' . $id . '_file_' . $numfile . '&literal.modulelink=' . $modulelink .
                        '&literal.module=forum&literal.type=3' . '&literal.directlink=' . $directlink .
                        '&literal.courseid=' . $forum->course . '&literal.contextlink=' . $contextlink;

                $globalsearch = \core_search::instance();
                $globalsearch->post_file($file, $url);
                $numfile++;
            }
        }

        return $docs;
    }

    public function search_access($id) {
        global $DB, $USER;

        try {
            $post = $DB->get_record('forum_posts', array('id' => $id), '*', MUST_EXIST);
            $discussion = $DB->get_record('forum_discussions', array('id' => $post->discussion), '*', MUST_EXIST);
            $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $forum->course), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id, false, MUST_EXIST);
        } catch (dml_missing_record_exception $ex) {
            return SEARCH_ACCESS_DELETED;
        }

        $context = \context_module::instance($cm->id);

        if ($discussion->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {
            if (!groups_group_exists($discussion->groupid)) {
                return SEARCH_ACCESS_DENIED;
            }

            if (!groups_is_member($discussion->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
                return SEARCH_ACCESS_DENIED;
            }
        }

        if (!forum_user_can_see_post($forum, $discussion, $post, $USER, $cm)) {
            return SEARCH_ACCESS_DENIED;
        }

        return SEARCH_ACCESS_GRANTED;
    }

    /**
     * Prepares a doc to be renderable.
     *
     * @param \core\search\document $doc
     * @return \core\search\document Same doc with the link.
     */
    public function prepare_doc($doc) {
        $link = new \moodle_url('/mod/forum/post.php', array('id' => $id));
        $doc->set_link($link);

        return $doc;
    }
}
