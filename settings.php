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
 * Admin settings for local_learninglens.
 *
 * @package    local_learninglens
 * @copyright  2026 Paul Vincent <paulvincent@oca-uk.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_learninglens', get_string('pluginname', 'local_learninglens'));

    $settings->add(new admin_setting_heading(
        'local_learninglens/heading',
        get_string('settings:heading', 'local_learninglens'),
        get_string('settings:heading_desc', 'local_learninglens')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_learninglens/enable',
        get_string('settings:enable', 'local_learninglens'),
        get_string('settings:enable_desc', 'local_learninglens'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'local_learninglens/position',
        get_string('settings:position', 'local_learninglens'),
        get_string('settings:position_desc', 'local_learninglens'),
        'left',
        [
            'left' => get_string('settings:position_left', 'local_learninglens'),
            'right' => get_string('settings:position_right', 'local_learninglens'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'local_learninglens/statementurl',
        get_string('settings:statementurl', 'local_learninglens'),
        get_string('settings:statementurl_desc', 'local_learninglens'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_heading(
        'local_learninglens/featuresheading',
        get_string('settings:featuresheading', 'local_learninglens'),
        get_string('settings:featuresheading_desc', 'local_learninglens')
    ));

    foreach ([
        'contrast', 'dyslexia', 'spacing', 'links', 'textsize',
        'pauseanim', 'hideimages', 'readingguide', 'readingmask', 'readaloud',
    ] as $feature) {
        $settings->add(new admin_setting_configcheckbox(
            'local_learninglens/enable' . $feature,
            get_string('settings:enable' . $feature, 'local_learninglens'),
            get_string('settings:enable' . $feature . '_desc', 'local_learninglens'),
            1
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
