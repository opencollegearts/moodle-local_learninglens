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

namespace local_learninglens\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\user_preference_provider;
use core_privacy\local\request\writer;
use local_learninglens\manager;

/**
 * Privacy provider for presentation preferences only.
 *
 * @package    local_learninglens
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    user_preference_provider {

    /**
     * Describe stored data.
     *
     * @param collection $collection Privacy metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            manager::PREFERENCE,
            'privacy:metadata:preference:prefs'
        );
        return $collection;
    }

    /**
     * Export the widget preference for a user.
     *
     * @param int $userid The user to export preferences for.
     */
    public static function export_user_preferences(int $userid): void {
        $pref = get_user_preferences(manager::PREFERENCE, null, $userid);
        if ($pref === null || $pref === '') {
            return;
        }
        writer::export_user_preference(
            manager::COMPONENT,
            manager::PREFERENCE,
            manager::clean_preference($pref),
            get_string('privacy:metadata:preference:prefs', manager::COMPONENT)
        );
    }
}
