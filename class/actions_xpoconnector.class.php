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
     * @var DoliDb		Database handler (result of a new DoliDB)
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
     * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		if (in_array('productcard', explode(':', $parameters['context'])) && $action=='regenerateXPO')
		{
			dol_include_once('/xpoconnector/class/xpoconnector.class.php');
			dol_include_once('/xpoconnector/class/xpopackagetype.class.php');
			XPOConnectorProduct::send($object);
		}
		if (in_array('ordersuppliercard', explode(':', $parameters['context'])) && $action=='regenerateXPO')
		{
			dol_include_once('/xpoconnector/class/xpoconnector.class.php');
			XPOConnectorSupplierOrder::send($object);
		}

		return 0;
	}
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf;
		$langs->load('xpoconnector@xpoconnector');
		if (in_array('productcard', explode(':', $parameters['context'])) && !empty($conf->global->XPOCONNECTOR_ENABLE_PRODUCT))
		{
			print '<a class="butAction" href="/dolibarr/acobal/dolibarr/htdocs/product/card.php?action=regenerateXPO&id='.$object->id.'">'.$langs->trans('ResendXPOFile').'</a>';
		}
		if (in_array('ordersuppliercard', explode(':', $parameters['context'])) && !empty($conf->global->XPOCONNECTOR_ENABLE_SUPPLIERORDER) && $object->statut >= CommandeFournisseur::STATUS_ORDERSENT )
		{
			print '<a class="butAction" href="/dolibarr/acobal/dolibarr/htdocs/fourn/commande/card.php?action=regenerateXPO&id='.$object->id.'">'.$langs->trans('ResendXPOFile').'</a>';
		}

		return 0;
	}
}
