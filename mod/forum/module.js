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
 * JavaScript library for the forum module.
 *
 * @package    mod_forum
 * @copyright  2015 onwards Daniel Neis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_forum = M.mod_forum || {};

// This function adds the 'show related discussions' link and list on every discussion.
M.mod_forum.show_related_discussions = function(Y, related_discussions) {
    var mainsection = Y.one('#page-mod-forum-discuss #region-main');

    mainsection.append('<h3>' + M.util.get_string('related_discussions', 'forum') + '</a></h3>');

    var related_list = '<ul id="relateddiscussions">';
    for (var i in related_discussions) {
        discussion = related_discussions[i];
        console.log(discussion);
        related_list += '<li><a href="' +M.cfg.wwwroot + discussion.contextlink + '">'+discussion.name +'</a></li>';
    }
    related_list += '</ul>';
    mainsection.append(related_list);
}
