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
 *
 * @package    local_customcleanurl
 * @copyright  2025 https://santoshmagar.com.np/
 * @author     santoshtmp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace local_customcleanurl;

use local_customcleanurl\local\helper;
use moodle_url;

/**
 * Clean url rewriter
 *
 * @package    local_customcleanurl
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customcleanurl implements \core\output\url_rewriter {
    /**
     * Rewrite moodle_urls into another form.
     * By using customcleanurl if not possible.
     *
     * @param \moodle_url $url a url to potentially rewrite
     * @return \moodle_url Returns a new, or the original, moodle_url;
     */
    public static function url_rewrite(moodle_url $url) {
        global $CFG;
        if (empty($CFG->upgraderunning)) {
            $cleanurl = new \local_customcleanurl\local\clean_url($url);
            return $cleanurl->cleanedurl;
        }
        return $url;
    }

    /**
     * Gives a url rewriting plugin a chance to rewrite the current page url
     * avoiding redirects and improving performance.
     *
     * @return void
     */
    public static function html_head_setup() {
        if (helper::is_enable_customcleanurl()) {
            global $PAGE;
            $cleanurl = $PAGE->url->out(false);
            $moodleurl = $PAGE->url->raw_out(false);

            $output = '';

            if ($moodleurl != $cleanurl) {
                // This page URL could have been cleaned up, so do it!
                $output .= self::get_base_href($moodleurl);
                $output .= self::get_replacestate_script($cleanurl);
                $output .= self::get_anchor_fix_javascript($cleanurl);
                $output .= self::get_link_canonical();
                // Set a custom HTTP header to store clean URL for server logs or analytics.
                if (!headers_sent()) {
                    header('X-Clean-URL: ' . $cleanurl);
                }
            }

            return $output;
        }
    }

    /**
     * Set base url
     *
     * @param string $uncleanedurl string
     * @return string
     */
    private static function get_base_href($uncleanedurl) {
        return "<base href=\"{$uncleanedurl}\">\n";
    }

    /**
     * If we have just loaded a legacy url AND we can clean it, instead of
     * cleaning the url, caching it, and waiting for the user or someone
     * else to come back again to see the good url, we can use html5
     * replaceState to fix it imeditately without a page reload.
     *
     * Importantly this needs to happen before any JS on the page uses it,
     * such as any analytics tracking.
     *
     * @param string $clean string
     * @return string
     */
    private static function get_replacestate_script($clean) {
        return "<script>history.replaceState && history.replaceState({}, '', '{$clean}');</script>\n";
    }

    /**
     * Rewire #anchor links dynamically
     *
     * This fixes an edge case bug where in the page there are simple links
     * to internal #hash anchors. But because we add a base href tag these
     * links now appear to link to another page and not this one and cause
     * a reload. So on the fly we detect this and insert the clean url base.
     *
     * @param string $clean string
     * @return string
     */
    private static function get_anchor_fix_javascript($clean) {
        return <<<HTML
<script>
document.addEventListener('click', function (event) {
    var element = event.target;
    while (element.tagName != 'A') {
        if (!element.parentElement) {
            return;
        }
        element = element.parentElement;
    }
    var href = element.getAttribute('href');
    var iscontrol = element.getAttribute('role') === 'button'
        || element.hasAttribute('data-action')
        || element.hasAttribute('data-toggle')
        || element.hasAttribute('data-bs-toggle');
    if (href && href.charAt(0) == '#' && !iscontrol) {
        element.href = '{$clean}' + href;
    }
}, true);
</script>
HTML;
    }

    /**
     * Now that each page has two valid urls, we need to tell robots like
     * GoogleBot that they are the same, otherwise Google may think they
     * are low quality duplicates and possibly split pagerank between them.
     *
     * We specify that the clean one is the 'canonical' url so this is what
     * will be shown in google search results pages.
     * @return string
     */
    private static function get_link_canonical() {
        global $PAGE;

        $cleanescaped = $PAGE->url->out(true);
        return "<link rel=\"canonical\" href=\"{$cleanescaped}\" />\n";
    }
}
