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
 * AI assistant.
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_analytics;

require_once($CFG->libdir . '/filelib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * AI assistant
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assistant {

    public function get_nlu() {
        // TODO New plugin type.
        return new \core_analytics\nlu\rasa\input_message_handler();
    }

    public function get_reply(int $conversationid, string $message): \stdClass {
        $reply = $this->get_nlu()->get_reply($conversationid, $message);

        if (empty($reply)) {
            $reply = (object)['messages' => get_string('notsurewhattoreply', 'analytics')];
        }
        return $reply;
    }

    public function get_intent(int $conversationid, string $message): \stdClass {
        return $this->get_nlu()->get_intent($conversationid, $message);
    }

    public function run_intent(\stdClass $intent) {
        // TODO specify return.

        $callable = $this->get_intent_callable($intent->name);

        if (!$callable) {
            return false;
        }

        if (!is_callable($callable)) {
            throw new \coding_exception('The provided callable ' . json_encode($callable) . ' can not be called.');
        }

        // TODO Possibly check confidence with a minconfidence defined in assistant.php?

        // TODO This is just a callable so we need to programmatically check that the structure
        // of the returned value adheres to the expected outcome of an intent.

        return call_user_func_array($callable, [$intent->entities]);
    }

    public function get_intent_callable(string $intentname) {
        global $CFG;

        // TODO for all components and cached.
        include($CFG->dirroot . '/lib/db/assistant.php');

        if (empty($assistant[$intentname])) {
            return false;
        }

        return $assistant[$intentname]['callable'];
    }
}
