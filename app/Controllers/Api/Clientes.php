<?php namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ClienteModel;

class Clientes extends ResourceController
{
    protected $modelName = 'App\Models\ClienteModel';
    protected $format    = 'json';

    /**
     * Devuelve una lista paginada y filtrada de clientes.
     */
    public function index()
    {
        // ... (La lógica de paginación que ya teníamos está perfecta) ...
        try {
            // 1. OBTENER PARÁMETROS DE LA PETICIÓN
            $page = $this->request->getGet('page') ?: 1;
            $rowsPerPage = $this->request->getGet('rowsPerPage') ?: 10;
            $filter = $this->request->getGet('filter');
            $sortBy = $this->request->getGet('sortBy') ?: 'apellido';
            $descending = $this->request->getGet('descending') === 'true';

            // 2. CONSTRUIR LA CONSULTA DE BÚSQUEDA
            $builder = $this->model->builder();
            if ($filter) {
                $builder->groupStart()
                        ->like('LOWER(apellido)', strtolower($filter))
                        ->orLike('LOWER(nombres)', strtolower($filter))
                        ->orLike('numero_documento', $filter)
                        ->groupEnd();
            }

            // 3. OBTENER EL NÚMERO TOTAL DE FILAS
            $total = $builder->countAllResults(false);

            // 4. APLICAR ORDENACIÓN Y PAGINACIÓN
            $offset = ($page - 1) * $rowsPerPage;
            $builder->orderBy($sortBy, $descending ? 'DESC' : 'ASC');
            // Si rowsPerPage es 0, significa "todos los resultados"
            if ($rowsPerPage > 0) {
                $builder->limit($rowsPerPage, $offset);
            }

            // 5. OBTENER LOS DATOS
            $data = $builder->get()->getResultArray();

            // 6. DEVOLVER LA RESPUESTA
            return $this->respond([
                'total' => $total,
                'data'  => $data
            ]);

        } catch (\Exception $e) {
            log_message('error', '[API Clientes] ' . $e->getMessage());
            return $this->failServerError('Error al consultar los clientes.');
        }
    }

    /**
     * Devuelve un único cliente por su ID.
     */
    public function show($id = null)
    {
        $cliente = $this->model->find($id);
        if ($cliente === null) {
            return $this->failNotFound('No se encontró un cliente con el ID ' . $id);
        }
        return $this->respond($cliente);
    }

    /**
     * Busca un cliente por su número de documento.
     *
     * @param string|null $numero_documento
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function dni(string $numero_documento = null)
    {
        if ($numero_documento === null) {
            return $this->failValidationErrors('Debe proporcionar un número de documento.');
        }

        $cliente = $this->model->where('numero_documento', $numero_documento)->first();

        if ($cliente === null) {
            return $this->respond([]); // Devolvemos un array vacío
        }

        return $this->respond($cliente);
    }    

    /**
     * Crea un nuevo cliente.
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        // El método 'insert' de CodeIgniter validará automáticamente los datos
        // usando las reglas que definimos en ClienteModel.
        if ($this->model->insert($data) === false) {
            // Si la validación falla, devolvemos un error 400 con los mensajes.
            return $this->failValidationErrors($this->model->errors());
        }
    
        // Si todo va bien, devolvemos una respuesta 201 (Created).
        return $this->respondCreated([
            'id' => $this->model->getInsertID(),
            'message' => 'Cliente creado exitosamente.'
        ]);
    }

    /**
     * Actualiza un cliente existente por su ID.
     */
    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        if (!$this->model->update($id, $data)) {
            return $this->failValidationErrors($this->model->errors());
        }
        return $this->respondUpdated(['id' => $id], 'Cliente actualizado exitosamente.');
    }

    /**
     * Elimina un cliente por su ID.
     */
    public function delete($id = null)
    {
        if ($this->model->find($id) === null) {
            return $this->failNotFound('No se encontró un cliente con el ID ' . $id);
        }
        $this->model->delete($id);
        return $this->respondDeleted(['id' => $id], 'Cliente eliminado exitosamente.');
    }
    
}