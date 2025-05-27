<?php
/**
 * Clase para manejar el inventario en formato JSON
 */
class Inventario {
    private $inventarioPath;
    private $data = null;
    
    /**
     * Constructor de la clase
     * 
     * @param string|null $path Ruta opcional al archivo JSON
     */
    public function __construct($path = null) {
        $this->inventarioPath = $path ?? __DIR__ . '/../Inventario/inventario.json';
        $this->cargarDatos();
    }
    
    /**
     * Carga los datos del archivo JSON
     * 
     * @return bool Verdadero si la carga fue exitosa
     */
    private function cargarDatos() {
        try {
            if (file_exists($this->inventarioPath)) {
                $jsonContent = file_get_contents($this->inventarioPath);
                if ($jsonContent === false) {
                    throw new Exception("No se pudo leer el archivo: {$this->inventarioPath}");
                }
                
                $this->data = json_decode($jsonContent, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
                }
                
                if (!$this->data || !isset($this->data['productos'])) {
                    $this->data = ['productos' => [], 'ultimaActualizacion' => date('Y-m-d H:i:s')];
                }
            } else {
                // Inicializar estructura vacía
                $this->data = ['productos' => [], 'ultimaActualizacion' => date('Y-m-d H:i:s')];
                
                // Intentar crear el directorio si no existe
                $dir = dirname($this->inventarioPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                // Guardar el archivo vacío
                $this->guardarDatos();
            }
            return true;
        } catch (Exception $e) {
            // En un entorno de producción, deberías registrar este error
            error_log("Error al cargar datos del inventario: " . $e->getMessage());
            $this->data = ['productos' => [], 'ultimaActualizacion' => date('Y-m-d H:i:s')];
            return false;
        }
    }
    
    /**
     * Obtiene todos los productos del inventario
     * 
     * @return array Lista de productos
     */
    public function obtenerProductos() {
        return $this->data['productos'] ?? [];
    }
    
    /**
     * Obtiene un producto por su código
     * 
     * @param string $codigo Código del producto
     * @return array|null Datos del producto o null si no se encuentra
     */
    public function obtenerProducto($codigo) {
        foreach ($this->data['productos'] as $producto) {
            if ($producto['codigo'] === $codigo) {
                return $producto;
            }
        }
        return null;
    }
    
    /**
     * Actualiza la cantidad de un producto
     * 
     * @param string $codigo Código del producto
     * @param int $cantidad Nueva cantidad
     * @param string $usuario Usuario que realiza la actualización
     * @param string|null $descripcion Descripción del producto (opcional)
     * @return bool Verdadero si la actualización fue exitosa
     */
    public function actualizarInventario($codigo, $cantidad, $usuario, $descripcion = null) {
        if (empty($codigo) || !is_string($codigo)) {
            return false;
        }
        
        // Asegurar que cantidad sea un entero
        $cantidad = intval($cantidad);
        if ($cantidad < 0) $cantidad = 0;
        
        // Validar usuario
        if (empty($usuario)) {
            $usuario = "Sistema";
        }
        
        $fecha = date('Y-m-d H:i:s');
        $encontrado = false;
        
        // Actualizar si ya existe
        foreach ($this->data['productos'] as &$producto) {
            if ($producto['codigo'] === $codigo) {
                $producto['inventario'] = $cantidad;
                $producto['usuarioUpdate'] = $usuario;
                $producto['fecha'] = $fecha;
                if ($descripcion) {
                    $producto['descripcion'] = $descripcion;
                }
                $encontrado = true;
                break;
            }
        }
        
        // Agregar si no existe
        if (!$encontrado && $descripcion) {
            $this->data['productos'][] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'inventario' => $cantidad,
                'usuarioUpdate' => $usuario,
                'fecha' => $fecha
            ];
        }
        
        $this->data['ultimaActualizacion'] = $fecha;
        return $this->guardarDatos();
    }
    
    /**
     * Elimina un producto del inventario
     * 
     * @param string $codigo Código del producto a eliminar
     * @return bool Verdadero si la eliminación fue exitosa
     */
    public function eliminarProducto($codigo) {
        if (empty($codigo)) {
            return false;
        }
        
        $encontrado = false;
        foreach ($this->data['productos'] as $i => $producto) {
            if ($producto['codigo'] === $codigo) {
                array_splice($this->data['productos'], $i, 1);
                $encontrado = true;
                break;
            }
        }
        
        if ($encontrado) {
            $this->data['ultimaActualizacion'] = date('Y-m-d H:i:s');
            return $this->guardarDatos();
        }
        
        return false;
    }
    
    /**
     * Busca productos por texto en código o descripción
     * 
     * @param string $texto Texto a buscar
     * @return array Lista de productos que coinciden con la búsqueda
     */
    public function buscarProductos($texto) {
        if (empty($texto)) {
            return $this->obtenerProductos();
        }
        
        $resultados = [];
        $texto = strtolower(trim($texto));
        
        foreach ($this->data['productos'] as $producto) {
            if (stripos($producto['codigo'], $texto) !== false || 
                stripos($producto['descripcion'], $texto) !== false) {
                $resultados[] = $producto;
            }
        }
        
        return $resultados;
    }
    
    /**
     * Guarda los datos en el archivo JSON
     * 
     * @return bool Verdadero si el guardado fue exitoso
     */
    public function guardarDatos() {
        try {
            // Ordenar productos por código antes de guardar
            usort($this->data['productos'], function($a, $b) {
                return strcmp($a['codigo'], $b['codigo']);
            });
            
            $jsonString = json_encode($this->data, JSON_PRETTY_PRINT);
            if ($jsonString === false) {
                throw new Exception("Error al codificar JSON: " . json_last_error_msg());
            }
            
            $result = file_put_contents($this->inventarioPath, $jsonString, LOCK_EX);
            if ($result === false) {
                throw new Exception("No se pudo escribir en el archivo: {$this->inventarioPath}");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error al guardar datos del inventario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene la fecha de última actualización
     * 
     * @return string Fecha de última actualización
     */
    public function obtenerUltimaActualizacion() {
        return $this->data['ultimaActualizacion'] ?? date('Y-m-d H:i:s');
    }
    
    /**
     * Obtiene estadísticas del inventario
     * 
     * @return array Estadísticas del inventario
     */
    public function obtenerEstadisticas() {
        $total = count($this->data['productos']);
        $conStock = 0;
        $sinStock = 0;
        $totalUnidades = 0;
        
        foreach ($this->data['productos'] as $producto) {
            $cantidad = intval($producto['inventario']);
            $totalUnidades += $cantidad;
            
            if ($cantidad > 0) {
                $conStock++;
            } else {
                $sinStock++;
            }
        }
        
        return [
            'totalProductos' => $total,
            'productosConStock' => $conStock,
            'productosSinStock' => $sinStock,
            'totalUnidades' => $totalUnidades,
            'ultimaActualizacion' => $this->obtenerUltimaActualizacion()
        ];
    }
}