# Wp2Joomla - Migra artículos de Wordpress a Joomla 4

## Instalación

Importar la base de datos de wordpress con el prefijo wp_ en la misma base de datos que Joomla 4. Una vez importada la base de datos, instalamos el plugin de la carpeta plg_system_wp2joomla en Joomla 4.

Vamos a la consola de comandos del servidor y nos ubicamos en la carpeta raíz de Joomla 4. 

Importamos las categorías de Wordpress en Joomla 4 con:
```bash
php cli/joomla.php migrate:categories
```

y luego los Posts de wordpress con:

```bash
php cli/joomla.php migrate:articles
```

Aclaración: si un post de wordpress tiene más de una categoría se elegirá la primera que se obtenga desde la base de datos.