<?php


/**
 * Clase auxiliar para crear formularios en las vistas.
 */

class YuppForm
{
   
	//private static $counter = 0;
	private $fields = array (); // Lista de campos o grupos del form.

	// Destino del form
	private $component;
	private $controller;
	private $action;

	public function __construct($component, $controller, $action)
	{
		$this->component = $component;
		$this->controller = $controller;
		$this->action = $action;
	}

	/**
	 * Depende de los helpers. Si se utiliza YuppForm por fuera de Yupp 
	 * se deberia especificar alguna forma de crear la url correcta 
	 * segun el sistema.
	 */
	public function getUrl()
	{
		return Helpers :: url(array (
			"component" => $this->component,
			"controller" => $this->controller,
			"action" => $this->action
		));
	}

	public function add($fieldOrGroup)
	{
		$this->fields[] = $fieldOrGroup;
      return $this;
	}
	public function get()
	{
		return $this->fields;
	}
}

/** 
 * Se usa para agrupar checkboxes o radios buttons.
 */
class YuppFormFieldGroup
{

	private $name;
	private $fields = array ();

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function add(YuppFormField $field)
	{
		$field->add("group", $this->name);
		$this->fields[] = $field;
	}

	public function get()
	{
		return $this->fields;
	}
}

/**
 * Campos simples.
 */
class YuppFormField
{

	private $label;
	private $type;
	private $args = array (); // Atributos particulares de cada campo.

	const TEXT = 1; // input text
	const HIDDEN = 2; // input hidden
	const BIGTEXT = 3; // textaea
	const SELECTOR = 4; // select size>1 multiple?
	const DROPDOWN = 5; // select
	const RADIO = 6; // input type = radio
	const CHECK = 7; // input type = checkbox
	const SUBMIT = 8; // input type = submit

	private function __construct($type, $label = NULL)
	{
		$this->label = $label;
		$this->type = $type;
	}

	public function add($name, $value)
	{
		$this->args[$name] = $value;
	}
	public function get($name)
	{
		return $this->args[$name];
	}

	public static function submit($name, $action, $label = "")
	{
		$f = new YuppFormField(self::SUBMIT, $label);
		$f->add("name", $name);
		$f->add("action", $action);
		return $f;
	}

	public static function text($name, $value = "", $label = "")
	{
		$f = new YuppFormField(self::TEXT, $label);
		$f->add("name", $name);
		$f->add("value", $value);
		return $f;
	}
   
   public static function bigtext($name, $value = "", $label = "")
   {
      $f = new YuppFormField(self::BIGTEXT, $label);
      $f->add("name", $name);
      $f->add("value", $value);
      return $f;
   }
   
   public static function hidden($name, $value = "")
   {
      $f = new YuppFormField( self::HIDDEN );
      $f->add("name", $name);
      $f->add("value", $value);
      return $f;
   }

	public function getType()
	{
		return $this->type;
	}

	public function getLabel()
	{
		return $this->label;
	}
}

/**
 * Clase para definir una forma de mostrar el formulario.
 */
class YuppFormDisplay
{

	/**
	 * 
	 */
	private static function displayField(YuppFormField $field)
	{
		// TODO: debe considerar el atributo "group" (es el atributo "name" del radio).

		$fieldHTML = '<div class="field_container">';

		switch ($field->getType())
		{
			case YuppFormField :: TEXT :

            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo TEXT." . __FILE__ . " " . __LINE__);
            
            $fieldHTML .= '<div class="label"><label for="'.$name.'">' . $field->getLabel() . '</label></div><div class="field">';
				$value = $field->get("value");
            $fieldHTML .= '<input type="text" name="'. $name .'" '. (($value)?'value="'. $value .'"':'') .' />';

			break;
			case YuppFormField :: HIDDEN :
         
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo HIDDEN." . __FILE__ . " " . __LINE__);
            
            $value = $field->get("value");
            $fieldHTML .= '<input type="hidden" name="'. $name .'" '. (($value)?'value="'. $value .'"':'') .' />';
         
			break;
			case YuppFormField :: BIGTEXT :
         
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo BIGTEXT." . __FILE__ . " " . __LINE__);
            
            $fieldHTML .= '<div class="label"><label for="'.$name.'">' . $field->getLabel() . '</label></div><div class="field">';
            $value = $field->get("value");
            $fieldHTML .= '<textarea name="'. $name .'">'. (($value)?$value:'') .'</textarea>';
         
			break;
			case YuppFormField :: SELECTOR :
			break;
			case YuppFormField :: DROPDOWN :
			break;
			case YuppFormField :: RADIO :
			break;
			case YuppFormField :: CHECK :
			break;
			case YuppFormField :: SUBMIT : // TODO

            // no tiene label for, la label es el texto del boton de submit.
            $fieldHTML .= '<div class="field">';
            $name = $field->get("name");
            if ($name === NULL || $name === "") $name = '_action_'.$field->get("action");
            if ($name === NULL)
               throw new Exception("Uno de los argumentos 'name' o 'action' es obligatorio para el campo SUBMIT." . __FILE__ . " " . __LINE__);
            
            // si no tiene name, se le pone action.
            // "value="'. $field->getLabel()
				$fieldHTML .= '<input type="submit" name="'. $name .'" value="'. $field->getLabel()  .'" />';

			break;
			default :
			break;
		}

		$fieldHTML .= '</div></div>';

		return $fieldHTML;
	}

	/**
	 * 
	 */
	private static function displayGroup(YuppFormFieldGroup $group)
	{
		$groupHTML = '<span class="group">';
		$fieldsOrGroups = $form->get();
		foreach ($fieldsOrGroups as $fieldOrGroup)
		{
			$groupHTML .= self::displayField($fieldOrGroup);
		}

		return $groupHTML . '</span>';
	}

	/**
	 * 
	 */
	public static function displayForm(YuppForm $form)
	{
		$fieldsOrGroups = $form->get();

		$formHTML = '<form action="'. $form->getUrl() .'">';

		foreach ($fieldsOrGroups as $fieldOrGroup)
		{
			if ($fieldOrGroup instanceof YuppFormField)
			{
				$formHTML .= self::displayField($fieldOrGroup);
			}
			else
			{
				$formHTML .= self::displayGroup($fieldOrGroup);
			}
		}

		$formHTML .= '</form>';
      
      echo $formHTML;
	}

}
?>