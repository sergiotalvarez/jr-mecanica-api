<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\TipoRevisionModel;
use App\Models\PuntoRevisionModel;

class Revision extends ResourceController
{
    protected $format = 'json';

    public function getStructure()
    {
        try {
            $tipoRevisionModel = new TipoRevisionModel();
            $puntoRevisionModel = new PuntoRevisionModel();

            $tipos = $tipoRevisionModel->orderBy('orden', 'ASC')->findAll();
            $structure = [];

            if (!empty($tipos)) {
                foreach ($tipos as $tipo) {
                    $puntos = $puntoRevisionModel
                        ->where('tipo_revision_id', $tipo['id'])
                        ->where('activo', true)
                        ->orderBy('orden', 'ASC')
                        ->findAll();
                    
                    if (!empty($puntos)) {
                        $tipo['puntos_revision'] = $puntos;
                        $structure[] = $tipo;
                    }
                }
            }

            return $this->respond($structure);

        } catch (\Exception $e) {
            log_message('error', '[API Revision::getStructure] ' . $e->getMessage());
            return $this->failServerError('Error al obtener la estructura de revisión: ' . $e->getMessage());
        }
    }
}
