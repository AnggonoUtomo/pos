<?php

namespace Tests\Feature\Modules\Platform\Identity;

use App\Models\User;
use App\Modules\Platform\Identity\Application\Actions\CreateInitialOwner;
use App\Modules\Platform\Identity\Database\Seeders\ProductionRolesSeeder;
use App\Modules\Platform\Identity\Presentation\Console\Commands\InitializeOwnerCommand;
use DomainException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDO;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class InitialOwnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('pos.standalone.enabled', true);
        config(['standalone.enabled' => true]);
        Role::findOrCreate('super-system', 'web');
    }

    private function profile(): array
    {
        $password = Str::password(24);

        return ['name' => 'Pemilik Latihan', 'email' => 'owner@example.test',
            'password' => $password, 'password_confirmation' => $password];
    }

    public function test_first_owner_can_login_and_audit_contains_no_profile_or_credentials(): void
    {
        $input = $this->profile();
        $id = app(CreateInitialOwner::class)->handle($input);
        $user = User::findOrFail($id);
        $this->assertTrue(Hash::check($input['password'], $user->password));
        $this->assertTrue($user->hasRole('super-system', 'web'));
        $this->assertTrue($user->is_active);
        $audit = Activity::where('event', 'identity.initial_owner_created')->sole();
        $this->assertSame(['source' => 'standalone-setup', 'roles' => ['super-system']], $audit->properties->all());
        $this->assertSame((string) $id, $audit->subject_id);
        $this->assertSame($id, $audit->causer_id);
        $this->post(route('login'), ['email' => $input['email'], 'password' => $input['password']])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_disabled_setup_is_rejected_even_with_an_empty_database(): void
    {
        config(['standalone.enabled' => false]);
        $this->expectException(DomainException::class);
        app(CreateInitialOwner::class)->handle($this->profile());
    }

    public function test_config_flag_alone_cannot_enable_owner_bootstrap(): void
    {
        $this->app->instance('pos.standalone.enabled', false);
        $this->expectException(DomainException::class);
        app(CreateInitialOwner::class)->handle($this->profile());
    }

    public function test_missing_system_role_is_rejected_without_creating_account_or_role(): void
    {
        Role::query()->delete();
        try {
            app(CreateInitialOwner::class)->handle($this->profile());
            $this->fail('Bootstrap tanpa role seharusnya ditolak.');
        } catch (DomainException) {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('roles', 0);
        }
    }

    public function test_repeat_does_not_reset_password_roles_or_audit(): void
    {
        $id = app(CreateInitialOwner::class)->handle($this->profile());
        $before = User::findOrFail($id)->getRawOriginal();
        try {
            app(CreateInitialOwner::class)->handle($this->profile());
            $this->fail('Bootstrap ulang seharusnya ditolak.');
        } catch (DomainException) {
            $this->assertTrue($before === User::findOrFail($id)->getRawOriginal());
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('model_has_roles', 1);
            $this->assertDatabaseCount('activity_log', 1);
        }
    }

    public function test_existing_non_owner_account_prevents_bootstrap(): void
    {
        User::factory()->create();
        $this->expectException(DomainException::class);
        app(CreateInitialOwner::class)->handle($this->profile());
    }

    public function test_existing_operational_data_prevents_bootstrap_without_changing_it(): void
    {
        DB::table('units')->insert(['id' => (string) Str::ulid(), 'code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'unit',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        try {
            app(CreateInitialOwner::class)->handle($this->profile());
            $this->fail('Database berisi master seharusnya ditolak.');
        } catch (DomainException) {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('units', 1);
        }
    }

    public function test_modified_operational_defaults_and_non_setup_audit_prevent_bootstrap(): void
    {
        DB::table('operational_settings')->update(['version' => 1]);
        $this->expectException(DomainException::class);
        app(CreateInitialOwner::class)->handle($this->profile());
    }

    public function test_invalid_profile_and_password_are_rejected_without_partial_account(): void
    {
        foreach ([['email' => 'invalid'], ['password' => 'short', 'password_confirmation' => 'short'],
            ['password_confirmation' => 'different'], ['password' => str_repeat('a', 73)],
            ['roles' => ['custom-role']]] as $overrides) {
            try {
                app(CreateInitialOwner::class)->handle(array_replace($this->profile(), $overrides));
                $this->fail('Input tidak valid seharusnya ditolak.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('users', 0);
                $this->assertDatabaseCount('activity_log', 0);
            }
        }
    }

    public function test_audit_failure_rolls_back_user_and_role_assignment(): void
    {
        Activity::creating(static function (): void {
            throw new RuntimeException('Synthetic audit failure');
        });
        try {
            app(CreateInitialOwner::class)->handle($this->profile());
            $this->fail('Kegagalan audit harus membatalkan setup.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('model_has_roles', 0);
        } finally {
            Activity::flushEventListeners();
        }
    }

    private function command(string $payload): array
    {
        $command = app(InitializeOwnerCommand::class);
        $command->setLaravel($this->app);
        $input = new ArrayInput(['--stdin' => true]);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $payload);
        rewind($stream);
        $input->setStream($stream);
        $output = new BufferedOutput;
        try {
            $exit = $command->run($input, $output);

            return [$exit, $output->fetch()];
        } finally {
            fclose($stream);
        }
    }

    public function test_stdin_command_creates_owner_without_echoing_private_input(): void
    {
        $profile = $this->profile();
        [$exit, $output] = $this->command(json_encode($profile, JSON_THROW_ON_ERROR));
        $this->assertSame(0, $exit);
        foreach (['name', 'email', 'password'] as $key) {
            $this->assertFalse(str_contains($output, $profile[$key]));
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_interactive_command_creates_owner_using_hidden_password_prompts(): void
    {
        $profile = $this->profile();
        $this->artisan('identity:initialize-owner')
            ->expectsQuestion('Nama pemilik', $profile['name'])
            ->expectsQuestion('Email pemilik', $profile['email'])
            ->expectsQuestion('Password awal, 12-72 byte', $profile['password'])
            ->expectsQuestion('Konfirmasi password', $profile['password_confirmation'])
            ->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_stdin_command_rejects_malformed_large_input_and_disabled_mode(): void
    {
        foreach (['{invalid', str_repeat('x', 8193), '[]', 'null'] as $payload) {
            $this->assertSame(1, $this->command($payload)[0]);
            $this->assertDatabaseCount('users', 0);
        }
        config(['standalone.enabled' => false]);
        $this->assertSame(1, $this->command(json_encode($this->profile(), JSON_THROW_ON_ERROR))[0]);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_sanitizes_unexpected_failure(): void
    {
        $private = Str::random(32);
        Activity::creating(static function () use ($private): void {
            throw new RuntimeException($private);
        });
        try {
            [$exit, $output] = $this->command(json_encode($this->profile(), JSON_THROW_ON_ERROR));
            $this->assertSame(1, $exit);
            $this->assertFalse(str_contains($output, $private));
            $this->assertDatabaseCount('users', 0);
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_command_does_not_echo_unexpected_domain_exception_details(): void
    {
        $private = Str::random(32);
        Activity::creating(static function () use ($private): void {
            throw new DomainException($private);
        });
        try {
            [$exit, $output] = $this->command(json_encode($this->profile(), JSON_THROW_ON_ERROR));
            $this->assertSame(1, $exit);
            $this->assertFalse(str_contains($output, $private));
            $this->assertDatabaseCount('users', 0);
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_non_setup_audit_prevents_bootstrap(): void
    {
        activity('identity')->event('identity.user_deleted')->log('Synthetic history');
        $this->expectException(DomainException::class);
        app(CreateInitialOwner::class)->handle($this->profile());
    }

    public function test_seeded_production_roles_and_only_their_audit_are_allowed(): void
    {
        foreach (array_unique(array_merge(...array_values(ProductionRolesSeeder::ROLE_PERMISSIONS))) as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->seed(ProductionRolesSeeder::class);
        app(CreateInitialOwner::class)->handle($this->profile());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_native_mysql_serializes_owner_setup_and_verifies_isolated_backup(): void
    {
        if (getenv('POS_MYSQL_QA') !== '1') {
            $this->markTestSkipped('Opt-in MySQL disposable: POS_MYSQL_QA=1 dan path client QA diperlukan.');
        }
        $config = config('database.connections.mysql');
        $this->assertContains($config['host'], ['127.0.0.1', 'localhost']);
        $this->assertEmpty($config['url']);
        $name = 'pos_owner_qa_'.bin2hex(random_bytes(8));
        $this->assertMatchesRegularExpression('/^pos_owner_qa_[a-f0-9]{16}$/D', $name);
        $root = sys_get_temp_dir().'/'.$name;
        $created = false;
        $workers = [];
        $server = new PDO('mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4',
            $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        try {
            $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $created = true;
            $server->exec('USE `'.$name.'`');
            $this->assertSame($name, $server->query('SELECT DATABASE()')->fetchColumn());
            foreach (['config', 'cache/qa-release', 'storage/app/private', 'storage/app/public',
                'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $path) {
                mkdir($root.'/'.$path, 0700, true);
            }
            file_put_contents($root.'/config/.env', "APP_ENV=testing\n");
            $env = ['POS_STANDALONE' => '1', 'POS_DATA_ROOT' => $root, 'POS_RELEASE_ID' => 'qa-release',
                'APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
                'APP_URL' => 'https://pos-qa.invalid', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $name,
                'DB_HOST' => $config['host'], 'DB_PORT' => (string) $config['port'], 'DB_USERNAME' => $config['username'],
                'DB_PASSWORD' => $config['password'], 'DB_URL' => false, 'DB_SOCKET' => false,
                'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'LOG_CHANNEL' => 'null',
                'BCRYPT_ROUNDS' => '4', 'POS_MYSQL_PATH' => getenv('POS_QA_MYSQL_PATH'),
                'POS_MYSQLDUMP_PATH' => getenv('POS_QA_MYSQLDUMP_PATH')];
            $run = static fn (array $arguments, ?string $input = null): Process => new Process(
                [PHP_BINARY, 'artisan', ...$arguments], base_path(), $env, $input, 40
            );
            $migrationPaths = ['database/migrations/0001_01_01_000000_create_users_table.php',
                'database/migrations/2026_09_12_182604_create_permission_tables.php',
                'database/migrations/2026_09_12_182607_create_activity_log_table.php',
                'database/migrations/2026_10_09_000001_support_ulid_activity_log_subjects.php',
                'app/Modules/Platform/Identity/Database/Migrations/2026_10_11_000001_add_account_status_to_users_table.php',
                'app/Modules/Platform/OperationalSettings/Database/Migrations/2026_10_25_000001_create_operational_settings.php',
                'app/Modules/Platform/OperationalSettings/Database/Migrations/2026_10_29_000001_add_table_per_page_to_operational_settings.php'];
            $migrate = $run(['migrate', '--force', '--no-interaction',
                ...array_map(static fn (string $path): string => '--path='.$path, $migrationPaths)]);
            $migrate->setTimeout(180);
            $migrate->run();
            $this->assertTrue($migrate->isSuccessful(), 'Migration database disposable gagal.');
            $server->exec("INSERT INTO roles (name, guard_name) VALUES ('super-system', 'web')");
            $server->beginTransaction();
            $server->query("SELECT id FROM roles WHERE name = 'super-system' AND guard_name = 'web' FOR UPDATE")->fetchAll();
            for ($i = 0; $i < 2; $i++) {
                $worker = $run(['identity:initialize-owner', '--stdin', '--no-interaction'], json_encode($this->profile(), JSON_THROW_ON_ERROR));
                $worker->start();
                $workers[] = $worker;
            }
            $observer = new PDO('mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4',
                $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $waiting = $observer->prepare("SELECT COUNT(*) FROM information_schema.innodb_trx t
                JOIN information_schema.PROCESSLIST p ON p.ID = t.trx_mysql_thread_id
                WHERE t.trx_state = 'LOCK WAIT' AND p.DB = ?");
            $bothWaiting = false;
            for ($i = 0; $i < 100; $i++) {
                $waiting->execute([$name]);
                if ((int) $waiting->fetchColumn() === 2) {
                    $bothWaiting = true;
                    break;
                }
                usleep(100000);
            }
            $server->commit();
            $this->assertTrue($bothWaiting, 'Dua setup harus terbukti menunggu row lock MySQL yang sama.');
            $codes = array_map(static fn (Process $worker): int => $worker->wait(), $workers);
            sort($codes);
            $this->assertSame([0, 1], $codes);
            $this->assertSame(1, (int) $server->query('SELECT COUNT(*) FROM users')->fetchColumn());
            $this->assertSame(1, (int) $server->query('SELECT COUNT(*) FROM model_has_roles')->fetchColumn());
            $this->assertSame(1, (int) $server->query("SELECT COUNT(*) FROM activity_log WHERE event = 'identity.initial_owner_created'")->fetchColumn());
            $snapshot = static fn (): string => hash('sha256', json_encode($server->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC), JSON_THROW_ON_ERROR));
            $before = $snapshot();
            $retry = $run(['identity:initialize-owner', '--stdin', '--no-interaction'], json_encode($this->profile(), JSON_THROW_ON_ERROR));
            $this->assertSame(1, $retry->run());
            $this->assertSame($before, $snapshot());
            $backup = $run(['pos:backup', '--verify', '--no-interaction']);
            $backup->run();
            $this->assertTrue($backup->isSuccessful(), 'Dump/restore disposable harus berhasil.');
            $this->assertStringContainsString('PASS: restore disposable identik', $backup->getOutput());
            $files = glob($root.'/backups/pos-*.sql');
            $this->assertCount(1, $files);
            $this->assertGreaterThan(0, filesize($files[0]));
            $this->assertSame($before, $snapshot());
        } finally {
            if ($server->inTransaction()) {
                $server->rollBack();
            }
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop(1);
                }
            }
            if ($created && preg_match('/^pos_owner_qa_[a-f0-9]{16}$/D', $name)) {
                $server->exec('DROP DATABASE `'.$name.'`');
            }
            $absolute = realpath($root);
            if ($absolute !== false && dirname($absolute) === realpath(sys_get_temp_dir()) && basename($absolute) === $name) {
                (new Filesystem)->deleteDirectory($absolute);
            }
        }
    }
}
