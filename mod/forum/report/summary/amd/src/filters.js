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
 * Module responsible for handling forum summary report filters.
 *
 * @module     forumreport_summary/filters
 * @package    forumreport_summary
 * @copyright  2019 Michael Hawkins <michaelh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Popper from 'core/popper';
import * as Str from 'core/str';

export const init = (root) => {
    root = $(root);

    // Hide loading spinner and show report once page is ready.
    // This ensures filters can be applied when sorting by columns.
    $(document).ready(function() {
        $('.loading-icon').hide();
        $('#summaryreport').removeClass('d-none');
    });

    /**
     * Generic filter handlers.
     */

    // Event handler to clear filters.
    $(root).on("click", ".filter-clear", function(event) {
        // Uncheck any checkboxes.
        $(event.target.parentNode.parentElement).find('input[type=checkbox]:checked').prop("checked", false);

        // Check the default checkbox.
        $(event.target.parentNode.parentElement).find('input[type=checkbox][value="0"]').prop("checked", true);
    });

    // Called to override click event to trigger a proper generate request with filtering.
    var generateWithFilters = event => {
        event.preventDefault();

        var filterParams = event.target.search.substr(1),
            newLink = $('#generatereport').attr('formaction') + '&' + filterParams;

        $('#generatereport').attr('formaction', newLink);
        $('#generatereport').click();
    };

    // Override 'reset table preferences' so it generates with filters.
    $('.resettable').on("click", "a", function(event) {
        generateWithFilters(event);
    });

    // Override table heading sort links so they generate with filters.
    $('thead').on("click", "a", function(event) {
        generateWithFilters(event);
    });

    // Override pagination page links so they generate with filters.
    $('.pagination').on("click", "a", function(event) {
        generateWithFilters(event);
    });

    /**
     * Groups filter specific handlers.
     */

    // Set groups filter button text to include relevant item count (or 'all').
    var setGroupFilterText = async (groupCount) => {
        let stringName = 'groupscountnumber';

        if (!groupCount || $('#filtergroups0').prop("checked")) {
            stringName = 'groupscountall';
        }

        const groupButtonText = await Str.get_string(stringName, 'forumreport_summary', groupCount);
        $('#filter_groups_button').text(groupButtonText);
    };

    // Control groups filter rules around 'all' option.
    $('#filter-groups-popover input[name="filtergroups[]"]').on('click', function(event) {
        // If checking 'all', uncheck others.
        var filterValue = event.target.value;

        // Uncheck other groups if 'all' selected.
        if (filterValue == 0) {
            if ($('#' + event.target.id).prop('checked')) {
                $(event.target.parentNode).find('input[name="filtergroups[]"]:checked').each(function() {
                    if ($(this).val() != 0) {
                        $(this).prop('checked', false);
                    }
                });
            } else {
                // Don't allow unchecking of 'all' directly.
                $('#' + event.target.id).prop('checked', true);
            }
        } else {
            // Uncheck 'all' if another group is checked.
            $('#filtergroups0').prop('checked', false);
        }
    });

    // Event handler for showing groups filter popover.
    $('#filter_groups_button').on('click', function() {
        // Create popover.
        var referenceElement = document.querySelector('#filter_groups_button'),
            popperContent = document.querySelector('#filter-groups-popover');

        new Popper(referenceElement, popperContent, {placement: 'bottom'});

        // Show popover.
        $('#filter-groups-popover').removeClass('d-none');
    });

    // Event handler to save groups filter.
    $(root).on("click", ".form-group > .filter-save", function() {
        // Close the popover.
        $('#filter-groups-popover').addClass('d-none');

        // Update group count on button.
        var groupsCount = $('#filter-groups-popover').find('input[name="filtergroups[]"]:checked').length;
        setGroupFilterText(groupsCount);
    });
};
