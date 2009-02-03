<?php

YuppLoader::load("cms.model", YuppCMSZone);

class YuppCMSPage extends TemplatePage
{
	//protected $withTable = "templatePages"; // si lo seteo en el contructor se setea para los hijos aunque se defina un wt para ellos (xq se llama en el constructor...)

	function __construct($args = array (), $isSimpleInstance = false)
	{
      // Definicion de campos
      $this->addAttribute("createdOn",      Datatypes :: DATETIME);
      $this->addAttribute("lastUpdate",     Datatypes :: DATETIME);
      
      
      // Definicion de relaciones
     
      // En realidad lo mas facil seria que una pagina tenga a todas sus 
      // paginas hijas y que el tipo de la coleccion sea lista asi se
      // inyecta automaticamente el ord (necesario para mostrar las
      // paginas en orden).
      $this->addHasOne("parentPage", YuppCMSPage); // Jerarquia de paginas
      $this->addHasOne("site", YuppCMSSite); // Sitio al que pertenece la pagina
      $this->addHasMany("zones", YuppCMSZone);
      
      
      // Inicializacion de campos
      $this->setCreatedOn(date("Y-m-d H:i:s")); // Ya con formato de MySQL!
      $this->setLastUpdate(date("Y-m-d H:i:s"));
      
      
      // Definicion de restriciones
		// ...
      // TODO: site: nullable(false)
      
      
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