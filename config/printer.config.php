<?php
/**
 * Configuración de la API de Impresión Local
 * 
 * Este archivo centraliza la configuración de la impresora/API local
 * Permite conexión HTTP desde servidor HTTPS (evita Mixed Content)
 */

return [
    // Configuración de la API local de impresión
    'printer_api' => [
        // IP o hostname de la computadora con la impresora
        // Usar 'localhost' si está en el mismo servidor
        // O la IP local: '192.168.1.100'
        'host' => 'localhost',
        
        // Puerto de la API de impresión
        'port' => '8090',
        
        // Timeout en segundos para la conexión
        'timeout' => 10,
        
        // Endpoints disponibles
        'endpoints' => [
            'health' => '/health',
            'imprimir' => '/imprimir',
            'imprimir_multiple' => '/imprimir-multiple',
        ],
        
        // Habilitar logs de debug
        'debug' => false,
    ],
    
    // Si tienes múltiples impresoras en diferentes ubicaciones
    'printer_locations' => [
        'almacen_1' => [
            'host' => 'localhost',
            'port' => '8090',
        ],
        'almacen_2' => [
            'host' => '192.168.1.101',
            'port' => '8090',
        ],
        // Añadir más según necesites
    ],
];
