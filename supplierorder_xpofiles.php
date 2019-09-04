<?php
require ('config.php');

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/xpoconnector/class/xpoconnector.class.php');

$langs->load("other");
$langs->load("suppliers");
$langs->load("orders");

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
$object = new CommandeFournisseur($db);

if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);
	$xpo = new XPOConnectorSupplierOrder($object->ref);
	$upload_dir=$xpo->upload_dir;
}


/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$help_url='EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('',$langs->trans("Order"),$help_url);

$object->fetch_thirdparty();

$head=ordersupplier_prepare_head($object);
dol_fiche_head($head, 'xpofiles', $langs->trans('SupplierOrder'), -1, 'order');


// Construit liste des fichiers
$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);


$totalsize=0;
foreach($filearray as $key => $file)
{
	$totalsize+=$file['size'];
}


$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
$morehtmlref='<div class="refidno">';
// Ref supplier
$morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
$morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
// Thirdparty
$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
// Project
if (! empty($conf->projet->enabled))
{
	$langs->load("projects");
	$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	if ($user->rights->fournisseur->commande->creer)
	{
		if ($action != 'classify')
			//$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
			$morehtmlref.=' : ';
		if ($action == 'classify') {
			//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref.='<input type="hidden" name="action" value="classin">';
			$morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
			$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$morehtmlref.='</form>';
		} else {
			$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
		}
	} else {
		if (! empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
			$morehtmlref.=$proj->ref;
			$morehtmlref.='</a>';
		} else {
			$morehtmlref.='';
		}
	}
}
$morehtmlref.='</div>';
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

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
	'/supplierorder/'.$object->ref.'/',		// relative path with no file. For example "0/1"
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
