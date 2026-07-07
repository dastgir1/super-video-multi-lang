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
 * Media manager: CRUD helper for the supervideo_media table.
 *
 * Each supervideo activity can have several media items (one per language).
 *
 * @package   mod_supervideo
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_supervideo\media;

use context_module;
use stdClass;

/**
 * Class media_manager
 */
class media_manager {

    /** Allowed media types. */
    const MEDIA_TYPES = ["video", "audio"];

    /** Allowed source types (legacy is only ever produced by the upgrade script). */
    const SOURCE_TYPES = ["upload", "url", "embed", "legacy"];

    /**
     * Returns every media item of an activity, ordered by sortorder.
     *
     * @param int $supervideoid
     * @return stdClass[]
     */
    public static function get_items($supervideoid) {
        global $DB;

        return $DB->get_records("supervideo_media", ["supervideo" => $supervideoid], "sortorder ASC, id ASC");
    }

    /**
     * Saves the media items submitted from mod_form (repeat_elements arrays) for one activity,
     * inserting, updating and deleting rows as needed, and storing any uploaded files.
     *
     * @param stdClass $supervideo the just-saved activity record (needs id and coursemodule).
     * @param stdClass $data the raw form data object.
     * @return void
     */
    public static function save_from_form(stdClass $supervideo, stdClass $data) {
        global $DB;

        $context = context_module::instance($supervideo->coursemodule);
        $existingids = [];

        $count = isset($data->mediarepeats) ? (int)$data->mediarepeats : 0;
        $sortorder = 0;

        for ($i = 0; $i < $count; $i++) {
            $mediatype = self::param($data, "mediatype", $i, "video");
            $sourcetype = self::param($data, "sourcetype", $i, "upload");
            $lang = self::param($data, "medialang", $i, "");
            $description = self::param($data, "mediadescription", $i, "");
            $externalurl = trim(self::param($data, "externalurl", $i, ""));
            $embedcode = self::param($data, "embedcode", $i, "");
            $itemid = (int)self::param($data, "mediaid", $i, 0);
            $uploadfile = self::param($data, "uploadfile", $i, null);
            $deleted = (int)self::param($data, "mediadeleted", $i, 0);

            if ($deleted) {
                if ($itemid) {
                    self::delete_item($itemid, $context);
                }
                continue;
            }

            // Skip completely empty rows (nothing was actually filled in).
            $hassource = ($sourcetype == "upload" && $uploadfile)
                || ($sourcetype == "url" && $externalurl !== "")
                || ($sourcetype == "embed" && trim($embedcode) !== "");
            if (!$hassource && !$itemid) {
                continue;
            }

            $media = new stdClass();
            if ($itemid) {
                $media->id = $itemid;
            }
            $media->supervideo = $supervideo->id;
            $media->mediatype = in_array($mediatype, self::MEDIA_TYPES) ? $mediatype : "video";
            $media->sourcetype = in_array($sourcetype, self::SOURCE_TYPES) ? $sourcetype : "upload";
            $media->lang = $lang;
            $media->description = $description;
            $media->descriptionformat = FORMAT_HTML;
            $media->sortorder = $sortorder++;
            $media->timemodified = time();

            if ($media->sourcetype == "url") {
                $media->externalurl = $externalurl;
                $media->embedcode = null;
            } else if ($media->sourcetype == "embed") {
                $media->externalurl = null;
                $media->embedcode = $embedcode;
            } else {
                $media->externalurl = null;
                $media->embedcode = null;
            }

            if ($itemid) {
                $DB->update_record("supervideo_media", $media);
                $mediaid = $itemid;
            } else {
                $media->timecreated = time();
                $mediaid = $DB->insert_record("supervideo_media", $media);
            }
            $existingids[] = $mediaid;

            if ($media->sourcetype == "upload" && $uploadfile) {
                $options = ["subdirs" => false, "maxfiles" => 1];
                file_save_draft_area_files($uploadfile, $context->id, "mod_supervideo", "media", $mediaid, $options);
            }
        }

        // Remove any media items that were not resubmitted (defensive cleanup).
        $current = $DB->get_records("supervideo_media", ["supervideo" => $supervideo->id], "", "id");
        foreach ($current as $row) {
            if (!in_array($row->id, $existingids)) {
                self::delete_item($row->id, $context);
            }
        }
    }

    /**
     * Deletes one media item and its uploaded file (if any).
     *
     * @param int $mediaid
     * @param context_module $context
     * @return void
     */
    public static function delete_item($mediaid, context_module $context) {
        global $DB;

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, "mod_supervideo", "media", $mediaid);

        $DB->delete_records("supervideo_media", ["id" => $mediaid]);
    }

    /**
     * Deletes every media item of an activity (called from supervideo_delete_instance).
     *
     * @param int $supervideoid
     * @param context_module $context
     * @return void
     */
    public static function delete_all($supervideoid, context_module $context) {
        global $DB;

        $items = self::get_items($supervideoid);
        foreach ($items as $item) {
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, "mod_supervideo", "media", $item->id);
        }
        $DB->delete_records("supervideo_media", ["supervideo" => $supervideoid]);
    }

    /**
     * Picks the media item that best matches the given language:
     * exact match first, then the item flagged as having no language ("any language"),
     * then the very first item.
     *
     * @param stdClass[] $items
     * @param string $lang
     * @return stdClass|null
     */
    public static function pick_for_language(array $items, $lang) {
        if (empty($items)) {
            return null;
        }

        foreach ($items as $item) {
            if ($item->lang === $lang) {
                return $item;
            }
        }

        // Try the "parent" language, e.g. "en" for "en_us".
        if (strpos($lang, "_") !== false) {
            $short = substr($lang, 0, strpos($lang, "_"));
            foreach ($items as $item) {
                if ($item->lang === $short) {
                    return $item;
                }
            }
        }

        foreach ($items as $item) {
            if ($item->lang === "" || $item->lang === null) {
                return $item;
            }
        }

        return reset($items);
    }

    /**
     * Small helper to read repeat_elements-style form fields ("field[$index]").
     *
     * @param stdClass $data
     * @param string $field
     * @param int $index
     * @param mixed $default
     * @return mixed
     */
    private static function param(stdClass $data, $field, $index, $default) {
        if (isset($data->{$field}) && is_array($data->{$field}) && array_key_exists($index, $data->{$field})) {
            return $data->{$field}[$index];
        }
        return $default;
    }
}
