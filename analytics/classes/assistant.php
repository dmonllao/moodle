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

    public function get_reply(int $conversationid, string $message): \stdClass {
        global $CFG, $USER;

        $CFG->rasaserver = 'http://rasa';
        $CFG->rasaport = 5005;
        $path = '/webhooks/rest/webhook';

        $url = $CFG->rasaserver . ':' . $CFG->rasaport . $path;
        $curl = new \curl();
        $curl->setHeader('Content-type: application/json');
        $params = [
            'sender' => $USER->firstname,
            'message' => $message,
        ];
        $response = $curl->post($url, json_encode($params));

        $return = (object)['messages' => []];
        if ($curl->get_errno() === 0) {
            $responsesobj = json_decode($response);

            foreach ($responsesobj as $responseobj) {
                if ($responseobj->text) {
                    $return->messages[] = $responseobj->text;
                } else if ($responseobj->image) {
                    $return->messages[] = $responseobj->image;
                }
            }
        } else {
            $return->error = $response;
        }

        return $return;
    }
}
