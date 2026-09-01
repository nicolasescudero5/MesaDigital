<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CategoriaResponsablesSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            // Legales — Laboral (Laura Legales + email externo de estudio jurídico)
            ['categoria_id' => 1, 'email' => 'legales@reditinere.com', 'usuario_id' => 12, 'activo' => 1],
            ['categoria_id' => 1, 'email' => 'estudio.juridico.externo@legalcorp.com.ar', 'usuario_id' => null, 'activo' => 1],
            
            // Legales — Civil y Comercial
            ['categoria_id' => 2, 'email' => 'legales@reditinere.com', 'usuario_id' => 12, 'activo' => 1],
            
            // Legales — Municipal / Regulatorio
            ['categoria_id' => 3, 'email' => 'legales@reditinere.com', 'usuario_id' => 12, 'activo' => 1],
            
            // Capital Humano (RRHH)
            ['categoria_id' => 4, 'email' => 'rrhh@reditinere.com', 'usuario_id' => 13, 'activo' => 1],
            
            // Real Estate / Mantenimiento Edilicio
            ['categoria_id' => 5, 'email' => 'mantenimiento@reditinere.com', 'usuario_id' => 14, 'activo' => 1],
            
            // Dirección Académica (Laura Legales & Admin por defecto)
            ['categoria_id' => 6, 'email' => 'admin@reditinere.com', 'usuario_id' => 1, 'activo' => 1],
            
            // Administración y Facturación
            ['categoria_id' => 7, 'email' => 'administracion@reditinere.com', 'usuario_id' => 15, 'activo' => 1],
            
            // Seguridad e Higiene
            ['categoria_id' => 8, 'email' => 'mantenimiento@reditinere.com', 'usuario_id' => 14, 'activo' => 1],
            
            // Sistemas / IT
            ['categoria_id' => 9, 'email' => 'sistemas@reditinere.com', 'usuario_id' => 16, 'activo' => 1],
            
            // Paquetería Personal
            ['categoria_id' => 10, 'email' => 'rrhh@reditinere.com', 'usuario_id' => 13, 'activo' => 1],
            
            // Sin Clasificar (Admin)
            ['categoria_id' => 11, 'email' => 'admin@reditinere.com', 'usuario_id' => 1, 'activo' => 1],
        ];

        $table = $this->table('categoria_responsables');
        $table->insert($data)->saveData();
    }
}
