<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow;

class DirectEmissionResult
{
    private array $errors = [];

    public function addError(\Throwable $error): void
    {
        $this->errors[] = $error;
    }

    public function hasErrors(): bool
    {
        return (bool) $this->errors;
    }

    /**
     * @return \Throwable[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
