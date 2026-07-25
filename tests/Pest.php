<?php

use App\Models\Certificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the application and hit the database, so they get the
| base TestCase plus a fresh schema per test. Add a second binding here if a
| suite is ever introduced that needs neither.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Comparing Eloquent models with toBe() compares object identity, which fails
| for two instances of the same row. These expectations compare by key and
| report the certificate number on failure instead of a model dump.
|
*/

expect()->extend('toBeCertificate', function (Certificate $expected) {
    expect($this->value)->not->toBeNull(
        "Expected certificate [{$expected->certificate_number}], but none was resolved."
    );

    expect($this->value->is($expected))->toBeTrue(
        "Expected certificate [{$expected->certificate_number}], got [{$this->value->certificate_number}]."
    );

    return $this;
});
