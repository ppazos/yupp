<?php

class DisplayHelper {

    // TODO: permitir el acceso a los params desde los helpers!
    //Model::getInstance();
    
    public static function message( $key, $locale = NULL, $defaultMessage = "" )
    {
       // Si locale es NULL saca el locale de las variables de entorno.
       if ( $locale === NULL )
       {
          $ctx = YuppContext::getInstance();
       	 $locale = $ctx->getLocale(); // se que siempre hay un locale valido.
       } 

       $m = I18nMessage::getInstance();
       return $m->g( $key, $locale, $defaultMessage );
    }

/*
    public static function template( $component, $viewDir, $template, $params )
    {
    	 // Necesito buscar el template en /components/$component/view/$viewDir/$template.template.php
       // Con los params que se me pasan, tengo que dejarlos accesibles para el script en ese template.
       
       // El primero lo cargaria con el class loader.
       // El pasaje de parametros creo que con solo declarar las variables funciona, pero hay q ver. tal vez con variables variables.
       
       // Talvez quisiera no pasarle component ni viewDir, component lo saco del request y viewDir seria el nombre del controller (por defecto).
    }
*/

    public static function errors( $po )
    {
       if ($po->hasErrors())
       {
          echo "<ul>";
          foreach ( $po->getErrors() as $attr => $errors )
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

       // Cabezal
       $res .= '<tr>';
       foreach ($attrs as $attr => $type )
       {
           $res .= '<th>';

           // FIXME: Problema, necesito los params actuales para saber que clase estoy mostrando, pero no tengo acceso. NO, ME PASAN LA CLASE!
           //$res .= '<a href="'. Helpers::params2url( $ ) .'">'; // TODO: no tengo acceso a los params desde helpers.
           // TODO: el order deberia ser toggleado, si ahora muestro ordenado por una columna, al hacer click en ella debo mostrar el orden inverso.
           //       pero como no tengo acceso a los params no se realmente por cual columna se ordena ni la direccion actual.

           $model = Model::getInstance();
           $sort = $model->get('sort');
           $dir = 'asc';

           if ( $sort == $attr && $model->get('dir') == 'asc' ) $dir = 'desc'; // Cambia la direccion del atributo por el que esta ordenado ahora.

           $res .= '<a href="'. Helpers::params2url( array('class'=>$clazz, 'sort'=>$attr, 'dir'=>$dir) ) .'">'; // TODO: no tengo acceso a los params desde helpers.
           $res .= $attr; // TODO: Habria que ver si esto es i18n, deberia haber algun "display name" asociado al nombre del campo.
           $res .= '</a>';
           $res .= '</th>';
       }
       $res .= '</tr>';


       // Filas
       foreach ($pos as $po) // pos puede ser vacio...
       {
          $res .= '<tr>';

          //$attrs = $po->getAttributeTypes();
          foreach ( $attrs as $attr => $type )
          {
             $res .= '<td>';

             if ($attr == "id")
             {
                $res .= '<a href="show?class='. $po->aGet('class') .'&id='. $po->aGet($attr) .'">';
                $res .= $po->aGet($attr);
                $res .= '</a>';
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
           else
           {
              $maxStringLength = NULL;
              if ( $type === Datatypes::TEXT )
              {
                 $maxLengthConstraint = $po->getConstraintOfClass( $attr, 'MaxLengthConstraint' );
                 if ($maxLengthConstraint !== NULL) $maxStringLength = $maxLengthConstraint->getValue();
              }
              
              $res .= self::field_to_html_edit( $attr, $type, $po->aGet($attr), $maxStringLength );
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
           // Los atributos inyectados no se deberian poder editar!
           $res .= '<tr><td>';
           $res .= $attr; // TODO: Habria que ver si esto es i18n, deberia haber algun "display name" asociado al nombre del campo.
           $res .= '</td><td>';
           $res .= self::field_to_html_show( $attr, $type, $po->aGet($attr) );
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

    /**
     * Genera un control html SELECT con el nombre y las opciones dadas.
     * Si se le pasa un valor, este queda seleccionado por defecto.
     */
    public static function select( $name, $options, $value = NULL, $id = NULL )
    {    
      if ($name === NULL)
         throw new Exception("El argumento 'name' no puede ser nulo. " . __FILE__ . " " . __LINE__);
      
      if ($options === NULL)
         throw new Exception("El argumento 'options' no puede ser nulo. " . __FILE__ . " " . __LINE__);
      
      if ( !is_array($options))
         throw new Exception("El argumento 'options' debe ser un Array. " . __FILE__ . " " . __LINE__);
      
      $fieldHTML = '';
      $fieldHTML .= '<select name="'.$name.'"'. (($id!==NULL)?' id="'.$id.'"':'') .'>';
      
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
    public static function html( $name, $content )
    {
       ob_start(); // agarro el output y devuelvo el string
       
       // FIXME: el lenguaje podria parametrizarse.
       echo '<textarea name="'.$name.'" id="'.$name.'">'.$content.'</textarea>';
       
       echo h('js', array('name'=>'tiny_mce/tiny_mce')); // js/tiny_mce/tiny_mce.js
       echo '<script type="text/javascript">
                 tinyMCE.init({
                     mode:     "exact", //"textareas"
                     theme:    "advanced",
                     elements: "'. $name .'", // ids de los elementos a aplicar el wysiwyg
                     language: "es",
                     
                     force_br_newlines : false,
                     cleanup_on_startup : true,
                     
                     plugins : "safari,style,layer,table,advhr,advimage,advlink,emotions,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template", //,imagemanager,filemanager",
                     //pagebreak,save,
                     theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect,|,forecolor,backcolor",
                     theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview",
                     theme_advanced_buttons3 : "tablecontrols,|,removeformat,visualaid,|,sub,sup,|,charmap,emotions,media,advhr,|,fullscreen",
                     theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,attribs,|,visualchars,nonbreaking,template,blockquote,|,insertfile,insertimage",
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
     */
    public static function calendar( $name, $value )
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