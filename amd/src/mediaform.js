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
 * Guarantees that, for every media item row in the activity edit form, only the field matching
 * the selected Source Type (Upload / URL / Embed code) is visible: this runs in addition to
 * Moodle's own hideIf, so the row never shows more than one "where's the media" field at once
 * even if hideIf did not apply for some reason (e.g. an older theme/JS cache).
 */
define(["jquery"], function($) {

    var FIELDS = {
        upload: "uploadfile",
        url: "externalurl",
        embed: "embedcode",
    };

    var applyRow = function($select) {
        var name = $select.attr("name") || "";
        var match = name.match(/^sourcetype\[(\d+)]$/);
        if (!match) {
            return;
        }
        var index = match[1];
        var selected = $select.val();

        Object.keys(FIELDS).forEach(function(key) {
            var fieldname = FIELDS[key] + "[" + index + "]";
            var $field = $("[name='" + fieldname + "'], [name='" + fieldname + "[]']");
            if (!$field.length) {
                return;
            }
            var $row = $field.closest(".fitem, .form-group");
            if (!$row.length) {
                $row = $field;
            }
            if (key === selected) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    };

    return {
        /**
         * Initialises the media item source-type toggling.
         */
        init: function() {
            var $selects = $("select[name^='sourcetype[']");
            $selects.each(function() {
                applyRow($(this));
            });
            $(document).on("change", "select[name^='sourcetype[']", function() {
                applyRow($(this));
            });
        }
    };
});
