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

define(["jquery"], function($) {

    var pauseMediaInside = function($container) {
        $container.find("video, audio").each(function() {
            try {
                this.pause();
            } catch (e) {
                // Ignore players that cannot be paused this way (e.g. not yet initialised).
            }
        });
    };

    return {
        /**
         * Initialises one language switcher instance.
         *
         * @param {String} uniqueid the id of the wrapper element (see language_player.mustache).
         */
        init: function(uniqueid) {
            var $wrapper = $("#" + uniqueid);
            if (!$wrapper.length) {
                return;
            }

            var $select = $wrapper.find(".supervideo-language-select");
            var $items = $wrapper.find(".supervideo-language-item");

            $select.on("change", function() {
                var lang = $(this).val();

                $items.each(function() {
                    var $item = $(this);
                    if ($item.data("supervideo-lang") === lang) {
                        $item.show();
                    } else {
                        pauseMediaInside($item);
                        $item.hide();
                    }
                });
            });
        }
    };
});
