<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class FigmaComponentService
{

    protected string $baseUrl = 'https://api.figma.com/v1';
    protected string $token;

    public function __construct()
    {
        $this->token = config('services.figma.token');
    }
protected function headers(): array
{
return [
'X-Figma-Token' => $this->token,
];
}

 protected function client()
    {
        return Http::withHeaders([
            'X-Figma-Token' => $this->token,
        ])->timeout(30);
    }


/**
* Fetch all components & component sets from a file
*/
public function fetchComponents(string $fileKey): array
{
$res = $this->client()->get("{$this->baseUrl}/files/{$fileKey}/components");
$res->throw();
return $res->json('meta.components') ?? [];
}


public function fetchComponentSets(string $fileKey): array
{
$res = Http::withHeaders($this->headers())
->get("{$this->baseUrl}/files/{$fileKey}/component_sets");


$res->throw();
return $res->json('meta.component_sets') ?? [];
}


/**
* Fetch node detail (for variants, icon detection, etc.)

*/
public function fetchNode(string $fileKey, string $nodeId): array
{
    return retry(5, function () use ($fileKey, $nodeId) {
        $response = Http::withHeaders($this->headers())
        ->get("{$this->baseUrl}/files/{$fileKey}/nodes", [
        'ids' => $nodeId,
        ]);


//$res->throw();

  if ($response->status() === 429) {
            $retryAfter = (int) $response->header('Retry-After', 2);
            sleep($retryAfter);
            throw new \Exception('Rate limited by Figma');
        }

        $this->ensureSuccess($response, 'fetchNode');

        return $response->json('nodes.' . $nodeId) ?? [];
    },1000); // wait 1s between retries
}

/**
     * Ensure API call success
     */
    protected function ensureSuccess($response, string $method): void
    {
        if (!$response->successful()) {
            Log::error("Figma API error in {$method}", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \Exception(
                "Figma API failed in {$method}: " . $response->body()
            );
        }
    }

}
