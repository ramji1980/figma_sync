<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FigmaService
{
    protected string $baseUrl = 'https://api.figma.com/v1';
    protected string $token;

    public function __construct()
    {
        $this->token = config('services.figma.token');
    }

    /**
     * Base HTTP client with auth headers
     */
    protected function client()
    {
        return Http::withHeaders([
            'X-Figma-Token' => $this->token,
        ])->timeout(30);
    }

    /**
     * Get complete Figma file JSON
     */
    public function getFile(string $fileKey): array
    {
        $response = $this->client()
            ->get("{$this->baseUrl}/files/{$fileKey}",[                
                    'depth' => 3,               
            ]);

        $this->ensureSuccess($response, 'getFile');

        return $response->json();
    }

    /**
     * Get only document tree (frames, pages)
     */
    public function getFileDocument(string $fileKey): array
    {
        $response = $this->client()
            ->get("{$this->baseUrl}/files/{$fileKey}", [
                'depth' => 3
            ]);

        $this->ensureSuccess($response, 'getFileDocument');

        return $response->json('document', []);
    }

    /**
     * Get node details by IDs (comma separated)
     */
    /*public function getNodes(string $fileKey, array $nodeIds): array
    {
        $ids = implode(',', $nodeIds);

        $response = $this->client()
            ->get("{$this->baseUrl}/files/{$fileKey}/nodes", [
                'ids' => $ids
            ]);

        $this->ensureSuccess($response, 'getNodes');

        return $response->json('nodes', []);
    }*/

    public function getNodes(string $fileKey, array $nodeIds): array
{
    return retry(5, function () use ($fileKey, $nodeIds) {

        $response = $this->client()->get(
            "{$this->baseUrl}/files/{$fileKey}/nodes",
            [
                'ids' => implode(',', $nodeIds),
            ]
        );

        if ($response->status() === 429) {
            $retryAfter = (int) $response->header('Retry-After', 2);
            sleep($retryAfter);
            throw new \Exception('Rate limited by Figma');
        }

        $this->ensureSuccess($response, 'getNodes');

        return $response->json('nodes', []);

    }, 1000); // wait 1s between retries
}


    /**
     * Get a single frame/node detail
     */
    public function getNode(string $fileKey, string $nodeId): array
    {
        $nodes = $this->getNodes($fileKey, [$nodeId]);

        return $nodes[$nodeId] ?? [];
    }

      /**
     * Get Iamge details by IDs (comma separated)
     */
   public function getImages(string $fileKey, array $frameIds, string $format = 'png',int $scale = 2): array {
    return retry(5, function () use ($fileKey, $frameIds, $format, $scale) {

        $response = $this->client()->get(
            "{$this->baseUrl}/images/{$fileKey}",
            [
                'ids' => implode(',', $frameIds),
                'format' => $format,
                'scale' => $scale,
            ]
        );

        if ($response->status() === 429) {
            sleep((int) $response->header('Retry-After', 2));
            throw new \Exception('Rate limited');
        }

        $this->ensureSuccess($response, 'getImages');

        return $response->json('images', []);

    }, 1000);
}


      public function getImage(string $fileKey, string $nodeId): array
    {
        $images = $this->getImages($fileKey, [$nodeId]);

        return [
        'node_id' => $nodeId,
        'url'     => $images[$nodeId] ?? null,
        'format'  => 'png',
        'scale'   => 2,
        'metadata' => $images,
        //'fetched_at' => now(),
    ];

        // return $nodes[$nodeId] ?? [];
    }

    /**
     * Validate node structure (children, layout, styles)
     */
    public function validateNode(array $node): bool
    {
        return isset($node['document']) &&
               isset($node['document']['type']) &&
               isset($node['document']['id']);
    }

    /**
     * Extract useful metadata from node
     */
    public function extractMetadata(array $node): array
    {
        $doc = $node['document'] ?? [];

        return [
            'id'        => $doc['id'] ?? null,
            'name'      => $doc['name'] ?? null,
            'type'      => $doc['type'] ?? null,
            'visible'   => $doc['visible'] ?? true,
            'children'  => isset($doc['children']) ? count($doc['children']) : 0,
            'layout'    => $doc['layoutMode'] ?? null,
            'styles'    => $doc['styles'] ?? [],
        ];
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
