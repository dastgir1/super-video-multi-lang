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
 * Language helpers for mod_supervideo.
 *
 * @package   mod_supervideo
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_supervideo\media;

/**
 * Class language_util
 */
class language_util {

    /**
     * Returns the list of languages a teacher can tag a media item with: a curated list of common
     * languages (so you can pick, e.g., Spanish or Arabic without installing that Moodle language
     * pack), merged with whatever language packs are actually installed on this site.
     *
     * @return array langcode => display name, sorted by name.
     */
    public static function get_installed_languages() {
        $common = [
            "en" => "English",
            "es" => "Español (Spanish)",
            "ja" => "日本語 (Japanese)",
            "ar" => "العربية (Arabic)",
            "fr" => "Français (French)",
            "de" => "Deutsch (German)",
            "it" => "Italiano (Italian)",
            "pt" => "Português (Portuguese)",
            "pt_br" => "Português do Brasil (Brazilian Portuguese)",
            "ru" => "Русский (Russian)",
            "zh_cn" => "简体中文 (Chinese, Simplified)",
            "zh_tw" => "繁體中文 (Chinese, Traditional)",
            "ko" => "한국어 (Korean)",
            "hi" => "हिन्दी (Hindi)",
            "id" => "Bahasa Indonesia (Indonesian)",
            "vi" => "Tiếng Việt (Vietnamese)",
            "th" => "ไทย (Thai)",
            "tr" => "Türkçe (Turkish)",
            "nl" => "Nederlands (Dutch)",
            "pl" => "Polski (Polish)",
            "sv" => "Svenska (Swedish)",
            "he" => "עברית (Hebrew)",
            "fa" => "فارسی (Persian)",
            "ur" => "اردو (Urdu)",
            "bn" => "বাংলা (Bengali)",
            "el" => "Ελληνικά (Greek)",
            "uk" => "Українська (Ukrainian)",
            "ro" => "Română (Romanian)",
            "cs" => "Čeština (Czech)",
        ];

        // Merge in whatever language packs are actually installed, so the list stays accurate
        // (and uses the site's own translated name) for anything beyond the common list above.
        $installed = get_string_manager()->get_list_of_translations();
        $languages = $installed + $common;

        asort($languages);
        return $languages;
    }

    /**
     * Returns the current user's preferred language (session/profile language), e.g. "en", "es", "ja".
     *
     * @return string
     */
    public static function get_user_language() {
        global $USER;

        if (!empty($USER->lang)) {
            return $USER->lang;
        }

        return current_language();
    }
}
