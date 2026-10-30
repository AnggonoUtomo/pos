<?php

namespace Tests\Unit\Modules\Platform\AuditLog;

use App\Modules\Platform\AuditLog\Application\Services\AuditLogProjection;
use PHPUnit\Framework\TestCase;

class AuditLogProjectionTest extends TestCase
{
    public function test_numeric_values_keep_precision_as_strings_and_nested_secrets_are_not_projected(): void
    {
        $result = (new AuditLogProjection)->changes(null,
            '{"after":{"total":9007199254740993,"unit_cost":"123456789012345678901234.123456","name":{"token":"hidden"},"password":"hidden"}}');
        $this->assertSame('9007199254740993', $result['after']['total']);
        $this->assertSame('123456789012345678901234.123456', $result['after']['unit_cost']);
        $this->assertArrayNotHasKey('name', $result['after']);
        $this->assertArrayNotHasKey('password', $result['after']);
    }

    public function test_unknown_invalid_deep_and_oversized_payloads_are_closed_by_default(): void
    {
        $projection = new AuditLogProjection;
        foreach (['{', json_encode(['token' => 'hidden']), json_encode(['name' => str_repeat('a', 262145)])] as $payload) {
            $this->assertSame(['before' => null, 'after' => null], $projection->changes(null, $payload));
        }
        $this->assertSame('Aktivitas lain', AuditLogProjection::eventName(null));
        $this->assertNull(AuditLogProjection::key('<script>'));
    }
}
