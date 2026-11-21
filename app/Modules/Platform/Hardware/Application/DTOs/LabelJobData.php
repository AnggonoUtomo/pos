<?php

namespace App\Modules\Platform\Hardware\Application\DTOs;

final readonly class LabelJobData
{
    public function __construct(public string $id, public string $printerId, public int $createdBy,
        public string $kind, public string $status, public array $snapshot, public array $printer,
        public int $copies, public ?string $originalJobId, public ?string $reason,
        public string $createdAt, public string $requestHash, public ?string $expiresAt = null) {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'printer_id' => $this->printerId, 'created_by' => $this->createdBy,
            'kind' => $this->kind, 'status' => $this->status, 'snapshot' => $this->snapshot,
            'printer' => $this->printer, 'copies' => $this->copies, 'original_job_id' => $this->originalJobId,
            'reason' => $this->reason, 'created_at' => $this->createdAt, 'expires_at' => $this->expiresAt];
    }
}
