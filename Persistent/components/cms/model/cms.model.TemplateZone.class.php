<?php

class TemplateZone extends PersistentObject
{
	protected $withTable = "templateZones"; // si lo seteo en el contructor se setea para los hijos aunque se defina un wt para ellos (xq se llama en el constructor...)

	function __construct($args = array (), $isSimpleInstance = false)
	{
      // Definicion de campos
      $this->addAttribute("name",           Datatypes :: TEXT);       // nombre para referirse a la zona
      $this->addAttribute("posX",           Datatypes :: INT_NUMBER); // ubicacion en px
      $this->addAttribute("posY",           Datatypes :: INT_NUMBER); // ubicacion en px
      $this->addAttribute("width",          Datatypes :: INT_NUMBER); // ancho en px
      $this->addAttribute("height",         Datatypes :: INT_NUMBER); // alto en px
      
      
      // Definicion de relaciones
      // ...
      
      
      // Inicializacion de campos
      $this->setWidth( 300 );
      $this->setHeight( 300 );


      // Definicion de restricciones
		$this->addConstraints("texto", array (
			Constraint :: minLength(10),
			Constraint :: maxLength(1000), // TODO: Para strings grandes deberia generar un campo textearea en la web.
			Constraint :: blank(false)
		));
      
      // Si el tamanio es menos de 20x20 no se va a ver nada (esto es para prevenir accidentes al redimencionar, para que no desaparezca la zona y se quede por lo menos de 20x20)
      $this->addConstraints("width", array (
         Constraint :: min(20)
      ));
      $this->addConstraints("height", array (
         Constraint :: min(20)
      ));
		
      
      // Las zonas pertenecen a una pagina. (Page hasMany Zone)
      $this->belongsTo = array( TemplatePage );
      
      
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