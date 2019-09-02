<?php
/* Copyright (C) 2019 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       admin/xpoconnector_extrafields.php
 *		\ingroup    xpoconnector
 *		\brief      Page to setup extra fields of xpoconnector
 */

$res = @include '../../main.inc.php'; // From htdocs directory
if (! $res) {
    $res = @include '../../../main.inc.php'; // From "custom" directory
}


/*
 * Config of extrafield page for XPOConnector
 */
require_once '../lib/xpoconnector.lib.php';
require_once '../class/xpoconnector.class.php';
$langs->loadLangs(array('xpoconnector@xpoconnector', 'admin', 'other'));

$xpoconnector = new XPOConnector($db);
$elementtype=$xpoconnector->table_element;  //Must be the $table_element of the class that manage extrafield

// Page title and texts elements
$textobject=$langs->transnoentitiesnoconv('XPOConnector');
$help_url='EN:Help XPOConnector|FR:Aide XPOConnector';
$pageTitle = $langs->trans('XPOConnectorExtrafieldPage');

// Configuration header
$head = xpoconnectorAdminPrepareHead();



/*
 *  Include of extrafield page
 */

require_once dol_buildpath('abricot/tpl/extrafields_setup.tpl.php'); // use this kind of call for variables scope
