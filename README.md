# OCA Learning Lens (`local_learninglens`)

**Short description:** Learning Lens is a floating on-page menu so learners can adjust contrast, text, reading aids, and read-aloud on the current Moodle page.

**Full description:** OCA Learning Lens (Learning Lens for short) is a local plugin for Moodle 4.5+. It adds a button to Moodle pages so learners can change how the live page looks and behaves without leaving the activity:

- Contrast (dark, light, invert, extra)
- Dyslexia-friendly font (bundled OpenDyslexic)
- Text spacing (WCAG 1.4.12 values)
- Highlight links
- Bigger text (125% / 150% / 200%)
- Pause animations
- Hide images (shows available alternative text)
- Reading guide and reading mask
- Read aloud using the browser's built-in speech, with saved speech speed

This is **not** a compliance overlay. It does not load remote scripts, does not rewrite page HTML to "fix" WCAG issues, and does not claim ADA or WCAG conformance. Read aloud uses the device speech engine only; nothing is sent to a third-party service.

## Requirements

- Moodle 4.5 or 5.0 (`$plugin->supported = [405, 500]`)
- No Composer, Node, or other build step is required to install
- No extra plugins or paid subscriptions

## Install

The plugin directory **must** be named `learninglens` (not the GitHub repository name).

**From git:**

```bash
cd /path/to/moodle/local
git clone https://github.com/opencollegearts/moodle-local_learninglens.git learninglens
```

**From ZIP:** unzip so you have `moodle/local/learninglens/version.php`.

Then:

1. Visit **Site administration → Notifications** and complete the install.
2. Configure **Site administration → Plugins → Local plugins → OCA Learning Lens**.

The ZIP from Moodle Marketplace can also be installed via **Site administration → Plugins → Install plugins**.

## Using Learning Lens

- Click the circular button (bottom-left by default) or press **Alt+Shift+A**.
- Toggle or cycle a card. Changes apply immediately and are remembered.
- **Read aloud** plays selected text, or the main page content. Click again to pause or resume. While it is on, click a paragraph to start from there. Press Escape to stop.
- **Reset all** clears adjustments and stops speech.
- Guests: choices stay in this browser (`localStorage`).
- Signed-in users: choices also sync as the Moodle user preference `local_learninglens_prefs`.
- The `local/learninglens:use` capability (allowed for users and guests by default) can hide Learning Lens for a role.

## Admin settings

| Setting | Purpose |
|---------|---------|
| Enable Learning Lens | Site-wide master switch |
| Launcher position | Bottom left or bottom right |
| Accessibility statement URL | Optional footer link (full `http`/`https` URL only) |
| Per-feature checkboxes | Hide individual adjustments |

## Complementary to Learning Prism

There is no dependency. If you also use [Learning Prism](https://github.com/opencollegearts/moodle-local_learningprism) (`local_learningprism`), that plugin changes **downloadable content** (DOCX, PDF, EPUB, TTS files). Learning Lens changes **live presentation** on the current page.

## Privacy

- No third-party services and no analytics.
- Signed-in users store a small JSON whitelist of presentation flags. This is a display preference, not a disability record.
- Guests store the same flags only in the browser.
- OpenDyslexic is bundled locally (SIL Open Font License 1.1). See `fonts/OFL.txt`.

## Licence

GNU GPL v3 or later (`LICENSE` and the file headers). OpenDyslexic is OFL 1.1 (`thirdpartylibs.xml`).

## Source, issues, and docs

- Source: https://github.com/opencollegearts/moodle-local_learninglens
- Issue tracker: https://github.com/opencollegearts/moodle-local_learninglens/issues
- Documentation: this README

The repository root **is** the plugin root (`version.php` at the top), following Moodle’s `moodle-{plugintype}_{pluginname}` naming.

## Development

```bash
# From Moodle root, after install:
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit local/learninglens/tests

# Rebuild AMD after editing amd/src/widget-lazy.js:
npx grunt amd --root=local/learninglens
```

The binder is `amd/src/widget-lazy.js`. The `-lazy` suffix keeps it out of Moodle's production `core/first.js` combo.

## Architecture (short)

A head hook injects a tiny boot script (applies saved `html` classes before first paint). Footer PHP renders the Mustache UI and loads AMD `local_learninglens/widget-lazy` after `window.load` (no `js_call_amd` / `js_pending`). Preferences are sanitised against a whitelist on write and on read. Page-level presentation classes are prefixed `local-learninglens-` so they cannot collide with other plugins.
