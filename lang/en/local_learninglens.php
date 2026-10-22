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
 * English language strings for local_learninglens.
 *
 * @package    local_learninglens
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'OCA Learning Lens';
$string['learninglens:use'] = 'Use Learning Lens';

$string['privacy:metadata'] = 'Learning Lens stores presentation choices (such as contrast, text size, and read-aloud) as a user preference. It does not store personal or health information and does not send data to third-party services.';
$string['privacy:metadata:preference:prefs'] = 'Your chosen presentation adjustments (for example contrast, text size, reading guide, click-to-speak, and speech speed). These are display settings, not a record of disability.';

$string['settings:heading'] = 'Learning Lens settings';
$string['settings:heading_desc'] = 'Learning Lens offers on-page presentation adjustments. It does not remediate content, does not load third-party scripts, and does not claim WCAG or ADA compliance.';
$string['settings:enable'] = 'Enable Learning Lens';
$string['settings:enable_desc'] = 'Master switch. When off, the widget is not shown on any page.';
$string['settings:position'] = 'Launcher position';
$string['settings:position_desc'] = 'Corner of the viewport for the floating button.';
$string['settings:position_left'] = 'Bottom left';
$string['settings:position_right'] = 'Bottom right';
$string['settings:statementurl'] = 'Accessibility statement URL';
$string['settings:statementurl_desc'] = 'Optional link shown in the widget footer. Leave blank to hide it. Use a full http or https URL.';
$string['settings:featuresheading'] = 'Available adjustments';
$string['settings:featuresheading_desc'] = 'Uncheck a feature to hide it from the widget. Existing user choices for a hidden feature are ignored.';
$string['settings:enablecontrast'] = 'Contrast options';
$string['settings:enablecontrast_desc'] = 'Cycle dark high contrast, light high contrast, invert, and extra contrast.';
$string['settings:enabledyslexia'] = 'Dyslexia-friendly font';
$string['settings:enabledyslexia_desc'] = 'Apply the bundled OpenDyslexic font to page text.';
$string['settings:enablespacing'] = 'Text spacing';
$string['settings:enablespacing_desc'] = 'Increase line height, letter spacing, word spacing, and paragraph spacing (WCAG 1.4.12 values).';
$string['settings:enablelinks'] = 'Highlight links';
$string['settings:enablelinks_desc'] = 'Give links a high-visibility underline and background.';
$string['settings:enabletextsize'] = 'Bigger text';
$string['settings:enabletextsize_desc'] = 'Cycle page text size: 100%, 125%, 150%, 200%.';
$string['settings:enablepauseanim'] = 'Pause animations';
$string['settings:enablepauseanim_desc'] = 'Stop CSS animations and transitions, and pause playing media.';
$string['settings:enablehideimages'] = 'Hide images';
$string['settings:enablehideimages_desc'] = 'Hide images, video, and canvas, and show available alternative text.';
$string['settings:enablereadingguide'] = 'Reading guide';
$string['settings:enablereadingguide_desc'] = 'A horizontal highlight bar that follows the pointer and keyboard focus.';
$string['settings:enablereadingmask'] = 'Reading mask';
$string['settings:enablereadingmask_desc'] = 'Dim the page except for a reading strip that follows the pointer and keyboard focus.';
$string['settings:enablereadaloud'] = 'Read aloud';
$string['settings:enablereadaloud_desc'] = 'Read the page or selected text using the browser\'s built-in speech (no third-party service). Learners can also click a paragraph to start from there. Speech speed is saved with their other adjustments.';

$string['widget:launcher'] = 'Learning Lens';
$string['widget:paneltitle'] = 'Learning Lens';
$string['widget:shortcut'] = 'Keyboard shortcut: Alt+Shift+A';
$string['widget:close'] = 'Close';
$string['widget:reset'] = 'Reset all';
$string['widget:statement'] = 'Accessibility statement';

$string['feature:contrast'] = 'Contrast';
$string['feature:contrast_dark'] = 'Dark';
$string['feature:contrast_light'] = 'Light';
$string['feature:contrast_invert'] = 'Invert';
$string['feature:contrast_extra'] = 'Extra';
$string['feature:dyslexia'] = 'Dyslexia friendly';
$string['feature:spacing'] = 'Text spacing';
$string['feature:links'] = 'Highlight links';
$string['feature:textsize'] = 'Bigger text';
$string['feature:pauseanim'] = 'Pause animations';
$string['feature:hideimages'] = 'Hide images';
$string['feature:readingguide'] = 'Reading guide';
$string['feature:readingmask'] = 'Reading mask';
$string['feature:readaloud'] = 'Read aloud';
$string['feature:readaloud_playing'] = 'Playing';
$string['feature:readaloud_paused'] = 'Paused';
$string['feature:readaloud_on'] = 'Click text';
$string['feature:readaloud_unavailable'] = 'Not available';
$string['feature:speechrate'] = 'Speech speed';
