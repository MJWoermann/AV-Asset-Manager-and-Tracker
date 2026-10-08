<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('email', 'admin@example.com')->firstOrFail();
$event = App\Models\AssetList::where('name', 'Stocktake 2027')->firstOrFail();
$inventory = App\Models\AssetList::where('name', 'FMI Export')->firstOrFail();

$session = App\Models\ScanSession::create([
    'user_id' => $user->id,
    'event_list_id' => $event->id,
    'inventory_list_id' => $inventory->id,
    'status' => 'active',
]);

$service = app(App\Services\ScanService::class);
$result = $service->scanCode($session, 'RC-1001', 1);

echo 'message='.$result['message'].PHP_EOL;
echo 'duplicate='.($result['duplicate'] ? 'yes' : 'no').PHP_EOL;
echo 'inventory='.$result['inventory_status'].PHP_EOL;
echo 'items='.count($result['items']).PHP_EOL;

$dup = $service->scanCode($session, 'RC-1001', 1);
echo 'dup_message='.$dup['message'].PHP_EOL;

$compare = app(App\Services\ListComparisonService::class)->compare($event->fresh(), $inventory);
echo 'matched='.$compare['matched']->count().' inventory_only='.$compare['inventory_only']->count().' scanned_only='.$compare['scanned_only']->count().PHP_EOL;
