<?php

use Illuminate\Support\Facades\Schedule;

// Monthly obligation generation — 1st of every month at midnight
Schedule::command('obligations:generate')->monthlyOn(1, '00:00');

// Arrears reminders via WhatsApp — every Monday at 09:00
Schedule::command('arrears:remind')->weeklyOn(1, '09:00');

// Auto-lock fiscal periods past grace window — daily at 01:00
Schedule::command('fiscal:auto-lock')->dailyAt('01:00');

// Encrypted daily backup — every day at 02:00
Schedule::command('backup:run')->dailyAt('02:00');
