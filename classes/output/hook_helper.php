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

namespace local_learninglens\output;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_standard_footer_html_generation;
use core\hook\output\before_standard_head_html_generation;
use local_learninglens\manager;

/**
 * Head/footer helpers for boot script, markup, and AMD.
 *
 * @package    local_learninglens
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_helper {
    /** @var bool Prevent double init if a hook fires more than once. */
    private static $headinitialised = false;

    /** @var bool Prevent double footer markup. */
    private static $footerinitialised = false;

    /**
     * Whether the widget should run on this page.
     *
     * @return bool
     */
    public static function should_display(): bool {
        global $PAGE;

        if (!manager::plugin_enabled()) {
            return false;
        }
        if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
            return false;
        }
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            return false;
        }
        if (defined('WS_SERVER') && WS_SERVER) {
            return false;
        }
        if (!isset($PAGE) || !$PAGE) {
            return false;
        }
        $context = $PAGE->context ?? null;
        if (!$context || !has_capability('local/learninglens:use', $context)) {
            return false;
        }

        $layout = (string) $PAGE->pagelayout;
        if (in_array($layout, ['embedded', 'maintenance', 'redirect'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Tiny first-paint boot script only. Do not js_call_amd from head —
     * requiring core/templates here makes requirejs.php emit a multi-MB bundle
     * in <head> and leaves Snap/Boost stuck on M.util.js_pending ('ghosted' nav).
     *
     * @param before_standard_head_html_generation $hook
     * @return void
     */
    public static function before_html_head(before_standard_head_html_generation $hook): void {
        try {
            if (self::$headinitialised) {
                return;
            }
            if (!self::should_display()) {
                return;
            }

            $cfg = manager::get_config_for_js();
            $json = json_encode(
                $cfg,
                JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            );
            if ($json === false) {
                return;
            }

            $hook->add_html(
                '<script id="local-learninglens-boot">' .
                'window.local_learninglens_cfg=' . $json . ';' .
                manager::boot_javascript() .
                '</script>'
            );
            self::$headinitialised = true;
        } catch (\Throwable $e) {
            // Do not call debugging() — Open LMS/Whoops promotes notices to fatals.
            error_log('local_learninglens hook_helper head: ' . $e->getMessage());
        }
    }

    /**
     * Render the launcher HTML and load a dependency-free AMD binder in the footer.
     *
     * @param before_standard_footer_html_generation $hook
     * @return void
     */
    public static function before_footer(before_standard_footer_html_generation $hook): void {
        try {
            if (self::$footerinitialised) {
                return;
            }
            if (!self::should_display()) {
                return;
            }

            $cfg = manager::get_config_for_js();
            $html = $hook->renderer->render_from_template(
                'local_learninglens/widget',
                manager::template_context($cfg)
            );
            if (is_string($html) && $html !== '') {
                $hook->add_html($html);
            }

            // Do not js_call_amd: that registers js_pending and, in production,
            // dumps every non-lazy AMD file into core/first.js. An anonymous
            // factory in that combo aborts core/first and ghosts navigation.
            // widget-lazy is fetched on demand after load, with no pending token.
            $hook->add_html(
                '<script>' .
                '(function(){function boot(){try{' .
                'if(typeof window.require!=="function"){return;}' .
                'window.require(["local_learninglens/widget-lazy"],function(m){' .
                'try{m&&m.init&&m.init();}catch(e){}});}catch(e){}}' .
                'if(document.readyState==="complete"){boot();}' .
                'else{window.addEventListener("load",boot);}})();' .
                '</script>'
            );
            self::$footerinitialised = true;
        } catch (\Throwable $e) {
            error_log('local_learninglens hook_helper footer: ' . $e->getMessage());
        }
    }
}
