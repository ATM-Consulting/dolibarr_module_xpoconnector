<?php
require ('config.php');

require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/xpoconnector/class/xpoconnector.class.php');
$langs->loadLangs(array("sendings","companies","bills",'deliveries','orders','stocks','other','propal'));


$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="date";
$object = new Expedition($db);

if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);
	$xpo = new XPOConnectorShipping($object->ref);
	$upload_dir=$xpo->upload_dir;
}


/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$help_url='EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('',$langs->trans("Shipment"),$help_url);

$object->fetch_thirdparty();

$head=shipping_prepare_head($object);
dol_fiche_head($head, 'xpofiles', $langs->trans('Shipping'), -1, 'sending');


// Construit liste des fichiers
$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);


$totalsize=0;
foreach($filearray as $key => $file)
{
	$totalsize+=$file['size'];
}

// Shipment card
$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">'.$langs->trans("BackToList").'</a>';

$morehtmlref='<div class="refidno">';
// Ref customer shipment
$morehtmlref.=$form->editfieldkey("RefCustomer", '', $object->ref_customer, $object, $user->rights->expedition->creer, 'string', '', 0, 1);
$morehtmlref.=$form->editfieldval("RefCustomer", '', $object->ref_customer, $object, $user->rights->expedition->creer, 'string', '', null, null, '', 1);
// Thirdparty
$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
// Project
if (! empty($conf->projet->enabled)) {
	$langs->load("projects");
	$morehtmlref .= '<br>' . $langs->trans('Project') . ' ';
	if (0) {    // Do not change on shipment
		if ($action != 'classify') {
			$morehtmlref .= '<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
		}
		if ($action == 'classify') {
			// $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			$morehtmlref .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
			$morehtmlref .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
			$morehtmlref .= '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
			$morehtmlref .= '</form>';
		} else {
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
		}
	} else {
		// We don't have project on shipment, so we will use the project or source object instead
		// TODO Add project on shipment
		$morehtmlref .= ' : ';
		if (! empty($objectsrc->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($objectsrc->fk_project);
			$morehtmlref .= '<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $objectsrc->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
			$morehtmlref .= $proj->ref;
			$morehtmlref .= '</a>';
		} else {
			$morehtmlref .= '';
		}
	}
}
$morehtmlref.='</div>';


dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


print '<div class="underbanner clearboth"></div>';

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield" width="100%">';

print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
print '</table>';

print '</div>';
print '<div style="clear:both"></div>';

dol_fiche_end();

if (empty($relativepathwithnofile)) $relativepathwithnofile='';

// List of document
$formfile->list_of_documents(
	$filearray,
	$object,
	'xpoconnector',
	$param,
	0,
	'/shipping/'.$object->ref.'/',		// relative path with no file. For example "0/1"
	0,
	0,
	'',
	0,
	'',
	'',
	0,
	0,
	$upload_dir,
	$sortfield,
	$sortorder,
	1
);

llxFooter();
