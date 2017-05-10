<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\User;

use CSRFSynchronizerToken;

class MyPresenter
{
    /**
     * @var UserPresenter
     */
    public $user_presenter;
    /**
     * @var DashboardPresenter[]
     */
    public $user_dashboards;
    public $has_dashboard;

    public $add_dashboard_label;
    public $dashboard_name_label;
    public $no_dashboard_label;

    public $cancel;
    public $close;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $delete_dashboard_label;
    public $edit_dashboard_label;
    public $delete_dashboard_title;
    public $edit_dashboard_title;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        UserPresenter $user_presenter,
        array $user_dashboards
    ) {
        $this->csrf_token      = $csrf;
        $this->user_presenter  = $user_presenter;
        $this->user_dashboards = $user_dashboards;
        $this->has_dashboard   = count($user_dashboards) > 0;

        $this->add_dashboard_label    = _('Add dashboard');
        $this->delete_dashboard_title = _('Delete dashboard');
        $this->delete_dashboard_label = _('Delete dashboard');
        $this->edit_dashboard_title   = _('Edit dashboard');
        $this->edit_dashboard_label   = _('Edit dashboard');
        $this->dashboard_name_label   = _('Dashboard name');
        $this->no_dashboard_label     = _("You don't have any dashboards.");

        $this->cancel = _('Cancel');
        $this->close  = _('Close');
    }
}