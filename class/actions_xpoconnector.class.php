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
 * \file    class/actions_xpoconnector.class.php
 * \ingroup xpoconnector
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsXPOConnector
 */
class ActionsXPOConnector
{
	/**
	 * @var DoliDb        Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 * @param DoliDB $db Database connector
	 */
	public function __construct($db) {
		$this->db = $db;
	}

	public function doActions($parameters, &$object, &$action, $hookmanager) {
		global $conf;
		$TContext = explode(':', $parameters['context']);
		if(in_array('productcard', $TContext) && $action == 'regenerateXPO') {
			dol_include_once('/xpoconnector/class/xpoconnector.class.php');
			dol_include_once('/xpoconnector/class/xpopackagetype.class.php');
			XPOConnectorProduct::send($object);
		}
		if(in_array('ordersuppliercard', $TContext) && $action == 'regenerateXPO') {
			dol_include_once('/xpoconnector/class/xpoconnector.class.php');
			XPOConnectorSupplierOrder::send($object);
		}
		if(in_array('expeditioncard', $TContext) && $action == 'regenerateXPO') {
			dol_include_once('/xpoconnector/class/xpoconnector.class.php');
			XPOConnectorShipping::send($object);
		}

		/*
		 * StackBuilder
		 */
		if((in_array('propalcard', $TContext) && $object->statut >= Propal::STATUS_VALIDATED)
			|| (in_array('ordercard', $TContext) && $object->statut >= Commande::STATUS_VALIDATED)
			|| (in_array('expeditioncard', $TContext) && $object->statut >= Expedition::STATUS_VALIDATED)) {
			dol_include_once('/xpoconnector/class/stackbuilderconnector.class.php');
			dol_include_once('/xpoconnector/class/xpopackagetype.class.php');

			if(!empty($conf->global->XPOCONNECTOR_ENABLE_STACKBUILDER) && $action == "stackbuilderdownload") {
				$filepath = StackBuilderConnector::generateXML($object);
				if(!empty($filepath)) {
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . filesize($filepath));
					readfile($filepath);
					exit;
				}
			}
		}

		return 0;
	}

	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf;
		$langs->load('xpoconnector@xpoconnector');
		$TContext = explode(':', $parameters['context']);

		if(in_array('productcard', $TContext) && !empty($conf->global->XPOCONNECTOR_ENABLE_PRODUCT)
			|| in_array('ordersuppliercard', $TContext) && !empty($conf->global->XPOCONNECTOR_ENABLE_SUPPLIERORDER) && $object->statut >= CommandeFournisseur::STATUS_ORDERSENT
			|| in_array('expeditioncard', $TContext) && !empty($conf->global->XPOCONNECTOR_ENABLE_SHIPPING) && $object->statut >= Expedition::STATUS_VALIDATED) {
			print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=regenerateXPO">' . $langs->trans('ResendXPOFile') . '</a>';
		}

		/*
		 * StackBuilder
		 */
		if((in_array('propalcard', $TContext) && $object->statut >= Propal::STATUS_VALIDATED)
			|| (in_array('ordercard', $TContext) && $object->statut >= Commande::STATUS_VALIDATED)
			|| (in_array('expeditioncard', $TContext) && $object->statut >= Expedition::STATUS_VALIDATED)) {
			if(!empty($conf->global->XPOCONNECTOR_ENABLE_STACKBUILDER)) print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=stackbuilderdownload">' . $langs->trans('DowloadStackBuilderFile') . '</a>';
		}

		return 0;
	}
}
