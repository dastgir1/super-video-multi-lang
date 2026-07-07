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
 * form file
 *
 * @package   mod_supervideo
 * @copyright 2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->dirroot}/course/moodleform_mod.php");

use mod_supervideo\media\language_util;
use mod_supervideo\media\media_manager;

/**
 * class mod_supervideo_mod_for
 *
 * @package   mod_supervideo
 * @copyright 2024 Eduardo Kraus {@link https://eduardokraus.com}
 */
class mod_supervideo_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     *
     * @throws Exception
     */
    public function definition() {
        global $DB, $CFG, $PAGE, $COURSE, $USER;

        $supervideo = null;
        if ($this->_cm && $this->_cm->instance) {
            $supervideo = $DB->get_record("supervideo", ["id" => $this->_cm->instance]);
        }

        $mform = $this->_form;
        $mform->updateAttributes(["enctype" => "multipart/form-data"]);

        $mform->addElement("header", "general", get_string("general", "form"));

        $mform->addElement("text", "name", get_string("name"), ["size" => "48"], []);
        $mform->setType("name", !empty($CFG->formatstringstriptags) ? PARAM_TEXT : PARAM_CLEANHTML);
        $mform->addRule("name", null, "required", null, "client");
        $mform->addRule("name", get_string("maximumchars", "", 255), "maxlength", 255, "client");

        // Display mode: Activity Page (default) or directly on the Course Page.
        $displaymodeoptions = [
            "activity" => get_string("displaymode_activity", "mod_supervideo"),
            "course" => get_string("displaymode_course", "mod_supervideo"),
        ];
        $mform->addElement("select", "displaymode", get_string("displaymode", "mod_supervideo"), $displaymodeoptions);
        $mform->setType("displaymode", PARAM_ALPHA);
        $mform->setDefault("displaymode", "activity");
        $mform->addHelpButton("displaymode", "displaymode", "mod_supervideo");

        // Player size.
        $sizeoptions = [
            "16x9" => "Video 16x9",
            "4x3" => "Video 4x3",
        ];
        if ($supervideo && $supervideo->playersize && !isset($sizeoptions[$supervideo->playersize])) {
            $sizeoptions[$supervideo->playersize] = $supervideo->playersize;
        }
        $mform->addElement("select", "playersize", get_string("playersize", "mod_supervideo"), $sizeoptions);
        $mform->setDefault("playersize", "16x9");
        $mform->setType("playersize", PARAM_TEXT);

        $config = get_config("supervideo");

        if ($config->showcontrols <= 1) {
            $mform->addElement("advcheckbox", "showcontrols", get_string("showcontrols_desc", "mod_supervideo"));
            $mform->setDefault("showcontrols", $config->showcontrols);
        }

        if ($config->autoplay <= 1) {
            $mform->addElement("advcheckbox", "autoplay", get_string("autoplay_desc", "mod_supervideo"));
            $mform->setDefault("autoplay", $config->autoplay);
        }

        // Media items: one audio/video item per language.
        $mform->addElement("header", "medialistheader", get_string("mediaitem", "mod_supervideo", ""));
        $mform->setExpanded("medialistheader");

        $existingmedia = [];
        if ($supervideo) {
            $existingmedia = array_values(media_manager::get_items($supervideo->id));
        }
        $repeatno = max(count($existingmedia), 1);

        $languageoptions = ["" => get_string("languagedefault", "mod_supervideo")] + language_util::get_installed_languages();

        $mediaitem = [];
        $mediaitem[] = $mform->createElement("hidden", "mediaid", 0);
        $mediaitem[] = $mform->createElement(
            "select",
            "mediatype",
            get_string("mediatype", "mod_supervideo"),
            [
                "video" => get_string("mediatype_video", "mod_supervideo"),
                "audio" => get_string("mediatype_audio", "mod_supervideo"),
            ]
        );
        $mediaitem[] = $mform->createElement(
            "select",
            "sourcetype",
            get_string("sourcetype", "mod_supervideo"),
            [
                "upload" => get_string("sourcetype_upload", "mod_supervideo"),
                "url" => get_string("sourcetype_url", "mod_supervideo"),
                "embed" => get_string("sourcetype_embed", "mod_supervideo"),
            ]
        );
        $mediaitem[] = $mform->createElement(
            "filepicker",
            "uploadfile",
            get_string("uploadfile", "mod_supervideo"),
            null,
            ["accepted_types" => [".mp3", ".mp4", ".webm", ".m4v", ".mov", ".aac", ".m4a"], "maxbytes" => -1]
        );
        $mediaitem[] = $mform->createElement(
            "text",
            "externalurl",
            get_string("externalurl", "mod_supervideo"),
            ["size" => "60"]
        );
        $mediaitem[] = $mform->createElement(
            "textarea",
            "embedcode",
            get_string("embedcode", "mod_supervideo"),
            ["rows" => 4, "cols" => 60]
        );
        $mediaitem[] = $mform->createElement(
            "select",
            "medialang",
            get_string("language", "mod_supervideo"),
            $languageoptions
        );
        $mediaitem[] = $mform->createElement(
            "textarea",
            "mediadescription",
            get_string("mediadescription", "mod_supervideo"),
            ["rows" => 3, "cols" => 60]
        );

        $repeatedoptions = [];
        $repeatedoptions["mediatype"]["default"] = "video";
        $repeatedoptions["sourcetype"]["default"] = "upload";

        $this->repeat_elements(
            $mediaitem,
            $repeatno,
            $repeatedoptions,
            "mediarepeats",
            "mediaaddfields",
            1,
            get_string("addmedia", "mod_supervideo"),
            true,
            "mediadelete"
        );

        $totalrepeats = $repeatno + 1;
        for ($i = 0; $i < $totalrepeats; $i++) {
            $mform->setType("mediaid[{$i}]", PARAM_INT);
            $mform->setType("mediatype[{$i}]", PARAM_ALPHA);
            $mform->setType("sourcetype[{$i}]", PARAM_ALPHA);
            $mform->setType("medialang[{$i}]", PARAM_ALPHANUMEXT);
            $mform->setType("externalurl[{$i}]", PARAM_TEXT);
            $mform->setType("embedcode[{$i}]", PARAM_RAW);
            $mform->setType("mediadescription[{$i}]", PARAM_TEXT);

            $mform->hideIf("uploadfile[{$i}]", "sourcetype[{$i}]", "neq", "upload");
            $mform->hideIf("externalurl[{$i}]", "sourcetype[{$i}]", "neq", "url");
            $mform->hideIf("embedcode[{$i}]", "sourcetype[{$i}]", "neq", "embed");

            $mform->addHelpButton("externalurl[{$i}]", "externalurl", "mod_supervideo");
            $mform->addHelpButton("embedcode[{$i}]", "embedcode", "mod_supervideo");
        }

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Grade Element.
        $mform->addElement("header", "modstandardgrade", get_string("modgrade", "grades"));

        $values = [
            0 => get_string("grade_approval_0", "mod_supervideo"),
            1 => get_string("grade_approval_1", "mod_supervideo"),
        ];
        $mform->addElement("select", "grade_approval", get_string("grade_approval", "mod_supervideo"), $values);

        $mform->addElement(
            "select",
            "gradecat",
            get_string("gradecategoryonmodform", "grades"),
            grade_get_categories_menu($COURSE->id, false)
        );
        $mform->addHelpButton("gradecat", "gradecategoryonmodform", "grades");
        $mform->hideIf("gradecat", "grade_approval", "eq", "0");

        $mform->addElement("text", "gradepass", get_string("gradepass", "grades"), ["size" => 4]);
        $mform->addHelpButton("gradepass", "gradepass", "grades");
        $mform->setType("gradepass", PARAM_INT);
        $mform->hideIf("gradepass", "grade_approval", "eq", "0");

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        $mform->hideIf("completionusegrade", "grade_approval", "eq", "0");
        $mform->hideIf("completionpassgrade", "grade_approval", "eq", "0");

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

        $PAGE->requires->js_call_amd("mod_supervideo/mediaform", "init", []);
    }

    /**
     * Set up the completion checkbox which is not part of standard data.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        if ($this->current->instance) {
            $id = intval($defaultvalues["id"]);
            $items = array_values(media_manager::get_items($id));

            $defaultvalues["mediaid"] = [];
            $defaultvalues["mediatype"] = [];
            $defaultvalues["sourcetype"] = [];
            $defaultvalues["externalurl"] = [];
            $defaultvalues["embedcode"] = [];
            $defaultvalues["medialang"] = [];
            $defaultvalues["mediadescription"] = [];
            $defaultvalues["uploadfile"] = [];

            foreach ($items as $index => $item) {
                $defaultvalues["mediaid"][$index] = $item->id;
                $defaultvalues["mediatype"][$index] = $item->mediatype;
                $defaultvalues["sourcetype"][$index] = $item->sourcetype == "legacy" ? "upload" : $item->sourcetype;
                $defaultvalues["externalurl"][$index] = $item->externalurl;
                $defaultvalues["embedcode"][$index] = $item->embedcode;
                $defaultvalues["medialang"][$index] = $item->lang;
                $defaultvalues["mediadescription"][$index] = $item->description;

                $draftitemid = file_get_submitted_draft_itemid("uploadfile[{$index}]");
                file_prepare_draft_area(
                    $draftitemid,
                    $this->context->id,
                    "mod_supervideo",
                    "media",
                    $item->id,
                    ["subdirs" => false, "maxfiles" => 1]
                );
                $defaultvalues["uploadfile"][$index] = $draftitemid;
            }
        }

        $defaultvalues["completionpercentenabled"] = !empty($defaultvalues["completionpercent"]) ? 1 : 0;
        if (empty($defaultvalues["completionpercent"])) {
            $defaultvalues["completionpercent"] = 1;
        }
    }

    /**
     * Allows modules to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionpercentenabled) || !$autocompletion) {
                $data->completionpercent = 0;
            }
        }
    }

    /**
     * add_completion_rules_oold function
     *
     * @return array
     * @throws Exception
     */
    public function add_completion_rules_oold() {
        $mform = &$this->_form;

        $mform->addElement("text", "completionpercent", get_string("completionpercent", "mod_supervideo"), ["size" => 4]);
        $mform->addHelpButton("completionpercent", "completionpercent", "mod_supervideo");
        $mform->setType("completionpercent", PARAM_INT);

        return ["completionpercent"];
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     *
     * @return array Array of string IDs of added items, empty array if none
     * @throws Exception
     */
    public function add_completion_rules() {
        $mform = &$this->_form;
        $group = [
            $mform->createElement(
                "checkbox",
                "completionpercentenabled",
                "",
                get_string("completionpercent_label", "mod_supervideo")
            ),
            $mform->createElement(
                "text",
                "completionpercent",
                get_string("completionpercent_label", "mod_supervideo"),
                ["size" => "2"]
            ),
            $mform->createElement("html", "%"),
        ];

        $mform->addGroup(
            $group,
            "completionpercentgroup",
            get_string("completionpercent", "mod_supervideo"),
            [" "],
            false
        );
        $mform->disabledIf("completionpercent", "completionpercentenabled", "notchecked");
        $mform->setDefault("completionpercent", 0);
        $mform->setType("completionpercent", PARAM_INT);
        return ["completionpercentgroup"];
    }

    /**
     * completion_rule_enabled function
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return ($data["completionpercent"] > 0);
    }

    /**
     * validation function
     *
     * @param $data
     * @param $files
     * @return array
     * @throws Exception
     */
    public function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);

        if (isset($data["completionpercent"]) && $data["completionpercent"] != "") {
            $data["completionpercent"] = intval($data["completionpercent"]);
            if ($data["completionpercent"] < 1) {
                $data["completionpercent"] = "";
            }
            if ($data["completionpercent"] > 100) {
                $errors["completionpercent"] = get_string("completionpercent_error", "mod_supervideo");
            }
        }

        if (isset($data["gradepass"]) && $data["gradepass"] != "") {
            $data["gradepass"] = intval($data["gradepass"]);
            if ($data["gradepass"] < 1) {
                $data["gradepass"] = "";
            }
            if ($data["gradepass"] > 100) {
                $errors["gradepass"] = get_string("completionpercent_error", "mod_supervideo");
            }
        }

        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();

        $count = isset($data["mediarepeats"]) ? (int)$data["mediarepeats"] : 0;
        $hasvalidmedia = false;

        for ($i = 0; $i < $count; $i++) {
            $sourcetype = $data["sourcetype"][$i] ?? "upload";
            $externalurl = trim($data["externalurl"][$i] ?? "");
            $embedcode = trim($data["embedcode"][$i] ?? "");
            $uploadfile = $data["uploadfile"][$i] ?? null;
            $mediaid = (int)($data["mediaid"][$i] ?? 0);

            $isfilledin = false;
            if ($sourcetype == "upload") {
                $files = $uploadfile ? $fs->get_area_files($usercontext->id, "user", "draft", $uploadfile, "sortorder, id", false) : [];
                $isfilledin = !empty($files);
                if (!$isfilledin && $mediaid) {
                    // Existing item: file was already saved previously, only re-validate if a new draft is expected.
                    $isfilledin = true;
                }
                if (!$isfilledin && ($externalurl !== "" || $embedcode !== "" || $mediaid)) {
                    $errors["uploadfile[{$i}]"] = get_string("mediarequired", "mod_supervideo");
                }
            } else if ($sourcetype == "url") {
                $isfilledin = $externalurl !== "";
                if (!$isfilledin && ($mediaid || $embedcode !== "")) {
                    $errors["externalurl[{$i}]"] = get_string("mediarequired", "mod_supervideo");
                }
            } else if ($sourcetype == "embed") {
                $isfilledin = $embedcode !== "";
                if (!$isfilledin && ($mediaid || $externalurl !== "")) {
                    $errors["embedcode[{$i}]"] = get_string("mediarequired", "mod_supervideo");
                }
            }

            if ($isfilledin || $mediaid) {
                $hasvalidmedia = true;
            }
        }

        if (!$hasvalidmedia) {
            $errors["medialistheader"] = get_string("mediaitem_required", "mod_supervideo");
        }

        return $errors;
    }
}
