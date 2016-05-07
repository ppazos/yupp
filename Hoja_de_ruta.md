  * http://code.google.com/p/yupp/downloads/list





En el momento tenemos un framework completo y funcional, con componentes de ORM (Mapeo Objeto-Relacional) y MVC (Model-View-Controller).

Igualmente todavía queda mucho para mejorar el framework, para arreglar arreglar, testear y documentar.

Si te interesa participar en alguna de estas áreas puedes hacérnoslo saber publicando un nuevo debate, son todos bienvenidos!

Aquí listaremos ideas y tareas pendientes  las iremos acomodando en las sucesivas liberaciones, intentando liberar una versión cada 3 o 4 semanas, y cada paso no tendrá mas de 4 o 5 tareas a realizar, para poder hacerlas en tiempo y forma, claro que esto depende de la complejidad de las tareas.

Para los problemas y errores que vayamos encontrando empezaremos a utilizar: http://code.google.com/p/yupp/ que tiene un sistema de tickets incorporado. Voy a estar migrando los tickets que tengo en un repositorio local para ahi, asi todo el mundo tiene acceso y quien quiera puede realizar alguna tarea y ayudar a avanzar. Luego definiremos un proceso de contribuyentes asi gente que esté interesada en el proyecto pueda mandar código corregido (patch).


## v0.1.3: liberado ##

  * Soporte para layouts (basico, basado en una tag)
  * Implementar soporte para serializacion de modelo a JSON (basico, para mejorar y completar mas adelante)
  * Soporte para SQLite


## v0.1.4: liberado ##

  * Integración con Prototype JS
  * TICKET #29: corregir las consultas SQL para hacer listados, de forma de disminuir la cantidad de datos cargada.
  * Se hicieron multiples mejoras tanto al componente MVC como al compoente de persistencia ver lista de cambios: http://www.simplewebportal.net/host/1022.htm


## v0.1.5: liberado ##

  * Implementar el mapeo de herencia sobre multiples tablas (ahora se usa la estrategia de herencia por tabla) [ESTOY TRABAJANDO EN ESTO!](AHORA.md)


## v0.1.6: liberado ##

  * Agregar tipos a los atributos hasMany (colection, set, list).
  * Agregar interfaz para generar las tablas de la base de datos.
  * Implementar custom validators para validación automática de información.


## v0.1.6-1: liberado ##

  * Agregar el chequeo de que si la vista que referencia el retorno "render" de una acción de un controller no existe, que intente buscar en las vistas de scaffolding dinámico una vista con el mismo nombre de la acción y le pase como parámetro la clase del modelo. Esto sirve para que no sea necesario generar las vistas "list", "show", "create" y "edit" mientras se está desarrollando. Sería bueno mostrar un pequeño warning en la página cuando se detectó que la vista no existe y se encontró una vista en scaffolding. Si ni siquiera se encuentra una vista en scaffolding dinámico, ahí se muestra diréctamente un error (debería lanzar una excepción).


## v0.1.6-2: liberado ##

  * Correccion en PersistentManager.generate() para que genere todas las tablas intermedias con la columna "ord" que se utiliza cuando el atributo hasMany es de tipo LIST, cuando es de tipo SET o COLLECTION, no se toma en cuenta.
  * Se agregó soporte para incluir CSS o imagenes desde un componente. Para esto se modificó el helper "css" y se agregó el helper "img".
  * Corrección al helper "errors", lanzaba un error si el elemento no tenía errores.
  * Correcciön al método "hasErrors" de PersistentObject, que no verificaba que el campo errors fuera NULL.
  * Se agrega el constructor para la condicion Not Equal a la clase Condition.
  * Correccion del metodo addOrder de la clase Criteria2.
  * Corrección de problema con la extracción de los parámetro de la urls del estilo:
    * http://localhost:8081/YuppPHPFramework/portal/page/display/mi_pagina_bbb/sdfda/asdf?as=sdfg
  * Varias correcciones para compatibilizar Yupp con versiones anteriores de PHP (PHP 5.2.0 y 5.2.1).
  * Se agregó el método firstToUpper a core.basic.String.
  * Se agregaron los siguientes campos a la configuración de Yupp (clase YuppConfig):
    * currentMode: indica el modo de ejecucion de la aplicacion (development, production o testing).
    * modeDefaultMapping: indica que accion debe ejecutarse por defecto, dependiendo del modo actual, al acceder a la aplicación.
    * Se agregó el método addCustomParams en la clase routing.Filter


## v0.1.6-3: liberado ##

  * Implementacion de helpers para formularios y formularios ajax, utiliza el plugin forms de jQuery.
  * Helpers: Correccion de compatibilidad con PHP 5.2.8
  * Corregido que si se crean urls con parametros llamados _param\_1,_param\_2, etc, esos se pongan en la propia url sin ?_param\_1&...
  * Correccion de clase Filter, el metodo que procesa los params dependía de la cantidad de directorios en la ruta a donde está instalado Yupp Framework, ahora es independiente de donde se instale el framework.
  * Modificacion a la forma que se verifican los filtros de controllers.
  * Correccion de metodo PersistentObject.hasErrors, tenia un error en la condicion._


## v0.1.6-4: liberado ##

  * Se corrigió y mejoró el web flow.
  * Se agrego el metodo 'validateOnly' a la clase PersistentObject.
  * Correcciones para DRUD y vistas dinamicas.
  * Cambia el nombre de la clase ControllerFilter2 por YuppControllerFilter.
  * Se agregó addslashes en los métodos insert\_query y update\_query de DAL y stripslashes en PersistentManager, para resolver problemas con caracteres de control de MySQL.
  * Se agregó el helper "pager" para crear links de paginación de registros para los listados.
  * Se agrego el helper orderBy para crear columnas ordenables en los listados.
  * Corrección de restricción de email.


## v0.1.6-5: liberado ##

  * Corregida la comparación de strings para generar consultas MySQL (donde el "=" no considera mayúsculas y minúsculas)
    * Para esto se reescribió completamente el componente que genera las consultas SQL, para que considera las particularidades de cada DBMS (MySQL, SQLite, PostgreSQL, etc)
  * Se agrega el método YuppController.componentControllersAction() que sirve para mostrar los controladores de un componente dado.
  * Se agrega la restricción "inList" que permite verificar si un valor está en una lista de valores dados.
  * Se agrega el metodo PersistentObject.hasFieldErrors( $attr ) para preguntar si existen errores en el valor de un atributo particular.
  * Se agrega el metodo PersistentObject.attributeDeclaredOnThisClass() para saber si un determinado atributo fue declarado en una clase. Sirve para derivar los nombres de las tablas intermedias en relaciones multiples.
  * Se agrega el helper DisplayHelper.yupp\_select para crear selects html de forma sencilla.
  * Se corrije el metodo PersistentObject.validate() para que priorice la validacion de nulos y vacios sobre el resto de las validaciones. Ahora si un valor es nullable(true) y tambien debe ser email(), la validacion de un valor vacio da true, antes fallaba en la validacion de email.


## v0.1.6-6: liberado ##

  * Correcciones menores en la clase Contraints.
  * El método PersistentObject.setProperties() ahora hace trim de los valores antes de asginarlos a los campos, esto es para evitar el llenado accidental de datos con espacios en blanco delante o detrás del valor ingresado.
  * Corrección en el método YuppConventions::relTableName(..) donde se creaba mal el nombre de la tabla intermedia para relaciones hasMany si se trataba de salvar una subclase y el atributo estaba declarado en su superclase.
  * Se corrige el helper Helpers.template() para mejorar el pasaje de parámetros.
  * Correccion en el mapeo de herencia de tabla múltiple para soportar modelos de relaciones y herencia complejos (PersistentManager, PersistentObject, MultipleTableInheritanceSupport).


## v0.1.6-7: liberado ##

  * Agregamos que se permita tener varios subdirectorios dentro del directorio de clases de modelo, permitiendo ordenar las clases del modelo cuando son muchas.
  * Se corrige el método ModelUtils.getModelClasses() para poder crear subdirectorios en el directorio del modelo de un componente y asi mejorar la organización de las clases del modelo.
  * Corrección al método YuppConventions::getModelPath().
  * Corrección en PackageNames a una expresión regular para poder poner subdirectorios en el modelo.
  * Correcciones en YuppLoader, en el cargado del modelo para permitir definir clases en subdirectorios.
  * Se corrije el metodo YuppController::getFlash() y se hace limpieza.


## v0.1.7: liberado ##

  * Se corrije un bug que se liberó con Yupp 0.1.6.7 en la clase routing.Executer, pasaba cuando se retornaba null de una accion de un controller.
  * Corrección de DatabaseMySQL en consultas que buscan por strings numéricos.
  * Se eliminaron las clases Filter y Mapping, y se creó una clase Router que cumple las tareas de las elminadas y es mucho mas simple de usar. (http://code.google.com/p/yupp/issues/detail?id=10)
  * YuppContext se quitan pasajes por referencia.
  * Se agregan pruebas de generacion de controles complejos en formularios: html y calendar. html usa TinyMCE y calendar usa YUI Calendar. Se agregan ambas librerias al framework.
  * Primer integración de PostgreSQL a Yupp. Nueva clase DatabasePosgreSQL.
  * Correcciones a DAL, PersistentObject y PersistentManager.


## v0.1.8: liberado ##

  * http://code.google.com/p/yupp/issues/detail?id=19 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=22 (HECHO)
  * Creación de juegos de test para validar el ORM (HECHO 30%).


## v0.1.9: liberado ##

  * Implementar soporte para serializacion de instancias del modelo persistente a XML
  * Crear demos/tests de los helpers
  * Creación de juegos de test para validar el ORM
  * Soporte para requests y responses HTTP.
  * Se corrigieron bugs en PersistenManager, Helpers, RequestManager, YuppForm2 y String.


## v0.2.0: liberado ##

  * http://code.google.com/p/yupp/issues/detail?id=32 (Mejorar la inclusión de librerías JavaScript) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=33  (Creación de agregaciones para las consultas creadas con Query) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=35 (Corregir la carga de campos booleanos) (HECHO)
  * Corrección en la clase HTTPResponse que parseaba mal la respuesta a HTTPRequest.


## v0.2.1: liberado ##

  * Completar la definicion de componentes para que sean auto-contenidos (modelo, controllers, vistas, filtros, mappings, i18n, etc) (HECHO)
  * Se hizo la generación de estructuras para nuevas aplicaciones (http://code.google.com/p/yupp/issues/detail?id=47)
  * http://code.google.com/p/yupp/issues/detail?id=23 (TICKET #40:hacer que los mensajes de error de validación sean i18n.) (HECHO)
  * Primer versión de Yupp Desktop, desde donde ser permite el acceso a las aplicaciones, la generación de tablas y la creación de nuevas aplicaciones de forma simple.
  * Nuevo componente de testing para crear y ejecutar casos de prueba de forma automática.
  * Correcciones al paquete http.


## v0.2.2: liberado ##

  * http://code.google.com/p/yupp/issues/detail?id=34 (Configuración por componente) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=44 (Agregar un app descriptor a cada componente) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=58 (Problemas con querys por valores nulos en MySQL) (HECHO)
  * Otras correcciones menores.


## v0.2.3: en svn ##

  * SVN: http://yupp.googlecode.com/svn/tags/v023/

  * http://code.google.com/p/yupp/issues/detail?id=61 (Scaffolding dinamico para vistas sin acciones) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=62 (Error al correr el bootstrap de las aplicaciones con configuracion de BD aparte) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=31 (Flexibilizar el retorno de las acciones de los controllers) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=12 (Mejorar la vista de scaffolding dinámico para hasOne) (HECHO)


## v0.2.4: en svn ##

  * http://code.google.com/p/yupp/issues/detail?id=67 (Implementar el dirty bit para mejorar la performance de las operaciones de update) (HECHO).
  * http://code.google.com/p/yupp/issues/detail?id=53 (generar vistas para errores http comunes) (HECHO)
  * Mejora de helper "img" para tomar parámetros extra, como "align", y agregarlos a la tag img generada. (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=64 (Error introducido en la versión 0.2.3, donde no se recordaba el locale entre requests al cambiar el idioma) (HECHO)
  * Además se hicieron:
    * Mejoras al Logger para poder loguear a un archivo.
    * Mejoras en los helpers que generan vistas dinámicas.
    * Correcciones en la clase ArtifactHolder.
    * Se crea la clase Timer para medir tiempos de ejecución.
    * Se agregan paquetes faltantes en el cálculo de las LOCs en la clase YuppStats.
    * Se agrega el método appExists($appName) en la clase Yupp.
    * Se agrega metodo dump para debug en clase YuppSession.
    * Se agregan tests de dirty bits a la aplicación "tests".
    * Correción en CoreController para poder ejecutar bootstraps de aplicaciones con su propia configuración de la base de datos.


## v0.2.5: codename Genesis (liberado) ##

Descarga: http://code.google.com/p/yupp/downloads/list

  * Agregar soporte para XSL en la generación de XML a partir de clases del modelo. (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=20 (Nullable e inList falla cuando viene un valor nulo) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=23 (Corregir mensajes de error de la verificacion de restricciones) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=66 (Mensaje de error de validacion vacio) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=71 (Error al copiar un a nueva app y entrar a dbStatus) (HECHO)
  * Correcciones al flujo de procesamiento del request en RequestManager
  * Simplificacion de las inclusiones para mejorar performance en index.php


## v0.3: codename Genesis 2 (liberado) ##

  * Generar instancias de PO a parir del XML generado al serializar la instancia (transformación inversa a la serialización). (HECHO)
  * Sacar la serializacion a XML de PO a una clase aparte. (HECHO)
  * Juntar paquetes support y utils en utils. Poner en ese utils las clases del paquete config que no sean de configuracion del framework.
  * Se agrega el parámetro 'path' al helper template. (HECHO)
  * Se agrega el parámetro 'attrs' a los helpers link y ajax\_link. (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=78 (Error por uso de is\_a()) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=30 (Se completa la generación recursiva de JSON) (HECHO)
  * Se mejora la vista de ajaxLinkTest para seleccionar librería javascript entre prototype y jquery.
  * Se corrige un warning que daba PHP 5.2.10 por llamar al date() sin haber seteado el default time zone.
  * http://code.google.com/p/yupp/issues/detail?id=70 (Mostrar la columna por la cual esta ordenada un listado) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=6 (Se agrega shortcut al helper message) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=29 (Se agrega seguridad para ejecutar acciones de CoreController en modo PROD) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=13 (Crear helpers para generación de campos para formularios) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=37 (Se cierra luego de verificar que funciona) (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=28 (Si YuppForm no recibe los parametros para armar la url de la acción, los toma del contexto) (HECHO)
  * Se incluyeron las nuevas versiones de las librerías javascript jQuery y Prototype. (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=72 (Se implementa método PO.preValidate()) (HECHO)
  * Mejoras a Yupp Desktop: filtro de aplicaciones, acceso a noticias desde twitter (HECHO)


## v0.4: codename Genesis 3 (liberado) ##

  * Extender la configuracion de DB de las aplicaciones para soportar los modos de ejecucion. (HECHO)
  * Agregar las referencias por loops en serialización a XML y JSON (HECHO).
  * http://code.google.com/p/yupp/issues/detail?id=97 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=16 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=87 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=84 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=93 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=91 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=86 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=82 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=99 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=103 (HECHO)
  * Actualización de librerías javascript: jQuery 1.5.1, TinyMCE 3.4.2
  * Correccion de bug en removeFrom para relaciones hasMany
  * http://code.google.com/p/yupp/issues/detail?id=106 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=109 (HECHO)
  * http://code.google.com/p/yupp/issues/detail?id=111 (HECHO)


## v0.5: codename Genesis 4 (liberado) ##

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


## v0.6: codename Deep Purple ##

  * Corregir información duplicada de ObjectReference en PersistentManager.generate, cuando se generan las tablas intermedias de relaciones hasMany.
    * No dejar ejecutar tests en modo prod, tampoco correr bootstraps (es por seguridad).
  * Integracion de modos de desarrollo, produccion y testing, y definición del comportamiento del sistema en cada modo.
  * Agregar el procesamiento de referencias de loops en la deserialización de XML a PO.
  * Mejorar Yupp Desktop con características como cantidad de información que se muestra de cada aplicación, ordenamiento y ubicación de iconos, búsqueda y filtrado de aplicaciones por nombre, descripción y tags, mostrar tags, permitir mostrar Yupp Desktop aún en modo PROD, permitir editar datos de descripción de las aplicaciones, como tags...
  * Capacidad de instalar y desinstalar aplicaciones a modo de sistema operativo instalando y desinstalando programas. Ideas de scripts para comprimir y descomprimir apps:
    * http://www.phpconcept.net/pclzip/user-guide
    * http://www.phpclasses.org/package/945-PHP-Create-tar-gzip-bzip2-zip-extract-tar-gzip-bzip2-.html
    * http://www.phpclasses.org/package/6110-PHP-Create-archives-of-compressed-files-in-ZIP-format.html


## v0.7: ##

  * Mejoras para performance (verificar inclusiones y dependencias, optimizacion de codigo).
  * Integración con otras librerías JS/FX/AJAX
  * Soporte para internacionalización automática de fechas
  * http://code.google.com/p/yupp/issues/detail?id=41 (Agregar métodos dinámicos para realizar búsquedas)
  * http://code.google.com/p/yupp/issues/detail?id=36 (Operaciones de inspeccion)
  * Documentacion de los ciclos de vida de PO y todas las operaciones del YORM (necesario para mejorar performance y optimizar código).


## v0.8: codename U2 ##

  * Arquitectura de plugins: componentes de bajo nivel capaces de ser instalados y usados por varios componentes (ajax, helpers, integracion con otras librerias p.e. javascript, WS, ORM externo, etc).
  * Crear un par de plugins de ejemplo: p.e. obtener actualizaciones e instalar aplicaciones. O reporte de bugs desde el propio framework.
  * Integración de SQL Server.


## v0.9: ##

  * Integración del YORM con sistemas externos mediante WS:
    * Soporte para fuentes de datos JSON
    * Soporte para fuentes de datos XML
  * http://code.google.com/p/yupp/issues/detail?id=83 (línea de comandos)
  * http://code.google.com/p/yupp/issues/detail?id=39 (Agregar soporte a Yupp Paths)


## v1.0: codename The Beatles ##

  * Verificación de errores y comportamiento robusto (soporte de mal pasaje de urls y parámetros)



Algunos temas importantes a organizar:

  * Soporte para Web Services (exponer WS mediante SOAP, Yupp ya es REST, manejar requests, parámetros, attachs, etc, procesar resultado)
  * Soporte para controles de GUI complejos (desde helpers, integrados con alguna lib javascript y posiblemente con ajax y fx)
  * Generación automática del modelo a partir de una base de datos existente.


Algunos temas que se irán haciendo a medida que haya tiempo:

  * Característica YORM: campos calculados. Que se puedan definir funciones asociadas a campos y cuando se salva una instancia estas funciones se ejecutan y se asigna el valor resultante al campo. El framework agregaría la ejecución de esos métodos, para que el usuario no tenga que invocarlos él mismo cada vez que va a guardar algo. Esto sirve para calcular campos en función de los otros campos de la clase, incluyendo sus atributos relacionados, por ejemplo si se tiene una factura con líneas, el total de la factura se calcula a partir del parcial de cada línea.
  * PHPDoc de los paquetes core y db
  * PHPDoc del módulo persistent
  * PHPDoc del módulo mvc
  * TICKET #53: agregar tipos de datos para representar strings chicos y muy grandes.
  * Corregir generación de FKs en SQLite.
  * Agregar el tipo de hasMany "ORDERED\_SET", que se comporta como un SET y como una LIST (no permite repetidos y conserva el orden).


Ideas y nuevas características:

  * I18nImage e I18nResource: imágenes y otros recursos que dependan del locale seleccionado.