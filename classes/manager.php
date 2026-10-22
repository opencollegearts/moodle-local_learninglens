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
 * Config, preference sanitization, and JS payload helpers.
 *
 * @package    local_learninglens
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** Frankenstyle component name. */
    public const COMPONENT = 'local_learninglens';

    /** User preference name (JSON whitelist). */
    public const PREFERENCE = 'local_learninglens_prefs';

    /** localStorage key (must match the AMD boot script). */
    public const STORAGE_KEY = 'local_learninglens';

    /** Maximum JSON payload accepted when sanitizing prefs. */
    public const PREFS_MAX_LENGTH = 4000;

    /** Contrast cycle values stored in prefs (empty/default is omitted). */
    public const CONTRAST_VALUES = ['dark', 'light', 'invert', 'extra'];

    /** Text size percentages other than the default 100. */
    public const TEXT_SIZES = [125, 150, 200];

    /** Boolean feature keys. */
    public const BOOL_FEATURES = [
        'dyslexia',
        'spacing',
        'links',
        'pauseanim',
        'hideimages',
        'readingguide',
        'readingmask',
        'readaloud',
    ];

    /** Speech rate percentages other than the default 100. */
    public const SPEECH_RATES = [75, 125, 150];

    /** All toggleable features, including cycling ones. */
    public const FEATURES = [
        'contrast',
        'dyslexia',
        'spacing',
        'links',
        'textsize',
        'pauseanim',
        'hideimages',
        'readingguide',
        'readingmask',
        'readaloud',
    ];

    /**
     * Whether the plugin is enabled site-wide.
     *
     * Missing config is treated as enabled (settings.php default is on).
     *
     * @return bool
     */
    public static function plugin_enabled(): bool {
        return get_config(self::COMPONENT, 'enable') !== '0';
    }

    /**
     * Whether a feature is enabled by the administrator.
     *
     * @param string $feature Feature id from FEATURES.
     * @return bool
     */
    public static function feature_enabled(string $feature): bool {
        if (!in_array($feature, self::FEATURES, true)) {
            return false;
        }
        return get_config(self::COMPONENT, 'enable' . $feature) !== '0';
    }

    /**
     * Feature ids the administrator has left enabled.
     *
     * @return string[]
     */
    public static function enabled_features(): array {
        $enabled = [];
        foreach (self::FEATURES as $feature) {
            if (self::feature_enabled($feature)) {
                $enabled[] = $feature;
            }
        }
        return $enabled;
    }

    /**
     * Drop unknown keys/values. Never persist raw CSS.
     *
     * @param mixed $prefs Array, JSON string, or anything else.
     * @return array Sanitized associative array of allowed prefs.
     */
    public static function sanitize_prefs($prefs): array {
        if (is_string($prefs)) {
            if (strlen($prefs) > self::PREFS_MAX_LENGTH) {
                return [];
            }
            $prefs = json_decode($prefs, true);
        }
        if (!is_array($prefs)) {
            return [];
        }

        $out = [];

        if (isset($prefs['contrast']) && is_string($prefs['contrast'])
                && in_array($prefs['contrast'], self::CONTRAST_VALUES, true)) {
            $out['contrast'] = $prefs['contrast'];
        }

        foreach (self::BOOL_FEATURES as $key) {
            if (!array_key_exists($key, $prefs)) {
                continue;
            }
            $value = $prefs[$key];
            if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
                $out[$key] = true;
            }
        }

        if (array_key_exists('textsize', $prefs)) {
            $size = (int) $prefs['textsize'];
            if (in_array($size, self::TEXT_SIZES, true)) {
                $out['textsize'] = $size;
            }
        }

        if (array_key_exists('speechrate', $prefs)) {
            $rate = (int) $prefs['speechrate'];
            if (in_array($rate, self::SPEECH_RATES, true)) {
                $out['speechrate'] = $rate;
            }
        }

        return $out;
    }

    /**
     * html class names implied by sanitized prefs.
     *
     * @param array $prefs Already-sanitized or raw prefs.
     * @return string[]
     */
    public static function html_classes(array $prefs): array {
        $prefs = self::sanitize_prefs($prefs);
        $classes = [];
        $prefix = 'local-learninglens-';
        if (!empty($prefs['contrast'])) {
            $classes[] = $prefix . 'contrast-' . $prefs['contrast'];
        }
        if (!empty($prefs['dyslexia'])) {
            $classes[] = $prefix . 'dyslexia';
        }
        if (!empty($prefs['spacing'])) {
            $classes[] = $prefix . 'spacing';
        }
        if (!empty($prefs['links'])) {
            $classes[] = $prefix . 'links';
        }
        if (!empty($prefs['pauseanim'])) {
            $classes[] = $prefix . 'pause-anim';
        }
        if (!empty($prefs['hideimages'])) {
            $classes[] = $prefix . 'hide-images';
        }
        if (!empty($prefs['readingguide'])) {
            $classes[] = $prefix . 'reading-guide';
        }
        if (!empty($prefs['readingmask'])) {
            $classes[] = $prefix . 'reading-mask';
        }
        if (!empty($prefs['textsize'])) {
            $classes[] = $prefix . 'text-' . (int) $prefs['textsize'];
        }
        if (!empty($prefs['readaloud'])) {
            $classes[] = $prefix . 'readaloud';
        }
        return $classes;
    }

    /**
     * Current user's stored prefs (empty for guests / missing).
     *
     * @return array
     */
    public static function get_user_prefs(): array {
        if (!isloggedin() || isguestuser()) {
            return [];
        }
        $raw = get_user_preferences(self::PREFERENCE, '{}');
        return self::sanitize_prefs($raw);
    }

    /**
     * Sanitise a raw preference value before Moodle stores it.
     *
     * @param mixed $value JSON string or array from the preference API.
     * @return string JSON whitelist only.
     */
    public static function clean_preference($value): string {
        $json = json_encode(self::sanitize_prefs($value), JSON_UNESCAPED_SLASHES);
        return ($json === false) ? '{}' : $json;
    }

    /**
     * Whether the current user may update this preference for $user.
     *
     * @param \stdClass $user The user whose preference would be changed.
     * @param string $preferencename Preference name being written.
     * @return bool
     */
    public static function can_edit_preference($user, $preferencename): bool {
        global $USER;
        if ($preferencename !== self::PREFERENCE) {
            return false;
        }
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        if (empty($USER->id) || empty($user->id)) {
            return false;
        }
        return (int) $USER->id === (int) $user->id;
    }

    /**
     * Single payload passed to the AMD module and the inline boot script.
     *
     * @return array
     */
    public static function get_config_for_js(): array {
        $features = [];
        foreach (self::FEATURES as $feature) {
            $features[$feature] = self::feature_enabled($feature);
        }

        $position = get_config(self::COMPONENT, 'position');
        if ($position !== 'right') {
            $position = 'left';
        }

        $statementurl = trim((string) get_config(self::COMPONENT, 'statementurl'));
        $statementurl = clean_param($statementurl, PARAM_URL);
        if ($statementurl !== '' && !preg_match('#^https?://#i', $statementurl)) {
            $statementurl = '';
        }

        $loggedin = isloggedin() && !isguestuser();

        return [
            'loggedin' => $loggedin,
            'position' => $position,
            'statementurl' => $statementurl,
            'features' => $features,
            'prefs' => (object) self::get_user_prefs(),
            'storagekey' => self::STORAGE_KEY,
            'strings' => self::js_strings(),
        ];
    }

    /**
     * Mustache context for the launcher/panel markup.
     *
     * @param array|null $cfg From get_config_for_js().
     * @return array
     */
    public static function template_context(?array $cfg = null): array {
        $cfg = $cfg ?? self::get_config_for_js();
        $features = $cfg['features'] ?? [];
        return [
            'position' => (($cfg['position'] ?? 'left') === 'right') ? 'right' : 'left',
            'statementurl' => $cfg['statementurl'] ?? '',
            'hasstatement' => !empty($cfg['statementurl']),
            'showcontrast' => !empty($features['contrast']),
            'showdyslexia' => !empty($features['dyslexia']),
            'showspacing' => !empty($features['spacing']),
            'showlinks' => !empty($features['links']),
            'showtextsize' => !empty($features['textsize']),
            'showpauseanim' => !empty($features['pauseanim']),
            'showhideimages' => !empty($features['hideimages']),
            'showreadingguide' => !empty($features['readingguide']),
            'showreadingmask' => !empty($features['readingmask']),
            'showreadaloud' => !empty($features['readaloud']),
            'strings' => $cfg['strings'] ?? self::js_strings(),
        ];
    }

    /**
     * Language strings the widget needs without an extra round trip.
     *
     * @return array
     */
    public static function js_strings(): array {
        $component = self::COMPONENT;
        return [
            'launcher' => get_string('widget:launcher', $component),
            'paneltitle' => get_string('widget:paneltitle', $component),
            'shortcut' => get_string('widget:shortcut', $component),
            'close' => get_string('widget:close', $component),
            'reset' => get_string('widget:reset', $component),
            'statement' => get_string('widget:statement', $component),
            'contrast' => get_string('feature:contrast', $component),
            'contrast_dark' => get_string('feature:contrast_dark', $component),
            'contrast_light' => get_string('feature:contrast_light', $component),
            'contrast_invert' => get_string('feature:contrast_invert', $component),
            'contrast_extra' => get_string('feature:contrast_extra', $component),
            'dyslexia' => get_string('feature:dyslexia', $component),
            'spacing' => get_string('feature:spacing', $component),
            'links' => get_string('feature:links', $component),
            'textsize' => get_string('feature:textsize', $component),
            'pauseanim' => get_string('feature:pauseanim', $component),
            'hideimages' => get_string('feature:hideimages', $component),
            'readingguide' => get_string('feature:readingguide', $component),
            'readingmask' => get_string('feature:readingmask', $component),
            'readaloud' => get_string('feature:readaloud', $component),
            'readaloud_playing' => get_string('feature:readaloud_playing', $component),
            'readaloud_paused' => get_string('feature:readaloud_paused', $component),
            'readaloud_on' => get_string('feature:readaloud_on', $component),
            'readaloud_unavailable' => get_string('feature:readaloud_unavailable', $component),
            'speechrate' => get_string('feature:speechrate', $component),
        ];
    }

    /**
     * Synchronous head script that applies html classes before first paint.
     *
     * Keep this vanilla ES5. It must not throw.
     *
     * @return string
     */
    public static function boot_javascript(): string {
        $key = self::STORAGE_KEY;
        return <<<JS
(function(){
try{
var C=window.local_learninglens_cfg||{};
var local=null;
try{local=JSON.parse(localStorage.getItem('{$key}')||'null');}catch(e){}
if(!local){try{local=JSON.parse(localStorage.getItem('local_a11ywidget')||'null');}catch(e){}}
var prefs=C.loggedin?(C.prefs||local||{}):(local||C.prefs||{});
var h=document.documentElement;
var drop=new RegExp('^(local-learninglens-|local-a11ywidget-|a11y-contrast-|' +
'a11y-dyslexia|a11y-spacing|a11y-links|a11y-text-|a11y-pause-anim|' +
'a11y-hide-images|a11y-reading-guide|a11y-reading-mask|a11y-readaloud)');
h.className=(h.className||'').split(/\\s+/).filter(function(c){return !drop.test(c);}).join(' ');
if(!prefs){return;}
var p='local-learninglens-';
var okC={dark:1,light:1,invert:1,extra:1};
var on=function(v){return v===true||v===1||v==='1';};
if(okC[prefs.contrast]){h.classList.add(p+'contrast-'+prefs.contrast);}
if(on(prefs.dyslexia)){h.classList.add(p+'dyslexia');}
if(on(prefs.spacing)){h.classList.add(p+'spacing');}
if(on(prefs.links)){h.classList.add(p+'links');}
var ts=parseInt(prefs.textsize,10);
if(ts===125||ts===150||ts===200){h.classList.add(p+'text-'+ts);}
if(on(prefs.pauseanim)){h.classList.add(p+'pause-anim');}
if(on(prefs.hideimages)){h.classList.add(p+'hide-images');}
if(on(prefs.readingguide)){h.classList.add(p+'reading-guide');}
if(on(prefs.readingmask)){h.classList.add(p+'reading-mask');}
if(on(prefs.readaloud)){h.classList.add(p+'readaloud');}
}catch(e){}
})();
JS;
    }
}
