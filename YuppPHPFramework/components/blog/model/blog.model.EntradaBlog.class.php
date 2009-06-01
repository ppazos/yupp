<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
 
YuppLoader :: load("blog.model", "Entrada"); // Si no esta me tira error de que no encuentra Entrada cuando hago un YuppLoader.loadModel.

class EntradaBlog extends Entrada
{
	protected $withTable = "entradas_blog";

	function __construct($args = array (), $isSimpleInstance = false)
	{
		$this->addAttribute("titulo", Datatypes :: TEXT);

      /*
      // Pruebas para generar columnas string de distinos tipos
		$this->addAttribute("aaa", Datatypes :: TEXT);
		$this->addAttribute("bbb", Datatypes :: TEXT);
		$this->addAttribute("ccc", Datatypes :: TEXT);
		$this->addAttribute("ddd", Datatypes :: TEXT);
      */
      
      // Titulo por defecto
		//$this->setTitulo("ingrese un titulo...");

		$this->addHasMany("comentarios", 'Comentario', PersistentObject::HASMANY_LIST);

		// Nueva forma de definir constraints
		$this->addConstraints("titulo", array (
			Constraint :: maxLength(24)
		));

      /*
		// Prueba de tipos de strings distintos dependiendo de su tamanio maximo.
		$this->addConstraints("aaa", array (
			Constraint :: maxLength(100),        // VARCHAR(100) en MySQL
			Constraint :: nullable(true)
		));

		$this->addConstraints("bbb", array (
			Constraint :: maxLength(300),        // TEXT en MySQL
			Constraint :: nullable(true)
		));

		$this->addConstraints("ccc", array (
			Constraint :: maxLength(pow(2, 18)), // MEDIUMTEXT en MySQL
			Constraint :: nullable(true)
		));

		$this->addConstraints("ddd", array (
			Constraint :: maxLength(pow(2, 25)), // LONGTEXT en MySQL
			Constraint :: nullable(true)
		));
      */
      
		parent :: __construct($args, $isSimpleInstance);
      
      // FIXME?: deberia hacer __construct del padre al principio para generar campos del padre y poder asignarle valores por defecto aca.
		// VERIFICAR QUE PROBLEMAS PUEDO TENER EN EL PROCESAMIENTO QUE HACE PO.construct
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