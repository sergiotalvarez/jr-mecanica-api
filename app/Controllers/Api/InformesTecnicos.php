<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
// 1. IMPORTAMOS TODOS LOS MODELOS NECESARIOS
use App\Models\VehiculoModel;
use App\Models\InformePuntoRevisionModel;

class InformesTecnicos extends ResourceController
{
    protected $modelName = 'App\Models\InformeTecnicoModel';
    protected $format    = 'json';

    public function index()
    {
        try {
            $page = $this->request->getGet('page') ?: 1;
            $rowsPerPage = $this->request->getGet('rowsPerPage') ?: 10;
            $filter = $this->request->getGet('filter');
            $sortBy = $this->request->getGet('sortBy') ?: 'fecha_hora';
            $descending = $this->request->getGet('descending') === 'true';

            $db = \Config\Database::connect();
            $builder = $db->table('informes_tecnicos it');

            $builder->select(
                'it.id, it.fecha_hora, it.estado, it.precio, ' .
                'c.apellido as cliente_apellido, c.nombres as cliente_nombres, ' .
                'v.patente, ma.marca, mo.modelo'
            );
            $builder->join('clientes c', 'c.id = it.cliente_id', 'left');
            $builder->join('vehiculos v', 'v.id = it.vehiculo_id', 'left');
            $builder->join('modelos_vehiculos mo', 'mo.id = v.modelo_id', 'left');
            $builder->join('marcas_vehiculos ma', 'ma.id = mo.marca_id', 'left');

            if ($filter) {
                $builder->groupStart()
                        ->like('LOWER(v.patente)', strtolower($filter))
                        ->orLike('LOWER(c.apellido)', strtolower($filter))
                        ->orLike('LOWER(c.nombres)', strtolower($filter))
                        ->groupEnd();
            }

            $total = $builder->countAllResults(false);

            $sortableColumns = [
                'id' => 'it.id',
                'fecha_hora' => 'it.fecha_hora',
                'precio' => 'it.precio'
            ];
            $sortColumn = $sortableColumns[$sortBy] ?? 'it.fecha_hora';
            
            $offset = ($page - 1) * $rowsPerPage;
            $builder->orderBy($sortColumn, $descending ? 'DESC' : 'ASC');
            if ($rowsPerPage > 0) {
                $builder->limit($rowsPerPage, $offset);
            }

            // --- LÍNEA DE DEBUG ---
            // Descomenta la siguiente línea para ver el SQL exacto que se está generando
            // log_message('error', 'SQL Query: ' . $builder->getCompiledSelect());

            $data = $builder->get()->getResultArray();

            return $this->respond(['total' => $total, 'data' => $data]);

        } catch (\Exception $e) {
            log_message('error', '[API InformesTecnicos::index] ' . $e->getMessage() . ' en la línea ' . $e->getLine());
            // ¡ESTA ES LA LÍNEA MÁS IMPORTANTE! DEVOLVERÁ EL ERROR REAL
            return $this->failServerError('Error al consultar los informes: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $payload = $this->request->getJSON(true);

            $vehiculoData = $payload['vehiculo'];
            $vehiculo_id = null;

            if ($vehiculoData['isNew']) {
                $vehiculoModel = new VehiculoModel();
                $newVehiculo = [
                    'cliente_id'        => $payload['cliente_id'],
                    'modelo_id'         => $vehiculoData['modelo_id'],
                    'patente'           => $vehiculoData['patente'],
                    'tipo_vehiculo_id'  => $vehiculoData['tipo_vehiculo_id'],
                    'carroceria_id'     => $vehiculoData['carroceria_id'],
                    'energia_id'        => $vehiculoData['energia_id'],
                ];
                if ($vehiculoModel->insert($newVehiculo) === false) {
                    return $this->failValidationErrors($vehiculoModel->errors());
                }
                $vehiculo_id = $vehiculoModel->getInsertID();
            } else {
                $vehiculo_id = $vehiculoData['id'];
            }

            $informeData = [
                'vehiculo_id'                   => $vehiculo_id,
                'cliente_id'                    => $payload['cliente_id'],
                'kilometraje_actual'            => $payload['kilometraje_actual'],
                'kilometraje_proximo_servicio'  => $payload['kilometraje_proximo_servicio'],
                'fecha_proximo_servicio'        => $payload['fecha_proximo_servicio'],
                'informe_final'                 => $payload['informe_final'],
                'precio'                        => $payload['precio'] ?? null     
            ];

            if ($this->model->insert($informeData) === false) {
                return $this->failValidationErrors($this->model->errors());
            }
            $informe_id = $this->model->getInsertID();

            if (isset($payload['revision_items']) && !empty($payload['revision_items'])) {
                $informePuntosModel = new InformePuntoRevisionModel();
                foreach ($payload['revision_items'] as $item) {
                    $item['informe_tecnico_id'] = $informe_id;
                    if ($informePuntosModel->insert($item) === false) {
                        return $this->failValidationErrors($informePuntosModel->errors());
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('Ocurrió un error durante la transacción.');
            }

            return $this->respondCreated(['id' => $informe_id], 'Informe guardado exitosamente.');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[API Create Informe] ' . $e->getMessage() . ' en la línea ' . $e->getLine());
            return $this->failServerError('Error en el servidor: ' . $e->getMessage());
        }
    }

    public function show($id = null)
    {
        try {
            // 1. Buscamos el informe principal
            $informe = $this->model->find($id);
            if ($informe === null) {
                return $this->failNotFound('No se encontró el informe con el ID ' . $id);
            }

            // 2. Buscamos los detalles de la revisión
            $puntosModel = new \App\Models\InformePuntoRevisionModel();
            $informe['revision_items'] = $puntosModel->where('informe_tecnico_id', $id)->findAll();

            // 3. Buscamos el objeto completo del cliente
            $clienteModel = new \App\Models\ClienteModel();
            $informe['cliente'] = $clienteModel->find($informe['cliente_id']);

            // 4. Buscamos el objeto completo del vehículo (con todos sus detalles)
            $db = \Config\Database::connect();
            $builder = $db->table('vehiculos v');
            $builder->select(
                'v.id, v.patente, v.cliente_id, ' .
                'mo.id as modelo_id, mo.modelo, ' .
                'ma.id as marca_id, ma.marca, ' .
                'tv.id as tipo_vehiculo_id, tv.nombre as tipo_vehiculo_nombre, ' .
                'ca.id as carroceria_id, ca.nombre as carroceria_nombre, ' .
                'en.id as energia_id, en.nombre as energia_nombre'
            );
            $builder->join('modelos_vehiculos mo', 'mo.id = v.modelo_id', 'left');
            $builder->join('marcas_vehiculos ma', 'ma.id = mo.marca_id', 'left');
            $builder->join('tipo_vehiculo tv', 'tv.id = v.tipo_vehiculo_id', 'left');
            $builder->join('carroceria ca', 'ca.id = v.carroceria_id', 'left');
            $builder->join('energia en', 'en.id = v.energia_id', 'left');
            $builder->where('v.id', $informe['vehiculo_id']);
            $informe['vehiculo'] = $builder->get()->getRowArray();

            // 5. Devolvemos el objeto completo
            return $this->respond($informe);

        } catch (\Exception $e) {
            log_message('error', '[API InformesTecnicos::show] ' . $e->getMessage());
            return $this->failServerError('Error al consultar el detalle del informe: ' . $e->getMessage());
        }
    }
}