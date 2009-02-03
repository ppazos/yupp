<?php

class YuppCMSZone extends TemplateZone
{
	protected $withTable = "yuppCMSZones"; // si lo seteo en el contructor se setea para los hijos aunque se defina un wt para ellos (xq se llama en el constructor...)

	function __construct($args = array (), $isSimpleInstance = false)
	{
      // Definicion de campos
      $this->addAttribute("isFloating",    Datatypes :: BOOLEAN); // para saber si tiene posicion absoluta (definida cuando se edita la zona) o si tiene una posicion fija (definida en el layout)
 
      
      // Definicion de relaciones
      $this->addHasMany("modules", YuppCMSModule, PersistentObject::HASMANY_LIST); // Importa el ord de los modules en la zona porque es el ordne en el que se van a mostrar
      
      
      // Inicializacion de campos
      $this->setIsFloating( false );


      // Definicion de restricciones
      // ...
		
      
      // Las zonas pertenecen a una pagina. (Page hasMany Zone)
      $this->belongsTo = array( YuppCMSPage );
      
      
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