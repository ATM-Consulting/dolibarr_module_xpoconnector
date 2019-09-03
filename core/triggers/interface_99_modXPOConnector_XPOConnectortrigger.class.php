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
 * 	\file		core/triggers/interface_99_modMyodule_XPOConnectortrigger.class.php
 * 	\ingroup	xpoconnector
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modXpoconnector_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class InterfaceXPOConnectortrigger
{

    private $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are empty functions."
            . "They have no effect."
            . "They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'xpoconnector@xpoconnector';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }
	
	
	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 * @param string $action code
	 * @param Object $object
	 * @param User $user user
	 * @param Translate $langs langs
	 * @param conf $conf conf
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	function runTrigger($action, $object, $user, $langs, $conf) {
		//For 8.0 remove warning
		$result=$this->run_trigger($action, $object, $user, $langs, $conf);
		return $result;
	}	
		

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
		dol_include_once('/xpoconnector/class/xpoconnector.class.php');
		dol_include_once('/xpoconnector/class/xpopackagetype.class.php');


        // Products
      	if ($action == 'PRODUCT_CREATE' || $action == 'PRODUCT_MODIFY' || $action == 'PRODUCT_DELETE') {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
            //Préparation du CSV
          	$xpoConnector = new XPOConnectorProduct($object->ref);
			//TODO
          	$xpoConnector->TSchema['Activite']['value'] = $action;
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

			//Dépôt sur le FTP TODO
			$xpoConnector->moveFileToFTP();
        }

        return 0;
    }
}
