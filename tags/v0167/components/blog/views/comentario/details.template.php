<?php

//$m = Model::getInstance();
// Tengo una variable declarada en quien llama al template que se llama "entrada".

// Necesito un helper display template para poder mostrar tal template con tal modelo.
// Que chekee que existe el template, que lo busque (dado que componente estoy y controller).
//


?>

<div id="top">
   <div id="izq">
      <?php $entrada->getTitulo(); ?> --usuario que hizo la entrada--
   </div>
   <div id="der">
      <?php $entrada->getFecha(); ?>
   </div>
</div>

<div id="contenido">
   <?php $entrada->getTexto(); ?>
</div>

<div>
   [ Ver comentarios ( --cant de comentarios-- ) ]
</div>

<?php

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

?>
