<?php

declare(strict_types=1);

namespace App\Validation;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public static function make(array $data, array $rules): self
    {
        $validator = new self($data);
        $validator->validate($rules);
        return $validator;
    }

    public function validate(array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                $ruleName = $rule;
                $ruleParam = null;

                if (str_contains($rule, ':')) {
                    [$ruleName, $ruleParam] = explode(':', $rule, 2);
                }

                $this->applyRule($field, $value, $ruleName, $ruleParam);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void
    {
        $label = $this->formatFieldLabel($field);

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, "El campo {$label} es obligatorio.");
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "El campo {$label} debe ser un correo electrónico válido.");
                }
                break;

            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "El campo {$label} debe ser un número entero.");
                }
                break;

            case 'min':
                $min = (int)$param;
                if (!empty($value) && mb_strlen((string)$value) < $min) {
                    $this->addError($field, "El campo {$label} debe tener al menos {$min} caracteres.");
                }
                break;

            case 'max':
                $max = (int)$param;
                if (!empty($value) && mb_strlen((string)$value) > $max) {
                    $this->addError($field, "El campo {$label} no puede superar los {$max} caracteres.");
                }
                break;

            case 'date':
                if (!empty($value)) {
                    $d = \DateTime::createFromFormat('Y-m-d', (string)$value);
                    if (!$d || $d->format('Y-m-d') !== (string)$value) {
                        $this->addError($field, "El campo {$label} debe ser una fecha válida en formato AAAA-MM-DD.");
                    }
                }
                break;

            case 'date_after_or_equal':
                $otherField = $param;
                $otherValue = $this->data[$otherField] ?? null;
                if (!empty($value) && !empty($otherValue)) {
                    if (strtotime((string)$value) < strtotime((string)$otherValue)) {
                        $otherLabel = $this->formatFieldLabel($otherField);
                        $this->addError($field, "El campo {$label} no puede ser anterior a {$otherLabel}.");
                    }
                }
                break;

            case 'in':
                $allowed = explode(',', (string)$param);
                if (!empty($value) && !in_array((string)$value, $allowed, true)) {
                    $this->addError($field, "El valor seleccionado para {$label} no es válido.");
                }
                break;
        }
    }

    private function formatFieldLabel(string $field): string
    {
        $labels = [
            'sede_id' => 'Sede',
            'categoria_id' => 'Categoría',
            'tipo_documento_id' => 'Tipo de documento',
            'caracter_remitente_id' => 'Carácter de remitente',
            'remitente' => 'Remitente',
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'fecha_recepcion' => 'Fecha de recepción',
            'plazo_legal' => 'Plazo legal',
            'motivo' => 'Motivo de reclasificación',
            'motivo_anulacion' => 'Motivo de anulación',
            'constancia_cierre' => 'Constancia de cierre',
            'comentario' => 'Comentario',
            'email' => 'Correo electrónico',
            'nombre' => 'Nombre',
            'rol' => 'Rol',
            'password' => 'Contraseña',
        ];

        return $labels[$field] ?? str_replace('_', ' ', $field);
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $messages) {
            if (!empty($messages[0])) {
                return $messages[0];
            }
        }

        return null;
    }
}
