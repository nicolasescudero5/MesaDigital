<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CategoriasSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'nombre' => 'Legales — Laboral', 'descripcion' => 'Cuestiones laborales, telegramas y acuerdos', 'orden' => 1, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 2, 'nombre' => 'Legales — Civil y Comercial', 'descripcion' => 'Contratos, reclamos y cédulas judiciales', 'orden' => 2, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 3, 'nombre' => 'Legales — Municipal / Regulatorio', 'descripcion' => 'Inspecciones, tasas y requerimientos de organismos', 'orden' => 3, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 4, 'nombre' => 'Capital Humano (RRHH)', 'descripcion' => 'Comunicaciones de personal, licencias y legajos', 'orden' => 4, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 5, 'nombre' => 'Real Estate / Mantenimiento Edilicio', 'descripcion' => 'Obras, proveedores de infraestructura y servicios', 'orden' => 5, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 6, 'nombre' => 'Dirección Académica', 'descripcion' => 'Notas de familias, proyectos pedagógicos y circulares', 'orden' => 6, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 7, 'nombre' => 'Administración y Facturación', 'descripcion' => 'Facturas, comprobantes de pago y cobranzas', 'orden' => 7, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 8, 'nombre' => 'Seguridad e Higiene', 'descripcion' => 'Protocolos de evacuación, simulacros y revisiones ART', 'orden' => 8, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 9, 'nombre' => 'Sistemas / IT', 'descripcion' => 'Equipamiento informático, licencias y conectividad', 'orden' => 9, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 10, 'nombre' => 'Paquetería Personal', 'descripcion' => 'Envíos personales para colaboradores de la institución', 'orden' => 10, 'es_reserva' => 0, 'activo' => 1],
            ['id' => 11, 'nombre' => 'Sin Clasificar', 'descripcion' => 'Categoría de reserva para reasignación y descarte', 'orden' => 99, 'es_reserva' => 1, 'activo' => 1],
        ];

        $table = $this->table('categorias');
        $table->insert($data)->saveData();
    }
}
