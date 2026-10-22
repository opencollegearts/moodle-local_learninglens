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
 * Install defaults for local_learninglens.
 *
 * @package    local_learninglens
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Set default config values on install.
 *
 * @return void
 */
function xmldb_local_learninglens_install(): void {
    set_config('enable', 1, 'local_learninglens');
    set_config('position', 'left', 'local_learninglens');
    set_config('statementurl', '', 'local_learninglens');
    foreach ([
        'contrast', 'dyslexia', 'spacing', 'links', 'textsize',
        'pauseanim', 'hideimages', 'readingguide', 'readingmask', 'readaloud',
    ] as $feature) {
        set_config('enable' . $feature, 1, 'local_learninglens');
    }
}
