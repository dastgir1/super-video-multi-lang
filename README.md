# Moodle mod_supervideo

# Super Video — Multi-Language Update
 
This build adds multi-language audio/video support on top of the existing Super Video plugin,
per the spec you shared. Version 3.2.3 (2026070802).
 
## 2026070802 fix (3.2.3) — language options
 
The Language dropdown was previously built from `get_string_manager()->get_list_of_translations()`,
which only lists Moodle **language packs actually installed on the site** — so if Spanish, Japanese
or Arabic aren't installed there, they simply didn't appear as choices. Changed
`language_util::get_installed_languages()` to a curated list of ~29 common languages (English,
Spanish, Japanese, Arabic, French, German, Portuguese, Chinese, etc.), merged with anything your
site does have installed. You can now tag a media item with any of these regardless of installed
language packs.
 
Note: the language **switcher only appears on the student view when an activity has more than one
media item**. To reproduce your example (English/Spanish/Japanese/Arabic), add four separate media
items in the activity edit form, each with its own file and language selected, then save.
 
## 2026070801 bugfix (3.2.2) — this is the one that actually explains "video not visible"
 
**Root cause found:** the plugin's file server (`supervideo_pluginfile()` in `lib.php`) expects
download URLs shaped like `/{context}/mod_supervideo/media/{fileid}/{itemid}{filepath}{filename}`
— it deliberately throws away the first path segment ("Remove File ID for cache") before reading
the real item id. My uploaded-file renderer was building URLs one segment short, so
`supervideo_pluginfile()` read the wrong values, the file lookup failed, and the video/audio never
loaded — on both the activity page and the course page. Fixed to build the URL in the exact shape
the file server expects (verified against the original single-language renderer's working code).
 
Also, per your request, added a small extra JS module (`mediaform.js`) that guarantees only the
one field matching the selected Source Type (Upload/URL/Embed) is visible per media item, as a
backup to Moodle's own form logic — so you should now only ever see one upload control per item,
not several stacked together.
 
**You'll need to re-upload the video/audio file for each existing media item once** — files
saved on any earlier build have the correct bytes on disk but were never reachable through a
working URL, so they need to go back through the (now-fixed) upload flow.
 
## 2026070800 bugfix (3.2.1)
 
Two smaller bugs, also fixed:
 
1. A leftover duplicate line in `media_manager::save_from_form()` saved the uploaded file into an
   unused filearea before the real save ran, emptying the draft area first. Removed.
2. `supervideo_cm_info_view()` called `$cm->get_course()`, which isn't a real method on `cm_info`
   — it threw, and the exception silently blanked the whole course-page content. Fixed to use
   `get_course($cm->course)`, and wrapped the callback in try/catch + `debugging()` so a future
   issue degrades gracefully instead of hiding everything.
## What was added
 
- **New table `supervideo_media`**: one row per media item (language + type + source + description),
  linked to an activity. New `displaymode` field on `supervideo` (activity | course).
- **Upgrade step**: existing activities are auto-migrated into one media item each, so nothing breaks
  on update. Youtube/Vimeo/Drive/Pandavideo/Ottflix items keep working via a "legacy" renderer that
  reuses the original single-language code untouched.
- **Teacher form (`mod_form.php`)**: "Display mode" selector, and a repeatable "Media item" block
  (Add/Delete) with Media Type, Source (Upload/URL/Embed Code), Language (from
  `get_string_manager()->get_list_of_translations()`), and Description.
- **Automatic language detection + switcher**: `view.php` picks the item matching the learner's
  Moodle language (`$USER->lang`, with a same-family fallback like `pt_br` → `pt`, then the
  no-language item, then the first item). All items are rendered up front and hidden/shown with a
  small JS module (`amd/src/language_switcher.js`) — switching languages never reloads the page.
- **Progress tracking**: unchanged. All language items for one activity share the same
  `supervideo_view` tracking row, so switching languages doesn't reset progress, exactly as asked.
- **Course Page display mode**: `supervideo_cm_info_view()` renders the description + player +
  switcher directly on the course page when that mode is selected.
- **Backup/restore**: updated to include the new table and per-item files.
## Source types, as scoped
 
- **Upload File** — stored in the new `mod_supervideo/media` file area, keyed by media item id.
- **External URL** — auto-detects Youtube/Vimeo/Google Drive links (reusing the existing regex/player
  logic) and falls back to a direct `<video>`/`<audio>` element (with HLS via hls.js) for anything else.
- **Embed Code** — accepts a raw URL or a pasted `<iframe>` snippet; the iframe `src` is extracted and
  played through the existing "embed" player/tracking path. If no iframe/URL can be found, the code is
  rendered as-is (sanitised), without progress tracking, since it could be any third-party widget.
## Known limitations / things worth a follow-up pass
 
- Editing a **pre-existing legacy item** (Youtube/Vimeo/Drive/Pandavideo/Ottflix, from before this
  update) through the new form only lets you change type/language/description — its original source
  isn't editable there yet, to avoid re-implementing every provider's picker in the new UI. Deleting and
  re-adding it as an "External URL" item picks up the new flow.
- The Moodle mobile app renderer (`classes/output/mobile.php`) still serves the old single-language
  view and hasn't been updated.
- The old "distraction-free" full-page video mode was dropped from `view.php` for now, since it was
  built around a single video and not requested in the spec.
- CSS in `styles.css`/`styles.scss` was hand-appended (no Sass compiler available here) — worth a
  `grunt sass` pass before release to fold it in cleanly.
## Suggested test pass
 
1. Install as an update over the existing plugin; confirm the upgrade step runs and old activities
   still play correctly.
2. Create a new activity with 3+ language items (mix of upload/URL/embed), switch your Moodle
   language, confirm auto-selection, then use the dropdown to switch without a reload.
3. Set "Display mode" to Course Page and confirm the player shows on the course page.
4. Watch a video, switch languages, confirm the progress bar/completion doesn't reset.
