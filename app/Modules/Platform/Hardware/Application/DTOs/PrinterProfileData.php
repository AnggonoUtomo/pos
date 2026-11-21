<?php

namespace App\Modules\Platform\Hardware\Application\DTOs;

final readonly class PrinterProfileData
{
    public function __construct(public string $id, public string $name, public string $printerName,
        public string $gatewayHost, public array $specification, public bool $isActive,
        public int $version, public ?string $deletedAt) {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'printer_name' => $this->printerName,
            'gateway_host' => $this->gatewayHost, ...$this->specification,
            'is_active' => $this->isActive, 'version' => $this->version, 'deleted_at' => $this->deletedAt];
    }
}
