<?php

YuppLoader :: load('core.support', 'I18nMessage');

$m = I18nMessage::getInstance();

$m->a( "validation.error.nullable",            "es", "El valor del atributo '{0}' no puede ser nulo" );
$m->a( "validation.error.blank",               "es", "El valor del atributo '{0}' no puede ser vacio" );
$m->a( "validation.error.lower",               "es", "El valor '{0}' del atributo '{1}' debe ser menor a {2}" );
$m->a( "validation.error.greater",             "es", "El valor '{0}' del atributo '{1}' debe ser mayor a {2}" );
$m->a( "validation.error.inList",              "es", "El valor '{0}' del atributo '{1}' debe estar en la lista {2}" );
$m->a( "validation.error.between",             "es", "El valor '{0}' del atributo '{1}' debe estar entre {2} y {3}" );
$m->a( "validation.error.minLengthConstraint", "es", "El valor '{0}' del atributo '{1}' debe tener largo minimo {2}" );
$m->a( "validation.error.maxLengthConstraint", "es", "El valor '{0}' del atributo '{1}' debe tener largo maximo {2}" );
$m->a( "validation.error.email",               "es", "El valor '{0}' del atributo '{1}' no es un email valido" );
$m->a( "validation.error.date",                "es", "El valor '{0}' del atributo '{1}' no es una fecha valida" );

$m->a( "validation.error.nullable",            "en", "Value of attribute '{0}' can't be null" );
$m->a( "validation.error.blank",               "en", "Value of attribute '{0}' can't be empty" );
$m->a( "validation.error.lower",               "en", "Value '{0}' of attribute '{1}' must be less than {2}" );
$m->a( "validation.error.greater",             "en", "Value '{0}' of attribute '{1}' must be greater than {2}" );
$m->a( "validation.error.inList",              "en", "Value '{0}' of attribute '{1}' must be one of {2}" );
$m->a( "validation.error.between",             "en", "Value '{0}' of attribute '{1}' must be between {2} and {3}" );
$m->a( "validation.error.minLengthConstraint", "en", "Value '{0}' of attribute '{1}' must have minimal length {2}" );
$m->a( "validation.error.maxLengthConstraint", "en", "Value '{0}' of attribute '{1}' must have maximum length {2}" );
$m->a( "validation.error.email",               "en", "Value '{0}' of attribute '{1}' is not a valid email" );
$m->a( "validation.error.date",                "en", "Value '{0}' of attribute '{1}' is not a valid date" );

?>