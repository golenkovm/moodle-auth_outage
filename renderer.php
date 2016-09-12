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

use auth_outage\models\outage;
use auth_outage\models\outageform;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * auth_outage auth_outage_renderer
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_outage_renderer extends plugin_renderer_base {
    public function rendersubtitle($subtitlekey) {
        if (!is_string($subtitlekey)) {
            throw new \InvalidArgumentException('$subtitle is not a string.');
        }
        return html_writer::tag('h2', get_string($subtitlekey, 'auth_outage'));
    }

    public function renderdeleteconfirmation(outage $outage) {
        return $this->rendersubtitle('outagedelete')
        . html_writer::tag('p', get_string('outagedeletewarning', 'auth_outage'))
        . $this->renderoutage($outage, false);
    }

    /**
     * Outputs the HTML data listing all given outages.
     * @param array $outages Outages to list.
     */
    public function renderoutagelist(array $active, array $future, array $past) {
        global $OUTPUT;

        if (!empty($active)) {
            echo $this->rendersubtitle('outageslistactive');
            $table = new \auth_outage\tables\manage();
            $table->set_data($active, true);
            $table->finish_output();
        }

        echo $this->rendersubtitle('outageslistfuture');
        if (empty($future)) {
            echo html_writer::tag('p', html_writer::tag('small', get_string('notfound', 'auth_outage')));
        } else {
            $table = new \auth_outage\tables\manage();
            $table->set_data($future, true);
            $table->finish_output();
        }

        echo $this->rendersubtitle('outageslistpast');
        if (empty($past)) {
            echo html_writer::tag('p', html_writer::tag('small', get_string('notfound', 'auth_outage')));
        } else {
            $table = new \auth_outage\tables\manage();
            $table->set_data($past, false);
            $table->finish_output();
        }

        // Add 'add' button.
        $url = new moodle_url('/auth/outage/new.php');
        $img = html_writer::empty_tag('img',
            ['src' => $OUTPUT->pix_url('t/add'), 'alt' => get_string('create'), 'class' => 'iconsmall']);
        echo
            html_writer::empty_tag('hr')
            . html_writer::tag('p',
                html_writer::link(
                    $url,
                    $img . ' ' . get_string('outagecreate', 'auth_outage'),
                    ['title' => get_string('delete')]
                )
            );
    }

    private function renderoutage(outage $outage, $buttons) {
        global $OUTPUT;

        $created = core_user::get_user($outage->createdby, 'firstname,lastname', MUST_EXIST);
        $created = html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $outage->createdby]),
            trim($created->firstname . ' ' . $created->lastname)
        );

        $modified = core_user::get_user($outage->modifiedby, 'firstname,lastname', MUST_EXIST);
        $modified = html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $outage->modifiedby]),
            trim($modified->firstname . ' ' . $modified->lastname)
        );

        $url = new moodle_url('/auth/outage/edit.php', ['id' => $outage->id]);
        $img = html_writer::empty_tag(
            'img',
            ['src' => $OUTPUT->pix_url('t/edit'), 'alt' => get_string('edit'), 'class' => 'iconsmall']
        );
        $linkedit = html_writer::link($url, $img, ['title' => get_string('edit')]);

        $url = new moodle_url('/auth/outage/delete.php', ['id' => $outage->id]);
        $img = html_writer::empty_tag(
            'img',
            ['src' => $OUTPUT->pix_url('t/delete'), 'alt' => get_string('delete'), 'class' => 'iconsmall']
        );
        $linkdelete = html_writer::link($url, $img, ['title' => get_string('delete')]);

        // TODO use language pack below, solve together with Issue #12.
        return html_writer::div(
            html_writer::span(
                html_writer::tag('b', $outage->title, ['data-id' => $outage->id])
                . html_writer::empty_tag('br')
                . html_writer::tag('i', $outage->description)
                . html_writer::empty_tag('br')
                . html_writer::tag('b', 'Warning: ')
                . userdate($outage->warntime, '%d %h %Y %l:%M%P')
                . html_writer::empty_tag('br')
                . html_writer::tag('b', 'Starts: ')
                . userdate($outage->starttime, '%d %h %Y %l:%M%P')
                . html_writer::empty_tag('br')
                . html_writer::tag('b', 'Stops: ')
                . userdate($outage->stoptime, '%d %h %Y %l:%M%P')
                . html_writer::empty_tag('br')
                . html_writer::tag('small',
                    'Created by ' . $created
                    . ', modified by ' . $modified . ' on '
                    . userdate($outage->lastmodified, '%d %h %Y %l:%M%P')
                )
                . html_writer::empty_tag('br')
                . ($buttons ? $linkedit . $linkdelete . html_writer::empty_tag('br') : '')
                . html_writer::empty_tag('br')
            )
        );
    }

    public function renderoutagepage(outage $outage) {
        $start = userdate($outage->starttime, get_string('strftimedatetimeshort'));
        $stop = userdate($outage->stoptime, get_string('strftimedatetimeshort'));

        $admin = '';
        if (is_siteadmin()) {
            $admin = html_writer::tag('div',
                '[' . html_writer::link(
                    new moodle_url('/auth/outage/edit.php', ['id' => $outage->id]),
                    get_string('outageedit', 'auth_outage')
                ) . ']'
            );
        }

        return html_writer::div(
            html_writer::tag('p',
                html_writer::tag('b', 'From: ')
                . $start
                . html_writer::tag('b', ' Until: ')
                . $stop
            )
            . html_writer::div($outage->get_description())
            . $admin
        );
    }

    /**
     * Renders the warning bar.
     * @param outage $outage The outage to show in the warning bar.
     * @return string HTML of the warning bar.
     * @SuppressWarnings("unused") because $message is used inside require(...)
     */
    public function renderoutagebar(outage $outage) {
        global $CFG;

        $start = userdate($outage->starttime, get_string('strftimedatetimeshort'));
        $stop = userdate($outage->stoptime, get_string('strftimedatetimeshort'));

        $message = get_string(
            $outage->is_ongoing() ? 'messageoutageongoing' : 'messageoutagewarning',
            'auth_outage',
            ['start' => $start, 'stop' => $stop]
        );

        ob_start();
        require($CFG->dirroot . '/auth/outage/views/warningbar.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}
