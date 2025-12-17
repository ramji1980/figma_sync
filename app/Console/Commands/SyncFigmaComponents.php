<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Services\FigmaComponentService;
use App\Services\FigmaIconDetectorService;
use App\Models\FigmaComponent;
use App\Models\FigmaComponentSet;
use App\Models\FigmaIcon;


class SyncFigmaComponents extends Command
{
protected $signature = 'figma:sync-components {fileKey}';
protected $description = 'Sync Figma Components & Component Sets';


public function handle(FigmaComponentService $figma, FigmaIconDetectorService $iconDetector)
{
$fileKey = $this->argument('fileKey');


$this->info('Starting Figma Component Sync');


// Component Sets
$sets = $figma->fetchComponentSets($fileKey);
$this->info('Component Sets Found: ' . count($sets));


foreach ($sets as $i => $set) {
$this->line("[Set " . ($i+1) . "] {$set['name']}");


FigmaComponentSet::updateOrCreate(
['figma_id' => $set['node_id']],
[
'file_key' => $fileKey,
'key' => $set['key'],
'name' => $set['name'],
'description' => $set['description'] ?? null,
//'page_id' => $set['page_id'],
]
);
}


// Components
$components = $figma->fetchComponents($fileKey);
$this->info('Components Found: ' . count($components));


foreach ($components as $i => $component) {
$this->line("[Component " . ($i+1) . "] {$component['name']}");


$node = $figma->fetchNode($fileKey, $component['node_id']);

$icon = $iconDetector->analyze($node);


FigmaComponent::updateOrCreate(
['figma_id' => $component['node_id']],
[
'file_key' => $fileKey,
'name' => $component['name'],
'key' => $component['key'],
'set_id' => $component['component_set_id'] ?? null,
'description' => $component['description'] ?? null,
//'page_id' => $component['page_id'],
'node_json' => $node,
]
);
FigmaIcon::updateOrCreate(
['node_id' => $component['node_id']],
[
'file_key' => $fileKey,
'name' => $component['name'],
'component_key' => $component['key'],
'is_icon_set' => $icon['is_icon'],
'icon_rules' => $icon['rules'],
'dimensions' => $icon['dimensions'],
'raw_node' => $node,
]);


}


$this->info('Figma Component Sync Completed');
}
}