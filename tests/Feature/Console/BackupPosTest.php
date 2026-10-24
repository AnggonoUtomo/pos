<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class BackupPosTest extends TestCase
{
    public function test_backup_rejects_unsupported_database_before_writing_files(): void
    {
        $this->artisan('pos:backup')->expectsOutput('Backup ini hanya mendukung MySQL.')->assertExitCode(1);
    }
}
