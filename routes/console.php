<?php

use App\Console\Commands\CompleteExpiredCampaignMappings;
use App\Console\Commands\TriggerCampaignPublishing;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote')->hourly();
//
//Schedule::command('zone:create-test')->everyMinute();


Schedule::command(TriggerCampaignPublishing::class)->everyMinute();
Schedule::command(CompleteExpiredCampaignMappings::class)->everyMinute();
