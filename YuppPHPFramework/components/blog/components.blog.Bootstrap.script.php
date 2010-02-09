<?php
/**
 * Created on 31/10/2008
 * components.blog.Boostrap.script.php
 * 
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
 
// =============================================================
// Usuario del blog, crea uno si no existe ya (es para el login)

Logger::show( "Blog Bootstrap: empieza", "h1" );

// Pregunta cantidad de usuarios
$cantidadUsuarios = Usuario::count();

Logger::show( "Cantidad de usuarios: $cantidadUsuarios" );

// Si no se ha ejecutado el bootstrap deberian haber cero
// La pregunta evita agregar 2 usuarios adminsitradores
if ( $cantidadUsuarios == 0 )
{
   $user = new Usuario( array(
                           "nombre" => "Admin Nimda",
                           "email" => "admin@admin.com",
                           "clave" => "abcd1234",
                           "fechaNacimiento" => "1981-10-24 09:59:00",
                           "edad" => "27",
                           "gggf" => "2008-09-23 00:39:38"
                           ) );
  
   // Intenta guardar el usuario en la base de datos
   if ( !$user->save() )
   {
      // Si no pudo guardar, muestra errores
      Logger::struct( $user->getErrors() );
   }
}

Logger::show( "Blog Bootstrap: termina", "h1" );
// =============================================================

?>
