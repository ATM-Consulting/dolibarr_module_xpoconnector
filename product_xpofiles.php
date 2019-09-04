<?php
require ('config.php');

require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/xpoconnector/class/xpoconnector.class.php');

$langs->load("other");
$langs->load("products");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="position_name";
$object = new Product($db);

if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);
	$xpo = new XPOConnectorProduct($object->ref);
	$upload_dir=$xpo->upload_dir;
}


/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label,16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
{
	$title = $langs->trans('Product')." ". $shortlabel ." - ".$langs->trans('XPOFiles');
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
{
	$title = $langs->trans('Service')." ". $shortlabel ." - ".$langs->trans('XPOFiles');
	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl);

$head=product_prepare_head($object);
$titre=$langs->trans("CardProduct".$object->type);
$picto=($object->type== Product::TYPE_SERVICE?'service':'product');

dol_fiche_head($head, 'xpofiles', $titre, -1, $picto);

// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);


	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


    $linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
    $object->next_prev_filter=" fk_product_type = ".$object->type;

    $shownav = 1;
    if ($user->societe_id && ! in_array('product', explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav=0;

    dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

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
	'/product/'.$object->ref.'/',		// relative path with no file. For example "0/1"
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
