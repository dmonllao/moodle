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
 * Rasa NLU backend.
 *
 * TODO: New plugin type.
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_analytics\nlu\rasa;

defined('MOODLE_INTERNAL') || die();

/**
 * Rasa NLU backend.
 *
 * @package   core_analytics
 * @copyright 2019 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class input_message_handler implements \core_analytics\nlu_message_handler {

    public function get_reply(int $conversationid, string $message): \stdClass {
        global $CFG, $USER;

        $url = $this->get_rasa_url('/webhooks/rest/webhook');

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

    public function get_intent(int $conversationid, string $message): \stdClass {
        global $CFG, $USER;

        $url = $this->get_rasa_url('/model/parse');

        $curl = new \curl();
        $curl->setHeader('Content-type: application/json');
        $params = [
            'text' => $message,
        ];
        $response = $curl->post($url, json_encode($params));

        $intent = new \stdClass();
        if ($curl->get_errno() === 0) {
            $responseobj = json_decode($response);

            $intent->name = $responseobj->intent->name;
            $intent->confidence = $responseobj->intent->confidence;

            $intent->entities = [];
            foreach ($responseobj->entities as $entitymatch) {
                // There could be multiple matches of the same entity.
                $intent->entities[$entitymatch->entity][] = $entitymatch->value;
            }

        } else {
            $intent->error = $response;
        }

        return $intent;
    }

    public function update_training_data() {

    }

    protected function get_rasa_url(string $path): string {
        global $CFG;
        return $CFG->rasaserver . ':' . $CFG->rasaport . $path;
    }
}