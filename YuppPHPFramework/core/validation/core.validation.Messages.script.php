<?php

YuppLoader :: load('core.support', 'I18nMessage');

$m = I18nMessage::getInstance();

$m->a( "validation.error.nullable",            "es", "El valor del atributo '{0}' no puede ser nulo" );
$m->a( "validation.error.blank",               "es", "El valor del atributo '{0}' no puede ser vacio" );
$m->a( "validation.error.lower",               "es", "El valor del atributo '{0}' debe ser menor a {1}" );
$m->a( "validation.error.greater",             "es", "El valor del atributo '{0}' debe ser mayor a {1}" );
$m->a( "validation.error.inList",              "es", "El valor del atributo '{0}' debe estar en la lista {1}" );
$m->a( "validation.error.between",             "es", "El valor del atributo '{0}' debe estar entre {1} y {2}" );
$m->a( "validation.error.minLengthConstraint", "es", "El valor del atributo '{0}' debe tener largo minimo {1}" );
$m->a( "validation.error.maxLengthConstraint", "es", "El valor del atributo '{0}' debe tener largo maximo {1}" );
$m->a( "validation.error.email",               "es", "El valor del atributo '{0}' no es un email valido" );

$m->a( "validation.error.nullable",            "en", "Value of attribute '{0}' can't be null" );
$m->a( "validation.error.blank",               "en", "Value of attribute '{0}' can't be empty" );
$m->a( "validation.error.lower",               "en", "Value of attribute '{0}' must be less than {1}" );
$m->a( "validation.error.greater",             "en", "Value of attribute '{0}' must be greater than {1}" );
$m->a( "validation.error.inList",              "en", "Value of attribute '{0}' must be one of {1}" );
$m->a( "validation.error.between",             "en", "Value of attribute '{0}' must be between {1} and {2}" );
$m->a( "validation.error.minLengthConstraint", "en", "Value of attribute '{0}' must have minimal length {1}" );
$m->a( "validation.error.maxLengthConstraint", "en", "Value of attribute '{0}' must have maximum length {1}" );
$m->a( "validation.error.email",               "en", "Value of attribute '{0}' is not a valid email" );

?>
