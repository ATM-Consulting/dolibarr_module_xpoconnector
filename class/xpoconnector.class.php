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


			if(!empty($schema['max_length'])) $value = substr($value,0,$schema['max_length']);
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
		$this->upload_path = $this->upload_dir.'/'.$object->ref.'-'.time().'.csv';
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

}

class XPOConnectorProduct extends XPOConnector
{
	public function __construct($ref)
	{
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
