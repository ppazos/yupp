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
   // FIXME: el method del form deberia ser un parametro. (post, get, etc)
   
   //private static $counter = 0;
   private $fields = array (); // Lista de campos o grupos del form.

   // Destino del form en partes.
   private $app;
   private $controller;
   private $action;
   
   // Destino del form url armada
   private $action_url = NULL; // Se inicializa en NULL para verificar si se usa este o el destino en partes.
   
   private $method = "post"; // Metodo para enviar el form
   
   private $isAjax; // True si se submitea el form por ajax.
   private $ajaxCallback; // Funcion JS que se llama al submitea via ajax el form.
   private $id;

   private static $counter = 0;
   private $hasFileFields = false; // True si se incluye algun campo de tipo FILE.

   /**
    * Crea una nueva instancia de un formulario.
    * 
    * @param array $params lista de parametros con nombre, los parametros posibles son:
    * 
    *   - string  actionUrl: direccion de destino del formulario.
    *   - string  app: 
    *   - string  controller: 
    *   - string  action: 
    *   - boolean isAjax: indica si al enviar el formulario se utiliza ajax o no.
    *   - string  ajaxCallback: nombre de la funcion javascript que es invocada cuando el
    *                           formulario termina de ser enviado via ajax, solo tiene
    *                           sentido darle un valor si $isAjax es true.
    *
    */
   //public function __construct($app, $controller, $action, $isAjax = false, $ajaxCallback = '')
   public function __construct( $params )
   {
      if (!is_array($params)) throw new Exception("Error: 'params' debe ser un array. " . __FILE__ ." ". __LINE__);
      
      if ( isset($params['actionUrl']) )
      {
         $this->action_url = $params['actionUrl'];
      }
      else
      {
         // Si no vienen los params de la url, los tomo del contexto
         // http://code.google.com/p/yupp/issues/detail?id=28
         
         $ctx = YuppContext::getInstance();
         if (isset($params['app']))
            $this->app = $params['app'];
         else
            $this->app = $ctx->getApp();
         
         if (isset($params['controller']))
            $this->controller = $params['controller'];
         else
            $this->controller = $ctx->getController();
         
         
         if (isset($params['action']))
            $this->action = $params['action'];
         else
            $this->action = $ctx->getAction();
      }
      
      if ( isset($params['method']) ) $this->method = $params['method'];
      else $this->method = 'get';
      
      if ( isset($params['isAjax']) )
      {
         $this->isAjax       = $params['isAjax'];
         $this->ajaxCallback = $params['ajaxCallback'];
      }
      
      // Para ids autogenerados
      $c = self::$counter;
      self::$counter++;
      $this->id = "form_$c";
   }
   
   public function getId() { return $this->id; }
   public function hasFileFields() { return $this->hasFileFields; }
   public function getAjaxCallback() { return $this->ajaxCallback; }
   public function isAjax() { return $this->isAjax; }
   public function get() { return $this->fields; }
   public function getMethod() { return $this->method; }

   /**
    * Depende de los helpers. Si se utiliza YuppForm por fuera de Yupp 
    * se deberia especificar alguna forma de crear la url correcta 
    * segun el sistema.
    */
   public function getUrl()
   {
      if ( is_null($this->action_url) )
      {
         return Helpers :: url(array (
            "app" => $this->app,
            "controller" => $this->controller,
            "action" => $this->action
         ));
      }

      return $this->action_url;
   }

   public function add($fieldOrGroup)
   {
      if ( $fieldOrGroup->isFile() ) $this->hasFileFields = true;

      $this->fields[] = $fieldOrGroup;
      return $this;
   }
   
   // Constructores de campos
   // TODO: crear campo bigtext pero HTML.
   public static function text( $params )
   {
      return YuppFormField2::text($params);
   }
   
   public static function bigtext( $params )
   {
      return YuppFormField2::bigtext($params);
   }
   
   public static function hidden( $params )
   {
      return YuppFormField2::hidden($params);
   }
   
   public static function password( $params )
   {
      return YuppFormField2::password($params);
   }
   
   public static function date( $params )
   {
      return YuppFormField2::date($params);
   }
   
   public static function select( $params )
   {
      return YuppFormField2::select($params);
   }
   
   public static function submit( $params )
   {
      return YuppFormField2::submit($params);
   }
   
   public static function radio( $params )
   {
      return YuppFormField2::radio($params);
   }
   
   public static function check( $params )
   {
      return YuppFormField2::check($params);
   }
   
   public static function file( $params )
   {
      return YuppFormField2::file($params);
   }
}

/** 
 * Se usa para agrupar checkboxes o radios buttons.
 */
class YuppFormField2Group
{
   public $fieldNumber; // Auxiliar para mostrar los campos del grupo
   private $name;
   private $fields = array ();

   public function __construct($name) { $this->name = $name; }
   
   public function getName() { return $this->name; }

   public function add(YuppFormField2 $field)
   {
      $field->add("group", $this->name);
      $this->fields[] = $field;
      return $this;
   }

   public function get() { return $this->fields; }
   
   // Este metodo se necesita para el add de Form que pregunta al campo si es file (para que group tenga la misma interfaz que field)
   public function isFile() { return false; }
}

/**
 * Campos simples.
 */
class YuppFormField2
{
   public $fieldNumber; // Auxiliar para mostrar el campo
   
   private $label;
   private $type;
   private $args = array (); // Atributos particulares de cada campo.

   const TEXT     = 1; // input text
   const HIDDEN   = 2; // input hidden
   const BIGTEXT  = 3; // textaea
   //const SELECTOR = 4; // select size>1 multiple? // No senecesario, se hace con select y parametros size y multiple.
   const SELECT = 4; // select
   const RADIO    = 5; // input type = radio
   const CHECK    = 6; // input type = checkbox
   const SUBMIT   = 7; // input type = submit
   const DATE     = 8; // 3 selects dia mes anio.
   const PASSWORD = 9;
   const FILE     = 10; // Recordar que si hay archivos para subir el form debe tener el atributo: enctype="multiplart/form-data"

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
      foreach ($this->args as $name => $value) $r .= $name .'="'. $value .'" ';
      return $r;
   }
 
   public function getType()
   {
      return $this->type;
   }

   public function isFile()
   {
      return $this->type === self::FILE;
   }

   public function getLabel()
   {
      return $this->label;
   }
   
   public static function date($params)
   {
      $label = ( (isset($params['label'])) ? $params['label'] : '' );
      unset($params['label']); // para que label no aparezca en la lista de params.
      $f = new YuppFormField2(self::DATE, $label);
      $f->set( $params );
      return $f;
   }
   
   public static function password($params)
   {
      $label = ( (isset($params['label'])) ? $params['label'] : '' );
      unset($params['label']); // para que label no aparezca en la lista de params.
      $f = new YuppFormField2(self::PASSWORD, $label);
      $f->set( $params );
      return $f;
   }

   public static function select( $params ) //($name, $action, $label = "")
   {
      $label = ( (isset($params['label'])) ? $params['label'] : '' );
      unset($params['label']); // para que label no aparezca en la lista de params.
      $f = new YuppFormField2(self::SELECT, $label);
      $f->set( $params );
      return $f;
   }

   public static function submit($params)
   {
      $label = ( (isset($params['label'])) ? $params['label'] : '' );
      unset($params['label']); // para que label no aparezca en la lista de params.
      $f = new YuppFormField2(self::SUBMIT, $label);
      $f->set( $params );
      return $f;
   }

   public static function text($params)
   {
      $label = ( (isset($params['label'])) ? $params['label'] : '' );
      unset($params['label']); // para que label no aparezca en la lista de params.
      $f = new YuppFormField2(self::TEXT, $label);
      $f->set( $params );
      return $f;
   }
   
   public static function radio($params)
   {
      $label = ( (isset($params['label'])) ? $params['label'] : '' );
      unset($params['label']); // para que label no aparezca en la lista de params.
      $f = new YuppFormField2(self::RADIO, $label);
      $f->set( $params );
      return $f;
   }
   
   public static function check($params)
   {
      $label = ( (isset($params['label'])) ? $params['label'] : '' );
      unset($params['label']); // para que label no aparezca en la lista de params.
      $f = new YuppFormField2(self::CHECK, $label);
      $f->set( $params );
      return $f;
   }
   
   public static function bigtext($params)
   {
      $label = ( (isset($params['label'])) ? $params['label'] : '' );
      unset($params['label']); // para que label no aparezca en la lista de params.
      $f = new YuppFormField2(self::BIGTEXT, $label);
      $f->set( $params );
      return $f;
   }
   
   public static function hidden($params)
   {
      $f = new YuppFormField2( self::HIDDEN );
      $f->set( $params );
      return $f;
   }
   
   public static function file($params)
   {
      $label = ( (isset($params['label'])) ? $params['label'] : '' );
      unset($params['label']); // para que label no aparezca en la lista de params.
      $f = new YuppFormField2(self::FILE, $label);
      $f->set( $params );
      return $f;
   }

} // YuppFormField

/**
 * Clase para definir una forma de mostrar el formulario.
 * Se puede sobreescribir segun las necesidades de generacion de HTML.
 * La clase por defecto genera una DIV para cada etiqueta y cada campo,
 * para luego aplicarle CSS para ubicar los elementos como se requiera.
 */
class YuppFormDisplay2
{
   private static function displayField(YuppFormField2 $field)//, $fieldNumber)
   {
      $fieldNumber = $field->fieldNumber;
      // TODO: debe considerar el atributo "group" (es el atributo "name" del radio) ???

      $fieldHTML = '<div class="field_container">';

      switch ($field->getType())
      {
         case YuppFormField2 :: DATE :
         
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo DATE." . __FILE__ . " " . __LINE__);
         
            $fieldHTML .= '<div class="label date">'. $field->getLabel() .'</div>';
            $fieldHTML .= '<div class="field date">';
            $fieldHTML .= '<label for="day">D&iacute;a: </label>'; // TODO: i18n soportado por el framework.
            $fieldHTML .= '<select name="'.$name.'_day">';
            $day = $field->get("value_day");
            for ( $d=1; $d<32; $d++ )
            {
               if ( $d === $day ) $fieldHTML .= '<option value="'. $d .'" selected="true">'. $d .'</option>';
               else $fieldHTML .= '<option value="'. $d .'">'. $d .'</option>';
            }
            $fieldHTML .= '</select>';
            
            $fieldHTML .= '<label for="month"> Mes: </label>'; // TODO: i18n soportado por el framework.
            $fieldHTML .= '<select name="'.$name.'_month">';
            $month = $field->get("value_month");
            for ( $m=1; $m<13; $m++ )
            {
               if ( $m === $month ) $fieldHTML .= '<option value="'. $m .'" selected="true">'. $m .'</option>';
               else $fieldHTML .= '<option value="'. $m .'">'. $m .'</option>';
            }
            $fieldHTML .= '</select>';
            
            $fieldHTML .= '<label for="year"> A&ntilde;o: </label>'; // TODO: i18n soportado por el framework.
            $fieldHTML .= '<select name="'.$name.'_year">';
            $year = $field->get("value_year");
            for ( $y=1930; $y<2010; $y++ )
            {
               if ( $y === $year ) $fieldHTML .= '<option value="'. $y .'" selected="true">'. $y .'</option>';
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
            
            $value = $field->get("value");
            $isReadOnly = $field->get("read-only"); // opcional
            $readOnly = ""; // $field->get("read-only") si viene es un bool
            
            if ( $isReadOnly !== NULL && $isReadOnly )
            {
               $readOnly = ' readonly="true" ';
            }
            
            $fieldHTML .= '<div class="label text"><label for="'.$name.'">' . $field->getLabel() . '</label></div>';
            $fieldHTML .= '<div class="field text"><input type="text" name="'. $name .'" '. (($value)?'value="'. $value .'"':'') . $readOnly . $field->getTagParams() .' /></div>';

         break;
         case YuppFormField2 :: HIDDEN :
         
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo HIDDEN." . __FILE__ . " " . __LINE__);
            
            $value = $field->get("value");
            if ($value === NULL)
               throw new Exception("El argumento 'value' es obligatorio para el campo HIDDEN." . __FILE__ . " " . __LINE__);
            
            $fieldHTML .= '<input type="hidden" name="'. $name .'" '. (($value)?'value="'. $value .'"':'') . $field->getTagParams() .' />';
         
         break;
         case YuppFormField2 :: PASSWORD :
         
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo PASSWORD." . __FILE__ . " " . __LINE__);
            
            $value = $field->get("value");
            
            $fieldHTML .= '<div class="label password">'. $field->getLabel() .'</div>';
            $fieldHTML .= '<div class="field password"><input type="password" name="'. $name .'" '. (($value)?'value="'. $value .'"':'') . $field->getTagParams() .' /></div>';
         
         break;
         case YuppFormField2 :: BIGTEXT :
         
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo BIGTEXT." . __FILE__ . " " . __LINE__);
            
            $value = $field->get("value");
            
            $readOnly = ""; // $field->get("read-only") si viene es un bool
            if ( $field->get("read-only") !== NULL && $field->get("read-only") )
            {
               $fieldHTML .= '<div class="label bigtext"><label for="'.$name.'">'. $field->getLabel() .'</label></div>';
               $fieldHTML .= '<div class="field bigtext">'. $value .'</div>';
            }
            else
            {
               $fieldHTML .= '<div class="label bigtext"><label for="'.$name.'">'. $field->getLabel() .'</label></div>';
               $fieldHTML .= '<div class="field bigtext"><textarea name="'. $name .'"'. $field->getTagParams() .'>'. (($value)?$value:'') .'</textarea></div>';
            }
            
         break;
         case YuppFormField2 :: SELECT :
         
            // OJO, al hacer get de los params, estos se borran!.
            $name = $field->get("name");
            
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo SELECT." . __FILE__ . " " . __LINE__);
            
            $value = $field->get("value");
            $options = $field->get("options");
            
            $fieldHTML .= '<div class="label select"><label for="'.$name.'">' . $field->getLabel() . '</label></div>';
            $fieldHTML .= '<div class="field select"><select name="'. $name .'"'. $field->getTagParams() .'>';
            foreach ( $options as $opt_value => $text )
            {
               if ( $opt_value === $value )
                  $fieldHTML .= '<option value="'. $opt_value .'" selected="true">'. $text .'</option>';
               else
                  $fieldHTML .= '<option value="'. $opt_value .'">'. $text .'</option>';
            }
            $fieldHTML .= '</select></div>';
         
         break;
         case YuppFormField2 :: RADIO :
         
            // OJO, al hacer get de los params, estos se borran!.
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo RADIO." . __FILE__ . " " . __LINE__);
               
            $value = $field->get("value");
            // En check y radio, podria no pasarse un valor, lo que importa es que se seleccione o no.
            //if ($value === NULL)
            //   throw new Exception("El argumento 'value' es obligatorio para el campo RADIO." . __FILE__ . " " . __LINE__);
            
            $fieldHTML .= '<div class="label radio"><label for="radio_'. $fieldNumber .'">' . $field->getLabel() . '</label></div>';
            $fieldHTML .= '<div class="field radio"><input type="radio" id="radio_'. $fieldNumber .'" name="'. $name .'" '. (($value)?'value="'. $value .'"':'') . $field->getTagParams() .' /></div>';
         
         break;
         case YuppFormField2 :: CHECK :
         
            // OJO, al hacer get de los params, estos se borran!.
            $name = $field->get("name");
            if ($name === NULL)
               throw new Exception("El argumento 'name' es obligatorio para el campo CHECK." . __FILE__ . " " . __LINE__);
               
            $value = $field->get("value");
            // En check y radio, podria no pasarse un valor, lo que importa es que se seleccione o no.
            //if ($value === NULL)
            //   throw new Exception("El argumento 'value' es obligatorio para el campo CHECK." . __FILE__ . " " . __LINE__);

//echo "VALUE: $value<br/>";
//echo gettype($value);

            $fieldHTML .= '<div class="label check"><label for="checkbox_'. $fieldNumber .'">' . $field->getLabel() . '</label></div>';
            $fieldHTML .= '<div class="field check"><input type="checkbox" id="checkbox_'. $fieldNumber .'" name="'. $name .'" value="'. $value .'" '. (($field->get("on"))?'checked="true"':'') . $field->getTagParams() .' /></div>';
         
         break;
         case YuppFormField2 :: SUBMIT :

            $action = $field->get("action"); // El get borra el param...
            $name   = $field->get("name");

            // no tiene label for, la label es el texto del boton de submit.
            $fieldHTML .= '<div class="field submit">';
            
            // Si no hay name, DEBE haber action.
            if ($name === NULL || $name === "") $name = '_action_'.$action;
            if ($name === NULL)
               throw new Exception("Uno de los argumentos 'name' o 'action' es obligatorio para el campo SUBMIT." . __FILE__ . " " . __LINE__);
            
            // si no tiene name, se le pone action.
            // "value="'. $field->getLabel()
            $fieldHTML .= '<input type="submit" name="'. $name .'" value="'. $field->getLabel() .'" '. $field->getTagParams() .' /></div>';

         break;
         case YuppFormField2 :: FILE :

            $name = $field->get("name");
            if ($name === NULL) // Nombre obligatorio
               throw new Exception("El argumento 'name' es obligatorio para el campo TEXT." . __FILE__ . " " . __LINE__);
            
            $fieldHTML .= '<div class="label file"><label for="'.$name.'">' . $field->getLabel() . '</label></div>';
            $fieldHTML .= '<div class="field file"><input type="file" name="'. $name .'" '. $field->getTagParams() .' /></div>';

         break;
         default :
         break;
      }

      $fieldHTML .= '</div>';

      return $fieldHTML;
   }


   private static function displayGroup(YuppFormField2Group $group)//, &$fieldNumber)
   {
      $fieldNumber = $group->fieldNumber;
      $groupHTML = '<div class="group">';
      $groupHTML .= '<div class="label">' . $group->getName() . '</label></div>';
      
      $fields = $group->get();
      foreach ( $fields as $field )
      {
         $field->fieldNumber = $fieldNumber;
         //$groupHTML .= self::displayField($field, &$fieldNumber);
         $groupHTML .= self::displayField($field);
         $fieldNumber++;
      }

      // No necesario?
      $fieldNumber--; // por que en el metodo de afuera hace otra suma, asi no suma 2 veces.

      return $groupHTML . '</div>';
   }

   private static function display_ajax_form_prototype($form)
   {
      $html = h("js", array("name"=>"prototype_170") );
      $html .= '<script type="text/javascript">' .
               'Event.observe(window, "load", function() {'.
               '  Event.observe("'.$form->getId().'", "submit", function(event) {'.
               '    $("'.$form->getId().'").request({'.
               '      onSuccess: '. $form->getAjaxCallback() .
               '    });'.
               '    Event.stop(event); /* stop the form from submitting */'.
               '  })'.
               '});'.
               '</script>';
      
      return $html;
   }
   
   private static function display_ajax_form_jquery($form)
   {
      $html = '';
      $html .= h('js', array('name'=>'jquery/jquery-1.5.1.min'));
      $html .= h('js', array('name'=>'jquery/jquery.form.2_43'));
   
      // TODO: llamar a una funcion JS antes de hacer el request AJAX.
      // Dependencia con jQuery.
      //$formHTML .= '<script type="text/javascript">$(document).ready(function() { '.
      //             '$(\'#'. $form->getId() .'\').ajaxForm(function() {'. $form->getAjaxCallback() . '(); });'. 
      //             '});</script>';
      
      // http://jquery.malsup.com/form/#ajaxForm
      $html .= '<script type="text/javascript">$(document).ready(function() { '.
               '  var options = {'.
               '    success: '. $form->getAjaxCallback() .
               '  };'.
               '  $(\'#'. $form->getId() .'\').ajaxForm(options);'. 
               '  });' .
               '</script>';
                   
      return $html;
   }

   public static function displayForm(YuppForm2 $form)
   {
      $formHTML = "";
      if ( $form->isAjax() )
      {
         $lm = LayoutManager::getInstance();
         $jslib = $lm->getJSLib();
         if (is_null($jslib)) $jslib = 'prototype'; // Libreria por defecto.
         
         eval('$formHTML .= self::display_ajax_form_'.$jslib.'($form);');
         
         //$formHTML .= self::display_ajax_form_jquery($form);
         //$formHTML .= self::display_ajax_form_prototype($form);
         
         /*
          $formHTML .= h('js', array('name'=>'jquery/jquery-1.3.1.min'));
          $formHTML .= h('js', array('name'=>'jquery/jquery.form.2_18'));
       
          // TODO: llamar a una funcion JS antes de hacer el request AJAX.
          // Dependencia con jQuery.
          //$formHTML .= '<script type="text/javascript">$(document).ready(function() { '.
          //             '$(\'#'. $form->getId() .'\').ajaxForm(function() {'. $form->getAjaxCallback() . '(); });'. 
          //             '});</script>';
          
          // http://jquery.malsup.com/form/#ajaxForm
          $formHTML .= '<script type="text/javascript">$(document).ready(function() { '.
                       'var options = {
                          //beforeSubmit:  showRequest,  // pre-submit callback 
                          success:       '. $form->getAjaxCallback() .'  // post-submit callback 
                       };' .
                       '$(\'#'. $form->getId() .'\').ajaxForm(options);'. 
                       '});</script>';
         */
      }
      
      $fieldCount = 0;
      $formHTML .= '<form action="'. $form->getUrl() .'" '.
                   'id="'. $form->getId() .'" '.
                   'name="'. $form->getId() .'" '.
                   'method="'. $form->getMethod() .'" '.
                   (($form->hasFileFields())?'enctype="multipart/form-data"':'') .'>';
      
      $fieldsOrGroups = $form->get();
      foreach ($fieldsOrGroups as $fieldOrGroup)
      {
         $fieldCount++;
         $fieldOrGroup->fieldNumber = $fieldCount;
         
         if ($fieldOrGroup instanceof YuppFormField2)
         {
            //$formHTML .= self::displayField($fieldOrGroup, &$fieldCount);
            $formHTML .= self::displayField($fieldOrGroup);
         }
         else
         {
            //$formHTML .= self::displayGroup($fieldOrGroup, &$fieldCount);
            $formHTML .= self::displayGroup($fieldOrGroup);
         }
      }

      $formHTML .= '</form>';
      
      echo $formHTML;
   }

}
?>
