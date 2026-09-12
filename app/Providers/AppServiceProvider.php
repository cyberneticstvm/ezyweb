<?php

namespace App\Providers;

use App\Tenancy\CurrentTenant;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentTenant::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DB::whenQueryingForLongerThan((int) config('observability.slow_query_ms', 500), function ($connection, $event): void {
            Log::warning('Cumulative database query time exceeded the configured threshold.', [
                'connection' => $connection->getName(),
                'query_time_ms' => $event->time,
            ]);
        });

        Queue::failing(function (JobFailed $event): void {
            Log::error('Queued job failed.', [
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job' => $event->job->resolveName(),
                'exception' => $event->exception::class,
            ]);
        });

        Storage::extend('gcs', function ($app, array $config): FilesystemAdapter {
            $clientConfig = array_filter([
                'projectId' => $config['project_id'] ?? null,
                'keyFilePath' => $config['key_file'] ?? null,
                'apiEndpoint' => $config['storage_api_uri'] ?? null,
            ]);

            $client = new StorageClient($clientConfig);
            $adapter = new GoogleCloudStorageAdapter(
                $client->bucket((string) $config['bucket']),
                (string) ($config['path_prefix'] ?? ''),
            );
            $filesystem = new Filesystem($adapter, $config);

            return new FilesystemAdapter($filesystem, $adapter, $config);
        });
    }
}
