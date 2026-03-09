<?php

use Illuminate\Support\Facades\Schedule;

// Monthly obligation generation — 1st of every month at midnight
Schedule::command('obligations:generate')->monthlyOn(1, '00:00');

// Arrears reminders via WhatsApp — every Monday at 09:00
Schedule::command('arrears:remind')->weeklyOn(1, '09:00');

// Monthly invoice generation from templates — 1st of every month at 00:30
Schedule::command('invoices:generate-monthly')->monthlyOn(1, '00:30');

// Auto-lock fiscal periods past grace window — daily at 01:00
Schedule::command('fiscal:auto-lock')->dailyAt('01:00');

// Refresh reporting materialized views — every 15 minutes
Schedule::command('reports:refresh-views')->everyFifteenMinutes();

// Prune activity log beyond retention window — daily at 03:00
Schedule::command('activitylog:prune')->dailyAt('03:00');

// Encrypted daily backup — every day at 04:00
Schedule::command('backup:run')->dailyAt('04:00');
