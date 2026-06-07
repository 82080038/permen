<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Input validation class
 */
class Validator
{
    private array $data = [];
    private array $errors = [];
    private array $rules = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Add validation rules
     *
     * @param string $field Field name
     * @param string $rules Pipe-separated rules (e.g., "required|email|min:3")
     */
    public function rule(string $field, string $rules): self
    {
        $this->rules[$field] = $rules;
        return $this;
    }

    /**
     * Validate all rules
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            foreach ($rules as $rule) {
                $this->checkRule($field, trim($rule));
            }
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $errors) {
            if (!empty($errors)) {
                return $errors[0];
            }
        }
        return null;
    }

    /**
     * Get sanitized value
     */
    public function value(string $field, mixed $default = null): mixed
    {
        return $this->data[$field] ?? $default;
    }

    /**
     * Get sanitized string
     */
    public function string(string $field, string $default = ''): string
    {
        $value = $this->value($field, $default);
        return is_string($value) ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : $default;
    }

    /**
     * Get integer value
     */
    public function int(string $field, int $default = 0): int
    {
        $value = $this->value($field, $default);
        return filter_var($value, FILTER_VALIDATE_INT) ?: $default;
    }

    /**
     * Get float value
     */
    public function float(string $field, float $default = 0.0): float
    {
        $value = $this->value($field, $default);
        return filter_var($value, FILTER_VALIDATE_FLOAT) ?: $default;
    }

    /**
     * Get email value
     */
    public function email(string $field, string $default = ''): string
    {
        $value = $this->value($field, $default);
        $filtered = filter_var($value, FILTER_VALIDATE_EMAIL);
        return $filtered ?: $default;
    }

    /**
     * Check individual rule
     */
    private function checkRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;

        if ($rule === 'required') {
            if (empty($value) && $value !== '0') {
                $this->errors[$field][] = "Field $field wajib diisi.";
            }
            return;
        }

        // Skip other rules if value is empty and not required
        if (empty($value) && $value !== '0') {
            return;
        }

        // Handle rules with parameters (e.g., min:3)
        $params = [];
        if (strpos($rule, ':') !== false) {
            [$ruleName, $param] = explode(':', $rule, 2);
            $params = explode(',', $param);
            $rule = $ruleName;
        }

        switch ($rule) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "Format email tidak valid.";
                }
                break;

            case 'min':
                $min = (int)($params[0] ?? 0);
                if (is_string($value) && strlen($value) < $min) {
                    $this->errors[$field][] = "Panjang minimum $min karakter.";
                } elseif (is_numeric($value) && $value < $min) {
                    $this->errors[$field][] = "Nilai minimum $min.";
                }
                break;

            case 'max':
                $max = (int)($params[0] ?? 0);
                if (is_string($value) && strlen($value) > $max) {
                    $this->errors[$field][] = "Panjang maksimum $max karakter.";
                } elseif (is_numeric($value) && $value > $max) {
                    $this->errors[$field][] = "Nilai maksimum $max.";
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->errors[$field][] = "Harus berupa angka.";
                }
                break;

            case 'int':
                if (!filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->errors[$field][] = "Harus berupa bilangan bulat.";
                }
                break;

            case 'in':
                if (!in_array($value, $params, true)) {
                    $this->errors[$field][] = "Nilai tidak valid.";
                }
                break;

            case 'phone':
                if (!preg_match('/^[0-9+\-\s()]{8,20}$/', (string)$value)) {
                    $this->errors[$field][] = "Format nomor telepon tidak valid.";
                }
                break;
        }
    }
}
