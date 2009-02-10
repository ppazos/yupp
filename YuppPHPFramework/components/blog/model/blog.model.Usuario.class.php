<?php
class Usuario extends PersistentObject
{
	function __construct($args = array (), $isSimpleInstance = false)
	{
		$this->withTable = "usuarios";

		$this->addAttribute("nombre", Datatypes :: TEXT);
		$this->addAttribute("email", Datatypes :: TEXT);
		$this->addAttribute("clave", Datatypes :: TEXT);
		$this->addAttribute("edad", Datatypes :: INT_NUMBER);
		$this->addAttribute("fechaNacimiento", Datatypes :: DATE);

		$this->addHasMany("comentarios", Comentario);
		$this->addHasMany("entradas", EntradaBlog);

		$this->constraints = array (
			"nombre" => array (
				Constraint :: minLength(1),
				Constraint :: maxLength(30),
				Constraint :: blank(false)
			),
			"clave" => array (
				Constraint :: minLength(5)
			),
			"edad" => array (
				Constraint :: between(10, 100)
			),
			"email" => array (
				Constraint :: email(),
				//new EmailStartsWithName($this)
			)
		);

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

} // Usuario

// Custom validators:
/**
 * Verifica que el email empieza con el nombre del usuario en minuscula.
 * Por ejemplo, si se llama Pablo, el email debera ser: pabloXXXXX@dominio.com
 */
class EmailStartsWithName extends Constraint
{
	private $usuario;

	public function __construct(Usuario $u)
	{
		$this->usuario = $u;
	}

	public function evaluate($value)
	{
		// Como puse la constraint en el atributo email, $value sera el valor del email.
		return String :: startsWith($this->usuario->getEmail(), strtolower($this->usuario->getNombre()));
	}

	public function getValue()
	{
		return $this->usuario;
	}

	public function __toString()
	{
		return "EmailStartsWithName: [Usuario: " . $this->usuario->getId() . "]";
	}

} // EmailStartsWithName
?>