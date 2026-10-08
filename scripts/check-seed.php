<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'types='.App\Models\ItemType::count().' assets='.App\Models\Asset::count().' lists='.App\Models\AssetList::count().PHP_EOL;
foreach (App\Models\ItemType::all() as $t) {
    echo $t->slug.PHP_EOL;
}
