<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use DateTime;

class Validator
{
    private array $rules = [];
    private array $errors = [];

    public function __construct(private readonly array $data)
    {
    }

    public function rules(array $rules): self
    {
        $this->rules = $rules;
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $this->validateField($field, explode('|', $ruleString));
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function validateField(string $field, array $rules): void
    {
        $value = $this->value($field);
        $nullable = in_array('nullable', $rules, true);

        if ($nullable && ($value === null || $value === '')) {
            return;
        }

        foreach ($rules as $rule) {
            [$name, $parameterString] = array_pad(explode(':', $rule, 2), 2, null);
            $parameters = $parameterString !== null ? str_getcsv($parameterString) : [];

            $method = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
            if (method_exists($this, $method)) {
                $this->{$method}($field, $value, $parameters);
            }
        }
    }

    private function value(string $field): mixed
    {
        if (str_contains($field, '.*')) {
            $baseField = strstr($field, '.*', true);
            return $this->data[$baseField] ?? null;
        }

        return $this->data[$field] ?? null;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function validateRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            $this->addError($field, sprintf('%s is required.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateString(string $field, mixed $value): void
    {
        if ($value !== null && !is_string($value)) {
            $this->addError($field, sprintf('%s must be a string.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateInteger(string $field, mixed $value): void
    {
        if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, sprintf('%s must be an integer.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateNumeric(string $field, mixed $value): void
    {
        if ($value !== null && !is_numeric($value)) {
            $this->addError($field, sprintf('%s must be numeric.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if ($value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, 'Must be a valid email address.');
        }
    }

    private function validateMin(string $field, mixed $value, array $parameters): void
    {
        $min = (float) ($parameters[0] ?? 0);

        if (is_array($value) && count($value) < $min) {
            $this->addError($field, sprintf('%s must have at least %s items.', ucfirst(str_replace('_', ' ', $field)), $parameters[0]));
            return;
        }

        if ($this->hasRule($field, 'string') && is_string($value)) {
            if (mb_strlen($value) < $min) {
                $this->addError($field, sprintf('%s must be at least %s characters.', ucfirst(str_replace('_', ' ', $field)), $parameters[0]));
            }
            return;
        }

        if (($this->hasRule($field, 'integer') || $this->hasRule($field, 'numeric')) && is_numeric($value) && (float) $value < $min) {
            $this->addError($field, sprintf('%s must be at least %s.', ucfirst(str_replace('_', ' ', $field)), $parameters[0]));
            return;
        }

        if (is_string($value) && !is_numeric($value) && mb_strlen($value) < $min) {
            $this->addError($field, sprintf('%s must be at least %s characters.', ucfirst(str_replace('_', ' ', $field)), $parameters[0]));
        }
    }

    private function validateMax(string $field, mixed $value, array $parameters): void
    {
        $max = (float) ($parameters[0] ?? 0);

        if (is_array($value) && count($value) > $max) {
            $this->addError($field, sprintf('%s may not have more than %s items.', ucfirst(str_replace('_', ' ', $field)), $parameters[0]));
            return;
        }

        if ($this->hasRule($field, 'string') && is_string($value)) {
            if (mb_strlen($value) > $max) {
                $this->addError($field, sprintf('%s may not be greater than %s characters.', ucfirst(str_replace('_', ' ', $field)), $parameters[0]));
            }
            return;
        }

        if (($this->hasRule($field, 'integer') || $this->hasRule($field, 'numeric')) && is_numeric($value) && (float) $value > $max) {
            $this->addError($field, sprintf('%s may not be greater than %s.', ucfirst(str_replace('_', ' ', $field)), $parameters[0]));
            return;
        }

        if (is_string($value) && !is_numeric($value) && mb_strlen($value) > $max) {
            $this->addError($field, sprintf('%s may not be greater than %s characters.', ucfirst(str_replace('_', ' ', $field)), $parameters[0]));
        }
    }

    private function hasRule(string $field, string $ruleName): bool
    {
        $ruleString = $this->rules[$field] ?? null;
        if (!is_string($ruleString)) {
            return false;
        }

        foreach (explode('|', $ruleString) as $rule) {
            [$name] = array_pad(explode(':', $rule, 2), 1, null);
            if ($name === $ruleName) {
                return true;
            }
        }

        return false;
    }

    private function validateBetween(string $field, mixed $value, array $parameters): void
    {
        $min = (float) ($parameters[0] ?? 0);
        $max = (float) ($parameters[1] ?? 0);

        if ($value !== null && (!is_numeric($value) || (float) $value < $min || (float) $value > $max)) {
            $this->addError($field, sprintf('%s must be between %s and %s.', ucfirst(str_replace('_', ' ', $field)), $parameters[0], $parameters[1]));
        }
    }

    private function validateIn(string $field, mixed $value, array $parameters): void
    {
        if ($value !== null && !in_array((string) $value, $parameters, true)) {
            $this->addError($field, sprintf('%s is invalid.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateDate(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->parseDate((string) $value) === null) {
            $this->addError($field, sprintf('%s must be a valid date.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateAfter(string $field, mixed $value, array $parameters): void
    {
        $other = $this->data[$parameters[0] ?? ''] ?? null;
        $date = $this->parseDate((string) $value);
        $otherDate = $this->parseDate((string) $other);

        if ($date !== null && $otherDate !== null && $date <= $otherDate) {
            $this->addError($field, sprintf('%s must be after %s.', ucfirst(str_replace('_', ' ', $field)), str_replace('_', ' ', $parameters[0])));
        }
    }

    private function validateBefore(string $field, mixed $value, array $parameters): void
    {
        $other = $this->data[$parameters[0] ?? ''] ?? null;
        $date = $this->parseDate((string) $value);
        $otherDate = $this->parseDate((string) $other);

        if ($date !== null && $otherDate !== null && $date >= $otherDate) {
            $this->addError($field, sprintf('%s must be before %s.', ucfirst(str_replace('_', ' ', $field)), str_replace('_', ' ', $parameters[0])));
        }
    }

    private function validateFile(string $field, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        if (!is_array($value) || !isset($value['tmp_name'])) {
            $this->addError($field, sprintf('%s must be a file upload.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateFileMax(string $field, mixed $value, array $parameters): void
    {
        if (!is_array($value) || !isset($value['size'])) {
            return;
        }

        $maxBytes = ((int) ($parameters[0] ?? 0)) * 1024;
        if ((int) $value['size'] > $maxBytes) {
            $this->addError($field, sprintf('%s exceeds the maximum file size.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateFileTypes(string $field, mixed $value, array $parameters): void
    {
        if (!is_array($value) || !isset($value['name'])) {
            return;
        }

        $extension = strtolower(pathinfo((string) $value['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, array_map('strtolower', $parameters), true)) {
            $this->addError($field, sprintf('%s has an invalid file type.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateUnique(string $field, mixed $value, array $parameters): void
    {
        if ($value === null || $value === '') {
            return;
        }

        [$table, $column] = $parameters;
        $row = Database::fetch("SELECT COUNT(*) AS aggregate FROM {$table} WHERE {$column} = :value", ['value' => $value]);

        if (($row['aggregate'] ?? 0) > 0) {
            $this->addError($field, sprintf('%s has already been taken.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateExists(string $field, mixed $value, array $parameters): void
    {
        if ($value === null || $value === '') {
            return;
        }

        [$table, $column] = $parameters;
        $row = Database::fetch("SELECT COUNT(*) AS aggregate FROM {$table} WHERE {$column} = :value", ['value' => $value]);

        if (($row['aggregate'] ?? 0) < 1) {
            $this->addError($field, sprintf('Selected %s is invalid.', str_replace('_', ' ', $field)));
        }
    }

    private function validateConfirmed(string $field, mixed $value): void
    {
        $confirmation = $this->data[$field . '_confirmation'] ?? null;

        if ($value !== $confirmation) {
            $this->addError($field, sprintf('%s confirmation does not match.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateArray(string $field, mixed $value): void
    {
        if ($value !== null && !is_array($value)) {
            $this->addError($field, sprintf('%s must be an array.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateBoolean(string $field, mixed $value): void
    {
        if ($value !== null && filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $this->addError($field, sprintf('%s must be true or false.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateRegex(string $field, mixed $value, array $parameters): void
    {
        $pattern = $parameters[0] ?? null;

        if ($pattern !== null && $value !== null && @preg_match($pattern, (string) $value) !== 1) {
            $this->addError($field, sprintf('%s format is invalid.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function validateStrongPassword(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $password = (string) $value;
        $isStrong = preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;

        if (!$isStrong) {
            $this->addError(
                $field,
                sprintf(
                    '%s must include at least one uppercase letter, one lowercase letter, one number, and one special character.',
                    ucfirst(str_replace('_', ' ', $field))
                )
            );
        }
    }

    private function validatePhonePh(string $field, mixed $value): void
    {
        if ($value !== null && !preg_match('/^(?:\+63\d{10}|09\d{9})$/', (string) $value)) {
            $this->addError($field, sprintf('%s must be a valid Philippine phone number.', ucfirst(str_replace('_', ' ', $field))));
        }
    }

    private function parseDate(string $value): ?DateTime
    {
        foreach (['Y-m-d', 'Y-m-d H:i:s'] as $format) {
            $date = DateTime::createFromFormat($format, $value);
            if ($date instanceof DateTime) {
                return $date;
            }
        }

        return null;
    }
}
