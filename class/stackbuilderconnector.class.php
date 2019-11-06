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

if(!class_exists('SeedObject')) {
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__) . '/../config.php';
}


class StackBuilderConnector extends SeedObject
{
	public static function generateXML($object) {
		global $conf, $langs;
		dol_include_once('/core/lib/files.lib.php');
		$langs->load('xpoconnector@xpoconnector');
		$upload_dir = '';
		$qtyColis = 0;
		if($object->element == 'propal') $upload_dir = $conf->propal->multidir_output[$object->entity] . '/' . dol_sanitizeFileName($object->ref);
		elseif($object->element == 'commande') $upload_dir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($object->ref);
		elseif($object->element == 'shipping') $upload_dir = $conf->expedition->dir_output . "/sending/" . dol_sanitizeFileName($object->ref);
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="ISO-8859-1"?><STACKBUILDER/>');
		if(!empty($object->lines)) {
			$compteur = 0;
			foreach($object->lines as $line) {
				$qtyLeft = $line->qty;
				if(!empty($line->fk_product)) {
					$line->fetch_product();
					if($line->product->type != Product::TYPE_PRODUCT) continue;
					if(!empty($line->product->array_options['options_prod_per_col']) && !empty($line->qty)) $qtyColis = ceil(floatval($line->qty) / floatval($line->product->array_options['options_prod_per_col']));
					elseif(empty($line->product->array_options['options_prod_per_col'])) {
						setEventMessage($langs->trans('MissingProdPerCol', $line->product->ref), 'errors');
						return false;
					}
					elseif(empty($line->qty)) {
						setEventMessage($langs->trans('MissingQty', $line->product->ref), 'errors');
						return false;
					}
					for($i = 0; $i<$qtyColis; $i++) { //Autant de ligne que de colis
						$poidsBrut = 0;
						$hauteur = 0;
						$longueur = 0;
						$largeur = 0;
						$colis = $xml->addChild('Colis');
						$colis->addChild('NumeroUC', $compteur);
						$colis->addChild('RefArticle', $line->product->ref);

						if(!empty($line->product->array_options['options_xpo_uc_code'])) {
							$packageType = new XPOPackageType($object->db);
							$packageType->fetch($line->product->array_options['options_xpo_uc_code']);
							$poidsAVideColis = $packageType->unladen_weight;
							if($i == $qtyColis-1) { //Pour la dernière ligne, traitement spécifique dans le cas où la quantité n'est pas multiple du nb de produit par col on prend la quantité restante
								$poidsBrut = $qtyLeft * $line->product->weight + $poidsAVideColis;
							} else $poidsBrut = $line->product->array_options["options_prod_per_col"] * $line->product->weight + $poidsAVideColis;
							$hauteur = $packageType->height;
							$longueur = $packageType->length;
							$largeur = $packageType->width;
						} else {
							setEventMessage($langs->trans('MissingUcCode',$line->product->ref), 'errors');
							return false;
						}
						$colis->addChild('HauteurColis',$hauteur);
						$colis->addChild('LongueurColis',$longueur);
						$colis->addChild('LargeurColis',$largeur);
						$colis->addChild('PoidsBrutColis',$poidsBrut);

						$qtyLeft -= $line->product->array_options["options_prod_per_col"];
						$compteur++;
					}
				}
			}
		} else {
			return false;
		}
		$filename = $upload_dir . '/stackbuilder-' . $object->ref . '.xml';
		if(!dol_is_dir($upload_dir)) dol_mkdir($upload_dir);
		if(!file_exists($filename)) fopen($filename, "w");
		$xml->asXML($filename);
		return $filename;
	}
}
