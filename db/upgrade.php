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
 * Upgrade steps for local_customcleanurl.
 *
 * @package    local_customcleanurl
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function for local_customcleanurl.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool True on success.
 */
function xmldb_local_customcleanurl_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026072401) {
        $table = new xmldb_table('local_customcleanurl');

        $index = new xmldb_index('custom_url_cleanurl_type', XMLDB_INDEX_NOTUNIQUE, ['custom_url', 'cleanurl_type']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('default_url_cleanurl_type', XMLDB_INDEX_NOTUNIQUE, ['default_url', 'cleanurl_type']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2026072401, 'local', 'customcleanurl');
    }

    return true;
}
