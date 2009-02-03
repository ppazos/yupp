<?php

class YuppCMSSite extends PersistentObject
{
	protected $withTable = "sites"; // si lo seteo en el contructor se setea para los hijos aunque se defina un wt para ellos (xq se llama en el constructor...)

	function __construct($args = array (), $isSimpleInstance = false)
	{
      // Definicion de campos
      $this->addAttribute("name",         Datatypes :: TEXT); // i18n?
      $this->addAttribute("description",  Datatypes :: TEXT); // i18n? // string grande!
      $this->addAttribute("keywords",     Datatypes :: TEXT); // i18n? // string grande!
      
      
      // Definicion de relaciones
      $this->addHasMany("installedSkins", YuppCMSSkin);
      $this->addHasOne ("selectedSkin",   YuppCMSSkin);
      $this->addHasMany("pages",          YuppCMSPage);
      
      
      // Inicializacion de campos
      // ...
      

      // Defiicion de restricciones
		$this->addConstraints("description", array (
			Constraint :: maxLength(1000)
		));
      $this->addConstraints("keywords", array (
         Constraint :: maxLength(1000)
      ));
      // TODO: size(installedSkins>0)
      
      
		parent :: __construct($args, $isSimpleInstance);
	}

	public static function listAll($params)
	{
		self :: $thisClass = __CLASS__;
		return PersistentObject :: listAll($params);
	}

	public static function count()
	{
		self :: $thisClass = __CLASS__;
		return PersistentObject :: count();
	}

	public static function get($id)
	{
		self :: $thisClass = __CLASS__;
		return PersistentObject :: get($id);
	}

	public static function findBy(Condition $condition, $params)
	{
		self :: $thisClass = __CLASS__;
		return PersistentObject :: findBy($condition, $params);
	}

	public static function countBy(Condition $condition)
	{
		self :: $thisClass = __CLASS__;
		return PersistentObject :: countBy($condition);
	}
}
?>