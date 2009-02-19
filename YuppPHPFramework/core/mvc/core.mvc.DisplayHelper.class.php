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
              	  $maxLengthConstraint = $po->getConstraintOfClass( $attr, MaxLengthConstraint );
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


}
?>