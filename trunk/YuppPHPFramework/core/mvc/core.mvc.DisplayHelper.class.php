<?php

// shortcut a DisplayHelper::message
// http://code.google.com/p/yupp/issues/detail?id=6
// TODO: mover a un archivo de script 'shortcuts.script.php' y que el usuario deba incuirlo si quiere usar los shortcuts.
function msg( $key, $locale = NULL, $defaultMessage = "" )
{
   // TODO: agregar un tercer parametro para indicar si se hace o no echo del resultado.
   return DisplayHelper::message( $key, $locale = NULL, $defaultMessage = "" );
}

class DisplayHelper {

   public static function message( $key, $locale = NULL, $defaultMessage = "" )
   {
      // Si locale es NULL saca el locale de las variables de entorno.
      if ( $locale === NULL )
      {
         $ctx = YuppContext::getInstance();
         $locale = $ctx->getLocale(); // se que siempre hay un locale valido.
      } 

      YuppLoader::load('core.support', 'I18nMessage');
      $m = I18nMessage::getInstance();
      return $m->g( $key, $locale, $defaultMessage );
   }

/*
    public static function template( $app, $viewDir, $template, $params )
    {
        // Necesito buscar el template en /apps/$app/view/$viewDir/$template.template.php
       // Con los params que se me pasan, tengo que dejarlos accesibles para el script en ese template.
       
       // El primero lo cargaria con el class loader.
       // El pasaje de parametros creo que con solo declarar las variables funciona, pero hay q ver. tal vez con variables variables.
       
       // Talvez quisiera no pasarle app ni viewDir, app lo saco del request y viewDir seria el nombre del controller (por defecto).
    }
*/

   public static function errors( $po )
   {
      if ($po === NULL) return;
      if (($ers = $po->getErrors()) != NULL && $ers->hasErrors())
      {
         echo "<ul>";
         foreach ( $ers as $attr => $errors )
         {
            echo "<li>";
            echo $attr;
            echo "<ul>";
            foreach ( $errors as $error )
            {
               echo "<li>" . $error . "</li>";
            }
            echo "</ul>";
            echo "</li>";
         }
         echo "</ul>";
      }
   }
    
   public static function fieldErrors($po, $attr)
   {
      if ($po === NULL) return;

      $res = '';
      if ($po->getErrors()->hasFieldErrors($attr))
      {
         $errors = $po->getErrors()->getFieldErrors($attr);
         $res .= '<ul>';
         foreach ( $errors as $error )
         {
            $res .= '<li>' . $error . '</li>';
         }
         $res .= '</ul>';
      }
      return $res;
   }

   /**
    * @param $pos es el PO a mostrar o una lista de POs si el mode es "list"
    * @param $mode es un de list, show, edit.
    * @param $clazz para el mode=list necesito saber de que clase son los elementos en la lista, porque si la lista es vacia solo muestro la primer fila con nombres de atributos.
    */
   public static function model( $pos, $mode, $clazz = NULL )
   {
      switch ($mode)
      {
         case "list": return self::display_list( $pos, $clazz ); // FIXME: clazz != NULL
         break;
         case "show": return self::display_show( $pos );
         break;
         case "edit": return self::display_edit( $pos );
         break;
      }
   }

   private static function display_list( $pos, $clazz )
   {
      $ins = new $clazz(); // Instancia para saber nombres de columnas...
      $attrs = $ins->getAttributeTypes();
      $res = '<table>';

      $ctx = YuppContext::getInstance();
      $m = Model::getInstance();
      $app = $m->get('app'); // Cuando se genera por la ap "core", viene "app" como parametro.
      if ( !isset($app) ) $app = $ctx->getApp(); // Cuando se genera por una app que no es "core"

      // Cabezal
      $res .= '<tr>';
      foreach ($attrs as $attr => $type )
      {
         // No quiero mostrar la columna 'deleted'
         if ( $attr === 'deleted') continue;

         $res .= '<th>';
         $res .= h('orderBy', array('attr'=>$attr, 'action'=>$ctx->getAction(), 'body'=>$attr, 'params'=>array('app'=>$app,'class'=>$m->get('class'))));
         $res .= '</th>';
      }
      $res .= '</tr>';

      YuppLoader::load('core.app', 'App');
      $theApp = new App($app);
       
      // Filas
      foreach ($pos as $po) // pos puede ser vacio...
      {
         $res .= '<tr>';
         foreach ( $attrs as $attr => $type )
         {
            // No quiero mostrar la columna 'deleted'
            if ( $attr === 'deleted') continue;
           
            $res .= '<td>';
            if ($attr == "id")
            {
               // Si en la aplicacion actual existe el controlador para esta clase de dominio, que vaya a la aplicacion actual y a ese controller.
               // Si no, va a la app y controller "core".
               if ($theApp->hasController($po->aGet('class')))
                  $res .= '<a href="'. h('url',
                      array('app'    => $app,
                            'controller' => String::firstToLower( $po->aGet('class') ),
                            'action' => 'show',
                            'class'  => $po->aGet('class'),
                            'id'     => $po->aGet($attr),
                            'params' => array('app'=>$app))) .'">'. $po->aGet($attr) .'</a>';
               else
                  $res .= '<a href="'. h('url',
                      array('app'    => 'core',
                            'controller' => 'core',
                            'action' => 'show',
                            'class'  => $po->aGet('class'),
                            'id'     => $po->aGet($attr),
                            'params' => array('app'=>$app))) .'">'. $po->aGet($attr) .'</a>';
            }
            else
            {
               $res .= $po->aGet($attr);
            }
            $res .= '</td>';
         }
         $res .= '</tr>';
      }
      $res .= '</table>';

      return $res;
   }

   /**
    * Devuelve HTML para edicion de un objeto como una tabla con 2 columnas, la primera de nombres de campos la segunda con campos con valores para modificar.
    */
   private static function display_edit( PersistentObject $po )
   {
      $res = '<table>';
      $attrs = $po->getAttributeTypes();
      foreach ( $attrs as $attr => $type )
      {
         // Los atributos inyectados no se deberian poder editar!
         $res .= '<tr><td>';
         $res .= $attr; // TODO: Habria que ver si esto es i18n, deberia haber algun "display name" asociado al nombre del campo.
         $res .= '</td><td>';

         if ( $po->isInyectedAttribute( $attr )) // Solo lo muestro, no lo dejo editar. DEberi llamara field_to de show...
         {
            //$res .= $po->aGet($attr);
            $res .= self::field_to_html_show( $attr, $type, $po->aGet($attr) );
         }
         else if (DatabaseNormalization::isSimpleAssocName($attr)) // http://code.google.com/p/yupp/issues/detail?id=105
         {
            // Si es un fk a un id de un hasOne, quiero mostrar una lista de los posibles ids
            // de la clase de la relacion HO que le puedo asignar, y como esto es create o edit,
            // si tiene un valor actual, deberia quedar seleccionado en el select.

            $currentValue = $po->aGet($attr); // Puede ser NULL

            $role = DatabaseNormalization::getSimpleAssocName($attr); // email_id -> email
            $relClass = $po->getType($role); // Clase de la relacion HO

            // Objetos que puedo tener relacionadoss
            // Se puede en PHP 5.3.0...
            //$list = $relClass::listAll(new ArrayObject()); // Objetos que podria tener asociados
            // ... pero por las dudas ...
            $list = call_user_func_array (array($relClass, 'listAll'), array(new ArrayObject()));

            $select = '<select name="'.$attr.'"><option value=""></option>';
            foreach ($list as $relObj)
            {
               $sel = (($currentValue == $relObj->getId()) ? ' selected="true"' : '');
               $select .= '<option value="'. $relObj->getId() .'"'. $sel .'>'.
                          $relClass.'['.$relObj->getId().']</option>'; // TODO: Si se tuviera un toString en la clase se mostraria mejor
            }
            $select .= '</select>';
            $res .= $select;
         }
         else
         {
            $maxStringLength = NULL;
            if ( $type === Datatypes::TEXT )
            {
               $maxLengthConstraint = $po->getConstraintOfClass( $attr, 'MaxLengthConstraint' );
               if ($maxLengthConstraint !== NULL) $maxStringLength = $maxLengthConstraint->getValue();
            }

            $res .= self::field_to_html_edit( $attr, $type, $po->aGet($attr), $maxStringLength );

            // Si el campo tiene errores, los muestro
            if ($po->getErrors()->hasFieldErrors($attr))
            {
               $res .= '<div class="errors">';
               $res .= self::fieldErrors($po, $attr);
               $res .= '</div>';
            }
         }
         $res .= '</td></tr>';
      }
      $res .= '</table>';

      return $res;
   }

   private static function display_show( PersistentObject $po )
   {
      $res = '<table>';
      $attrs = $po->getAttributeTypes();
      foreach ( $attrs as $attr => $type )
      {
         $res .= '<tr><td>';
         $res .= $attr; // TODO: Habria que ver si esto es i18n, deberia haber algun "display name" asociado al nombre del campo.
         $res .= '</td><td>';
         $res .= self::field_to_html_show( $attr, $type, $po->aGet($attr) );
         $res .= '</td></tr>';
      }

      // Necesito el nombre de la aplicacion y no deberia ser 'core', lo obtengo de ctx o de los params.
      $ctx = YuppContext::getInstance();
      $m = Model::getInstance();

      // Muestro hasOne: http://code.google.com/p/yupp/issues/detail?id=12
      $hone = $po->getHasOne();
      foreach ( $hone as $attr => $clazz )
      {
         // TODO: Habria que ver si esto es i18n, deberia haber algun "display name" asociado al nombre del campo.
         $res .= "<tr><td>$attr</td><td>";
           
         $relObj = $po->aGet($attr);
         if ($relObj == NULL) continue;
           
         $ctx = YuppContext::getInstance();
         $app = $m->get('app');
         if ( !isset($app) ) $app = $ctx->getApp();

         // Link a vista de scaffolding del objeto relacionado con hasOne
         $res .= h('link', array(
                   'app'        => 'core', //$app, //( ($ctx->getApp()=='core') ? $m->get('app') : $ctx->getApp() ),
                   'controller' => 'core',
                   'action'     => 'show',
                   'class'      => $relObj->getClass(),
                   'id'         => $relObj->getId(),
                   'body'       => $relObj->getClass() . ' ['. $relObj->getId() .']',
                   'params'     => array('app'=>$app)
                 ));

         $res .= '</td></tr>';
      }
      $res .= '</table>';

      return $res;
   }

   // MAPEOS DE TIPOS DE CAMPOS DE PO A OBJETOS HTML //
   public static function field_to_html_edit( $fieldName, $fieldType, $value, $maxStringLength = NULL )
   {
       $res = "";
       switch ($fieldType)
       {
          case Datatypes::TEXT:
             if ($maxStringLength !== NULL)
             {
                if ($maxStringLength > 100) $res = '<textarea name="'. $fieldName .'">'. $value .'</textarea>';
                else $res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
             }
             else
                $res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
          break;
          case Datatypes::INT_NUMBER:   $res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
          break;
          case Datatypes::LONG_NUMBER:  $res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
          break;
          case Datatypes::FLOAT_NUMBER: $res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
          break;
          case Datatypes::BOOLEAN:
             //$res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
             // TODO I18n
             //$res = '<select><option '. (($value)?'selected="true"':'') .'>TRUE</option><option '. ((!$value)?'selected="true"':'') .'>FALSE</option></select>';
             $res = '<input type="checkbox" '. (($value)?'checked="true"':'') .' />';
          break;
          case Datatypes::DATE: // podrian ser 3 selects para anio mes y dia, los cuales obtienen sus valores maximos y minimos de algun tipo de configuracion.
             $res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
          break;
          case Datatypes::TIME: // Pueden ser 2 selects par hora y minutos.
             $res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
          break;
          case Datatypes::DATETIME: // podria ser los selects de date y time juntos...
             $res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
          break;
       }

       return $res;
   }

   public static function field_to_html_show( $fieldName, $fieldType, $value, $maxStringLength = NULL )
   {
      $res = "";
      switch ($fieldType)
      {
         case Datatypes::TEXT:         $res = '<span>'. $value .'</span>';
         break;
         case Datatypes::INT_NUMBER:   $res = '<span>'. $value .'</span>';
         break;
         case Datatypes::LONG_NUMBER:  $res = '<span>'. $value .'</span>';
         break;
         case Datatypes::FLOAT_NUMBER: $res = '<span>'. $value .'</span>';
         break;
         case Datatypes::BOOLEAN:
            //$res = '<input type="text" value="'. $value .'" name="'. $fieldName .'" />';
            // TODO I18n
            //$res = '<select><option '. (($value)?'selected="true"':'') .'>TRUE</option><option '. ((!$value)?'selected="true"':'') .'>FALSE</option></select>';
            $res = '<input type="checkbox" '. (($value)?'checked="true"':'') .' disabled="true" />';
         break;
         case Datatypes::DATE: // podrian ser 3 selects para anio mes y dia, los cuales obtienen sus valores maximos y minimos de algun tipo de configuracion.
            $res = '<span>'. $value .'</span>';
         break;
         case Datatypes::TIME: // Pueden ser 2 selects par hora y minutos.
            $res = '<span>'. $value .'</span>';
         break;
         case Datatypes::DATETIME: // podria ser los selects de date y time juntos...
            $res = '<span>'. $value .'</span>';
         break;
      }

      return $res;
   }


   // Controles para formularios
   // TODO: id y demas atributos deberian venir en un array
   /**
    * Genera un control html SELECT con el nombre y las opciones dadas.
    * Si se le pasa un valor, este queda seleccionado por defecto.
    */
   public static function select( $name, $options, $value = NULL, $attrs = array())
   {    
      if ($name === NULL)
         throw new Exception("El argumento 'name' no puede ser nulo. " . __FILE__ . " " . __LINE__);

      if ($options === NULL)
         throw new Exception("El argumento 'options' no puede ser nulo. " . __FILE__ . " " . __LINE__);

      if ( !is_array($options))
         throw new Exception("El argumento 'options' debe ser un Array. " . __FILE__ . " " . __LINE__);

      $strattrs = '';
      foreach ($attrs as $name=>$val) $strattrs .= ' '. $name .'="'. $val .'"';
      $fieldHTML = '<select name="'.$name.'" '. $strattrs .'>';
      foreach ( $options as $opt_value => $text )
      {
         //echo "val: ".gettype($value) . "<br/>";
         //echo "option: ".gettype($opt_value) . "<br/>";
         if ( (string)$opt_value === (string)$value )
            $fieldHTML .= '<option value="'. $opt_value .'" selected="true">'. $text .'</option>';
         else
            $fieldHTML .= '<option value="'. $opt_value .'">'. $text .'</option>';
      }
      $fieldHTML .= '</select>';

      echo $fieldHTML;
   }

   public static function text($name, $value = NULL, $attrs = array())
   {
      $strattrs = '';
      foreach ($attrs as $attr=>$val) $strattrs .= ' '. $attr .'="'. $val .'"';
      return '<input type="text" name="'. $name .'" value="'. $value .'"'. $strattrs .' />';
   }

   public static function bigtext($name, $value = NULL, $attrs = array())
   {
      $strattrs = '';
      foreach ($attrs as $attr=>$val) $strattrs .= ' '. $attr .'="'. $val .'"';
      return '<textarea name="'. $name .'"'. $strattrs .' >'. $value .'</textarea>';
   }
    
   /**
    * Para que aparezca chequeado debe venir $value en true
    */
   public static function check($name, $value = NULL, $attrs = array())
   {
      if ($value === true) $attrs['checked'] = 'true';
      $strattrs = '';
      foreach ($attrs as $attr=>$val) $strattrs .= ' '. $attr .'="'. $val .'"';
      return '<input type="checkbox" name="'. $name .'" '. $strattrs .' />';
   }
    
   public static function radio($name, $value = NULL, $attrs = array())
   {
      $strattrs = '';
      foreach ($attrs as $attr=>$val) $strattrs .= ' '. $attr .'="'. $val .'"';
      return '<input type="radio" name="'. $name .'" value="'. $value .'"'. $strattrs .' />';
   }
    
   public static function hidden($name, $value = NULL, $attrs = array())
   {
      $strattrs = '';
      foreach ($attrs as $attr=>$val) $strattrs .= ' '. $attr .'="'. $val .'"';
      return '<input type="hidden" name="'. $name .'" value="'. $value .'"'. $strattrs .' />';
   }
    
   public static function password($name, $value = NULL, $attrs = array())
   {
      $strattrs = '';
      foreach ($attrs as $attr=>$val) $strattrs .= ' '. $attr .'="'. $val .'"';
      return '<input type="password" name="'. $name .'" value="'. $value .'"'. $strattrs .' />';
   }
    
   public static function file($name, $value = NULL, $attrs = array())
   {
      $strattrs = '';
      foreach ($attrs as $attr=>$val) $strattrs .= ' '. $attr .'="'. $val .'"';
      return '<input type="file" name="'. $name .'" value="'. $value .'"'. $strattrs .' />';
   }

   /**
    * Value puede tener 3 valores con las siguientes claves: d (para el dia), m (para el mes), y (para el anio)
    */
   public static function date($name, $value = array(), $attrs = array())
   {
      $strattrs = '';
      foreach ($attrs as $attr=>$val) $strattrs .= ' '. $attr .'="'. $val .'"';

      $fieldHTML = '<label for="day">D&iacute;a: </label>'; // TODO: i18n soportado por el framework.
      $fieldHTML .= '<select name="'.$name.'_day">';
      $day = NULL;
      if (isset($value['d'])) $day = $value['d'];
      for ( $d=1; $d<32; $d++ )
      {
         if ( $d === $day ) $fieldHTML .= '<option value="'. $d .'" selected="true">'. $d .'</option>';
         else $fieldHTML .= '<option value="'. $d .'">'. $d .'</option>';
      }
      $fieldHTML .= '</select>';

      $fieldHTML .= '<label for="month">Mes: </label>'; // TODO: i18n soportado por el framework.
      $fieldHTML .= '<select name="'.$name.'_month">';
      $month = NULL;
      if (isset($value['m'])) $month = $value['m'];
      for ( $m=1; $m<13; $m++ )
      {
         if ( $m === $month ) $fieldHTML .= '<option value="'. $m .'" selected="true">'. $m .'</option>';
         else $fieldHTML .= '<option value="'. $m .'">'. $m .'</option>';
      }
      $fieldHTML .= '</select>';
       
      $fieldHTML .= '<label for="year"> A&ntilde;o: </label>'; // TODO: i18n soportado por el framework.
      $fieldHTML .= '<select name="'.$name.'_year">';
      $year = NULL;
      if (isset($value['y'])) $year = $value['y'];
      for ( $y=1930; $y<2010; $y++ )
      {
         if ( $y === $year ) $fieldHTML .= '<option value="'. $y .'" selected="true">'. $y .'</option>';
         else $fieldHTML .= '<option value="'. $y .'">'. $y .'</option>';
      }
      $fieldHTML .= '</select>';

      return $fieldHTML;
   }
    
   public static function submit($name, $value = NULL, $attrs = array())
   {
      $strattrs = '';
      foreach ($attrs as $attr=>$val) $strattrs .= ' '. $attr .'="'. $val .'"';
      return '<input type="submit" name="'. $name .'" value="'. $value .'"'. $strattrs .' />';
   }

   /**
    * Crear un campo html TEXTAREA con el editor TinyMCE.
    * 
    * TODO: podria querer pasarle algunos parametros extra para configurar el TinyMCE,
    *       como por ejemplo la lista de links que aparece en la pantalla modal cuando
    *       se crea un link.
    * 
    * TODO: si quisiera poner muchos htmls en la misma vista, el JS se incluiria cada vez,
    *       seria bueno pasarle todos los nombres de campos y contenidos para cada uno
    *       y que el JS se incluya una sola vez.
    *       Esto se podria hacer mas ordenado mediante la inclusion ocntrolada de JS a la
    *       vista, o sea, diciendole a la vista que JS se va a usar, en lugar de incluirlo
    *       aca como un string simple que se pega en la pagina. Ahorraria codigo y la 
    *       inclusion de JS es mas ordenada. Idem para YUI Calendar, NiftyCorners, Prototype, etc.
    */
   public static function html( $name, $content = '', $params = array() )
   {
      ob_start(); // agarro el output y devuelvo el string

      // FIXME: el lenguaje podria parametrizarse.
      echo '<textarea name="'.$name.'" id="'.$name.'">'.$content.'</textarea>';
      echo h('js', array('name'=>'tiny_mce_35b3/tiny_mce')); // js/tiny_mce/tiny_mce.js
      echo '<script type="text/javascript">
       if (!htmlinit) // Si el usuario no define la funcion
       {
         // Dummy para que no de error la carga de TinyMCE, donde se
         // configura htmlinit como funcion de callback al cargar el editor.
         // A ser sobre escrita por el usuario...
         var htmlinit = function() {};                 
       }
       tinyMCE.init({
         mode:     "exact", //"textareas"
         theme:    "advanced",
         elements: "'. $name .'", // ids de los elementos a aplicar el wysiwyg
         language: "en",
         theme_advanced_resizing_use_cookie : false, // http://www.tinymce.com/wiki.php/Configuration:theme_advanced_resizing_use_cookie

         // para evitar que ponga la tag P al ppio y final
         // http://stackoverflow.com/questions/5211687/tinymce-problem-extra-paragraphs
         // http://tinymce.moxiecode.com/forum/viewtopic.php?id=9887
         forced_root_block : false,
         force_br_newlines : false,
         cleanup_on_startup : true,

         // Setear el tamanio inicial del editor
         // http://tinymce.moxiecode.com/forum/viewtopic.php?id=9817
         width : "'.((isset($params['width']))?$params['width']:'100%').'",
         height : "'.((isset($params['height']))?$params['height']:400).'",

         // Callback para cuando carga el editor, esta deberia ser implementada por el usuario
         // http://tinymce.moxiecode.com/wiki.php/Configuration:oninit
         oninit: htmlinit,

         plugins : "safari,style,layer,table,advhr,advimage,advlink,emotions,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template", //,imagemanager,filemanager",
         //pagebreak,save,
         theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
         //theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview",
         theme_advanced_buttons2 : "forecolor,backcolor,|,bullist,numlist,|,outdent,indent,blockquote,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview",
         theme_advanced_buttons3 : "tablecontrols,|,removeformat,visualaid,|,sub,sup",
         theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,attribs,|,visualchars,nonbreaking,template,blockquote,|,insertfile,insertimage,|,charmap,emotions,media,advhr,|,fullscreen",
         //pagebreak,hr,del,ins,cite,abbr,acronym,styleselect,|,search,replace,

         theme_advanced_statusbar_location : "bottom",
         theme_advanced_toolbar_location : "top",
         theme_advanced_toolbar_align : "left",
         theme_advanced_resizing : true,
         theme_advanced_path : false, 
         extended_valid_elements : "img[class|src|border=0|alt|title|hspace|vspace|width|height|align|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",

         relative_urls : false,
         remove_script_host : false,
         document_base_url : "'. $_SERVER['HTTP_HOST'] .'"
      });
    </script>';

      echo ob_get_clean();
   }
    
   /**
    * Control de calendario basado en Yui Calendar.
    * FIXME: no esta tomando el value.
    * FIXME: los campos input deberian ser hidden.
    */
   public static function calendar( $name, $value = '' )
   {
      echo '<input type="text" name="day" id="day" />
           <input type="text" name="month" id="month" />
           <input type="text" name="year" id="year" />
           <div id="calendarContainer"></div>';

      echo h('css', array('name'=>'yui/calendar/calendar'));
      echo h('js', array('name'=>'yui/yahoo/yahoo-min'));
      echo h('js', array('name'=>'yui/event/event-min'));
      echo h('js', array('name'=>'yui/dom/dom-min'));
      echo h('js', array('name'=>'yui/calendar/calendar-min'));

      echo '<script type="text/javascript">
         var calendar; // Calendario global from
         // Acciones que se hacen onload ...
         /* forma inobstructiva window.onload
         function addEvent(obj, evType, fn) { 
            if (obj.addEventListener){ 
              obj.addEventListener(evType, fn, false); 
              return true; 
            } else if (obj.attachEvent){ 
              var r = obj.attachEvent("on"+evType, fn); 
              return r; 
            } else { 
              return false; 
            } 
         }
         addEvent(window, "load", foo);
         */
         
         window.onload = function () {
            calendar = new YAHOO.widget.Calendar("calendar", "calendarContainer" );
     
            // Mueve el calendario al mes que se selecciono la fecha.
            //calendar.cfg.setProperty("pagedate", "${date.getMonth()+1}/${date.getYear()+1900}" );
            
            calendar.select(calendar.today);
            //calendar.select( new Date( ${date.getYear()+1900}, ${date.getMonth()}, ${date.getDate()} ) );
            
            //alert( new Date( ${date.getYear()+1900}, ${date.getMonth()}, ${date.getDate()} ) );
            
            calendar.selectEvent.subscribe(setDate); // Esto pasa cuando se selecciona una fecha, tambien cuando se hace "select" de forma programatica, por lo que debe estar luego del select para que el formulario no se submitee solo!.
            calendar.render();
            calendar.show();
         } 
         
         
         // funcion reusable
         function objById( id )
         {
            if (document.getElementById)
                var returnVar = document.getElementById(id);
            else if (document.all)
                var returnVar = document.all[id];
            else if (document.layers)
                var returnVar = document.layers[id];
            return returnVar;
         }
         
         function setDate()
         {
            var arrDates = calendar.getSelectedDates();
            var date = arrDates[0];
            
            objById("day").value = date.getDate();
            objById("month").value = date.getMonth()+1; // Ojo, empieza en cero!
            objById("year").value = date.getFullYear();
         }
      </script>';
   }
}
?>