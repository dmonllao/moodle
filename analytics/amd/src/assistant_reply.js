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
 * Gets assistant replies.
 *
 * @module     analytics/assistant_reply
 * @class      assistant_reply
 * @package    core_analytics
 * @copyright  2019 David Monllao {@link http://www.davidmonllao.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
        'core/config',
        'core/notification',
        'core_message/message_repository'], function(
        $,
        Config,
        Notification,
        Repository) {


    return /** @alias module:analytics/assistant_reply */ {

        forMe: function(conversation) {

            if (conversation.members.length != 2) {
                // Only private conversations.
                return false;
            }

            if (typeof Config.assistantuserid == 'undefined') {
                console.error('No $CFG->assistantuserid defined');
                return false;
            }

            for (i in conversation.members) {
                userid = conversation.members[i].id;
                if (userid !== conversation.loggedInUserId && userid == parseInt(Config.assistantuserid)) {
                    // We know that the assistant participates on this conversation. We still need to check that this
                    // message comes from the logged in user as we don't want to forward the assistant messages
                    // to the assistant.
                    lastMessage = conversation.messages[conversation.messages.length - 1];
                    if (lastMessage.fromloggedinuser === true) {
                        return true;
                    }
                }
            }

            return false;
        },

        /**
         * Forwards the message to the assistant and sends the user to an URL provided by the assistant if necessary.
         *
         * @method loadInfo
         * @param {int} conversationId
         * @param {Object} message
         */
        forwardMessage: function(conversationId, message) {

            var messageText = $(message.text).text()
            return Repository.sendMessageToAssistant(conversationId, messageText)
                .then(function(data) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else if (data.replies) {
                        // The assistant replies have already been inserted into the database and will appear in the
                        // conversation box once refreshed.
                        // TODO We should prevent users from abusing the assistant web service.
                        console.log(data.replies);
                    }

                    if (data.warnings) {
                        Notification.exception(new Error(data.warnings[0].message));
                    }
                })
                .catch(Notification.exception);
        }
    };
});
