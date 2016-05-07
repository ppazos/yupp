# NOTAS DE LA VERSION: #

  * Versión 0.5 de Yupp PHP Framework


# Incluye los siguientes componentes #

  * versión 1.0.1 del YORM (Yupp Object Relational Mapping)
  * versión 0.5.1 del YMVC (Yupp Model View Controller)
  * versión 0.1.4 del Yupp Desktop


# Requisitos y pruebas realizadas #

  * Versión de PHP: 5.2.x o 5.3.x
    * Probado en 5.2.3, 5.2.4, 5.2.5, 5.2.7, 5.2.8, 5.2.9-2, 5.2.10 y 5.2.11 (versión de Windows)
    * Probado en 5.3.0 (windows y linuz)
    * Probado con WAMP 2.0 y con AppServ 2.5.10
  * Motor de bases de datos: MySQL 5.x o superior (nosotros utilizamos 5.0.41 o 5.1.33)
  * Motor de bases de datos: SQLite (nosotros utilizamos 2.8.17)
  * Motor de bases de datos: PostgreSQL 8.3
  * Tener el módulo de Apache MOD\_REWRITE instalado y activado.


Preguntas frecuentes: http://code.google.com/p/yupp/wiki/FAQ


# Cambios con respecto a la versión 0.4 #

  * Mejoras generales de performance.
  * Se agrega transaccionalidad al save en cascada de PersistentObject.
  * Se agrega método aRemoveAllFrom a PersistentObject.
  * Se externaliza la generación de errores de validación en PersistentObject a la nueva clase Errors.
  * Actualización de librerías javascript (jQuery, TinyMCE).
  * Mejoras al helper html.
  * Mejoras a vistas de scaffolding, por ejemplo para mostrar errores en campos.
  * Soporte para creación de bases de datos que aún no existen.
  * Soporte para serialización a JSON de listas de elementos PersistentObject.
  * Mejoras a Yupp Desktop (por ejemplo poder cambiar de layout).
  * Se quita el soporte para webflows.
  * Otros cambios y correcciones menores (PersistentObject, PersistentManager, DAL, Logger, entre otras clases).


# ¿Que contiene esta liberación? #

> Contiene los componentes mencionados antes, el YORM y el YMVC.

> Esta liberación contiene un ejemplo de un sistema de Blog sencillo donde
> se pueden crear entradas y comentarios, mostrando el funcionamiento básico
> del framework, con funcionalidades como:

  * Definicion de modelo persistente
  * Crear modelo
  * Modificar modelo
  * Obtener modelo
  * Definicion de controladores
  * Definicion de vistas
  * Uso de helpers basicos
  * Validacion automatica de informacion


Descarga aplicaciones para probar: http://code.google.com/p/yupp-apps/downloads/list


# Para correr el framework #

  * Servidor Apache con soporte para PHP corriendo.
  * PHP 5.2.x o superior.
  * MySQL 5.x o superior instalado y corriendo.
    * Alternativas: SQLite o PostgreSQL.


Descomprime el contenido de la liberacion que descargaste desde
http://code.google.com/p/yupp/downloads/list, en el "web root"
de tu servidor Apache ("www" o "public\_html").

De aquí en adelante asumimos que yupp se encuentra en la ruta "servidor/www/yupp".



## Configuración de la base de datos y creación de la base ##

> Para configurar los datos de conexion a la base de datos MySQL/SQLite/PostgreSQL
> se debe editar la informacion presente en el archivo:

> "/core/config/core.config.YuppConfig.class.php",
> modificando el campo $default\_datasource, estableciendo los valores correctos
> para cada clave de dicho array: url, user, pass y database.

> Debes crear la base de datos con el nombre que configurado en
> "/core/config/core.config.YuppConfig.class.php" en el campo "database"
> antes de correr el framework.


## Acceder al escritorio de Yupp ##

> Desde http://localhost:8080/yupp debería acceder automáticamente al escritorio,
> mostrando las aplicaciones con las que cuenta el framework (suponiendo que el
> servidor escucha en el puerto 8080).


## Generación de las tablas ##

> Desde escritorio de Yupp, acceder a "Base de datos", donde debería aparecer
> un link "Crear tablas". Haciendo clic en ese link se deberían generar las
> tablas para todas las aplicaciones de forma automática.

> Si tienes algún problema o alguna pregunta, no dudes en comunicarte con nosotros:
> http://groups.google.com/group/yuppframeworkphp


# CONTACTO #

Cualquier duda o sugerencia, publica tu comentario en nuestro grupo:
http://groups.google.com/group/yuppframeworkphp o en nuestra wiki:
http://code.google.com/p/yupp/w/list


Pablo Pazos Gutiérrez
Yupp PHP Framework
Líder del proyecto
http://yuppframework.blogspot.com