<?php

/**
 * Global Search API
 * @package mod_wiki
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function wiki_search_iterator($from = 0) {
    global $DB;

    $sql = "SELECT id, timemodified AS modified FROM {wiki_pages} WHERE timemodified > ? ORDER BY timemodified ASC";

    return $DB->get_recordset_sql($sql, array($from));
}

function wiki_search_get_documents($id) {
    global $DB;

    $docs = array();
    try {
        $wikipage = $DB->get_record('wiki_pages', array('id' => $id), '*', MUST_EXIST);
        $subwiki = $DB->get_record('wiki_subwikis', array('id' => $wikipage->subwikiid), '*', MUST_EXIST);
        $wiki = $DB->get_record('wiki', array('id' => $subwiki->wikiid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('wiki', $wiki->id, $wiki->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
    } catch (mdml_missing_record_exception $ex) {
        return $docs;
    }

    $contextlink = '/mod/wiki/view.php?pageid=' . $wikipage->id;

    // Prepare associative array with data from DB.
    $doc = array();
    $doc['type'] = SEARCH_TYPE_HTML;
    $doc['id']          = 'wiki_' . $wikipage->id;
    $doc['created']     = gmdate('Y-m-d\TH:i:s\Z', $wikipage->timecreated);
    $doc['modified']    = gmdate('Y-m-d\TH:i:s\Z', $wikipage->timemodified);
    $doc['intro']       = strip_tags($wiki->intro);
    $doc['name']        = $wiki->name;
    $doc['content']     = strip_tags($wikipage->cachedcontent);
    $doc['title']       = $wikipage->title;
    $doc['courseid']    = $wiki->course;
    $doc['contextlink'] = $contextlink;
    $doc['modulelink']  = '/mod/wiki/view.php?id=' . $cm->id;
    $doc['module']      = 'wiki';
    $docs[] = $doc;

    return $docs;
}

function wiki_search_files($id = 0) {
    global $DB;

    $wikifiles = $DB->get_records('files', array('component' => 'mod_wiki'), 'id', 'id, itemid, filepath, filename, filesize');
    foreach ($wikifiles as $wikifile) {
        if ($wikifile->id <= $id or $wikifile->filesize == 0) {
            unset($wikifiles[$wikifile->id]);
        }
    }
    $fs = get_file_storage();

    if (!empty($wikifiles)) {
        $lastindexedfilerun = end($wikifiles)->id;
    }
    foreach ($wikifiles as $wikifile) {
        try {
            $wikipage = $DB->get_record('wiki_pages', array('subwikiid' => $wikifile->itemid), 'id', IGNORE_MULTIPLE);
            $subwiki = $DB->get_record('wiki_subwikis', array('id' => $wikifile->itemid), 'id, wikiid', MUST_EXIST);
            $wiki = $DB->get_record('wiki', array('id' => $subwiki->wikiid), 'id, course, name, intro', MUST_EXIST);
            $cm = get_coursemodule_from_instance('wiki', $wiki->id, $wiki->course, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
        } catch (mdml_missing_record_exception $ex) {
            exit();
        }

        $file = $fs->get_file($context->id, 'mod_wiki', 'attachments', $wikifile->itemid, $wikifile->filepath, $wikifile->filename);

        if (strpos($mime = $file->get_mimetype(), 'image') === false) {
            $filename = urlencode($file->get_filename());
            $directlink = '/pluginfile.php/' . $context->id . '/mod_wiki/attachments/' . $wikifile->itemid . '/' . $filename;
            $modulelink = '/mod/wiki/view.php?id=' . $cm->id;
            $url = 'literal.id=' . 'wiki_' . $wikipage->id . '_file_' . $wikifile->id . '&literal.module=wiki&literal.type=3' .
                    '&literal.directlink=' . $directlink . '&literal.courseid=' . $wiki->course . '&literal.modulelink=' . $modulelink;

            $globalsearch = new core_search();
            $globalsearch->post_file($file, $url);
        }
    }
    if (!empty($wikifiles)) {
        set_config('wiki' . '_lastindexedfilerun', $lastindexedfilerun, 'search');
    }
}

function wiki_search_access($id) {
    global $DB;
    try {
        $wikipage = $DB->get_record('wiki_pages', array('id' => $id), '*', MUST_EXIST);
        $subwiki = $DB->get_record('wiki_subwikis', array('id' => $wikipage->subwikiid), '*', MUST_EXIST);
        $wiki = $DB->get_record('wiki', array('id' => $subwiki->wikiid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('wiki', $wiki->id, $wiki->course, MUST_EXIST);
        $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    } catch (dml_missing_record_exception $ex) {
        return SEARCH_ACCESS_DELETED;
    }

    try {
        $context = context_module::instance($cm->id);
        require_capability('mod/wiki:viewpage', $context);
    } catch (moodle_exception $ex) {
        return SEARCH_ACCESS_DENIED;
    }

    if ($subwiki->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {
        if (!groups_group_exists($subwiki->groupid)) {
            return SEARCH_ACCESS_DENIED;
        }

        if (!groups_is_member($subwiki->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
            return SEARCH_ACCESS_DENIED;
        }
    }

    return SEARCH_ACCESS_GRANTED;
}
