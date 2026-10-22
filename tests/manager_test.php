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

/**
 * Manager sanitization and feature-flag tests.
 *
 * @package    local_learninglens
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_learninglens\manager
 */
class manager_test extends \advanced_testcase {

    /**
     * Unknown keys and raw CSS are dropped.
     */
    public function test_sanitize_prefs_drops_unknown_keys_and_css(): void {
        $raw = [
            'contrast' => 'dark',
            'dyslexia' => true,
            'evil' => '<script>alert(1)</script>',
            'css' => 'body{display:none}',
            'contrast-extra' => 'invert',
        ];
        $out = manager::sanitize_prefs($raw);
        $this->assertSame(['contrast' => 'dark', 'dyslexia' => true], $out);
        $this->assertArrayNotHasKey('evil', $out);
        $this->assertArrayNotHasKey('css', $out);
    }

    /**
     * Invalid enums, sizes, and rates are dropped.
     */
    public function test_sanitize_prefs_rejects_invalid_enums_and_sizes(): void {
        $out = manager::sanitize_prefs([
            'contrast' => 'javascript:alert(1)',
            'textsize' => 133,
            'dyslexia' => 'nope',
            'spacing' => 0,
            'links' => false,
            'speechrate' => 90,
            'readaloud' => 'nope',
        ]);
        $this->assertSame([], $out);
    }

    /**
     * JSON strings and bool-like values are accepted.
     */
    public function test_sanitize_prefs_accepts_json_and_bool_coercion(): void {
        $json = json_encode([
            'contrast' => 'invert',
            'textsize' => 150,
            'hideimages' => '1',
            'readingguide' => 1,
            'pauseanim' => true,
            'readaloud' => true,
            'speechrate' => 125,
        ]);
        $out = manager::sanitize_prefs($json);
        $this->assertSame('invert', $out['contrast']);
        $this->assertSame(150, $out['textsize']);
        $this->assertTrue($out['hideimages']);
        $this->assertTrue($out['readingguide']);
        $this->assertTrue($out['pauseanim']);
        $this->assertTrue($out['readaloud']);
        $this->assertSame(125, $out['speechrate']);
    }

    /**
     * Preference cleaner returns whitelist JSON only.
     */
    public function test_clean_preference_returns_whitelisted_json_only(): void {
        $raw = json_encode([
            'contrast' => 'dark',
            'evil' => '<script>',
            'speechrate' => 125,
        ]);
        $out = json_decode(manager::clean_preference($raw), true);
        $this->assertSame(['contrast' => 'dark', 'speechrate' => 125], $out);
    }

    /**
     * Oversized JSON payloads are rejected.
     */
    public function test_sanitize_prefs_rejects_oversized_json(): void {
        $huge = '{"dyslexia":true,"pad":"' . str_repeat('x', manager::PREFS_MAX_LENGTH) . '"}';
        $this->assertSame([], manager::sanitize_prefs($huge));
    }

    /**
     * HTML classes stay on the namespaced whitelist.
     */
    public function test_html_classes_match_whitelist(): void {
        $classes = manager::html_classes([
            'contrast' => 'extra',
            'dyslexia' => true,
            'textsize' => 200,
            'readingmask' => true,
            'readaloud' => true,
            'unknown' => true,
        ]);
        sort($classes);
        $this->assertSame([
            'local-learninglens-contrast-extra',
            'local-learninglens-dyslexia',
            'local-learninglens-readaloud',
            'local-learninglens-reading-mask',
            'local-learninglens-text-200',
        ], $classes);
    }

    /**
     * Default values do not emit HTML classes.
     */
    public function test_html_classes_omit_defaults(): void {
        $this->assertSame([], manager::html_classes([
            'contrast' => '',
            'textsize' => 100,
            'dyslexia' => false,
        ]));
    }

    /**
     * Admin feature flags control enabled_features().
     */
    public function test_enabled_features_respects_config(): void {
        $this->resetAfterTest();
        set_config('enable', 1, 'local_learninglens');
        set_config('enablecontrast', 1, 'local_learninglens');
        set_config('enabledyslexia', 0, 'local_learninglens');
        set_config('enablespacing', 1, 'local_learninglens');
        set_config('enablelinks', 1, 'local_learninglens');
        set_config('enabletextsize', 1, 'local_learninglens');
        set_config('enablepauseanim', 1, 'local_learninglens');
        set_config('enablehideimages', 1, 'local_learninglens');
        set_config('enablereadingguide', 0, 'local_learninglens');
        set_config('enablereadingmask', 1, 'local_learninglens');
        set_config('enablereadaloud', 1, 'local_learninglens');

        $this->assertTrue(manager::plugin_enabled());
        $enabled = manager::enabled_features();
        $this->assertContains('contrast', $enabled);
        $this->assertNotContains('dyslexia', $enabled);
        $this->assertNotContains('readingguide', $enabled);
        $this->assertContains('readingmask', $enabled);
        $this->assertContains('readaloud', $enabled);

        set_config('enable', 0, 'local_learninglens');
        $this->assertFalse(manager::plugin_enabled());
    }

    /**
     * Statement URL must be http or https.
     */
    public function test_statementurl_allows_http_only(): void {
        $this->resetAfterTest();
        set_config('statementurl', 'javascript:alert(1)', 'local_learninglens');
        $cfg = manager::get_config_for_js();
        $this->assertSame('', $cfg['statementurl']);

        set_config('statementurl', 'https://example.edu/accessibility', 'local_learninglens');
        $cfg = manager::get_config_for_js();
        $this->assertSame('https://example.edu/accessibility', $cfg['statementurl']);
    }
}
