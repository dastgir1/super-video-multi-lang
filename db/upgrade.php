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
 * Upgrade file
 *
 * @package   mod_supervideo
 * @copyright 2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * function xmldb_supervideo_upgrade
 *
 * @param int $oldversion
 *
 * @return bool
 *
 * @throws Exception
 */
function xmldb_supervideo_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019010303) {

        $tablesupervideo = new xmldb_table("supervideo");

        $fieldurl = new xmldb_field("supervideoid", XMLDB_TYPE_CHAR, 255);
        if ($dbman->field_exists($tablesupervideo, $fieldurl)) {
            $dbman->rename_field($tablesupervideo, $fieldurl, "url");
        }

        upgrade_mod_savepoint(true, 2019010303, "supervideo");
    }

    if ($oldversion < 2023032506) {

        $tablesupervideoview = new xmldb_table("supervideo_view");

        $tablesupervideoview->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $tablesupervideoview->add_field("cm_id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $tablesupervideoview->add_field("user_id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $tablesupervideoview->add_field("currenttime", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $tablesupervideoview->add_field("duration", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $tablesupervideoview->add_field("percent", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $tablesupervideoview->add_field("mapa", XMLDB_TYPE_CHAR, null, null, XMLDB_NOTNULL);
        $tablesupervideoview->add_field("timecreated", XMLDB_TYPE_INTEGER, "20", null, XMLDB_NOTNULL);
        $tablesupervideoview->add_field("timemodified", XMLDB_TYPE_INTEGER, "20", null, XMLDB_NOTNULL);

        $tablesupervideoview->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);

        if (!$dbman->table_exists($tablesupervideoview)) {
            $dbman->create_table($tablesupervideoview);
        }

        $tablesupervideo = new xmldb_table("supervideo");

        $fieldgradeapproval = new xmldb_field("grade_approval", XMLDB_TYPE_INTEGER, 10);
        if (!$dbman->field_exists($tablesupervideo, $fieldgradeapproval)) {
            $dbman->add_field($tablesupervideo, $fieldgradeapproval);
        }

        $fieldcompletpercent = new xmldb_field("completionpercent", XMLDB_TYPE_INTEGER, 10);
        if (!$dbman->field_exists($tablesupervideo, $fieldcompletpercent)) {
            $dbman->add_field($tablesupervideo, $fieldcompletpercent);
        }

        upgrade_mod_savepoint(true, 2023032506, "supervideo");
    }

    if ($oldversion < 2023071800) {

        $table = new xmldb_table("supervideo");
        $field = new xmldb_field("showshowinfo", XMLDB_TYPE_INTEGER, 10, null, null, null, null, null, 0, "showcontrols");
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, "showinfo");
        }

        upgrade_mod_savepoint(true, 2023071800, "supervideo");
    }

    if ($oldversion < 2023072700) {

        $table = new xmldb_table("supervideo");
        $field = new xmldb_field("complet_percent", XMLDB_TYPE_INTEGER, 10, null, null, null, null, null, 0, "grade_approval");
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, "completionpercent");
        }

        upgrade_mod_savepoint(true, 2023072700, "supervideo");
    }

    if ($oldversion < 2023080701) {

        $table = new xmldb_table("supervideo");

        $index = new xmldb_index("showrel", XMLDB_INDEX_NOTUNIQUE, ["showrel"]);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field = new xmldb_field("showrel", XMLDB_TYPE_INTEGER, 10);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2023080701, "supervideo");
    }

    if ($oldversion < 2023081100) {

        $table = new xmldb_table("supervideo");

        $index = new xmldb_index("showinfo", XMLDB_INDEX_NOTUNIQUE, ["showinfo"]);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field = new xmldb_field("showinfo", XMLDB_TYPE_INTEGER, 10);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2023081100, "supervideo");
    }

    if ($oldversion < 2023081602) {

        $table = new xmldb_table("supervideo");

        $field1 = new xmldb_field("playersize", XMLDB_TYPE_CHAR, 15, null, false, false, "", "videourl");
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);

            $sql = "UPDATE {supervideo} SET playersize = videosize";
            $DB->execute($sql);
        }

        $index = new xmldb_index("videosize", XMLDB_INDEX_NOTUNIQUE, ["videosize"]);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field2 = new xmldb_field("videosize", XMLDB_TYPE_CHAR, 15);
        if ($dbman->field_exists($table, $field2)) {
            $dbman->drop_field($table, $field2);
        }

        upgrade_mod_savepoint(true, 2023081602, "supervideo");
    }

    if ($oldversion < 2024083102) {

        $table = new xmldb_table("supervideo");

        $origem = new xmldb_field("origem", XMLDB_TYPE_CHAR, 10, null, false, false, "", "introformat");
        if (!$dbman->field_exists($table, $origem)) {
            $dbman->add_field($table, $origem);
        }

        $supervideos = $DB->get_records("supervideo");
        foreach ($supervideos as $supervideo) {
            $origem = xmldb_supervideo_upgrade_parse($supervideo->videourl);
            if ($origem) {
                $supervideo->origem = $origem;
            }

            if ($supervideo->origem == "link") {
                $supervideo->videourl = str_replace("[link]:", "", $supervideo->videourl);
            }

            $DB->update_record("supervideo", $supervideo);
        }

        upgrade_mod_savepoint(true, 2024083102, "supervideo");
    }

    if ($oldversion < 2024100800) {

        $table = new xmldb_table("supervideo_auth");
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_mod_savepoint(true, 2024100800, "supervideo");
    }

    if ($oldversion < 2024101100) {

        $sql = "
            SELECT cm.id AS cm_id, c.id AS c_id, cm.instance AS cm_instance
              FROM {course_modules} cm
              JOIN {modules}        m ON m.id = cm.module
              JOIN {context}        c ON c.instanceid = cm.id
             WHERE m.name      LIKE 'supervideo'
               AND c.contextlevel = :contextlevel";
        $modules = $DB->get_records_sql($sql, ["contextlevel" => CONTEXT_MODULE]);

        foreach ($modules as $module) {
            $files = $DB->get_records("files", ["contextid" => $module->c_id]);
            foreach ($files as $file) {
                if ($file->itemid != $module->cm_instance) {
                    $file->itemid = $module->cm_instance;
                    $file->pathnamehash = get_file_storage()->get_pathname_hash(
                        $file->contextid, $file->component, $file->filearea, $file->itemid,
                        $file->filepath, $file->filename);
                    $DB->update_record("files", $file);
                }
            }
        }

        upgrade_mod_savepoint(true, 2024101100, "supervideo");
    }

    if ($oldversion < 2025041600) {

        $table = new xmldb_table("supervideo");

        $origem = new xmldb_field("ottflix_ia", XMLDB_TYPE_CHAR, 100, null, false, false, "", "videourl");
        if (!$dbman->field_exists($table, $origem)) {
            $dbman->add_field($table, $origem);
        }

        upgrade_mod_savepoint(true, 2025041600, "supervideo");
    }

    if ($oldversion < 2025080400) {

        $sql = "UPDATE {supervideo} SET origem = 'pandavideo' WHERE origem LIKE 'panda'";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2025080400, "supervideo");
    }

    if ($oldversion < 2026051800) {

        $table = new xmldb_table('supervideo_view');
        $oldfield = new xmldb_field('mapa', XMLDB_TYPE_TEXT);
        if ($dbman->field_exists($table, $oldfield)) {
            $dbman->rename_field($table, $oldfield, 'map');
        }

        $showmap = get_config("supervideo", "showmapa");
        if ($showmap === false) {
            $showmap = get_config("supervideo", "showmap");
        }
        if ($showmap === false) {
            $showmap = 1;
        }
        set_config("showmap", !empty($showmap) ? 1 : 0, "supervideo");

        upgrade_mod_savepoint(true, 2026051800, "supervideo");
    }

    if ($oldversion < 2026061801) {

        $legacyshowmap = get_config("supervideo", "showmapa");
        $showmap = get_config("supervideo", "showmap");

        if ($legacyshowmap !== false) {
            set_config("showmap", !empty($legacyshowmap) ? 1 : 0, "supervideo");
        } else if ($showmap === false || $showmap === "") {
            set_config("showmap", 1, "supervideo");
        }

        upgrade_mod_savepoint(true, 2026061801, "supervideo");
    }

    if ($oldversion < 2026070400) {

        // Add the displaymode field (Activity Page vs Course Page).
        $table = new xmldb_table("supervideo");
        $displaymode = new xmldb_field("displaymode", XMLDB_TYPE_CHAR, 10, null, false, false, "activity", "completionpercent");
        if (!$dbman->field_exists($table, $displaymode)) {
            $dbman->add_field($table, $displaymode);
        }

        // Create the supervideo_media table (one audio/video item per language).
        $tablemedia = new xmldb_table("supervideo_media");
        if (!$dbman->table_exists($tablemedia)) {
            $tablemedia->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $tablemedia->add_field("supervideo", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
            $tablemedia->add_field("mediatype", XMLDB_TYPE_CHAR, "10", null, XMLDB_NOTNULL, null, "video");
            $tablemedia->add_field("sourcetype", XMLDB_TYPE_CHAR, "10", null, XMLDB_NOTNULL, null, "upload");
            $tablemedia->add_field("externalurl", XMLDB_TYPE_CHAR, "1333", null, false, null, null);
            $tablemedia->add_field("embedcode", XMLDB_TYPE_TEXT, null, null, false, null, null);
            $tablemedia->add_field("lang", XMLDB_TYPE_CHAR, "30", null, false, null, null);
            $tablemedia->add_field("description", XMLDB_TYPE_TEXT, null, null, false, null, null);
            $tablemedia->add_field("descriptionformat", XMLDB_TYPE_INTEGER, "4", null, false, null, 1);
            $tablemedia->add_field("sortorder", XMLDB_TYPE_INTEGER, "10", null, false, null, 0);
            $tablemedia->add_field("timecreated", XMLDB_TYPE_INTEGER, "10", null, false, null, null);
            $tablemedia->add_field("timemodified", XMLDB_TYPE_INTEGER, "10", null, false, null, null);

            $tablemedia->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);

            $tablemedia->add_index("supervideo", XMLDB_INDEX_NOTUNIQUE, ["supervideo"]);
            $tablemedia->add_index("lang", XMLDB_INDEX_NOTUNIQUE, ["lang"]);

            $dbman->create_table($tablemedia);
        }

        // Migrate every existing single-language activity into one supervideo_media row,
        // so activities that were already created keep working exactly as before.
        $supervideos = $DB->get_records("supervideo");
        foreach ($supervideos as $supervideo) {
            $exists = $DB->record_exists("supervideo_media", ["supervideo" => $supervideo->id]);
            if ($exists || empty($supervideo->videourl)) {
                continue;
            }

            $media = new stdClass();
            $media->supervideo = $supervideo->id;
            $media->lang = "";
            $media->description = "";
            $media->descriptionformat = FORMAT_HTML;
            $media->sortorder = 0;
            $media->timecreated = $supervideo->timemodified ?: time();
            $media->timemodified = $media->timecreated;
            $media->mediatype = "video";

            if (preg_match('/^https?.*\.(mp3|aac|m4a)/i', (string)$supervideo->videourl)) {
                $media->mediatype = "audio";
            }

            if ($supervideo->origem == "upload") {
                $media->sourcetype = "upload";
                $media->externalurl = null;
                $media->embedcode = null;

                $fs = get_file_storage();
                $cm = $DB->get_record("course_modules", ["module" =>
                    $DB->get_field("modules", "id", ["name" => "supervideo"]), "instance" => $supervideo->id]);
                if ($cm) {
                    $context = context_module::instance($cm->id);
                    $files = $fs->get_area_files($context->id, "mod_supervideo", "content", $supervideo->id, "sortorder", false);
                    foreach ($files as $file) {
                        if (preg_match('/\.(mp3|aac|m4a)$/i', $file->get_filename())) {
                            $media->mediatype = "audio";
                        }
                        break;
                    }
                }
            } else if ($supervideo->origem == "embed") {
                $media->sourcetype = "embed";
                $media->embedcode = $supervideo->videourl;
            } else if ($supervideo->origem == "link") {
                $media->sourcetype = "url";
                $media->externalurl = $supervideo->videourl;
            } else {
                // Youtube, Vimeo, Drive, Pandavideo, Ottflix and any other legacy source:
                // keep them rendering exactly as before via the legacy renderer.
                $media->sourcetype = "legacy";
                $media->embedcode = json_encode([
                    "origem" => $supervideo->origem,
                    "videourl" => $supervideo->videourl,
                    "ottflix_ia" => $supervideo->ottflix_ia,
                ]);
            }

            $DB->insert_record("supervideo_media", $media);
        }

        upgrade_mod_savepoint(true, 2026070400, "supervideo");
    }

    return true;
}

/**
 * Function xmldb_supervideo_upgrade_parse
 *
 * @param $videourl
 *
 * @return bool|string
 */
function xmldb_supervideo_upgrade_parse($videourl) {

    if (strpos($videourl, "ottflix.com") > 1) {
        return "ottflix";
    }
    if (strpos($videourl, "[link]:") === 0) {
        return "link";
    }
    if (strpos($videourl, "[resource-file") === 0) {
        return "upload";
    }
    if (strpos($videourl, "youtu")) {
        if (preg_match('/youtu(\.be|be\.com)\/(watch\?v=|embed\/|live\/|shorts\/)?([a-z0-9_\-]{11})/i', $videourl, $output)) {
            return "youtube";
        }
    }
    if (strpos($videourl, "vimeo")) {
        return "vimeo";
    }
    if (strpos($videourl, "docs.google.com") || strpos($videourl, "drive.google.com")) {
        return "drive";
    }

    if (preg_match('/^https?.*\.(mp3|mp4|m3u8|webm)/i', $videourl, $output)) {
        return "link";
    }

    return false;
}
