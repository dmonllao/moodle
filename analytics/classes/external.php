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
 * This is the external API for this component.
 *
 * @package    core_analytics
 * @copyright  2019 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_analytics;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/message/lib.php');
require_once($CFG->dirroot . '/message/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;

/**
 * This is the external API for this component.
 *
 * @package    core_analytics
 * @copyright  2019 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Returns description of send_message_to_assistant() parameters.
     *
     * @return external_function_parameters
     * @since  Moodle 3.8
     */
    public static function send_message_to_assistant_parameters() {
        return new external_function_parameters(
            array(
                'conversationid' => new external_value(PARAM_INT, 'id of the conversation'),
                'message' => new external_value(PARAM_TEXT, 'the text of the message'),
            )
        );
    }

    /**
     * Sends a message to the assistant.
     *
     * @param  int $conversationid
     * @param  string $message
     * @return array
     * @since  Moodle 3.8
     */
    public static function send_message_to_assistant(int $conversationid, string $message) {
        global $CFG;

        $params = self::validate_parameters(self::send_message_to_assistant_parameters(),
            ['conversationid' => $conversationid, 'message' => $message]);
        $conversationid = $params['conversationid'];
        $message = $params['message'];

        // Check if messaging is enabled.
        if (empty($CFG->messaging)) {
            throw new \moodle_exception('disabled', 'message');
        }

        // Ensure the current user is allowed to run this function.
        $context = \context_system::instance();
        self::validate_context($context);

        $assistantmessagetext = $message . '? WTF';

        $createdmessage = \core_message\api::send_message_to_conversation($CFG->assistantuserid, $conversationid, $assistantmessagetext,
                FORMAT_PLAIN);
        $createdmessage->text = message_format_message_text((object) [
            'smallmessage' => $createdmessage->text,
            'fullmessageformat' => external_validate_format($message['textformat']),
            'fullmessagetrust' => $createdmessage->fullmessagetrust
        ]);

        return [
            'replies' => [
                $createdmessage->text
            ]
        ];
    }

    /**
     * Returns description of send_message_to_assistant() result value.
     *
     * @return external_description
     * @since  Moodle 3.8
     */
    public static function send_message_to_assistant_returns() {
        return new external_single_structure(
            array(
                // TODO This should only work for external URLs we need something different for internal stuff that takes requests
                // from the mobile app into account.
                'redirect' => new external_value(PARAM_URL, 'the URL (either internal or external) where the user should be redirected',
                    VALUE_OPTIONAL),
                'replies' => new external_multiple_structure(
                    new external_value(PARAM_RAW, 'A reply from the assistant.')
                , 'List of replies from the assistant', VALUE_OPTIONAL),
                'warnings' => new external_warnings(),
            )
        );
    }
}
