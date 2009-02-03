<?php

class TemplatePage extends PersistentObject
{
	protected $withTable = "templatePages"; // si lo seteo en el contructor se setea para los hijos aunque se defina un wt para ellos (xq se llama en el constructor...)

   const STATUS_NORMAL   = "normal";
   const STATUS_DRAFT    = "draft";
   const STATUS_HIDDEN   = "hidden";
   const STATUS_DISABLED = "disabled"; // esto tal vez sea lo mismo que hidden, hay que analizar bien si se necesitan ambas.

	function __construct($args = array (), $isSimpleInstance = false)
	{
      // Definicion de campos
      $this->addAttribute("name",           Datatypes :: TEXT); // se puede usar nomalizado en las urls (sirve para SEO)
      $this->addAttribute("title",          Datatypes :: TEXT); // i18n?
      $this->addAttribute("status",         Datatypes :: TEXT);
      $this->addAttribute("description",    Datatypes :: TEXT); // i18n? // string grande!
      $this->addAttribute("keywords",       Datatypes :: TEXT); // i18n? // string grande!
      $this->addAttribute("comments",       Datatypes :: TEXT); // para dejar comentarios que se ven solo al editar la pagina
      
      
      // Definicion de relaciones
      $this->addHasMany("templateZones", TemplateZone);
      
      
      // Inicializacion de campos
      $this->setStatus( self::STATUS_NORMAL ); // normal o draft?


      // Defiicion de restricciones
		$this->addConstraints("description", array (
			Constraint :: maxLength(1000)
		));
      $this->addConstraints("keywords", array (
         Constraint :: maxLength(1000)
      ));
      
      
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