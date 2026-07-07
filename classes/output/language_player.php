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
 * Builds the multi-language player + language switcher, used both by view.php (Activity Page
 * mode) and by supervideo_cm_info_view() (Course Page mode).
 *
 * @package   mod_supervideo
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_supervideo\output;

use context_module;
use mod_supervideo\analytics\supervideo_view;
use mod_supervideo\media\language_util;
use mod_supervideo\media\media_item_renderer;
use mod_supervideo\media\media_manager;
use mod_supervideo\util\config_util;
use stdClass;

/**
 * Class language_player
 */
class language_player {

    /**
     * Renders the language switcher (if there is more than one language available) plus every
     * media item's player, and returns ready-to-echo HTML. Only the item matching the learner's
     * preferred language (or the best fallback) is initially visible; switching between the
     * others happens client-side, with no page reload.
     *
     * @param stdClass $cm
     * @param stdClass $course
     * @param stdClass $supervideo
     * @param context_module $context
     *
     * @return string
     */
    public static function render($cm, $course, $supervideo, context_module $context) {
        global $PAGE, $OUTPUT;

        $items = media_manager::get_items($supervideo->id);
        if (empty($items)) {
            return $OUTPUT->render_from_template("mod_supervideo/error", [
                "elementId" => "message_notfound",
                "type" => "danger",
                "message" => get_string("idnotfound", "mod_supervideo"),
            ]);
        }

        $config = config_util::get_config($supervideo);
        $supervideoview = supervideo_view::create($cm->id);

        $userlang = language_util::get_user_language();
        $selecteditem = media_manager::pick_for_language($items, $userlang);

        $languagenames = language_util::get_installed_languages();

        $languages = [];
        $mustacheitems = [];
        $seenlangs = [];

        foreach ($items as $item) {
            $lang = (string)$item->lang;

            if (!isset($seenlangs[$lang])) {
                $seenlangs[$lang] = true;
                $languages[] = [
                    "lang" => $lang,
                    "langname" => $lang !== "" && isset($languagenames[$lang])
                        ? $languagenames[$lang]
                        : get_string("languagedefault", "mod_supervideo"),
                    "selected" => $item->id == $selecteditem->id,
                ];
            }

            $player = media_item_renderer::render($item, $supervideo, $context, $supervideoview, $config, $cm, $course);
            $description = "";
            if (!empty($item->description)) {
                $description = format_text($item->description, $item->descriptionformat, ["context" => $context]);
            }

            $mustacheitems[] = [
                "lang" => $lang,
                "player" => $player,
                "description" => $description,
                "selected" => $item->id == $selecteditem->id,
            ];
        }

        $uniqueid = "supervideo-lang-{$cm->id}";

        $PAGE->requires->js_call_amd("mod_supervideo/language_switcher", "init", [$uniqueid]);

        return $OUTPUT->render_from_template("mod_supervideo/language_player", [
            "uniqueid" => $uniqueid,
            "showswitcher" => count($languages) > 1,
            "languages" => $languages,
            "items" => $mustacheitems,
        ]);
    }
}
