<?php

/**
 * Global Search API
 * @package mod_lesson
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function lesson_search_iterator($from = 0) {
    global $DB;

    if ($from==0) {
        $sql = "SELECT id, timecreated AS modified FROM {lesson_pages} WHERE timecreated > ? ORDER BY timecreated ASC";
    } else {
        $sql = "SELECT id, timemodified AS modified FROM {lesson_pages} WHERE timemodified > ? ORDER BY timemodified ASC";
    }

    return $DB->get_recordset_sql($sql, array($from));
}

function lesson_search_get_documents($id) {
    global $DB;

    $docs = array();
    try {
        $lessonpage = $DB->get_record('lesson_pages', array('id' => $id), '*', MUST_EXIST);
        $lesson = $DB->get_record('lesson', array('id' => $lessonpage->lessonid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('lesson', $lesson->id, $lesson->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
    } catch (mdml_missing_record_exception $ex) {
        return $docs;
    }

    // Prepare associative array with data from DB.
    $doc = array();
    $doc['type'] = SEARCH_TYPE_HTML;
    $doc['id'] = 'lesson_' . $lessonpage->id;
    $doc['created'] = gmdate('Y-m-d\TH:i:s\Z', $lessonpage->timecreated);
    $doc['modified'] = gmdate('Y-m-d\TH:i:s\Z', $lessonpage->timemodified);
    $doc['name'] = $lesson->name;
    $doc['content'] = strip_tags($lessonpage->contents);
    $doc['title'] = $lessonpage->title;
    $doc['courseid'] = $lesson->course;
    $doc['contextlink'] = '/mod/lesson/view.php?id=' . $cm->id . '&pageid=' . $lessonpage->id;
    $doc['modulelink'] = '/mod/lesson/view.php?id=' . $cm->id;
    $doc['module'] = 'lesson';
    $docs[] = $doc;

    return $docs;
}

function lesson_search_files($from = 0) {
    global $DB;

    $sql = "SELECT id, timemodified AS modified FROM {lesson} WHERE timemodified > ? ORDER BY timemodified ASC";

    $lessonrecords = $DB->get_recordset_sql($sql, array($from));

    $fs = get_file_storage();

    foreach ($lessonrecords as $lessonrecord) {
        try {
            $lesson = $DB->get_record('lesson', array('id' => $lessonrecord->id), '*', MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $lesson->course), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('lesson', $lesson->id, $lesson->course, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
        } catch (mdml_missing_record_exception $ex) {
            exit();
        }

        $files = $fs->get_area_files($context->id, 'mod_lesson', 'mediafile', 0, 'timemodified', false);

        foreach ($files as $file) {
            if (strpos($mime = $file->get_mimetype(), 'image') === false) {
                $filename = urlencode($file->get_filename());
                $directlink = '/mod/lesson/mediafile.php?id=' . $context->id;
                $modulelink = '/mod/lesson/view.php?id=' . $cm->id;
                $url = 'literal.id=' . 'lesson_' . $lesson->id . '_file_' . $file->get_id() .
                        '&literal.module=lesson&literal.type=3' . '&literal.directlink=' . $directlink .
                        '&literal.courseid=' . $lesson->course . '&literal.modulelink=' . $modulelink;

                $globalsearch = new core_search();
                $globalsearch->post_file($file, $url);
            }
        }
    }
}

function lesson_search_access($id) {
    global $DB, $USER;
    try {
        $lessonpage = $DB->get_record('lesson_pages', array('id'=>$id), '*', MUST_EXIST);
        $lesson = $DB->get_record('lesson', array('id'=>$lessonpage->lessonid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('lesson', $lesson->id, $lesson->course, MUST_EXIST);
        $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    } catch (dml_missing_record_exception $ex) {
        return SEARCH_ACCESS_DELETED;
    }

    if (!can_access_course($course, null, '', true)) {
        return SEARCH_ACCESS_DENIED;
    }

    $context = context_module::instance($cm->id);
    // give access to search results to teacher or editing-teacher or manager
    $issuperuser = has_capability('mod/lesson:manage', $context);

    if (!$issuperuser) {
        // checks for time boundation
        if (!empty($lesson->available) or !empty($lesson->deadline)) {
            if (empty($lesson->available) and time() > $lesson->deadline) {
                return SEARCH_ACCESS_DENIED;
            } else if (empty($lesson->deadline) and time() < $lesson->available) {
                return SEARCH_ACCESS_DENIED;
            } else if (time() < $lesson->available or time() > $lesson->deadline) {
                return SEARCH_ACCESS_DENIED;
            }
        }

        // if timed lesson - deny access as time gets activated when students click on lesson link.
        // if password-protected lesson - deny access.
        if (!empty($lesson->timed) or !empty($lesson->usepassword)) {
            return SEARCH_ACCESS_DENIED;
        }

        // check for dependencies
        if ($lesson->dependency) {
            if ($dependentlesson = $DB->get_record('lesson', array('id' => $lesson->dependency))) {
                $conditions = unserialize($lesson->conditions);

                // check for the timespent condition
                if ($conditions->timespent) {
                    $timespent = false;
                    if ($attempttimes = $DB->get_records(
                                                        'lesson_timer',
                                                        array("userid"=>$USER->id, "lessonid"=>$dependentlesson->id))
                                                        ) {
                        foreach ($attempttimes as $attempttime) {
                            $duration = $attempttime->lessontime - $attempttime->starttime;
                            if ($conditions->timespent < $duration/60) {
                                $timespent = true;
                            }
                        }
                    }
                    if (!$timespent) {
                        return SEARCH_ACCESS_DENIED;
                    }
                }

                // check for the gradebetterthan condition
                if ($conditions->gradebetterthan) {
                    $gradebetterthan = false;
                    if ($studentgrades = $DB->get_records(
                                                            'lesson_grades',
                                                            array("userid"=>$USER->id, "lessonid"=>$dependentlesson->id))
                                                        ) {
                        foreach ($studentgrades as $studentgrade) {
                            if ($studentgrade->grade >= $conditions->gradebetterthan) {
                                $gradebetterthan = true;
                            }
                        }
                    }
                    if (!$gradebetterthan) {
                        return SEARCH_ACCESS_DENIED;
                    }
                }

                // check for the completed condition
                if ($conditions->completed) {
                    if (!$DB->count_records('lesson_grades', array('userid'=>$USER->id, 'lessonid'=>$dependentlesson->id))) {
                        return SEARCH_ACCESS_DENIED;
                    }
                }
            }

            // if reaches here means that dependency found but no dependent lesson
            // dependent lesson might have been deleted- access granted
        }
    }

    return SEARCH_ACCESS_GRANTED;
}
