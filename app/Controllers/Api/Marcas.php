<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\MarcaModel;
use App\Models\ModeloModel;

class Marcas extends ResourceController
{
    /**
     * Devuelve un array de Marcas con sus Modelos anidados.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function index()
    {
        // 1. Validamos el token JWT que viene en la cabecera
        // Por ahora, fallará si usas un token de relleno, es normal.
        //$decodedToken = validateJWTFromRequest();
        //if ($decodedToken === null) {
        //    return $this->failUnauthorized('Acceso denegado. Token inválido o ausente.');
        //}

        try {
            // 2. Instanciamos los modelos para interactuar con la BD
            $marcaModel = new MarcaModel();
            $modeloModel = new ModeloModel();

            // 3. Obtenemos todas las marcas, ordenadas alfabéticamente
            $marcas = $marcaModel->orderBy('marca', 'ASC')->findAll();

            $resultado = [];

            // 4. Si encontramos marcas, iteramos para buscar sus modelos
            if (!empty($marcas)) {
                foreach ($marcas as $marca) {
                    // Buscamos los modelos para la marca actual
                    $modelos = $modeloModel->where('marca_id', $marca['id'])
                                           ->orderBy('modelo', 'ASC')
                                           ->findAll();
                    
                    // Añadimos el array de modelos (incluso si está vacío) a la marca
                    $marca['modelos'] = $modelos;
                    $resultado[] = $marca;
                }
            }
            
            // 5. Devolvemos el resultado final como un JSON
            return $this->respond($resultado);

        } catch (\Exception $e) {
            // Si algo falla con la BD, devolvemos un error de servidor
            log_message('error', '[API Marcas] ' . $e->getMessage());
            //return $this->failServerError('Ha ocurrido un problema en el servidor al consultar las marcas.');
            return $this->failServerError($e->getMessage());
        }
    }
    
    // Dejamos los otros métodos vacíos por ahora
    public function show($id = null) {}
    public function new() {}
    public function create() {}
    public function edit($id = null) {}
    public function update($id = null) {}
    public function delete($id = null) {}
}