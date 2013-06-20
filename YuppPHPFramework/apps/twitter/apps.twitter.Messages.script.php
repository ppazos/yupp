<?php

YuppLoader::load('core.support', 'I18nMessage');

$m = I18nMessage::getInstance();

$m->a("twitter.user.welcome", "es", "Hola ");
$m->a("twitter.user.welcome", "en", "Hello ");

$m->a("twitter.user.register.title", "es", "Registro de usuario");
$m->a("twitter.user.register.title", "en", "User register");

$m->a("twitter.user.register.name", "es", "Nombre");
$m->a("twitter.user.register.name", "en", "Name");
$m->a("twitter.user.register.email", "es", "Correo electronico");
$m->a("twitter.user.register.email", "en", "Email");
$m->a("twitter.user.register.username", "es", "Nombre de usuario");
$m->a("twitter.user.register.username", "en", "Username");
$m->a("twitter.user.register.password", "es", "Clave");
$m->a("twitter.user.register.password", "en", "Password");

$m->a("twitter.user.register.error", "es", "Ocurrio un error, verifique sus datos");
$m->a("twitter.user.register.error", "en", "An error occurred, please verify your data");
$m->a("twitter.user.register.ok", "es", "Usuario registrado");
$m->a("twitter.user.register.ok", "en", "User registered");

$m->a("twitter.user.login.incomplete", "es", "Por favor ingrese nombre de usuario y clave");
$m->a("twitter.user.login.incomplete", "en", "Please input ypur username and password");
$m->a("twitter.user.login.failed", "es", "El usuario no existe");
$m->a("twitter.user.login.failed", "en", "User does not exists");
$m->a("twitter.user.login.ok", "es", "Usuario logueado con éxito");
$m->a("twitter.user.login.ok", "en", "User logged");

$m->a("twitter.user.follow.started", "es", "Estas siguiedo a ");
$m->a("twitter.user.follow.started", "en", "You started folowing ");
$m->a("twitter.user.follow.stopped", "es", "Dejaste de seguir a ");
$m->a("twitter.user.follow.stopped", "en", "You stopped following ");

$m->a("twitter.message.sendMessage.error", "es", "Ingrese un mensaje");
$m->a("twitter.message.sendMessage.error", "en", "Input a message");

$m->a("twitter.user.twitt.title", "es", "¿Que estas haciendo?");
$m->a("twitter.user.twitt.title", "en", "What are you doing?");
$m->a("twitter.user.twitt.write", "es", "esribe algo");
$m->a("twitter.user.twitt.write", "en", "write something");

$m->a("twitter.user.timeline.isFollowing", "es", " esta siguiendo a");
$m->a("twitter.user.timeline.isFollowing", "en", " is following");

$m->a("twitter.user.search.label", "es", "Buscar");
$m->a("twitter.user.search.label", "en", "Search");

?>