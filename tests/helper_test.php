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

namespace local_customcleanurl;

use local_customcleanurl\local\helper;

/**
 * Tests for the request-path resolver used by the public router.
 *
 * @package    local_customcleanurl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class helper_test extends \advanced_testcase {

    /**
     * Enable the feature before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        set_config('enable_customcleanurl', '1', 'local_customcleanurl');
        set_config('cleanurl_type', 'defineurl,courseurl,userurl', 'local_customcleanurl');
    }

    /**
     * The resolver must never hand the public router back to itself as an
     * include target (SEC-01): requesting route.php directly must not
     * resolve to route.php, which would cause it to require() itself.
     */
    public function test_check_requesturl_does_not_resolve_to_router_itself(): void {
        global $CFG;

        $routerpath = $CFG->dirroot . '/local/customcleanurl/route.php';
        $requesturl = $CFG->wwwroot . '/local/customcleanurl/route.php';

        $result = helper::check_requesturl($requesturl);

        $resolvedpath = !empty($result['filepath']) ? realpath($result['filepath']) : false;

        $this->assertNotEquals(
            realpath($routerpath),
            $resolvedpath,
            'The resolver must not return route.php as an includable target.'
        );
        $this->assertNotEquals('phppath', $result['urltype']);
    }

    /**
     * Normal PHP-path resolution (e.g. the plugin's own 404 handler) should
     * still work after the SEC-01 fix.
     */
    public function test_check_requesturl_still_resolves_other_php_paths(): void {
        global $CFG;

        $requesturl = $CFG->wwwroot . '/local/customcleanurl/404.php';

        $result = helper::check_requesturl($requesturl);

        $this->assertEquals('phppath', $result['urltype']);
        $this->assertEquals(
            realpath($CFG->dirroot . '/local/customcleanurl/404.php'),
            realpath($result['filepath'])
        );
    }
}
