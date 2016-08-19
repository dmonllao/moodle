<?php

namespace tool_ie\indicator;

class course extends base {

    static function get_all($modinfo) {

        // TODO It should be weeks or a format extending weeks (for example)
        // even better any timerange-based format.
        return [
            intval($modinfo->get_course()->format === 'weeks'),
            $modinfo->get_course()->enablecompletion,
            $modinfo->get_course()->groupmode,
            $modinfo->get_course()->groupmodeforce,
            $modinfo->get_course()->showgrades,
            intval(!empty(\core_competency\course_competency::list_course_competencies($modinfo->get_course()->id)))
        ];
    }
}

