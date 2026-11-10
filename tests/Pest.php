<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Feature testovi rade sa celom aplikacijom (Tests\TestCase) i svaki
 * počinje sa praznom, migriranom bazom (RefreshDatabase, odeljak 14.4).
 * Unit testovi ne podižu Laravel, pa su mnogo brži.
 */
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
