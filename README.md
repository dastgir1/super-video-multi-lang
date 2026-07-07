# Super Video — Multi-Language Activity
### Testing Guide & User Documentation

**Plugin version:** 3.2.3 (build 2026070802)
**Audience:** Teachers/course editors and learners testing the new multi-language feature.

---

## 1. What's new

Super Video activities can now hold **several audio/video files, one per language**, instead of
just one video. Learners automatically see the version that matches their Moodle language, and can
switch to another language at any time without leaving the page. Watch progress and completion
tracking carry over no matter which language is playing.

You can also choose whether the activity opens on its own page (default) or plays directly on the
course page.

---

## 2. Feature list

| Feature | Description |
|---|---|
| Multiple language versions | Add as many media items as you like to one activity — e.g. English, Spanish, Japanese, Arabic. |
| Media sources | Each item can be an **uploaded file**, an **external URL** (direct link, Youtube, Vimeo, Google Drive), or **pasted embed code**. |
| Media type | Each item is tagged as **Video** or **Audio**. |
| Language tagging | Each item is tagged with a language from a list of ~30 common languages (not limited to language packs installed on the site). |
| Per-item description | Optional description text shown under the player for that language. |
| Automatic language detection | Learners see the item matching their own Moodle language automatically when they open the activity. |
| Manual language switcher | A dropdown lets learners change language at any time; the player updates instantly, no page reload. |
| Display mode | Choose **Activity Page** (learner opens the activity) or **Course Page** (player shows directly on the course page). |
| Progress tracking | Watch/listen progress, completion %, time spent, and activity completion are tracked per learner and are **not reset** when switching languages. |

---

## 3. Teacher workflow — creating an activity

1. In your course, turn editing on and choose **Add an activity or resource → Super Video**.
2. Enter a **Name** and, optionally, a **Description**.
3. Set **Display mode**:
   - **Activity Page** — learners click into the activity to watch (default).
   - **Course Page** — the player appears directly on the course page; no click-through needed.
4. Choose a **Player size** (16x9 or 4x3) and, if you want, **Play automatically** / **Show
   controls**.
5. Under **Media item 1**:
   - **Media type** — Video or Audio.
   - **Source** — Upload File, External URL, or Embed Code.
     - *Upload File*: use the file picker to upload the video/audio file.
     - *External URL*: paste a direct link, or a Youtube/Vimeo/Google Drive link.
     - *Embed Code*: paste a `<iframe>` embed snippet (or a bare URL) from a third-party host.
   - **Language** — pick the language this file is in (e.g. English).
   - **Description** — optional notes for this language version.
6. Click **Add media item** to add another language (e.g. Spanish), and repeat step 5 for each
   language you want to offer. Use the **Delete** button next to a row to remove it.
7. Set completion/grading options as needed, then **Save and display**.

> **Tip for this test round:** to reproduce the four-language example, add one media item each for
> **English, Spanish, Japanese, and Arabic**, selecting the matching language for every item.

---

## 4. Student workflow — watching a video

1. Open the course and click the Super Video activity (or, in Course Page mode, the player is
   already visible on the course page).
2. The activity automatically loads the version matching your Moodle language. If there's no exact
   match, it falls back to the item with no language set, then to the first item.
3. If a **Language** dropdown appears above the player, you can pick a different language at any
   time — the player swaps instantly, without reloading the page.
4. Watch/listen as normal. Your progress, completion percentage, and time spent are tracked
   automatically and continue to build up even if you switch languages partway through.
5. Once you've watched enough (per the activity's completion settings), the activity is marked
   complete on your course page / course completion report.

---

## 5. Suggested test checklist

- [ ] Create an activity with 4 media items (English/Spanish/Japanese/Arabic), each a different
      source type (try at least one Upload, one External URL, one Embed Code).
- [ ] Change your own profile language (Preferences → Preferred language) and confirm the activity
      auto-selects the matching version when opened.
- [ ] Use the Language dropdown to switch to another language and confirm the player updates
      without a page reload.
- [ ] Watch part of a video, switch languages, and confirm your progress bar / completion % does
      **not** reset.
- [ ] Set **Display mode = Course Page**, save, and confirm the player and description show
      directly on the course page (no click-through required).
- [ ] Set **Display mode = Activity Page** and confirm the course page shows only the activity link
      as usual.
- [ ] As a teacher, edit the activity, delete one media item, and add a new one — confirm changes
      save correctly.
- [ ] Confirm activity completion is awarded once the configured watch percentage is reached.

---

## 6. Known limitations (current build)

- The Moodle **mobile app** view of this activity has not been updated for multiple languages yet
  — it still shows the original single-video behaviour.
- Activities created with the **previous plugin version** (before multi-language support) were
  automatically converted into a single-language media item, so they keep working as before.
- The old "distraction-free / full page" video mode from the original plugin is not available in
  this build.

---

## 7. Reporting an issue

If something doesn't behave as expected, the most useful details to include are:

1. Whether it happened in **Activity Page** or **Course Page** display mode.
2. The **Source type** of the media item involved (Upload / URL / Embed Code) and its language.
3. Your browser and, if possible, any error shown in the browser console (F12 → Console tab) or
   Network tab (F12 → Network, filtered to the video request).
4. Steps to reproduce, starting from a fresh activity if possible.
