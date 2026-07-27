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
 * Public routing endpoint for custom clean URLs.
 *
 * @package    local_customcleanurl
 * @copyright  2025 https://santoshmagar.com.np/
 * @author     santoshtmp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- This endpoint is intentionally public.
require_once(__DIR__ . '/../../config.php');

$requesturi = $_SERVER['REQUEST_URI'];

$responsedata = \local_customcleanurl\local\helper::check_requesturl($requesturi);

if (!empty($responsedata['status'])) {
    $urltype = $responsedata['urltype'] ?? '';

    if ($urltype === 'customcleanurl_routetest') {
        echo json_encode(['status' => true]);
        exit;
    }

    $moodleurl = $responsedata['moodleurl'] ?? '';
    $filepath = $responsedata['filepath'] ?? '';
    $param = $responsedata['param'] ?? [];
    $redirecturltype = [
        'mod_page_view',
        'mod_resource_view',
        'mod_folder_view',
        'mod_url_view',
        'mod_url_index',
        'mod_imscp_view',
        'mod_bigbluebuttonbn_view',
    ];
    if (!empty($moodleurl) && in_array($urltype, $redirecturltype, true)) {
        redirect($moodleurl);
        exit;
    }

    if ($urltype === '404') {
        header('HTTP/1.0 404 Not Found');
        http_response_code(404);
        $_SERVER['REDIRECT_STATUS'] = '404';
        chdir(dirname($filepath));
        require($filepath);
        exit;
    }

    foreach ($param as $key => $value) {
        $_GET[$key] = $value;
    }
    if (is_file($filepath) && realpath($filepath) !== realpath(__FILE__)) {
        chdir(dirname($filepath));
        require($filepath);
        exit;
    }
}
// Output message content.
$context = \context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($requesturi);
$PAGE->set_pagelayout('standard');
echo $OUTPUT->header();
echo $responsedata['message'] ?? '';
echo $OUTPUT->footer();
