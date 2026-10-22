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
 * Callbacks for local_learninglens.
 *
 * @package    local_learninglens
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_learninglens\manager;

/**
 * Register the user preference used to persist widget settings.
 *
 * @return array Preference definitions for the core preference API.
 */
function local_learninglens_user_preferences(): array {
    return [
        manager::PREFERENCE => [
            'type' => PARAM_RAW,
            'null' => NULL_NOT_ALLOWED,
            'default' => '{}',
            'cleancallback' => [manager::class, 'clean_preference'],
            'permissioncallback' => [manager::class, 'can_edit_preference'],
        ],
    ];
}

// Intentionally no local_learninglens_before_standard_html_head().
// On Moodle 4.4+/Open LMS, a legacy callback triggers debugging() migration
// notices that Whoops converts into fatal ErrorExceptions and breaks all JS
// (nav clicks only append "#"). Use db/hooks.php only.
