<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class FertigMelderValidationTest extends TestCaseSymconValidation
{
    public function testValidateFertigMelder(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateDoneNotifierModule(): void
    {
        $this->validateModule(__DIR__ . '/../DoneNotifier');
    }
}