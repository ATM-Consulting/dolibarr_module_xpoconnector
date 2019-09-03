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
 *	\file		lib/xpoconnector.lib.php
 *	\ingroup	xpoconnector
 *	\brief		This file is an example module library
 *				Put some comments here
 */

/**
 * @return array
 */
function xpoconnectorAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load('xpoconnector@xpoconnector');

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/xpoconnector/admin/xpoconnector_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
//    $head[$h][0] = dol_buildpath("/xpoconnector/admin/xpoconnector_extrafields.php", 1);
//    $head[$h][1] = $langs->trans("ExtraFields");
//    $head[$h][2] = 'extrafields';
//    $h++;
    $head[$h][0] = dol_buildpath("/xpoconnector/admin/xpoconnector_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@xpoconnector:/xpoconnector/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@xpoconnector:/xpoconnector/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'xpoconnector');

    return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	XPOConnector	$object		Object company shown
 * @return 	array				Array of tabs
 */
function xpoconnector_prepare_head(XPOConnector $object)
{
    global $langs, $conf;
    $h = 0;
    $head = array();
    $head[$h][0] = dol_buildpath('/xpoconnector/card.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("XPOConnectorCard");
    $head[$h][2] = 'card';
    $h++;
	
	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@xpoconnector:/xpoconnector/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@xpoconnector:/xpoconnector/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'xpoconnector');
	
	return $head;
}

/**
 * @param Form      $form       Form object
 * @param XPOConnector  $object     XPOConnector object
 * @param string    $action     Triggered action
 * @return string
 */
function getFormConfirmXPOConnector($form, $object, $action)
{
    global $langs, $user;

    $formconfirm = '';

    if ($action === 'valid' && !empty($user->rights->xpoconnector->write))
    {
        $body = $langs->trans('ConfirmValidateXPOConnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmValidateXPOConnectorTitle'), $body, 'confirm_validate', '', 0, 1);
    }
    elseif ($action === 'accept' && !empty($user->rights->xpoconnector->write))
    {
        $body = $langs->trans('ConfirmAcceptXPOConnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmAcceptXPOConnectorTitle'), $body, 'confirm_accept', '', 0, 1);
    }
    elseif ($action === 'refuse' && !empty($user->rights->xpoconnector->write))
    {
        $body = $langs->trans('ConfirmRefuseXPOConnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmRefuseXPOConnectorTitle'), $body, 'confirm_refuse', '', 0, 1);
    }
    elseif ($action === 'reopen' && !empty($user->rights->xpoconnector->write))
    {
        $body = $langs->trans('ConfirmReopenXPOConnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmReopenXPOConnectorTitle'), $body, 'confirm_refuse', '', 0, 1);
    }
    elseif ($action === 'delete' && !empty($user->rights->xpoconnector->write))
    {
        $body = $langs->trans('ConfirmDeleteXPOConnectorBody');
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmDeleteXPOConnectorTitle'), $body, 'confirm_delete', '', 0, 1);
    }
    elseif ($action === 'clone' && !empty($user->rights->xpoconnector->write))
    {
        $body = $langs->trans('ConfirmCloneXPOConnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCloneXPOConnectorTitle'), $body, 'confirm_clone', '', 0, 1);
    }
    elseif ($action === 'cancel' && !empty($user->rights->xpoconnector->write))
    {
        $body = $langs->trans('ConfirmCancelXPOConnectorBody', $object->ref);
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ConfirmCancelXPOConnectorTitle'), $body, 'confirm_cancel', '', 0, 1);
    }

    return $formconfirm;
}
