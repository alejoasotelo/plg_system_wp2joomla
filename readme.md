# Wp2Joomla - Migración de Posts de Wordpress a Joomla 4

Plugin para Joomla 4 que permite migrar artículos y categorías de Wordpress a Joomla 4 desde la consola de comandos.

## Ejecución

Tener instalado un Joomla 4 de pruebas, importar la base de datos de wordpress con el prefijo wp_ en la misma base de datos que Joomla 4. Una vez importada la base de datos, instalamos el plugin de la carpeta plg_system_wp2joomla en Joomla 4 como cualquier extensión desde el instalador en el administrador de Joomla 4 y lo habilitamos.
Vamos a la consola de comandos del servidor y nos ubicamos en la carpeta raíz de Joomla 4.

Importamos las categorías de Wordpress en Joomla 4 con:
```bash
php cli/joomla.php migrate:categories
```

y luego los Posts de wordpress con:

```bash
php cli/joomla.php migrate:articles
```

Una vez finalizado el proceso de migración en el Joomla de pruebas, si todo va bien, podemos migrar los datos en el Joomla de producción.

Aclaraciónes:
1. ⚠️ Es importante el órden de ejecución de los comandos. Primero las categorías y luego los artículos. Los artículos dependen de las categorías.
2. Si un post de Wordpress tiene más de una categoría se elegirá la primera que se obtenga desde la base de datos.3.  