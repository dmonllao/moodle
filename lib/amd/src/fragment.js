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
 * Standard Ajax wrapper for Moodle. It calls the central Ajax script,
 * which can call any existing webservice using the current session.
 * In addition, it can batch multiple requests and return multiple responses.
 *
 * @module     core/fragment
 * @class      fragment
 * @package    core
 * @copyright  2016 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/config'], function($, config) {

    return {
        fragment_load: function(callback, params) {

            // Ajax stuff.
            var deferred = $.Deferred();

            // Worth noting somewhere that the assign module seems to require userid, rownum etc. to be passed via POST / GET.
            var promise = $.ajax({
                method: "POST",
                url: config.wwwroot + '/lib/ajax/fragment.php',
                dataType: "json",
                data: { callback: callback, params: params, userid: params.studentid }
                // data: { callback: callback, params: params }
            });

            promise.done(function(data) {
                deferred.resolve(data);
            }).fail(function(ex) {
                deferred.reject(ex);
            });
            return deferred.promise();
        }
    };
});