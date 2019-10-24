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

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
	dol_include_once('/core/lib/files.lib.php');
}


class XPOConnector extends SeedObject
{
    /**
     * XPOConnector constructor.
     * @param DoliDB    $db    Database connector
     */
	public $TSchema;
	public $upload_dir;
	public $upload_path;
	public $filename;
	public $pref_filename;
	public $supplierOrderDir;
	public $orderDir;

    public function __construct()
    {
    	$this->supplierOrderDir = DOL_DATA_ROOT.'/xpoconnector/received/supplierorder/';
    	$this->orderDir = DOL_DATA_ROOT.'/xpoconnector/received/order/';
		$this->init();
    }

    public function generateCSV($object) {
    	global $langs;
    	$error = 0;

    	$line = array();
    	//On formate le schema en une ligne
		foreach($this->TSchema as $key => $schema) {
			if($schema['from_object']) {
				$value = eval('return $object->'.$schema['object_field'].';');
			}
			else $value = $schema['value'];


			if(!empty($schema['max_length']) && strlen($value) > $schema['max_length']) $value = substr($value,0,$schema['max_length']);
			$line[] = $value;

		}
		//Si le dossier n'existe pas, on le crée
		if(!dol_is_dir($this->upload_dir)) {
			$res = dol_mkdir($this->upload_dir);
			if($res < 0){
				setEventMessage($langs->trans('CantCreateDirectory'),'errors');
				return -1;
			}
		}
		$this->filename = $this->pref_filename.'_'.$object->ref.'_'.time().'.csv';
		$this->upload_path = $this->upload_dir.'/'.$this->filename;

		//On génère le fichier CSV
		$f_out = fopen($this->upload_path, 'a');
		if($f_out == false) {
			setEventMessage($langs->trans('CantOpenCreateFile'),'errors');
			return -2;
		}
		fputcsv($f_out, $line, ";");
		fclose($f_out);
		return 1;
	}

	public function moveFileToFTP($target_folder) {
		global $conf, $langs;
		if(!empty($conf->global->XPOCONNECTOR_ENABLE_FTP)) {
			if(!empty($this->upload_path)) {
				if($co = $this->connectFTP()) {
					if(ftp_put($co, $target_folder, $this->upload_path, FTP_BINARY)) setEventMessage($langs->trans('FTPFileSuccess'));
					else setEventMessage($langs->trans('FTPUploadError'), 'errors');

					ftp_close($co);
				}
			}
			else {
				setEventMessage($langs->trans('MissingLocalPath'), 'errors');
			}
		}
	}

	public function connectFTP() {
		global $conf, $langs;
		$ftp_host = (empty($conf->global->XPOCONNECTOR_FTP_HOST)) ? "" : $conf->global->XPOCONNECTOR_FTP_HOST;
		$ftp_port = (empty($conf->global->XPOCONNECTOR_FTP_PORT)) ? 21 : $conf->global->XPOCONNECTOR_FTP_PORT;
		$ftp_user = (empty($conf->global->XPOCONNECTOR_FTP_USER)) ? "" : $conf->global->XPOCONNECTOR_FTP_USER;
		$ftp_pass = (empty($conf->global->XPOCONNECTOR_FTP_PASS)) ? "" : $conf->global->XPOCONNECTOR_FTP_PASS;
		if($co = ftp_connect($ftp_host, $ftp_port)) {
			if(ftp_login($co, $ftp_user, $ftp_pass)) {
				return $co;
			}
			else setEventMessage($langs->trans('FTPLoginError'), 'errors');
		} else setEventMessage($langs->trans('FTPConnectionError'), 'errors');

		return false;
	}

	/*
	 * Méthode CRON
	 */
	public function runGetOrderXPO() {
    	global $langs;
		if($co = $this->connectFTP()) {

		} else {
			$this->output = $langs->trans('FTPConnectionError');
			return -1;
		}
	}

	/*
	 * Méthode CRON
	 */
	public function runGetSupplierOrderXPO() {
		global $langs, $db, $user, $conf;
		if($co = $this->connectFTP()) {
			if(!dol_is_dir($this->supplierOrderDir)) {
				$res = dol_mkdir($this->supplierOrderDir);
				if($res < 0){
					$this->output = $langs->trans('CantCreateDirectory');
					return -4;
				}
			}
			$downloadDir = !empty($conf->global->XPOCONNECTOR_FTP_RECEIVING_SUPPLIERORDER_PATH)?rtrim($conf->global->XPOCONNECTOR_FTP_RECEIVING_SUPPLIERORDER_PATH, '/').'/':'';
			$TFiles = ftp_nlist($co, $downloadDir.'M41_*');
			if(!empty($TFiles)) {
				foreach($TFiles as $file) {
					$TPath = explode('/',$file);
					if(ftp_get($co, $this->supplierOrderDir.end($TPath), $file, FTP_BINARY)) {
						$handle = fopen($this->supplierOrderDir.end($TPath), "r");
						while(($data = fgetcsv($handle,0,';')) !== false) {
							//Activité  = à revoir avec XPO ??
							//Référence commande = numéro de commande fournisseur d’origine (ref commande fourn dans Dolibarr)
							//Code interne du produit = référence du produit
							//Nombre d'unités = quantité réceptionnée
							//Nombre total d'U.V.C. réceptionnées = quantité total initialement commandée au fournisseur
							//Unité de saisie
							//Date de réception = date de réception
							//code fournisseur = laisse vide
							//numéro de lot
							$cmd = new CommandeFournisseur($db);
							$cmd->dispatchProduct($user, GETPOST($prod, 'int'), GETPOST($qty), GETPOST($ent, 'int'), GETPOST($pu), GETPOST('comment'), $dDLC, $dDLUO, GETPOST($lot, 'alpha'), GETPOST($fk_commandefourndet, 'int'), $notrigger);


						}
					}
					else {
						$this->output = $langs->trans('FTPGetError', $file);
						return -3;
					}
				}
			} else {
				$this->output = $langs->trans('FTPNoFile');
				return -2;
			}
		} else {
			$this->output = $langs->trans('FTPConnectionError');
			return -1;
		}
	}

}

class XPOConnectorProduct extends XPOConnector
{
	public function __construct($ref)
	{
		$this->pref_filename = 'M30';
		$this->upload_dir = DOL_DATA_ROOT.'/xpoconnector/product/'.$ref;
		$this->init();
	}

	public $TSchema = array('Activite' => array('max_length' => 3, 'from_object'=>0),
							'Code produit'=> array('max_length' => 17, 'from_object'=>1, 'object_field'=>'ref'),
							'Famille du produit' => array('max_length' => 10, 'from_object'=>0),
							'Designation'=> array('max_length' => 60, 'from_object'=>1, 'object_field'=>'label'),
							'Code EAN'=> array('max_length' => 17, 'from_object'=>0), //Non géré
							'Par combien (PCB)'=> array('max_length' => 5, 'from_object'=>1, 'object_field'=>"array_options['options_prod_per_col']"),
							'Unité de mesure'=> array('max_length' => 3, 'from_object'=>0),
							'Poids brut de l UVC'=> array('max_length' => 7, 'from_object'=>0), //Non géré
							'Poids net de l UVC'=> array('max_length' => 7, 'from_object'=>1, 'object_field'=>'weight'),
							'Hauteur de l UVC'=> array('max_length' => 3, 'from_object'=>1, 'object_field'=>'height'),
							'Longueur de l UVC'=> array('max_length' => 3, 'from_object'=>1, 'object_field'=>'length'),
							'Largeur de l UVC'=> array('max_length' => 3,'from_object'=>1, 'object_field'=>'width'),
							'Poids brut du colis'=> array('max_length' => 7, 'from_object'=>0), // Valeur calculée
							'Hauteur du colis'=> array('max_length' => 3, 'from_object'=>0),
							'Longueur du colis'=> array('max_length' => 3, 'from_object'=>0),
							'Largeur du colis'=> array('max_length' => 3, 'from_object'=>0),
							'Colis couche'=> array('max_length' => 8, 'from_object'=>0), //Non géré
							'Couche par palette'=> array('max_length' => 8, 'from_object'=>0) //Non géré
							);

	public static function send($object){
		global $conf;
		if(!empty($conf->global->XPOCONNECTOR_ENABLE_PRODUCT) ) {
			//Préparation du CSV
			$xpoConnector = new XPOConnectorProduct($object->ref);
			//TODO
			$xpoConnector->TSchema['Activite']['value'] = 'ACO';
			$xpoConnector->TSchema['Unité de mesure']['value'] = 'UVC';
			//Categorie
			if(!empty($conf->global->XPOCONNECTOR_PRODUCT_CATEGORY)) {
				$categ = new Categorie($object->db);
				$TCategId = GETPOST('categories');
				$action = GETPOST('action');


				if($action == 'regenerateXPO') {
					$TCateg = $categ->getListForItem($object->id, $type = 'product');
					$TCategId = array();
					foreach($TCateg as $category) {
						$TCategId[] = $category['id'];
					}
				}

				if(!empty($TCategId)) {
					foreach($TCategId as $fk_category) {

						$categ->fetch($fk_category);
						$TMotherCategWays = $categ->get_all_ways();
						if(!empty($TMotherCategWays)) {
							foreach($TMotherCategWays as $TMotherCateg) {
								foreach($TMotherCateg as $motherCateg) {
									if($motherCateg->id == $conf->global->XPOCONNECTOR_PRODUCT_CATEGORY) { //On parcourt toutes les catégories, si une des catégories parentes est celle de la conf, on utilise cette categ

										$xpoConnector->TSchema['Famille du produit']['value'] = $categ->label;
										break;
									}
								}
							}
						}
					}
				}
			}
			//Info lié au colis
			if(!empty($object->array_options['options_xpo_uc_code'])) {
				$packageType = new XPOPackageType($object->db);
				$packageType->fetch($object->array_options['options_xpo_uc_code']);
				$poidsAVideColis = $packageType->unladen_weight;
				$poidsBrut = $object->array_options["options_prod_per_col"] * $object->weight + $poidsAVideColis;
				$xpoConnector->TSchema['Poids brut du colis']['value'] = $poidsBrut;
				$xpoConnector->TSchema['Hauteur du colis']['value'] = $packageType->height;
				$xpoConnector->TSchema['Longueur du colis']['value'] = $packageType->length;
				$xpoConnector->TSchema['Largeur du colis']['value'] = $packageType->width;
			}

			//Génération du fichier CSV
			$res = $xpoConnector->generateCSV($object);
			if($res < 0) return 0;

			//Dépôt sur le FTP
			$downloadDir = !empty($conf->global->XPOCONNECTOR_FTP_SENDING_PRODUCT_PATH)?rtrim($conf->global->XPOCONNECTOR_FTP_SENDING_PRODUCT_PATH, '/').'/':'';
			$xpoConnector->moveFileToFTP($downloadDir.$xpoConnector->filename);
		}
	}
}

class XPOConnectorSupplierOrder extends XPOConnector
{
	public function __construct($ref)
	{
		$this->pref_filename = 'M40';
		$this->upload_dir = DOL_DATA_ROOT.'/xpoconnector/supplierorder/'.$ref;
		$this->init();
	}

	public $TSchema = array('Activite' => array('max_length' => 3, 'from_object'=>0),
							'Reference commande'=> array('max_length' => 30, 'from_object'=>1, 'object_field'=>'ref'),
							'Date de reception prevue' => array('max_length' => 8, 'from_object'=>1,  'object_field'=>'date_reception_prevue'),
							'Heure de reception prevue'=> array('max_length' => 4, 'from_object'=>0), //Non géré
							'Code produit'=> array('max_length' => 17, 'from_object'=>1, 'object_field'=>'product_ref'),
							'Code du lot'=> array('max_length' => 20, 'from_object'=>0), //Non géré
							'Nombre d unites reapprovisionnees'=> array('max_length' => 9, 'from_object'=>1, 'object_field'=>'qty'),
							'Unite de saisie des quantites commandees'=> array('max_length' => 3, 'from_object'=>0),
							'Message sur bon de reception'=> array('max_length' =>60, 'from_object'=>0), //Non géré
							'Code fournisseur'=> array('max_length' => 0, 'from_object'=>0)//Non géré
	);

	public static function send($object){
		global $conf;
		if(!empty($conf->global->XPOCONNECTOR_ENABLE_SUPPLIERORDER) ) {
			//Préparation du CSV
			$xpoConnector = new XPOConnectorSupplierOrder($object->ref);
			//TODO
			$xpoConnector->TSchema['Activite']['value'] = 'ACO';
			$xpoConnector->TSchema['Unite de saisie des quantites commandees']['value'] = 'UVC';

			if(!empty($object->lines)) {
				foreach($object->lines as $line) {
					$line->ref = $object->ref;
					$line->fetch_optionals();

					if(!empty($conf->global->XPOCONNECTOR_SUPPLIERORDER_DATE_EXTRAFIELD) && !empty($line->array_options['options_'.$conf->global->XPOCONNECTOR_SUPPLIERORDER_DATE_EXTRAFIELD])) {
						$line->date_reception_prevue = date('Ymd',$line->array_options['options_'.$conf->global->XPOCONNECTOR_SUPPLIERORDER_DATE_EXTRAFIELD]);
					} else $line->date_reception_prevue = $object->date_livraison;
					//Génération du fichier CSV
					$res = $xpoConnector->generateCSV($line);
					if($res < 0) return 0;
				}
			}

			//Dépôt sur le FTP
			$downloadDir = !empty($conf->global->XPOCONNECTOR_FTP_SENDING_SUPPLIERORDER_PATH)?rtrim($conf->global->XPOCONNECTOR_FTP_SENDING_SUPPLIERORDER_PATH, '/').'/':'';
			$xpoConnector->moveFileToFTP($downloadDir.$xpoConnector->filename);
		}
	}
}

class XPOConnectorShipping extends XPOConnector
{
	public function __construct($ref)
	{
		$this->pref_filename = 'M50';
		$this->upload_dir = DOL_DATA_ROOT.'/xpoconnector/shipping/'.$ref;
		$this->init();
	}

	public $TSchema = array('Activite' => array('max_length' => 3, 'from_object'=>0),
							'Reference livraison'=> array('max_length' => 30, 'from_object'=>1, 'object_field'=>'ref'),
							'Reference commande destinataire' => array('max_length' => 30, 'from_object'=>0),
							'Code client'=> array('max_length' => 14, 'from_object'=>0),
							'Nom client'=> array('max_length' => 30, 'from_object'=>0),
							'Adresse client'=> array('max_length' => 60, 'from_object'=>0),
							'Code postal client'=> array('max_length' => 5, 'from_object'=>0),
							'Ville client'=> array('max_length' => 26, 'from_object'=>0),
							'Telephone client'=> array('max_length' => 20, 'from_object'=>0),
							'Code pays client'=> array('max_length' => 3, 'from_object'=>0),
							'Code produit'=> array('max_length' => 17, 'from_object'=>1, 'object_field'=>'product_ref'),
							'Nombre UVC Commandees'=> array('max_length' => 9, 'from_object'=>1, 'object_field'=>'qty'),
							'Unite de saisie'=> array('max_length' => 3, 'from_object'=>0),
							'Code du lot'=> array('max_length' => 20, 'from_object'=>1, 'object_field'=>'batch_number'),
							'Date de livraison'=> array('max_length' => 8, 'from_object'=>1, 'object_field'=>'delivery_date'),
							'Message sur bon de preparation'=> array('max_length' =>60, 'from_object'=>0), //Non géré
							'Message sur bon de livraison'=> array('max_length' => 60, 'from_object'=>0)//Non géré
	);

	public static function send($object){
		global $conf, $db, $langs;
		if(!empty($conf->global->XPOCONNECTOR_ENABLE_SHIPPING) ) {
			//Préparation du CSV
			$xpoConnector = new XPOConnectorShipping($object->ref);

			$xpoConnector->TSchema['Activite']['value'] = 'ACO';
			$xpoConnector->TSchema['Unite de saisie']['value'] = 'UVC';

			if($object->origin == 'commande') {
				$commande = new Commande($db);
				$commande->fetch($object->origin_id);
				$xpoConnector->TSchema['Reference commande destinataire']['value'] = $commande->ref_client;
				$TContactCommande = $commande->liste_contact(-1,'external',0,'SHIPPING');
				if(!empty($TContactCommande)) {
					$contact = new Contact($db);
					$contact->fetch($TContactCommande[0]['id']);
					if(empty($contact->thirdparty)) $contact->fetch_thirdparty();
					$xpoConnector->TSchema['Code client']['value'] = $contact->thirdparty->code_client;
					$xpoConnector->TSchema['Nom client']['value'] = $contact->getFullName($langs);
					$xpoConnector->TSchema['Adresse client']['value'] = $contact->address;
					$xpoConnector->TSchema['Code postal client']['value'] = $contact->zip;
					$xpoConnector->TSchema['Ville client']['value'] = $contact->town;
					$xpoConnector->TSchema['Telephone client']['value'] = $contact->phone_pro;
					$xpoConnector->TSchema['Code pays client']['value'] = $contact->country_code;
				}
			}

			if(empty($object->thirdparty)) $object->fetch_thirdparty();
			if(empty($TContactCommande) && !empty($object->thirdparty)) {
				$xpoConnector->TSchema['Code client']['value'] = $object->thirdparty->code_client;
				$xpoConnector->TSchema['Nom client']['value'] = $object->thirdparty->nom;
				$xpoConnector->TSchema['Adresse client']['value'] = $object->thirdparty->address;
				$xpoConnector->TSchema['Code postal client']['value'] = $object->thirdparty->zip;
				$xpoConnector->TSchema['Ville client']['value'] = $object->thirdparty->town;
				$xpoConnector->TSchema['Telephone client']['value'] = $object->thirdparty->phone;
				$xpoConnector->TSchema['Code pays client']['value'] = $object->thirdparty->country_code;
			}


			if(!empty($object->lines)) {
				foreach($object->lines as $line) {
					$line->ref = $object->ref;
					$line->fetch_optionals();

					if(!empty($conf->global->XPOCONNECTOR_ORDER_DATE_EXTRAFIELD) && $line->fk_origin && !empty($line->fk_origin_line)) {
						$orderline = new OrderLine($db);
						$orderline->fetch($line->fk_origin_line);
						$orderline->fetch_optionals();
						if(!empty($orderline->array_options['options_'.$conf->global->XPOCONNECTOR_ORDER_DATE_EXTRAFIELD])) {
							$line->delivery_date = date('Ymd',$orderline->array_options['options_'.$conf->global->XPOCONNECTOR_ORDER_DATE_EXTRAFIELD]);
						}
					}
					if(empty($line->delivery_date)) $line->delivery_date = date('Ymd',$object->date_delivery);
					//Génération du fichier CSV
					if(!empty($line->detail_batch)) {
						foreach($line->detail_batch as $detail_batch) {
							$line->batch_number = $detail_batch->batch;
							$line->qty = $detail_batch->dluo_qty;
							$res = $xpoConnector->generateCSV($line);
						}
					} else $res = $xpoConnector->generateCSV($line);
					if($res < 0) return 0;
				}
			}

			//Dépôt sur le FTP
			$downloadDir = !empty($conf->global->XPOCONNECTOR_FTP_SENDING_SHIPPING_PATH)?rtrim($conf->global->XPOCONNECTOR_FTP_SENDING_SHIPPING_PATH, '/').'/':'';
			$xpoConnector->moveFileToFTP($downloadDir.$xpoConnector->filename);
		}
	}
}

//class XPOConnectorDet extends SeedObject
//{
//    public $table_element = 'xpoconnectordet';
//
//    public $element = 'xpoconnectordet';
//
//
//    /**
//     * XPOConnectorDet constructor.
//     * @param DoliDB    $db    Database connector
//     */
//    public function __construct($db)
//    {
//        $this->db = $db;
//
//        $this->init();
//    }
//}
