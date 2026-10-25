<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class BackupPosTest extends TestCase
{
    public function test_backup_rejects_unsupported_database_before_writing_files(): void
    {
        $this->artisan('pos:backup')->expectsOutput('Backup ini hanya mendukung MySQL.')->assertExitCode(1);
    }

    public function test_backup_rejects_overlap_before_dump_or_reading_credentials(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldNotReceive('getConfig');
        DB::shouldReceive('connection')->once()->andReturn($connection);
        $lock = Cache::lock('pos:database-backup', 900);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('pos:backup')->expectsOutput('Backup lain sedang berjalan. Coba lagi setelah selesai.')->assertExitCode(1);
        } finally {
            $lock->release();
        }
    }
}
