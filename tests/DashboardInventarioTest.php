<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../class/bdfirebird.php';
require_once __DIR__ . '/../class/dashboardinventario.php';

class DashboardInventarioTest extends TestCase
{
    public function testGetAlmacenScopeConditionReturnsCorrectScope()
    {
        $dashboardinventario = new DashboardInventario();
        $alias = "ST";
        $almacenId = 5;

        $resultadoEsperado = " AND (" . $alias . ".STOCK_ALMACENIDACTUAL = " . $almacenId . " OR " . $alias . ".STOCK_ALMACENIDACTUAL IN (
            SELECT A.ALMACEN_ID
            FROM AMPAR_HIS_ALMACEN A
            WHERE A.ALMACEN_ALMACEN_MS = " . $almacenId . "
              AND A.ALMACEN_TIPOALMACEN = 3
              AND A.DELETED_AT IS NULL
        ))";

        $resultadoReal = $dashboardinventario->getAlmacenScopeCondition($alias, $almacenId);

        $this->assertEquals($resultadoEsperado, $resultadoReal);
    }

    public function testGetAlmacenScopeConditionStringReturnsCorrectScope()
    {
        $dashboardinventario = new DashboardInventario();
        $alias = "ST";
        $almacenId = "5";

        $resultadoEsperado = " AND (" . $alias . ".STOCK_ALMACENIDACTUAL = " . $almacenId . " OR " . $alias . ".STOCK_ALMACENIDACTUAL IN (
            SELECT A.ALMACEN_ID
            FROM AMPAR_HIS_ALMACEN A
            WHERE A.ALMACEN_ALMACEN_MS = " . $almacenId . "
              AND A.ALMACEN_TIPOALMACEN = 3
              AND A.DELETED_AT IS NULL
        ))";

        $resultadoReal = $dashboardinventario->getAlmacenScopeCondition($alias, $almacenId);

        $this->assertEquals($resultadoEsperado, $resultadoReal);
    }

    public function testGetArticulosPorCaducidadSinDatos()
    {
        $dbMock = $this->createMock(FirebirdConnection::class);
        $dbMock->expects($this->exactly(5))
            ->method('query')
            ->willReturn([]);

        $dashboard = new dashboardinventario();

        $resultado = $dashboard->getArticulosPorCaducidad($dbMock, 'global');

        $keysEsperadas = ['6m', '3m', '2m', '1s', 'caducado'];

        foreach ($keysEsperadas as $key) {
            $this->assertArrayHasKey($key, $resultado, "Falta la llave: $key");
            $this->assertIsArray($resultado[$key], "La llave $key deberia ser un array");
            $this->assertEmpty($resultado[$key], "La llave $key deberia estar vacia");
        }
    }

    public function testGetArticulosPorCaducidadConDatos()
    {
        $dbMock = $this->createMock(FirebirdConnection::class);
        $dbMock->expects($this->any())
            ->method('query')
            ->willReturnCallback(function ($sql) {
                if (strpos($sql, "'6m' AS ETAPA") !== false) {
                    return [
                        ['STOCK_ID' => 1, 'FOLIO' => '0122A', 'NOMBRE' => 'ARTICULO A', 'ALMACEN_ID' => 5]
                    ];
                }

                if (strpos($sql, "'3m' AS ETAPA") !== false) {
                    return [
                        ['STOCK_ID' => 2, 'FOLIO' => '0122B', 'NOMBRE' => 'ARTICULO B', 'ALMACEN_ID' => 5]
                    ];
                }

                if (strpos($sql, "'2m' AS ETAPA") !== false) {
                    return [
                        ['STOCK_ID' => 3, 'FOLIO' => '0122C', 'NOMBRE' => 'ARTICULO C', 'ALMACEN_ID' => 5]
                    ];
                }

                if (strpos($sql, "'1s' AS ETAPA") !== false) {
                    return [
                        ['STOCK_ID' => 4, 'FOLIO' => '0122D', 'NOMBRE' => 'ARTICULO D', 'ALMACEN_ID' => 5]
                    ];
                }

                if (strpos($sql, "'caducado' AS ETAPA") !== false) {
                    return [
                        ['STOCK_ID' => 5, 'FOLIO' => '0122E', 'NOMBRE' => 'ARTICULO E', 'ALMACEN_ID' => 5]
                    ];
                }

                return [];
            });

        $dashboard = new dashboardinventario();
        $resultado = $dashboard->getArticulosPorCaducidad($dbMock, 'global');

        $this->assertCount(1, $resultado['6m'], "Deberia haber 1 articulo en 6m");
        $this->assertEquals('ARTICULO A', $resultado['6m'][0]['NOMBRE']);

        $this->assertCount(1, $resultado['3m'], "Deberia haber 1 articulo en 3m");
        $this->assertEquals('ARTICULO B', $resultado['3m'][0]['NOMBRE']);

        $this->assertCount(1, $resultado['2m'], "Deberia haber 1 articulo en 2m");
        $this->assertEquals('ARTICULO C', $resultado['2m'][0]['NOMBRE']);

        $this->assertCount(1, $resultado['1s'], "Deberia haber 1 articulo en 1s");
        $this->assertEquals('ARTICULO D', $resultado['1s'][0]['NOMBRE']);

        $this->assertCount(1, $resultado['caducado'], "Deberia haber 1 articulo en caducado");
        $this->assertEquals('ARTICULO E', $resultado['caducado'][0]['NOMBRE']);
    }

    public function testEvaluarAccionInteligenteFallaSiYaEstaEnTraspaso()
    {
        $dbMock = $this->createMock(FirebirdConnection::class);
        $dbMock->expects($this->once())
            ->method('query')
            ->willReturn([
                ['TRASPASODET_ID' => 1]
            ]);

        $dashboard = new dashboardinventario();
        $resultado = $dashboard->evaluarAccionInteligenteStock($dbMock, 123);

        $this->assertFalse($resultado['ok']);
        $this->assertEquals('Este artículo ya está en traspaso.', $resultado['message']);
    }

    public function testEvaluarAccionInteligenteFallaSiNoExisteFisicamente()
    {
        $dbMock = $this->createMock(FirebirdConnection::class);
        $dbMock->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls(
                [],
                []
            );

        $dashboard = new dashboardinventario();
        $resultado = $dashboard->evaluarAccionInteligenteStock($dbMock, 123);

        $this->assertFalse($resultado['ok']);
        $this->assertEquals('El artículo ya no se encuentra físicamente en el almacén.', $resultado['message']);
    }

    public function testEvaluarAccionInteligenteStockSiYaEstaEnUnaMaleta()
    {
        $dbMock = $this->createMock(FirebirdConnection::class);
        $dbMock->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls(
                [],
                [['ALMACEN_TIPOALMACEN' => 3]]
            );

        $dashboard = new dashboardinventario();
        $resultado = $dashboard->evaluarAccionInteligenteStock($dbMock, 123);

        $this->assertFalse($resultado['ok']);
        $this->assertEquals('La acción inteligente solo aplica para artículos que no están en maleta.', $resultado['message']);
    }
}
