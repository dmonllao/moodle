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
 * Needs to have a description about forms and stuff.
 *
 * @module     core/forms
 * @class      forms
 * @package    core
 * @copyright  2015 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.1
 */
define(['jquery', 'core/templates', 'core/fragment'], function($, templates, fragment) {

    var load_student = function(assignid, rownum, studentid) {
        var params = {id: assignid, rownum: rownum, studentid: studentid};
        $.when(fragment.fragment_load('mod_assign', 'fragment', params)).then(function(data) {

            // Quick hack to put in user id.
            var studentidbox = '<div><input type="text" id="studentid" />';
            studentidbox += '<button id="nextstudent">go</button></div>';
            data.html =  '<div id="grade-content">' + studentidbox + data.html + '</div>';

            $("#grade-content").empty();
            $("#grade-content").remove();
            $("#ajax-import-scripts").empty();
            // Remove dialogue base.
            $(".moodle-dialogue-base").empty();
            $(".moodle-dialogue-base").remove();

            $("#add-action-here").after(data.html);

            $('#ajax-import-scripts').append(data.javascript);

            $('#nextstudent').click(function() {
                var studentid = $('#studentid').val();
                studentid = parseInt(studentid);
                load_student(assignid, rownum, studentid);
            });
        });
    };

    return {

        init: function(assignid, rownum) {


            // Other quick test.
            var params = {id: assignid, rownum: rownum};
            $.when(fragment.fragment_load('mod_assign', "fragment", params)).then(function(data) {
                // Quick hack to put in user id.
                var studentidbox = '<div><input type="text" id="studentid" />';
                studentidbox += '<button id="nextstudent">go</button></div>';
                data.html =  '<div id="grade-content">' + studentidbox + data.html + '</div>';

                $("#add-action-here").after(data.html);

                $('#page').append("<div id=\"ajax-import-scripts\">");
                $('#page').append("</div>");
                $('#ajax-import-scripts').append(data.javascript);

                $('#nextstudent').click(function() {
                    var studentid = $('#studentid').val();
                    studentid = parseInt(studentid);
                    load_student(assignid, rownum, studentid);
                });
            });
        }

    };

});
