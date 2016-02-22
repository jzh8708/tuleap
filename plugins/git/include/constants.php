<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

define('GIT_BASE_URL', '/plugins/git');
define('GIT_BASE_DIR', dirname(__FILE__));
define('GIT_TEMPLATE_DIR', GIT_BASE_DIR . '/../templates');

/**
 * Check if platform can use gerrit
 *
 * Parameters:
 *     'platform_can_use_gerrit' => boolean
 */
define('GIT_EVENT_PLATFORM_CAN_USE_GERRIT', 'git_event_platform_can_use_gerrit');
define('REST_GIT_PULL_REQUEST_ENDPOINTS', 'rest_git_pull_request_endpoints');
define('REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY', 'rest_git_pull_request_get_for_repository');
