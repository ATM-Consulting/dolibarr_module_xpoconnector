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

    public function __construct()
    {
		$this->init();
    }

    public function generateCSV($object) {
    	global $langs;
    	$error = 0;

    	dol_include_once('/core/lib/files.lib.php');
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

	public function moveFileToFTP() {
    	global $conf, $langs;
    	if(!empty($this->upload_path)) {
			$target_folder = $this->filename;//to define
			$ftp_host = (empty($conf->global->XPOCONNECTOR_FTP_HOST)) ? "" : $conf->global->XPOCONNECTOR_FTP_HOST;
			$ftp_port = (empty($conf->global->XPOCONNECTOR_FTP_PORT)) ? 21 : $conf->global->XPOCONNECTOR_FTP_PORT;
			$ftp_user = (empty($conf->global->XPOCONNECTOR_FTP_USER)) ? "" : $conf->global->XPOCONNECTOR_FTP_USER;
			$ftp_pass = (empty($conf->global->XPOCONNECTOR_FTP_PASS)) ? "" : $conf->global->XPOCONNECTOR_FTP_PASS;
			if($co = ftp_connect($ftp_host, $ftp_port)) {
				if(ftp_login($co, $ftp_user, $ftp_pass)) {
					if(ftp_put($co, $target_folder, $this->upload_path, FTP_BINARY)) {
						setEventMessage($langs->trans('FTPFileSuccess'));
					}
					else {
						setEventMessage($langs->trans('FTPUploadError'), 'errors');
					}
				}
				else {
					setEventMessage($langs->trans('FTPLoginError'), 'errors');
				}
				ftp_close($co);
			}
			else {
				setEventMessage($langs->trans('FTPConnectionError'), 'errors');
			}
		} else {
    		setEventMessage($langs->trans('MissingLocalPath'), 'errors');
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
							'Unité de mesure'=> array('max_length' => 3, 'from_object'=>1, 'object_field'=>'array_options["options_"]'), //TODO A DISCUTER AVEC GEO
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
			$xpoConnector->TSchema['Activite']['value'] = '';
			//Categorie
			if(!empty($conf->global->XPOCONNECTOR_PRODUCT_CATEGORY)) {
				$TCategId = GETPOST('categories');
				if(!empty($TCategId)) {
					foreach($TCategId as $fk_category) {
						$categ = new Categorie($object->db);
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
			$xpoConnector->moveFileToFTP();
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
							'Unite de saisie des quantites commandees'=> array('max_length' => 3, 'from_object'=>0),//TODO A DISCUTER AVEC GEO
							'Message sur bon de reception'=> array('max_length' =>60, 'from_object'=>0), //Non géré
							'Code fournisseur'=> array('max_length' => 0, 'from_object'=>0)//Non géré
	);

	public static function send($object){
		global $conf;
		if(!empty($conf->global->XPOCONNECTOR_ENABLE_SUPPLIERORDER) ) {
			//Préparation du CSV
			$xpoConnector = new XPOConnectorSupplierOrder($object->ref);
			//TODO
			$xpoConnector->TSchema['Activite']['value'] = '';

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
			$xpoConnector->moveFileToFTP();
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
