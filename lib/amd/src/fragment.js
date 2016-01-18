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
 * A way to call HTML fragments to be inserted as required via JavaScript.
 *
 * @module     core/fragment
 * @class      fragment
 * @package    core
 * @copyright  2016 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/ajax'], function($, ajax) {

    return /** @alias module:core/fragment */{

        /**
         * Loads an HTML fragment through a callback.
         *
         * @method fragment_load
         * @param {string} component Component where callback is located.
         * @param {string} callback Callback function name.
         * @param {object} params Parameters for the callback.
         * @return {Promise} JQuery promise object resolved when the fragment has been loaded.
         */
        fragment_load: function(component, callback, params) {

            // Change params into required webservice format.
            var formattedparams = [];
            for (var index in params) {
                formattedparams.push({name: index, value: params[index]});
            }

            // Ajax stuff.
            var deferred = $.Deferred();

            var promises = ajax.call([{
                methodname: 'core_get_fragment',
                args:{
                    component: component,
                    callback: callback,
                    args: formattedparams
                }
            }], false);

            // Worth noting somewhere that the assign module seems to require userid, rownum etc. to be passed via POST / GET.

            promises[0].done(function(data) {
                deferred.resolve(data);
            }).fail(function(ex) {
                deferred.reject(ex);
            });
            return deferred.promise();
        }
    };
});