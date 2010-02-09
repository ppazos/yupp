<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */

class Entrada extends PersistentObject
{
	function __construct($args = array (), $isSimpleInstance = false)
	{
      $this->setWithTable("entradas");
      
		$this->addAttribute("texto", Datatypes :: TEXT);
		$this->addAttribute("fecha", Datatypes :: DATETIME);

		$this->setFecha(date("Y-m-d H:i:s")); // Ya con formato de MySQL!

		$this->addHasOne("usuario", "Usuario"); // Usuario que hizo la entrada		

		$this->addConstraints("texto", array (
			Constraint :: minLength(10),
			Constraint :: maxLength(1000), // TODO: Para strings grandes deberia generar un campo textearea en la web.
			Constraint :: blank(false)
		));

		// Por ahora el usuario no se usa.
		//"usuario" => array(
		//               Constraint::nullable(false)
		//            )

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