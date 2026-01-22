<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
//$routes->get('/', 'Home::index');

// app/Config/Routes.php

$routes->group('api', ['filter' => 'cors'], static function ($routes) {
  $routes->get('clientes/dni/(:num)', 'Api\Clientes::dni/$1');
  $routes->get('vehiculos/cliente/(:num)', 'Api\Vehiculos::porCliente/$1'); 
  $routes->get('vehiculos/patente/(:any)', 'Api\Vehiculos::porPatente/$1');

  // Ruta para obtener la estructura del formulario de revisión
  $routes->get('revision-structure', 'Api\Revision::getStructure');

   // RUTAS RESTful estándar
  $routes->resource('clientes', ['controller' => 'Api\Clientes']);
  $routes->resource('marcas', ['controller' => 'Api\Marcas']);
  $routes->resource('vehiculos', ['controller' => 'Api\Vehiculos']);
  $routes->resource('tiposvehiculos', ['controller' => 'Api\TiposVehiculos']);
  $routes->resource('carrocerias', ['controller' => 'Api\Carrocerias']);
  $routes->resource('energias', ['controller' => 'Api\Energias']);
  $routes->resource('informes', ['controller' => 'Api\InformesTecnicos']);
  $routes->resource('estados-revision', ['controller' => 'Api\EstadosRevision']);
});

