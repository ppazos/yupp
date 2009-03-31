<?php

/**
 * Clase auxiliar para crear formularios en las vistas.
 */

// TODO: agregar campo html que incluya el tinymce como editor, y que los params que se le pasen 
// al campo, sean pasados al tinymce si se quiere customizar.

// FIXME: en lugar de hacer> $f->add(YuppFormField2::text(array(...))
//        hacer> $f->text(array(...))

class YuppForm2
{
   
	//private static $counter = 0;
	private $fields = array (); // Lista de campos o grupos del form.

	// Destino del form
	private $component;
	private $controller;
	private $action;
   
   private $isAjax; // True si se submitea el form por ajax.
   private $ajaxCallback; // Funcion JS que se llama al submitea via ajax el form.
   private $id;

   private static $counter = 0;

	public function __construct($component, $controller, $action, $isAjax = false, $ajaxCallback = '')
	{
		$this->component = $component;
		$this->controller = $controller;
		$this->action = $action;
      
      $this->isAjax = $isAjax;
      $this->ajaxCallback = $ajaxCallback;
      
      // Para ids autogenerados
      $c = self::$counter;
      self::$counter++;
      $this->id = "form_$c";
   }
   
   public function getId()
   {
      return $this->id;
   }
   
   public function getAjaxCallback()
   {
      return $this->ajaxCallback;
   }

   public function isAjax()
   {
      return $this->isAjax;
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
class YuppFormField2Group
{

	private $name;
	private $fields = array ();

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function add(YuppFormField2 $field)
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
class YuppFormField2
{
	private $label;
	private $type;
	private $args = array (); // Atributos particulares de cada campo.

	const TEXT     = 1; // input text
	const HIDDEN   = 2; // input hidden
	const BIGTEXT  = 3; // textaea
	const SELECTOR = 4; // select size>1 multiple?
	const DROPDOWN = 5; // select
	const RADIO    = 6; // input type = radio
	const CHECK    = 7; // input type = checkbox
	const SUBMIT   = 8; // input type = submit
   
   const DATE     = 9; // 3 selects dia mes anio.
   const PASSWORD = 10;

	private function __construct($type, $label = NULL)
	{
		$this->label = $label;
		$this->type = $type;
	}

   // FIXME: los params que agregue extra, que se pongan exactamente iguales a como estan en params en la tag como name=val name=val.
   public function set( $params )
   {
      $this->args = $params;
   }
	public function add($name, $value)
	{
		$this->args[$name] = $value;
	}
	public function get($name)
	{
		if (isset($this->args[$name]))
      {
         $val = $this->args[$name];
         unset($this->args[$name]); // Para que al final queden solo los que no se pidieron.
         return $val;
      }
      return NULL;
	}
   
   // Devuelve todos los params que haya en args, si se hizo get de algunos params, esos no van a estar!
   public function getAll()
   {
      return $this->args;
   }
   
   // Arma un string para poner en la tag los params que faltan tipo: par1="val1" par2="val2" ...
   public function getTagParams()
   {
      $r = " ";
      foreach ($this->args as $name => $value)
      {
         $r .= $name . '="' . $value . '" ';
      }
      return $r;
   }
   
   public static function date($params)
   {
      $f = new YuppFormField2(self::DATE, $params['label']);
      $f->set( $params );
      return $f;
   }
   
   public static function password($params)
   {
      $f = new YuppFormField2(self::PASSWORD, $params['label']);
      $f->set( $params );
      return $f;
   }

   public static function select( $params ) //($name, $action, $label = "")
   {
      $f = new YuppFormField2(self::DROPDOWN, $params['label']);
      $f->set( $params );
      //$f->add("name",    $params['name']);
      //$f->add("value",   $params['value']); // algun valor de options para que quede seleccionado.
      //$f->add("options", $params['options']);
      return $f;
   }

	public static function submit($params)
	{
		$f = new YuppFormField2(self::SUBMIT, $params['label']);
      $f->set( $params );
		//$f->add("name",   $params['name']);
		//$f->add("action", $params['action']);
		return $f;
	}

	public static function text($params)
	{
		$f = new YuppFormField2(self::TEXT, $params['label']);
      $f->set( $params );
		//$f->add("name",  $params['name']);
		//$f->add("value", $params['value']);
		return $f;
	}
   
   public static function bigtext($params)
   {
      $f = new YuppFormField2(self::BIGTEXT, $params['label']);
      $f->set( $params );
      //$f->add("name",  $params['name']);
      //$f->add("value", $params['value']);
      return $f;
   }
   
   public static function hidden($params)
   {
      $f = new YuppFormField2( self::HIDDEN );
      $f->set( $params );
      //$f->add("name",  $params['name']);
      //$f->add("value", $params['value']);
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
class YuppFormDisplay2
{
	/**
	 * 
	 */
	private static function displayField(YuppFormField2 $field)
	{
		// TODO: debe considerar el atributo "group" (es el atributo "name" del radio).

		$fieldHTML = '<div class="field_container">';

		switch ($field->getType())
		{
         case YuppFormField2 :: DATE :
         
            $name = $field->get("name");
         
            $fieldHTML .= '<div class="label">'. $field->getLabel() .'</div>';
            $fieldHTML .= '<div class="field">';
            $fieldHTML .= '<label for="day">D&iacute;a: </label>'; // TODO: i18n soportado por el framework.
            $fieldHTML .= '<select name="'.$name.'_day">';
            for ( $d=1; $d<32; $d++ )
            {
               if ( $d === $field->get("value_day") ) $fieldHTML .= '<option value="'. $d .'" selected="true">'. $d .'</option>';
               else $fieldHTML .= '<option value="'. $d .'">'. $d .'</option>';
            }
            $fieldHTML .= '</select>';
            
            $fieldHTML .= '<label for="month"> Mes: </label>'; // TODO: i18n soportado por el framework.
            $fieldHTML .= '<select name="'.$name.'_month">';
            for ( $m=1; $m<13; $m++ )
            {
               if ( $m === $field->get("value_month") ) $fieldHTML .= '<option value="'. $m .'" selected="true">'. $m .'</option>';
               else $fieldHTML .= '<option value="'. $m .'">'. $m .'</option>';
            }
            $fieldHTML .= '</select>';
            
            $fieldHTML .= '<label for="year"> A&ntilde;o: </label>'; // TODO: i18n soportado por el framework.
            $fieldHTML .= '<select name="'.$name.'_year">';
            for ( $y=1930; $y<2010; $y++ )
            {
               if ( $y === $field->get("value_year") ) $fieldHTML .= '<option value="'. $y .'" selected="true">'. $y .'</option>';
               else $fieldHTML .= '<option value="'. $y .'">'. $y .'</option>';
            }
            $fieldHTML .= '</select>';
            $fieldHTML .= '</div>';
         
         break;
			case YuppFormField2 :: TEXT :

            $name = $field->get("name");
            
            // Nombre obligatorio
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo TEXT." . __FILE__ . " " . __LINE__);
            
            $isReadOnly = $field->get("read-only"); // opcional
            $readOnly = ""; // $field->get("read-only") si viene es un bool
            
            if ( $isReadOnly !== NULL && $isReadOnly )
            {
               $readOnly = ' readonly="true" ';
            }
            
            $fieldHTML .= '<div class="label"><label for="'.$name.'">' . $field->getLabel() . '</label></div><div class="field">';
				$value = $field->get("value");
            $fieldHTML .= '<input type="text" name="'. $name .'" '. (($value)?'value="'. $value .'"':'') . $readOnly . $field->getTagParams() .' /></div>';

			break;
			case YuppFormField2 :: HIDDEN :
         
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo HIDDEN." . __FILE__ . " " . __LINE__);
            
            $value = $field->get("value");
            $fieldHTML .= '<input type="hidden" name="'. $name .'" '. (($value)?'value="'. $value .'"':'') . $field->getTagParams() .' />';
         
			break;
         case YuppFormField2 :: PASSWORD :
         
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo PASSWORD." . __FILE__ . " " . __LINE__);
            
            $value = $field->get("value");
            $fieldHTML .= '<input type="password" name="'. $name .'" '. (($value)?'value="'. $value .'"':'') . $field->getTagParams() .' />';
         
         break;
			case YuppFormField2 :: BIGTEXT :
         
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo BIGTEXT." . __FILE__ . " " . __LINE__);
            
            $readOnly = ""; // $field->get("read-only") si viene es un bool
            if ( $field->get("read-only") !== NULL && $field->get("read-only") )
            {
               $fieldHTML .= '<div class="label"><label for="'.$name.'">'. $field->getLabel() .'</label></div><div class="field">';
               $fieldHTML .= $field->get("value").'</div>';
            }
            else
            {
               $fieldHTML .= '<div class="label"><label for="'.$name.'">'. $field->getLabel() .'</label></div><div class="field">';
               $value = $field->get("value");
               $fieldHTML .= '<textarea name="'. $name .'"'. $field->getTagParams() .'>'. (($value)?$value:'') .'</textarea></div>';
            }
            
			break;
			case YuppFormField2 :: SELECTOR :
         
           // TODO
         
			break;
			case YuppFormField2 :: DROPDOWN :
         
            $name = $field->get("name"); // FIXME: verificar que es obligatorio
            $fieldHTML .= '<div class="label"><label for="'.$name.'">' . $field->getLabel() . '</label></div><div class="field">';

            $fieldHTML .= '<select name="'. $name .'"'. $field->getTagParams() .'>';
            foreach ( $field->get("options") as $value => $text )
            {
               if ( $value === $field->get("value") )
                  $fieldHTML .= '<option value="'. $value .'" selected="true">'. $text .'</option>';
               else
                  $fieldHTML .= '<option value="'. $value .'">'. $text .'</option>';
            }
            $fieldHTML .= '</select></div>';
         
			break;
			case YuppFormField2 :: RADIO :
         
            // TODO
         
			break;
			case YuppFormField2 :: CHECK :
         
            // TODO
         
			break;
			case YuppFormField2 :: SUBMIT :

            $action = $field->get("action"); // El get borra el param...
            $name   = $field->get("name");
            
            // no tiene label for, la label es el texto del boton de submit.
            $fieldHTML .= '<div class="field">';
            
            // Si no hay name, DEBE haber action.
            if ($name === NULL || $name === "") $name = '_action_'.$action;
            if ($name === NULL)
               throw new Exception("Uno de los argumentos 'name' o 'action' es obligatorio para el campo SUBMIT." . __FILE__ . " " . __LINE__);
            
            // si no tiene name, se le pone action.
            // "value="'. $field->getLabel()
				$fieldHTML .= '<input type="submit" name="'. $name .'" value="'. $field->getLabel() .'" '. $field->getTagParams() .' /></div>';

			break;
			default :
			break;
		}

		$fieldHTML .= '</div>';

		return $fieldHTML;
	}

	/**
	 * 
	 */
	private static function displayGroup(YuppFormField2Group $group)
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
	public static function displayForm(YuppForm2 $form)
	{
      $formHTML = "";
		if ( $form->isAjax() )
      {
          $formHTML .= h('js', array('component'=>'portal', 'name'=>'jquery/jquery-1.3.1.min'));
          $formHTML .= h('js', array('component'=>'portal', 'name'=>'jquery/jquery.form.2_18'));
       
          $formHTML .= '<script type="text/javascript">'. 
                       '$(document).ready(function() { '.
                       '  $(\'#'. $form->getId() .'\').ajaxForm(function() {'. 
                       //alert("Thank you for your comment!"); // TODO: pasar algun nombre de funcion JS para que se llame aca.
                       $form->getAjaxCallback() . '();'. 
                       '  });'. 
                       '});'.
                       '</script>';
      }

		$formHTML .= '<form action="'. $form->getUrl() .'" id="'. $form->getId() .'">';
      $fieldsOrGroups = $form->get();
		foreach ($fieldsOrGroups as $fieldOrGroup)
		{
			if ($fieldOrGroup instanceof YuppFormField2)
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