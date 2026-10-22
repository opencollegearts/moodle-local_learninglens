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

namespace local_learninglens;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_standard_footer_html_generation;
use core\hook\output\before_standard_head_html_generation;
use local_learninglens\output\hook_helper;

/**
 * Hook callbacks.
 *
 * @package    local_learninglens
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Load the boot script and AMD widget on HTML pages.
     *
     * @param before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        hook_helper::before_html_head($hook);
    }

    /**
     * Render widget markup and load the binder AMD in the footer.
     *
     * @param before_standard_footer_html_generation $hook
     */
    public static function before_standard_footer_html_generation(before_standard_footer_html_generation $hook): void {
        hook_helper::before_footer($hook);
    }
}
