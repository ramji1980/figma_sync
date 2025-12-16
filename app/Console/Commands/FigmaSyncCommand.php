<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Services\FigmaService;
use App\Models\FigmaFrame;
use App\Models\FigmaNode;
use App\Models\FigmaImage;
use Illuminate\Support\Facades\Log;


class FigmaSyncCommand extends Command
{
protected $signature = 'figma:sync {fileKey}';
protected $description = 'Sync Figma frames and nodes into MongoDB';


public function handle(FigmaService $figma)
{
$fileKey = $this->argument('fileKey');


$this->info('Fetching Figma file...');
$file = $figma->getFile($fileKey);


$pages = $file['document']['children'] ?? [];


foreach ($pages as $page) {
$this->info("Processing Page: {$page['name']}");
$this->processChildren($figma, $fileKey, $page['children'] ?? []);
}


$this->info('Figma sync completed successfully');
}


protected function processChildren(FigmaService $figma, string $fileKey, array $children)
{
foreach ($children as $node) {
if ($node['type'] === 'FRAME') {
$this->info("Syncing Frame: {$node['name']} ({$node['id']})");


$nodeData = $figma->getNode($fileKey, $node['id']);
$imageData = $figma->getImage($fileKey, $node['id']);


if (!$figma->validateNode($nodeData)) {
continue;
}


FigmaFrame::updateOrCreate(
['node_id' => $node['id']],
[
'name' => $node['name'],
'raw' => $nodeData,
'metadata' => $figma->extractMetadata($nodeData),
]);


FigmaNode::updateOrCreate(
['node_id' => $node['id']],
[
    'raw' => $nodeData,
    'metadata' => $figma->extractMetadata($nodeData),   
]);

FigmaImage::updateOrCreate(
['node_id' => $node['id']],
[
'file_key' => $fileKey,
'format' => 'png',  
'scale' => 1,
'image_url' => $imageData['url'] ?? null,   
'metadata' => [$imageData['metadata'],'fetched_at' => now(),],
]
);

}



if (!empty($node['children'])) {
$this->processChildren($figma, $fileKey, $node['children']);
}
}
}
}