<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class Vehiculos extends ResourceController
{
    protected $modelName = 'App\Models\VehiculoModel';
    protected $format    = 'json';
    
    /**
     * Return an array of resource objects, themselves in array format.
     *
     * @return ResponseInterface
     */
    public function index()
    {
        //
    }

    /**
     * Return the properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Return a new resource object, with default properties.
     *
     * @return ResponseInterface
     */
    public function new()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters.
     *
     * @return ResponseInterface
     */
    public function create()
    {
        $data = $this->request->getJSON(true);
        
        // Aquí podrías añadir reglas de validación en VehiculoModel si quieres
        if ($this->model->insert($data) === false) {
            return $this->failValidationErrors($this->model->errors());
        }
        
        // Devolvemos el ID del nuevo vehículo
        return $this->respondCreated(['id' => $this->model->getInsertID()]);
    }

    /**
     * Return the editable properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Delete the designated resource object from the model.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function delete($id = null)
    {
        //
    }

    /**
     * Devuelve todos los vehículos asociados a un cliente específico.
     */
    public function porCliente($cliente_id = null)
    {
        if ($cliente_id === null) {
            return $this->failValidationErrors('Debe proporcionar un ID de cliente.');
        }

        $db = \Config\Database::connect();
        $builder = $db->table('vehiculos v');
        $builder->select('v.id, v.patente, ma.marca, mo.modelo');
        $builder->join('modelos_vehiculos mo', 'mo.id = v.modelo_id');
        $builder->join('marcas_vehiculos ma', 'ma.id = mo.marca_id');
        $builder->where('v.cliente_id', $cliente_id);
        $vehiculos = $builder->get()->getResultArray();

        return $this->respond($vehiculos);
    }

    /**
     * Busca un único vehículo por su patente.
     */
    public function porPatente($patente = null)
    {
        if ($patente === null) {
            return $this->failNotFound('Debe proporcionar una patente.');
        }
    
        $db = \Config\Database::connect();
        $builder = $db->table('vehiculos v');
        
        // Seleccionamos todos los campos que necesitamos
        $builder->select(
            'v.id, v.patente, v.cliente_id, ' .
            'mo.id as modelo_id, mo.modelo, ' .
            'ma.id as marca_id, ma.marca, ' .
            'tv.id as tipo_vehiculo_id, tv.nombre as tipo_vehiculo_nombre, ' .
            'ca.id as carroceria_id, ca.nombre as carroceria_nombre, ' .
            'en.id as energia_id, en.nombre as energia_nombre'
        );
        
        // Unimos todas las tablas relacionadas
        $builder->join('modelos_vehiculos mo', 'mo.id = v.modelo_id');
        $builder->join('marcas_vehiculos ma', 'ma.id = mo.marca_id');
        $builder->join('tipo_vehiculo tv', 'tv.id = v.tipo_vehiculo_id', 'left'); // LEFT JOIN por si es null
        $builder->join('carroceria ca', 'ca.id = v.carroceria_id', 'left');   // LEFT JOIN por si es null
        $builder->join('energia en', 'en.id = v.energia_id', 'left');       // LEFT JOIN por si es null
        
        // Buscamos por la patente
        $builder->where('v.patente', $patente);
        
        $vehiculo = $builder->get()->getRowArray(); // getRowArray() para un solo resultado
    
        // Devolvemos el vehículo o un objeto vacío si no se encuentra
        return $this->respond($vehiculo ?? (object)[]);
    }
}
