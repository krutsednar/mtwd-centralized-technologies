<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnlineHrisService
{
    public function getToken(): string
    {
        $token = config('services.online_hris.token');

        if (! blank($token)) {
            return $token;
        }

        $response = Http::timeout(15)
            ->post(config('services.online_hris.url').'/api/login', [
                'email' => config('services.online_hris.email'),
                'password' => config('services.online_hris.password'),
            ]);

        if ($response->failed()) {
            Log::error('Online HRIS: login request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Online HRIS authentication failed — check ONLINE_HRIS_URL, ONLINE_HRIS_EMAIL and ONLINE_HRIS_PASSWORD in .env.');
        }

        $token = $response->json('token') ?? $response->json('access_token');

        if (blank($token)) {
            Log::error('Online HRIS: login succeeded but no token in response.', [
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Online HRIS login response did not contain a token. See laravel.log for the raw response.');
        }

        $this->writeTokenToEnv($token);

        return $token;
    }

    public function fetchUnsyncedAttendances(): Collection
    {
        $records = collect();
        $url = config('services.online_hris.url').'/api/attendances';
        $retried = false;

        while ($url !== null) {
            $response = Http::timeout(15)
                ->withToken($this->getToken())
                ->get($url, ['is_synced' => 'false']);

            if ($response->status() === 401) {
                if ($retried) {
                    Log::error('Online HRIS: repeated 401 on fetchUnsyncedAttendances, aborting pagination.');
                    break;
                }

                $this->handleUnauthorized();
                $retried = true;

                continue;
            }

            if ($response->failed()) {
                Log::error('Online HRIS: failed fetching attendances.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                break;
            }

            $records = $records->merge($response->json('data', []));
            $url = $response->json('links.next');
            $retried = false;
        }

        return $records;
    }

    /**
     * @param  string[]  $fields  Time field names that were synced, e.g. ['morning_in', 'afternoon_in']
     */
    public function markSynced(int $remoteId, array $fields = []): void
    {
        $attempted = false;

        do {
            $response = Http::timeout(15)
                ->withToken($this->getToken())
                ->patch(
                    config('services.online_hris.url')."/api/attendances/{$remoteId}/mark-synced",
                    ['fields' => $fields]
                );

            if ($response->status() === 401 && ! $attempted) {
                $this->handleUnauthorized();
                $attempted = true;

                continue;
            }

            if ($response->failed()) {
                Log::error('Online HRIS: failed to mark record synced.', [
                    'remote_id' => $remoteId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            break;
        } while (true);
    }

    public function handleUnauthorized(): void
    {
        $this->writeTokenToEnv('');
        $this->getToken();
    }

    private function writeTokenToEnv(string $token): void
    {
        $envPath = base_path('.env');
        $contents = file_get_contents($envPath);
        $contents = preg_replace('/^ONLINE_HRIS_TOKEN=.*/m', 'ONLINE_HRIS_TOKEN='.$token, $contents);
        file_put_contents($envPath, $contents);
        config(['services.online_hris.token' => $token]);
    }
}
