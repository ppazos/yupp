# Notas de la version: #

Versión 0.1.8 de Yupp PHP Framework

# Incluye los siguientes componentes: #

  * version 0.8.3 del YORM (Yupp Object Relational Mapping)
  * version 0.3 del YMVC (Yupp Model View Controller)


# Requisitos: #

  * Versión de PHP: 5.2.x (nosotros utilizamos 5.2.3)
    * Probado en 5.2.0, 5.2.4, 5.2.6 y 5.2.8 (versión de Windows)
    * Probado con WAMP y con AppServ 2.5.10
  * Motor de bases de datos: MySQL 5.x o superior (nosotros utilizamos 5.0.41)
  * Motor de bases de datos: SQLite (nosotros utilizamos 2.8.17)
  * Motor de bases de datos: PostgreSQL 8.3
  * Tener el modulo de Apache MOD\_REWRITE instalado y activado.


# Cambios con respecto a la versión anterior: #

Esta versión incluye grandes mejoras de YORM en el soporte a persistencia de estructuras de clases con herencia y varias mejoras al YMVC.

  1. Se corrijen los tickets #19 y #22 del YORM:
    1. http://code.google.com/p/yupp/issues/detail?id=19
    1. http://code.google.com/p/yupp/issues/detail?id=22

  1. Se crea y libera el primer juego de tests del YORM.


# Que contiene esta liberación? #

Contiene los componentes mencionados antes, el YORM y el YMVC.

Esta liberación contiene un ejemplo de un sistema de Blog sencillo donde
se pueden crear entradas y comentarios, mostrando el funcionamiento básico
del framework, con funcionalidades como:

  * Definicion de modelo persistente
  * Crear modelo
  * Modificar modelo
  * Obtener modelo
  * Definicion de controladores
  * Definicion de vistas
  * Uso de helpers basicos
  * Validacion automatica de informacion


También cuenta con el componente "Hello World". Este nuevo componente muestra la implementación de la aplicación mínima en Yupp PHP Framework, apenas una clase de dominio y un controller con 3 líneas de código!. Este componente puede hacer altas, bajas y modificaciones de usuarios.


# Para correr el ejemplo #

  * Debes tener un servidor Apache con soporte para PHP corriendo.
  * Debes tener PHP 5.2.x o superior.
  * Debes tener MySQL 5.x o superior instalado y corriendo.
    * Alternativas: SQLite o PostgreSQL.

Descomprime el contenido de la liberacion que descargaste desde
http://www.simplewebportal.net/host/1022.htm o
http://code.google.com/p/yupp/downloads/list
en el "web root" de tu servidor Apache ("www" o "public\_html").


## Configuración de la base de datos y creación de la base: ##

  * Para configurar los datos de conexión a la base de datos MySQL se debe editar la información presente en el archivo: "/core/config/core.config.YuppConfig.class.php", modificando el campo $dev\_datasource, estableciendo los valores correctos para cada clave de dicho array: url, user, pass y database. Y se debe crear la base de datos con el nombre que hayas configurado en "/core/config/core.config.YuppConfig.class.php" en el campo "database".


## Acceder al ejemplo: ##

> Accede al directorio donde se descomprimió Yupp Framework PHP desde un
> browser, aparecerá una página con links, esos links ejecutan las acciones
> por defecto de los controladores existentes, el único controlador que
> tiene utilidad por el momento es "EntradaBlog" que es de donde se permite
> crear entradas para el blog.


## Generación de las tablas: ##

> Desde la pantalla de administración que aparece al instalar Yupp y accederlo
> mediante un navegador web, existe una sección llamada "Información del modelo",
> ahí se listan todas las clases presentes en el modelo de datos de todos los
> componentes instalados (con esta liberación el único componente instalado es
> el de "blog"). En esta sección hay un link "Generar tablas", que al hacerle clic
> ejecutará la generación de todas las tablas necesarias en la base de datos
> configurada previamente.

> Si tienes algún problema o alguna pregunta, no dudes en comunicarte con nosotros:
> > http://groups.google.com/group/yuppframeworkphp


## CONTACTO: ##
Cualquier duda o sugerencia, publica tu comentario en nuestro grupo:
http://groups.google.com/group/yuppframeworkphp o en nuestra wiki:
http://code.google.com/p/yupp/w/list

```
Pablo Pazos
Líder del proyecto
www.SimpleWebPortal.net
```