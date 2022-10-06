<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
/**
 * Jeedom plugin installation function.
 */
function RteSync_install()
{
    message::removeAll('RteSync');
    message::add('RteSync', '{{Installation du plugin RteSync terminée}}''.', null, null);
}
/**
 * Jeedom plugin update function.
 */
function RteSync_update()
{
    log::add('RteSync', 'debug', 'RteSync_update');
    message::removeAll('RteSync');
    message::add('RteSync', '{{Mise à jour du plugin RteSync terminée}}''.', null, null);
}
/**
 * Jeedom plugin remove function.
 */
function RteSync_remove()
{
    log::add('RteSync', 'debug', 'RteSync_remove');
    message::removeAll('RteSync');
    message::add('RteSync', '{{Désinstallation du plugin RteSync terminée}}''.', null, null);
}
