<?php

/**
 * Global Search API
 * @package mod_page
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function page_search_iterator($from = 0) {
    global $DB;

    $sql = "SELECT id, timemodified AS modified FROM {page} WHERE timemodified > ? ORDER BY timemodified ASC";

    return $DB->get_recordset_sql($sql, array($from));
}

function page_search_get_documents($id) {
    global $DB;

    $docs = array();
    try {
        $page = $DB->get_record('page', array('id' => $id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('page', $page->id, $page->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
    } catch (mdml_missing_record_exception $ex) {
        return $docs;
    }

    // Prepare associative array with data from DB.
    $doc = array();
    $doc['type'] = SEARCH_TYPE_HTML;
    $doc['id']          = 'page_' . $page->id;
    $doc['modified']    = gmdate('Y-m-d\TH:i:s\Z', $page->timemodified);
    $doc['intro']       = strip_tags($page->intro);
    $doc['name']        = $page->name;
    $doc['content']     = strip_tags($page->content);
    $doc['courseid']    = $page->course;
    $doc['contextlink'] = '/mod/page/view.php?id=' . $cm->id;
    $doc['modulelink']  = '/mod/page/view.php?id=' . $cm->id;
    $doc['module']      = 'page';
    $docs[] = $doc;

    return $docs;
}

function page_search_access($id) {
    global $DB;
    try {
        $page = $DB->get_record('page', array('id'=>$id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('page', $page->id, $page->course, MUST_EXIST);
        $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    } catch (dml_missing_record_exception $ex) {
        return SEARCH_ACCESS_DELETED;
    }

    if (!can_access_course($course, null, '', true)) {
        return SEARCH_ACCESS_DENIED;
    }

    try {
        $context = context_module::instance($cm->id);
        require_capability('mod/page:view', $context);
    } catch (moodle_exception $ex) {
        return SEARCH_ACCESS_DENIED;
    }

    return SEARCH_ACCESS_GRANTED;
}
