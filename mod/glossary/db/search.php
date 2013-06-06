<?php

/**
 * Global Search API
 * @package mod_glossary
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function glossary_search_iterator($from = 0) {
    global $DB;

    $sql = "SELECT id, timemodified AS modified FROM {glossary_entries} WHERE timemodified > ? ORDER BY timemodified ASC";

    return $DB->get_recordset_sql($sql, array($from));
}

function glossary_search_get_documents($id) {
    global $DB;

    $docs = array();
    try {
        $glossaryentry = $DB->get_record('glossary_entries', array('id' => $id), '*', MUST_EXIST);
        $glossary = $DB->get_record('glossary', array('id' => $glossaryentry->glossaryid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('glossary', $glossary->id, $glossary->course);
        $user = $DB->get_record('user', array('id' => $glossaryentry->userid));
        $context = context_module::instance($cm->id);
    } catch (mdml_missing_record_exception $ex) {
        return $docs;
    }

    $contextlink = '/mod/glossary/showentry.php?eid=' . $glossary->id;
    $modulelink = '/mod/glossary/view.php?id=' . $cm->id;

    // Prepare associative array with data from DB.
    $doc = array();
    $doc['type'] = SEARCH_TYPE_HTML;
    $doc['id']          = 'glossary_' . $glossaryentry->id;
    $doc['user']        = $user->firstname . ' ' . $user->lastname;
    $doc['created']     = gmdate('Y-m-d\TH:i:s\Z', $glossaryentry->timecreated);
    $doc['modified']    = gmdate('Y-m-d\TH:i:s\Z', $glossaryentry->timemodified);
    $doc['intro']       = strip_tags($glossary->intro);
    $doc['name']        = $glossary->name;
    $doc['content']     = strip_tags($glossaryentry->definition);
    $doc['title']       = $glossaryentry->concept;
    $doc['courseid']    = $glossary->course;
    $doc['contextlink'] = $contextlink;
    $doc['modulelink']  = $modulelink;
    $doc['module']      = 'glossary';
    $docs[] = $doc;

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_glossary', 'attachment', $glossary->id, 'timemodified', false);

    $numfile = 1;
    foreach ($files as $file) {
        if (strpos($mime = $file->get_mimetype(), 'image') === false) {
            $filename = urlencode($file->get_filename());
            $directlink = '/pluginfile.php/' . $context->id . '/mod_glossary/attachment/' . $glossary->id . '/' . $filename;
            $url = 'literal.id=' . 'glossary_' . $id . '_file_' . $numfile . '&literal.module=glossary&literal.type=3' .
                    '&literal.directlink=' . $directlink . '&literal.courseid=' . $glossary->course .
                    '&literal.contextlink=' . $contextlink . '&literal.modulelink=' . $modulelink;

            $globalsearch = new core_search();
            $globalsearch->post_file($file, $url);
            $numfile++;
        }
    }

    return $docs;
}

function glossary_search_access($id) {
    global $DB, $USER;

    try {
        $entry = $DB->get_record('glossary_entries', array('id' => $id), '*', MUST_EXIST);
        $glossary = $DB->get_record('glossary', array('id' => $entry->glossaryid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('glossary', $glossary->id, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    } catch (dml_missing_record_exception $ex) {
        return SEARCH_ACCESS_DELETED;
    }

    try {
        $context = context_module::instance($cm->id);
        require_capability('mod/glossary:view', $context);
    } catch (moodle_exception $ex) {
        return SEARCH_ACCESS_DENIED;
    }

    // give access to search results to teacher or editing-teacher or manager
    $issuperuser = has_capability('mod/glossary:approve', $context) or has_capability('mod/glossary:manageentries', $context);

    if (!$issuperuser) {
        if (!$entry->approved && $USER != $entry->userid) {
            return SEARCH_ACCESS_DENIED;
        }
    }

    return SEARCH_ACCESS_GRANTED;
}
