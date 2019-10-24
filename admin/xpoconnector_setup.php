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
 * 	\file		admin/xpoconnector.php
 * 	\ingroup	xpoconnector
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include '../../main.inc.php'; // From htdocs directory
if (! $res) {
    $res = @include '../../../main.inc.php'; // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once '../lib/xpoconnector.lib.php';
dol_include_once('abricot/includes/lib/admin.lib.php');

// Translations
$langs->loadLangs(array('xpoconnector@xpoconnector', 'admin', 'others', 'suppliers','sendings','orders'));

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if ($code == 'XPOCONNECTOR_FTP_CONF')
	{
		$res = dolibarr_set_const($db, "XPOCONNECTOR_FTP_HOST", GETPOST("XPOCONNECTOR_FTP_HOST"), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "XPOCONNECTOR_FTP_PORT", GETPOST("XPOCONNECTOR_FTP_PORT"), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "XPOCONNECTOR_FTP_USER", GETPOST("XPOCONNECTOR_FTP_USER"), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "XPOCONNECTOR_FTP_PASS", GETPOST("XPOCONNECTOR_FTP_PASS"), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "XPOCONNECTOR_FTP_SENDING_PRODUCT_PATH", GETPOST("XPOCONNECTOR_FTP_SENDING_PRODUCT_PATH"), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "XPOCONNECTOR_FTP_SENDING_SUPPLIERORDER_PATH", GETPOST("XPOCONNECTOR_FTP_SENDING_SUPPLIERORDER_PATH"), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "XPOCONNECTOR_FTP_SENDING_SHIPPING_PATH", GETPOST("XPOCONNECTOR_FTP_SENDING_SHIPPING_PATH"), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "XPOCONNECTOR_FTP_RECEIVING_SUPPLIERORDER_PATH", GETPOST("XPOCONNECTOR_FTP_RECEIVING_SUPPLIERORDER_PATH"), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "XPOCONNECTOR_FTP_RECEIVING_ORDER_PATH", GETPOST("XPOCONNECTOR_FTP_RECEIVING_ORDER_PATH"), 'chaine', 0, '', $conf->entity);

		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "XPOConnectorSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = xpoconnectorAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104844Name"),
    -1,
    "xpoconnector@xpoconnector"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';


if(!function_exists('setup_print_title')){
    print '<div class="error" >'.$langs->trans('AbricotNeedUpdate').' : <a href="http://wiki.atm-consulting.fr/index.php/Accueil#Abricot" target="_blank"><i class="fa fa-info"></i> Wiki</a></div>';
    exit;
}

setup_print_title("Product");
setup_print_on_off('XPOCONNECTOR_ENABLE_PRODUCT');
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans('XPOCONNECTOR_PRODUCT_CATEGORY').'</td>';

print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="500">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_XPOCONNECTOR_PRODUCT_CATEGORY">';
print $form->select_all_categories('product', $conf->global->XPOCONNECTOR_PRODUCT_CATEGORY, 'XPOCONNECTOR_PRODUCT_CATEGORY');
print '<input type="submit" class="butAction" value="'.$langs->trans("Modify").'">';
print '</form>';

setup_print_title("SupplierOrder");
setup_print_on_off('XPOCONNECTOR_ENABLE_SUPPLIERORDER');
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("XPOCONNECTOR_SUPPLIERORDER_DATE_EXTRAFIELD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_XPOCONNECTOR_SUPPLIERORDER_DATE_EXTRAFIELD">';
$liste = _getExtrafields('commande_fournisseurdet');
print $form->selectarray('XPOCONNECTOR_SUPPLIERORDER_DATE_EXTRAFIELD', $liste, $conf->global->XPOCONNECTOR_SUPPLIERORDER_DATE_EXTRAFIELD,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

setup_print_title("Sending");
setup_print_on_off('XPOCONNECTOR_ENABLE_SHIPPING');
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("XPOCONNECTOR_ORDER_DATE_EXTRAFIELD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_XPOCONNECTOR_ORDER_DATE_EXTRAFIELD">';
$liste = _getExtrafields('commandedet');
print $form->selectarray('XPOCONNECTOR_ORDER_DATE_EXTRAFIELD', $liste, $conf->global->XPOCONNECTOR_ORDER_DATE_EXTRAFIELD,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

setup_print_title("FTP");
setup_print_on_off('XPOCONNECTOR_ENABLE_FTP');
print '<tr id ="FtpXPOConf" ' . $bc[$var] . '><td>' . $langs->trans("XPOCONNECTOR_FTP_CONF") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="800">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_XPOCONNECTOR_FTP_CONF">';
print $langs->trans("XPOCONNECTOR_FTP_HOST").' : <input type="text" size="30" name="XPOCONNECTOR_FTP_HOST" value="'.$conf->global->XPOCONNECTOR_FTP_HOST.'"><BR>';
print $langs->trans("XPOCONNECTOR_FTP_PORT").' : <input type="text" size="30" name="XPOCONNECTOR_FTP_PORT" value="'.$conf->global->XPOCONNECTOR_FTP_PORT.'"><BR>';
print $langs->trans("XPOCONNECTOR_FTP_USER").' : <input type="text" size="30" name="XPOCONNECTOR_FTP_USER" value="'.$conf->global->XPOCONNECTOR_FTP_USER.'"><BR>';
print $langs->trans("XPOCONNECTOR_FTP_PASS").' : <input type="password" size="30" name="XPOCONNECTOR_FTP_PASS" value="'.$conf->global->XPOCONNECTOR_FTP_PASS.'"><BR>';
print $langs->trans("XPOCONNECTOR_FTP_SENDING_PRODUCT_PATH").' : <input type="text" size="30" name="XPOCONNECTOR_FTP_SENDING_PRODUCT_PATH" value="'.$conf->global->XPOCONNECTOR_FTP_SENDING_PRODUCT_PATH.'"><BR>';
print $langs->trans("XPOCONNECTOR_FTP_SENDING_SUPPLIERORDER_PATH").' : <input type="text" size="30" name="XPOCONNECTOR_FTP_SENDING_SUPPLIERORDER_PATH" value="'.$conf->global->XPOCONNECTOR_FTP_SENDING_SUPPLIERORDER_PATH.'"><BR>';
print $langs->trans("XPOCONNECTOR_FTP_SENDING_SHIPPING_PATH").' : <input type="text" size="30" name="XPOCONNECTOR_FTP_SENDING_SHIPPING_PATH" value="'.$conf->global->XPOCONNECTOR_FTP_SENDING_SHIPPING_PATH.'"><BR>';
print $langs->trans("XPOCONNECTOR_FTP_RECEIVING_SUPPLIERORDER_PATH").' : <input type="text" size="30" name="XPOCONNECTOR_FTP_RECEIVING_SUPPLIERORDER_PATH" value="'.$conf->global->XPOCONNECTOR_FTP_RECEIVING_SUPPLIERORDER_PATH.'"><BR>';
print $langs->trans("XPOCONNECTOR_FTP_RECEIVING_ORDER_PATH").' : <input type="text" size="30" name="XPOCONNECTOR_FTP_RECEIVING_ORDER_PATH" value="'.$conf->global->XPOCONNECTOR_FTP_RECEIVING_ORDER_PATH.'"><BR>';

print '<BR><input type="submit" class="butAction" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td>';
print '</tr>';

print '</table>';

dol_fiche_end(-1);

llxFooter();

$db->close();

function _getExtrafields($elementtype){
	global $db;
	dol_include_once('/core/class/extrafields.class.php');
	$extra = new ExtraFields($db);
	$extra->fetch_name_optionals_label($elementtype);
	if(!empty($extra->attributes[$elementtype]['label'])) return $extra->attributes[$elementtype]['label'];
	else return array();
}
