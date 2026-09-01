<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\Validator;
use Tests\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * Criterio N°2: Falta de campos obligatorios bloquea el guardado
     */
    public function test_bloquea_creacion_sin_campos_obligatorios(): void
    {
        $data = [
            'sede_id' => '',
            'remitente' => '',
            'asunto' => '',
            'descripcion' => '',
            'fecha_recepcion' => ''
        ];

        $validator = Validator::make($data, [
            'sede_id' => 'required',
            'remitente' => 'required',
            'asunto' => 'required',
            'descripcion' => 'required',
            'fecha_recepcion' => 'required|date'
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sede_id', $validator->errors());
        $this->assertArrayHasKey('remitente', $validator->errors());
        $this->assertArrayHasKey('asunto', $validator->errors());
        $this->assertArrayHasKey('descripcion', $validator->errors());
        $this->assertArrayHasKey('fecha_recepcion', $validator->errors());
    }

    /**
     * Criterio N°9: Validación de constancia de resolución
     */
    public function test_bloquea_resolucion_sin_constancia_o_corta(): void
    {
        $data = ['constancia_cierre' => 'corta'];
        $validator = Validator::make($data, [
            'constancia_cierre' => 'required|min:10'
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('constancia_cierre', $validator->errors());

        $validData = ['constancia_cierre' => 'Se recepcionó el certificado y se archivó la constancia.'];
        $validator2 = Validator::make($validData, [
            'constancia_cierre' => 'required|min:10'
        ]);
        $this->assertTrue($validator2->passes());
    }

    /**
     * Validación de fechas y plazos legales
     */
    public function test_valida_plazo_legal_no_anterior_a_fecha_recepcion(): void
    {
        $data = [
            'fecha_recepcion' => '2026-09-10',
            'plazo_legal' => '2026-09-05'
        ];

        $validator = Validator::make($data, [
            'fecha_recepcion' => 'required|date',
            'plazo_legal' => 'date|date_after_or_equal:fecha_recepcion'
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('plazo_legal', $validator->errors());
    }
}
