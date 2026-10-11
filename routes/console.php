<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('webpulse:monitor')
    ->everyFiveMinutes()
    ->withoutOverlapping();
