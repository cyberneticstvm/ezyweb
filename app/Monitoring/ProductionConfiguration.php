<?php

namespace App\Monitoring;

class ProductionConfiguration
{
    /** @return array<string, array{ok: bool, message: string}> */
    public function checks(): array
    {
        $mediaDisk = (string) config('media.disk');

        return [
            'environment' => $this->result(app()->environment('production'), 'APP_ENV must be production.'),
            'debug' => $this->result(! config('app.debug'), 'APP_DEBUG must be false.'),
            'https' => $this->result(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL must use HTTPS.'),
            'app_key' => $this->result((string) config('app.key') !== '', 'APP_KEY must be generated.'),
            'cache' => $this->result(config('cache.default') === 'redis', 'CACHE_STORE must be redis.'),
            'queue' => $this->result(config('queue.default') === 'redis', 'QUEUE_CONNECTION must be redis.'),
            'session_driver' => $this->result(config('session.driver') === 'redis', 'SESSION_DRIVER must be redis.'),
            'secure_cookie' => $this->result(config('session.secure') === true, 'SESSION_SECURE_COOKIE must be true.'),
            'encrypted_session' => $this->result(config('session.encrypt') === true, 'SESSION_ENCRYPT must be true.'),
            'mail' => $this->result(! in_array(config('mail.default'), ['log', 'array'], true), 'Configure a production mail transport.'),
            'turnstile' => $this->result((string) config('services.turnstile.secret_key') !== '', 'TURNSTILE_SECRET_KEY must be configured.'),
            'media_disk' => $this->result($mediaDisk === 'gcs', 'MEDIA_DISK must be gcs.'),
            'gcs_project' => $this->result((string) config('filesystems.disks.gcs.project_id') !== '', 'GOOGLE_CLOUD_PROJECT_ID must be configured.'),
            'gcs_bucket' => $this->result((string) config('filesystems.disks.gcs.bucket') !== '', 'GOOGLE_CLOUD_STORAGE_BUCKET must be configured.'),
            'csp_enforced' => $this->result(config('security.csp_report_only') === false, 'CSP_REPORT_ONLY must be false after report-only validation.'),
        ];
    }

    public function isReady(): bool
    {
        return collect($this->checks())->every(fn (array $check): bool => $check['ok']);
    }

    /** @return array{ok: bool, message: string} */
    private function result(bool $ok, string $message): array
    {
        return ['ok' => $ok, 'message' => $message];
    }
}
