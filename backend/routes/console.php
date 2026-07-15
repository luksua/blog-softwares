<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Publicación programada del blog
|--------------------------------------------------------------------------
|
| Revisa cada minuto si hay registros en estado "programado" cuya fecha/hora
| de publicación ya se cumplió y los publica automáticamente.
|
*/
Schedule::command('blog:publicar-programados')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
