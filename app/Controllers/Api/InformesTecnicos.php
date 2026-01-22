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

    public function create()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $payload = $this->request->getJSON(true);

            // --- PASO 1: MANEJAR EL VEHÍCULO ---
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
                    // Si falla la inserción del vehículo, detenemos todo
                    return $this->failValidationErrors($vehiculoModel->errors());
                }
                $vehiculo_id = $vehiculoModel->getInsertID();
            } else {
                $vehiculo_id = $vehiculoData['id'];
            }

            // --- PASO 2: CREAR EL INFORME PRINCIPAL ---
            $informeData = [
                'vehiculo_id'                   => $vehiculo_id,
                'cliente_id'                    => $payload['cliente_id'],
                'kilometraje_actual'            => $payload['kilometraje_actual'],
                'kilometraje_proximo_servicio'  => $payload['kilometraje_proximo_servicio'],
                'fecha_proximo_servicio'        => $payload['fecha_proximo_servicio'],
                'informe_final'                 => $payload['informe_final'],
            ];
            if ($this->model->insert($informeData) === false) {
                return $this->failValidationErrors($this->model->errors());
            }
            $informe_id = $this->model->getInsertID();

            // --- PASO 3: GUARDAR LOS PUNTOS DE REVISIÓN ---
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
            $db->transRollback(); // Aseguramos el rollback en caso de excepción
            log_message('error', '[API Create Informe] ' . $e->getMessage() . ' en la línea ' . $e->getLine());
            // 2. DEVOLVEMOS EL ERROR REAL PARA DEPURAR
            return $this->failServerError('Error en el servidor: ' . $e->getMessage());
        }
    }
}