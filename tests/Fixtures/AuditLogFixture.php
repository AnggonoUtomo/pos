<?php

namespace Tests\Fixtures;

use App\Models\User;
use App\Modules\Platform\AuditLog\Database\Seeders\AuditLogAccessSeeder;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AuditLogFixture
{
    public static function create(string $password): array
    {
        if (! app()->environment('testing')) {
            throw new LogicException('Fixture audit hanya testing disposable.');
        }
        app(AuditLogAccessSeeder::class)->run();
        $viewer = User::factory()->create(['email' => 'qa-auditor@example.test', 'password' => $password]);
        $viewer->givePermissionTo('platform.audit-log.view');
        for ($i = 0; $i < 25; $i++) {
            DB::table('activity_log')->insert([
                'log_name' => 'inventory.items', 'event' => 'updated', 'description' => 'audit-private-marker',
                'subject_type' => 'historical-subject', 'subject_id' => sprintf('AUDIT-%03d', $i),
                'causer_type' => $i === 24 ? null : User::class, 'causer_id' => $i === 24 ? null : $viewer->id,
                'properties' => json_encode(['before' => ['name' => 'Barang lama'],
                    'after' => ['code' => sprintf('AUDIT-%03d', $i), 'name' => 'Barang baru', 'quantity' => '1.500000', 'unit_cost' => '123456789012345.123456', 'password' => 'audit-private-marker'],
                    'token' => 'audit-private-marker']),
                'created_at' => sprintf('2026-10-30 12:00:%02d', $i), 'updated_at' => sprintf('2026-10-30 12:00:%02d', $i),
            ]);
        }

        return ['audit_fingerprint' => self::fingerprint()];
    }

    public static function fingerprint(): string
    {
        return hash('sha256', DB::table('activity_log')->orderBy('id')->get()->toJson());
    }
}
