<?php

YuppLoader::load('core.support', 'I18nMessage');

$m = I18nMessage::getInstance();

$m->a( 'error.500.InternalServerError', 'es', 'Error interno del servidor' );
$m->a( 'error.404.NotFound',            'es', 'Recurso no encontrado' );
$m->a( 'error.403.Forbidden',           'es', 'Acceso prohibido' );

?>
