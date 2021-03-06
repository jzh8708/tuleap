<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SvnCore\Admin;

use ForgeConfig;
use SVN_Apache_SvnrootConf;

abstract class Presenter
{
    public $is_token_pane_active = false;
    public $is_cache_pane_active = false;

    /**
     * @return string
     */
    public function admin_title()
    {
        return $GLOBALS['Language']->getText('svn_siteadmin', 'global_title');
    }

    /**
     * @return string
     */
    public function cache_panel_title()
    {
        return $GLOBALS['Language']->getText('svn_siteadmin', 'cache_panel_title');
    }

    /**
     * @return string
     */
    public function token_panel_title()
    {
        return $GLOBALS['Language']->getText('svn_siteadmin', 'token_panel_title');
    }

    /**
     * @return bool
     */
    public function is_svn_token_configurable()
    {
        return ForgeConfig::get(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY) !== SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL;
    }
}
