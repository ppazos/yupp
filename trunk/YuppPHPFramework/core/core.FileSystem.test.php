<?php
/*
 * Created on 25/02/2008
 * utils.FileSystem.test.php
 */

include_once('core.FileSystem.class.php');

// Ok
echo "<pre>";
print_r( FileSystem::getFileNames(".") ); // Todos los archivos
print_r( FileSystem::getFileNames(".", "/\.php$/i") ); // Todos los php
print_r( FileSystem::getFileNames(".", "/^utils\.(.*)\.php$/i") ); // Todos los php del paquete utils

print_r( FileSystem::getFileNames(".", "/(.*)\.php$/i", array(1)) ); // Todos los php, pero sin el .php (ver que la regexp tiene un grupo y en el array selecciono ese grupo)
print_r( FileSystem::getFileNames(".", "/^utils\.(.*)\.php$/i", array(1,5,0)) ); // Todos los php del paquete utils, idem anterior, ahora sin el "utils."

echo "Todos los subdirectorios:<br/>";
print_r( FileSystem::getSubdirNames(".") ); // Todos los subdirectorios

echo "Todos los subdirectorios que empiezan con mayus:<br/>";
print_r( FileSystem::getSubdirNames(".", "/^[A-Z]/") ); // Todos los subdirectorios que empiezan con mayusculas.

echo "Todos los subdirectorios que tienen '_':<br/>";
print_r( FileSystem::getSubdirNames(".", "/.*_.*/") ); // Todos los subdirectorios que tienen un guion bajo en el nombre
echo "</pre>";

//FileSystem::createEmptyFile("./pepe.popo"); // Ok

?>