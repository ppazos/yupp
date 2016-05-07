# Preguntas Frecuentes #

Este es un espacio donde se listan las preguntas más frecuentes.
Si tienes alguna duda sobre la instalación y el funcionamiento del framework, por favor publícala en nuestro grupo de discusión: http://groups.google.com/group/yuppframeworkphp

## Pregunta 1 ##

  * **Estoy corriendo por primera vez el framework en AppServ (un compilado con Apache, PHP  y MySQL), y me sale este error:**
```
Internal Server Error

The server encountered an internal error or misconfiguration and was unable to complete your request.

Please contact the server administrator, admin@admin.com and inform them of the time the error occurred, and anything you might have done that may have caused the error.

More information about this error may be available in the server error log.
```

  * Verifica que tienes activo el módulo "rewrite\_module" de Apache. Esto lo haces verificando que la línea **LoadModule rewrite\_module modules/mod\_rewrite.so** está descomentada en el archivo de configuración de Apache (httpd.conf).


## Pregunta 2 ##

  * **Instalo el framework con éxito pero los links están rotos**
```
Me pasa que instalo el framework, veo la página principal con los links de crear tablas, ejecutar bootstrap y ver estadísticas, pero al hacer clic en ellos no me lleva a ningún lado.

Estos links no funcionan:
http://localhost/Yupp_Framework_PHP_v0.1.6.3/core/core/createModelTables
http://localhost/Yupp_Framework_PHP_v0.1.6.3/core/core/executeBootstrap?componentName=blog
```

  * Lo que puede pasar es que no tengas el MOD\_REWRITE (o REWRITE MODULE) de Apache activado. Verifica si está activado, y si no, actívalo. Si al activarlo sigues con el error por favor publica un comentario en esta página o abre una discusión nueva en el grupo: http://groups.google.com/group/yuppframeworkphp

  * Verifica que tienes AllowOverride All en el httpd.conf de tu Apache:
    * Lee: http://www.jarrodoberto.com/articles/2011/11/enabling-mod-rewrite-on-ubuntu
    * Lee: http://stackoverflow.com/questions/7816429/apache-mod-rewrite-is-not-working-or-not-enable
```
<Directory "path/to/www">

    Options Indexes FollowSymLinks

    AllowOverride All

    Order allow,deny
    Allow from all
</Directory>
```


## Pregunta 3 ##

  * **Me aparece un error de que no encuentra la base de datos**
```
Instalo el framework correctamente y al acceder a la aplicación un mensaje me dice que no puede seleccionar la base de datos "carlitos".
```

  * Antes de intentar acceder a la aplicación debes configurar la base de datos en el archivo "core.config.YuppConfig.class.php", cambiando el nombre de la base de datos, el usuario y password para ingresar a ella. Por defecto la base de datos se llama "carlitos". Luego de configurar correctamente estos datos, debes crear la base (por ejemplo si usas MySQL puedes crearla mediante el PHPMyAdmin), y debe llamarse igual al nombre configurado en YuppConfig.

## Pregunta 4 ##

  * **Al acceder al framework obtengo el siguiente mensaje:**
```
mysql_connect() [function.mysql-connect]: Access denied for user 'root'@'localhost' (using password: NO) [C:\AppServ\www\YuppPHPFramework\index.php : 20]
```

  * Tienes los datos de acceso de la base de datos mal configurados, verifica que el usuario y clave de acceso a la base están correctamente establecidos en el archivo YuppConfig. Lo que debes configurar es el campo $dev\_datasource:
```
   private $dev_datasource = array(
                               self::DB_MYSQL =>
                                  array( 'url' => 'localhost',
                                         'user' => 'root',
                                         'pass' => '',
                                         'database' => 'carlitos'),
                               self::DB_SQLITE =>
                                  array( 'url'  => '',
                                         'user' => '',
                                         'pass' => '',
                                         'database' => 'C:\\wamp\\sqlitemanager\\test.sqlite')
                             );
```


## Pregunta 5 ##

  * **Al instalar la aplicación Hello World me da un error:**

```
Fatal error: Class 'Persona' not found in C:\wamp\www\Yupp_PHP_Framework_v0.2.2\core\mvc\core.mvc\YuppController.class.php: eval()'d code on line 1.
```

  * Prueba borrar la sesión y el caché de tu navegador (también puedes probar cerrando y abriendo el navegador o usando otro navegador). El problema está en que Yupp guarda datos en la sesión, y al instalar una nueva aplicación con un navegador abierto, tratará de acceder a objetos que no están en ese caché, provocando el error.