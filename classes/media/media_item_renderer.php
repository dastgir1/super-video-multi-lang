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
 * Renders a single media item (one language) of a multi-language supervideo activity.
 *
 * Reuses the same mustache templates and mod_supervideo/player_create AMD calls as the
 * original single-language player, so progress tracking, completion and the visual player
 * keep working exactly as before, no matter which language is showing.
 *
 * @package   mod_supervideo
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_supervideo\media;

use coding_exception;
use context_module;
use mod_supervideo\output\view as legacy_view;
use moodle_url;
use stdClass;

/**
 * Class media_item_renderer
 */
class media_item_renderer {

    /**
     * Renders one media item.
     *
     * @param stdClass $mediaitem row from supervideo_media
     * @param stdClass $supervideo the activity record
     * @param context_module $context
     * @param stdClass $supervideoview the shared tracking record (mod_supervideo\analytics\supervideo_view)
     * @param stdClass $config plugin config (from config_util::get_config)
     * @param stdClass $cm
     * @param stdClass $course
     *
     * @return string HTML for the player.
     * @throws coding_exception
     */
    public static function render(
        stdClass $mediaitem,
        stdClass $supervideo,
        context_module $context,
        stdClass $supervideoview,
        stdClass $config,
        stdClass $cm,
        stdClass $course
    ) {
        global $PAGE, $OUTPUT;

        $elementid = "svmedia-{$mediaitem->id}-" . uniqid();

        switch ($mediaitem->sourcetype) {
            case "upload":
                return self::render_upload($mediaitem, $context, $supervideo, $supervideoview, $config, $elementid);

            case "url":
                return self::render_url($mediaitem, $supervideo, $supervideoview, $config, $elementid);

            case "embed":
                return self::render_embed($mediaitem, $supervideo, $supervideoview, $context, $elementid);

            case "legacy":
                return self::render_legacy($mediaitem, $cm, $course, $context);

            default:
                return $OUTPUT->render_from_template("mod_supervideo/error", [
                    "elementId" => "message_notfound_{$mediaitem->id}",
                    "type" => "danger",
                    "message" => get_string("idnotfound", "mod_supervideo"),
                ]);
        }
    }

    /**
     * Renders an uploaded audio/video file.
     *
     * @param stdClass $mediaitem
     * @param context_module $context
     * @param stdClass $supervideo
     * @param stdClass $supervideoview
     * @param stdClass $config
     * @param string $elementid
     * @return string
     */
    private static function render_upload($mediaitem, context_module $context, $supervideo, $supervideoview, $config, $elementid) {
        global $PAGE, $OUTPUT;

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, "mod_supervideo", "media", $mediaitem->id, "sortorder, id", false);
        $file = reset($files);

        if (!$file) {
            return $OUTPUT->render_from_template("mod_supervideo/error", [
                "elementId" => "message_notfound_{$mediaitem->id}",
                "type" => "danger",
                "message" => get_string("filenotfound", "mod_supervideo"),
            ]);
        }

        $path = implode("/", [
            "",
            $context->id,
            "mod_supervideo/media",
            $file->get_id(),
            "{$mediaitem->id}{$file->get_filepath()}{$file->get_filename()}",
        ]);
        $fullurl = moodle_url::make_file_url("/pluginfile.php", $path, false)->out();

        $amdfunction = $mediaitem->mediatype == "audio" ? "resource_audio" : "resource_video";
        $params = [(int)$supervideoview->id, $supervideoview->currenttime, $elementid];
        if ($amdfunction == "resource_video") {
            $params[] = false;
        }
        $PAGE->requires->js_call_amd("mod_supervideo/player_create", $amdfunction, $params);

        return $OUTPUT->render_from_template("mod_supervideo/embed_div", [
            "elementid" => $elementid,
            "videourl" => $fullurl,
            "autoplay" => $supervideo->autoplay ? "true" : "false",
            "showcontrols" => $supervideo->showcontrols ? 1 : 0,
            "controls" => $config->controls,
            "speed" => $config->speed,
        ]);
    }

    /**
     * Renders an external URL, auto-detecting Youtube, Vimeo, Google Drive or a direct
     * audio/video/HLS link, the same way the original single-language player did.
     *
     * @param stdClass $mediaitem
     * @param stdClass $supervideo
     * @param stdClass $supervideoview
     * @param stdClass $config
     * @param string $elementid
     * @return string
     */
    private static function render_url($mediaitem, $supervideo, $supervideoview, $config, $elementid) {
        global $PAGE, $OUTPUT;

        $url = trim((string)$mediaitem->externalurl);

        // Youtube.
        $pattern = '/youtu(\.be|be\.com)\/(watch\?v=|embed\/|live\/|shorts\/)?([a-z0-9_\-]{11})/i';
        if (preg_match($pattern, $url, $output)) {
            $PAGE->requires->js_call_amd("mod_supervideo/player_create", "youtube", [
                (int)$supervideoview->id,
                $supervideoview->currenttime,
                $elementid,
                $output[3],
                $supervideo->playersize ?: "16x9",
                $supervideo->showcontrols ? 1 : 0,
                $supervideo->autoplay ? 1 : 0,
            ]);
            return $OUTPUT->render_from_template("mod_supervideo/embed_div", ["elementid" => $elementid]);
        }

        // Vimeo.
        if (preg_match("/vimeo\.com\/(\d+)(\/(\w+))?/", $url, $output)) {
            $parametersvimeo = implode("&amp;", [
                "pip=1",
                "title=0",
                "byline=0",
                "playsinline=1",
                $supervideo->showcontrols ? "title=1" : "title=0",
                $supervideo->autoplay ? "autoplay=1" : "autoplay=0",
                $supervideo->showcontrols ? "controls=1" : "controls=0",
            ]);
            $vimeourl = isset($output[3])
                ? "{$output[1]}?h={$output[3]}&pip{$parametersvimeo}"
                : "{$output[1]}?pip{$parametersvimeo}";

            $PAGE->requires->js_call_amd("mod_supervideo/player_create", "vimeo", [
                $supervideoview->id,
                $supervideoview->currenttime,
                $url,
                $elementid,
            ]);
            return $OUTPUT->render_from_template("mod_supervideo/embed_vimeo", [
                "elementid" => $elementid,
                "vimeo_id" => $vimeourl,
                "parametersvimeo" => $parametersvimeo,
            ]);
        }

        // Google Drive.
        if (preg_match('/\/d\/\K[^\/]+(?=\/)/', $url, $output)) {
            $parametersdrive = implode("&amp;", [
                $supervideo->showcontrols ? "controls=1" : "controls=0",
                $supervideo->autoplay ? "autoplay=1" : "autoplay=0",
            ]);
            $PAGE->requires->js_call_amd("mod_supervideo/player_create", "drive", [
                (int)$supervideoview->id,
                $elementid,
                $supervideo->playersize ?: "16x9",
            ]);
            return $OUTPUT->render_from_template("mod_supervideo/embed_drive", [
                "elementid" => $elementid,
                "driveid" => $output[0],
                "parametersdrive" => $parametersdrive,
            ]);
        }

        // Direct file (mp4/webm/mp3/m3u8/etc) or any other http(s) link: play it as audio/video.
        $hasaudio = $mediaitem->mediatype == "audio" || preg_match("/^https?.*\.(mp3|aac|m4a)/i", $url);
        $hls = (bool)preg_match("/^https?.*\.(m3u8)/i", $url);

        $PAGE->requires->js_call_amd(
            "mod_supervideo/player_create",
            $hasaudio ? "resource_audio" : "resource_video",
            [
                (int)$supervideoview->id,
                $supervideoview->currenttime,
                $elementid,
                $hls,
            ]
        );

        return $OUTPUT->render_from_template("mod_supervideo/embed_div", [
            "elementid" => $elementid,
            "videourl" => $url,
            "autoplay" => $supervideo->autoplay ? 1 : 0,
            "showcontrols" => $supervideo->showcontrols ? 1 : 0,
            "controls" => $config->controls,
            "speed" => $config->speed,
            "hls" => $hls,
            "has_audio" => $hasaudio,
        ]);
    }

    /**
     * Renders a pasted embed code (an <iframe> snippet, or a bare URL to embed in an iframe).
     *
     * @param stdClass $mediaitem
     * @param stdClass $supervideo
     * @param stdClass $supervideoview
     * @param context_module $context
     * @param string $elementid
     * @return string
     */
    private static function render_embed($mediaitem, $supervideo, $supervideoview, context_module $context, $elementid) {
        global $PAGE, $OUTPUT;

        $code = trim((string)$mediaitem->embedcode);

        $src = null;
        if (preg_match('/^https?:\/\//i', $code)) {
            $src = $code;
        } else if (preg_match('/<iframe[^>]*\ssrc=["\']([^"\']+)["\']/i', $code, $output)) {
            $src = $output[1];
        }

        if ($src) {
            $PAGE->requires->js_call_amd("mod_supervideo/player_create", "embed", [
                (int)$supervideoview->id,
                $supervideoview->currenttime,
                $elementid,
                $supervideo->playersize ?: "16x9",
            ]);

            $currenttime = (int)$supervideoview->currenttime;
            if ($currenttime > 0) {
                $separator = (strpos($src, '?') !== false) ? '&' : '?';
                $src .= "{$separator}t={$currenttime}";
            }

            return $OUTPUT->render_from_template("mod_supervideo/embed_iframe", [
                "elementid" => $elementid,
                "videourl" => $src,
            ]);
        }

        // Could not find an iframe src: fall back to rendering the sanitised HTML as-is.
        // Progress cannot be tracked for arbitrary third-party embed widgets like this.
        return "<div id=\"{$elementid}\" class=\"supervideo-embedcode\">" .
            format_text($code, FORMAT_HTML, ["context" => $context, "noclean" => true]) .
            "</div>";
    }

    /**
     * Renders a media item migrated from a pre-multi-language version of the plugin
     * (Ottflix, Pandavideo, or anything else the simple upload/url/embed detection could
     * not confidently classify), by delegating to the original single-language renderer.
     *
     * @param stdClass $mediaitem
     * @param stdClass $cm
     * @param stdClass $course
     * @param context_module $context
     * @return string
     */
    private static function render_legacy($mediaitem, $cm, $course, context_module $context) {
        $legacydata = json_decode((string)$mediaitem->embedcode);
        if (!$legacydata) {
            global $OUTPUT;
            return $OUTPUT->render_from_template("mod_supervideo/error", [
                "elementId" => "message_notfound_{$mediaitem->id}",
                "type" => "danger",
                "message" => get_string("idnotfound", "mod_supervideo"),
            ]);
        }

        global $DB;
        $supervideo = $DB->get_record("supervideo", ["id" => $mediaitem->supervideo], "*", MUST_EXIST);
        $supervideo->origem = $legacydata->origem ?? "link";
        $supervideo->videourl = $legacydata->videourl ?? "";
        $supervideo->ottflix_ia = $legacydata->ottflix_ia ?? "";

        $legacyview = new legacy_view($cm, $course, $supervideo, $context);
        return $legacyview->get_player();
    }
}
