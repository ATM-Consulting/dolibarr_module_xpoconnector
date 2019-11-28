<?php
require('config.php');

require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
$langs->loadLangs(array("sendings", "companies", "bills", 'deliveries', 'orders', 'stocks', 'other', 'propal'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if(empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if(!$sortorder) $sortorder = "DESC";
if(!$sortfield) $sortfield = "date";
$object = new Expedition($db);
$form = new Form($db);
$formcore = new TFormCore;
$formfile = new FormFile($db);

if($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
}
$upload_dir = $conf->expedition->dir_output . '/sending/' . dol_sanitizeFileName($object->ref);

/*
 * Actions
 */

if($action == 'import_xml') dol_add_file_process($upload_dir, 1, 0, 'file', 'import_stackbuilder.xml');

$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('', $langs->trans("Shipment"), $help_url, '', 0, 0, '', array('/xpoconnector/css/xpoconnector.css'));

$object->fetch_thirdparty();

$head = shipping_prepare_head($object);
dol_fiche_head($head, 'palletization', $langs->trans('Shipping'), -1, 'sending');

print $formcore->begin_form(null, 'importatm_form', 'POST', true);

print $formcore->hidden('action', 'import_xml');
print $formcore->hidden('token', $_SESSION['newtoken']);

print '<table id="importtable" class="noborder" width="100%">';

$var = false;

print '<tr ' . $bc[$var] . '>';
print '<td class="fieldrequired" width="15%">' . $langs->trans('File') . '</td>';
print '<td><input type="file" name="file" value="" /></td>';
print '</tr>';
print '</table>';

print '<div class="center"><input class="button" value="' . $langs->trans('Import') . '" type="submit"></div>';

$formcore->end_form();

print '<hr/>';

/*
 * Affichage des données
 */

if(file_exists($upload_dir . '/import_stackbuilder.xml')) {
	$xml = simplexml_load_file($upload_dir . '/import_stackbuilder.xml');
//	var_dump($xml->data);exit;
//	foreach($xml->data->items as $palletAnalysis) {
//		var_dump($palletAnalysis);
//	}
//
//	exit;
	print'<div class="horizontal">';
	print '		<div class="tree">
				<div class="palette">
				Palette 1<br/>
				L cm*l cm*h cm<br/>
				poids chargement:xxxkg <br/>
				poids:xxxKg 
				
				</div>
				
					<div class="line"></div><div class="produit">Produit N</br>1/3</div>
					<div class="line"></div><div class="produit">Produit Y</br>2/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>3/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>4/4</div>
			
				</div>
			';
	print '		<div class="tree">
				<div class="palette">
				Palette 1<br/>
				L cm*l cm*h cm<br/>
				poids chargement:xxxkg <br/>
				poids:xxxKg 
				
				</div>
				
					<div class="line"></div><div class="produit">Produit <a href="#">SAZE20154698</a></br></br>1/3</div>
					<div class="line"></div><div class="produit">Produit N</br>2/3</div>
			
			
				</div>
			';
	print '		<div class="tree">
				<div class="palette">
				Palette 1<br/>
				L cm*l cm*h cm<br/>
				poids chargement:xxxkg <br/>
				poids:xxxKg 
				
				</div>
				
					<div class="line"></div><div class="produit">Produit N</br>1/3</div>
					<div class="line"></div><div class="produit">Produit N</br>2/3</div>
					<div class="line"></div><div class="produit">Produit N</br>3/3</div>
					<div class="line"></div><div class="produit">Produit Y</br>1/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>2/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>3/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>4/4</div>	
					<div class="line"></div><div class="produit">Produit Y</br>1/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>2/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>3/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>4/4</div>	
					<div class="line"></div><div class="produit">Produit Y</br>1/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>2/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>3/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>4/4</div>
			
				</div>
			';
	print '		<div class="tree">
				<div class="palette">
				Palette 1<br/>
				L cm*l cm*h cm<br/>
				poids chargement:xxxkg <br/>
				poids:xxxKg 
				
				</div>
				
					<div class="line"></div><div class="produit">Produit N</br>1/3</div>
					<div class="line"></div><div class="produit">Produit N</br>2/3</div>
					<div class="line"></div><div class="produit">Produit N</br>3/3</div>
					<div class="line"></div><div class="produit">Produit Y</br>1/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>2/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>3/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>4/4</div>
			
				</div>
			';
	print '		<div class="tree">
				<div class="palette">
				Palette 1<br/>
				L cm*l cm*h cm<br/>
				poids chargement:xxxkg <br/>
				poids:xxxKg 
				
				</div>
				
					<div class="line"></div><div class="produit">Produit N</br>1/3</div>
					<div class="line"></div><div class="produit">Produit N</br>2/3</div>
					<div class="line"></div><div class="produit">Produit N</br>3/3</div>
					<div class="line"></div><div class="produit">Produit Y</br>1/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>2/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>3/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>4/4</div>
			
				</div>
			';
	print '		<div class="tree">
				<div class="palette">
				Palette 1<br/>
				L cm*l cm*h cm<br/>
				poids chargement:xxxkg <br/>
				poids:xxxKg 
				
				</div>
				
					<div class="line"></div><div class="produit">Produit N</br>1/3</div>
					<div class="line"></div><div class="produit">Produit N</br>2/3</div>
					<div class="line"></div><div class="produit">Produit N</br>3/3</div>
					<div class="line"></div><div class="produit">Produit Y</br>1/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>2/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>3/4</div>
					<div class="line"></div><div class="produit">Produit Y</br>4/4</div>
			
				</div>
			';

	print '</div>';
}
else setEventMessage($langs->trans('XMLFileNotFound'), 'warnings');

dol_fiche_end();
llxFooter();
