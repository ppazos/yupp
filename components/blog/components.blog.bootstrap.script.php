<?php
/**
 * Created on 31/10/2008
 * components.blog.boostrap.script.php
 * 
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
 
// =============================================================
// Usuario del blog, crea uno si no existe ya (es para el login)

Logger::show( "Blog bootstrap: empieza", "h1" );

$cantidadUsuarios = Usuario::count();

Logger::show( "Cantidad de usuarios: $cantidadUsuarios" );

if ( $cantidadUsuarios == 0 )
{
   $user = new Usuario( array(
                           "nombre" => "Pablo Pazos",
                           "email" => "pablo.swp@gmail.com",
                           "clave" => "abcd1234",
                           "fechaNacimiento" => "1981-10-24 09:59:00",
                           "edad" => "27",
                           "gggf" => "2008-09-23 00:39:38"
                           ) );
   if ( !$user->save() )
   {
   	Logger::struct( $user->getErrors() );
   }
}

Logger::show( "Blog bootstrap: termina", "h1" );
// =============================================================

?>
