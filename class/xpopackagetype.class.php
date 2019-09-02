<?php

if (!class_exists('SeedObject'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call or for session timeout on our module page
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

class XPOPackageType extends SeedObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'c_xpo_package_type';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'xpo_package_type';

	/** @var int $isextrafieldmanaged Enable the fictionalises of extrafields */
	public $isextrafieldmanaged = 0;

	/** @var int $ismultientitymanaged 0=No test on entity, 1=Test with field entity, 2=Test with link by societe */
	public $ismultientitymanaged = 1;

	public $fields = array(

		'code' => array(
			'type' => 'varchar(50)',
			'length' => 50,
			'label' => 'Code',
			'enabled' => 1,
			'visible' => 1,
			'notnull' => 1,
			'index' => 1,
			'position' => 10,
		),

		'entity' => array(
			'type' => 'integer',
			'label' => 'Entity',
			'enabled' => 1,
			'visible' => 0,
			'default' => 1,
			'notnull' => 1,
			'index' => 1,
			'position' => 20
		),

		'active' => array(
			'type' => 'integer',
			'label' => 'Active',
			'enabled' => 1,
			'visible' => 0,
			'notnull' => 1,
			'default' => 0,
			'index' => 1,
			'position' => 30
		),

		'label' => array(
			'type' => 'varchar(255)',
			'label' => 'Label',
			'enabled' => 1,
			'visible' => 1,
			'position' => 40,
		),
		'unladen_weight' => array(
			'type' => 'double',
			'label' => 'UnladenWeight',
			'enabled' => 1,
			'visible' => 1,
			'position' => 50
		),
		'height' => array(
			'type' => 'double',
			'label' => 'Height',
			'enabled' => 1,
			'visible' => 1,
			'position' => 50
		),
		'length' => array(
			'type' => 'double',
			'label' => 'Length',
			'enabled' => 1,
			'visible' => 1,
			'position' => 50
		),
		'width' => array(
			'type' => 'double',
			'label' => 'Width',
			'enabled' => 1,
			'visible' => 1,
			'position' => 50
		)
	);
}
