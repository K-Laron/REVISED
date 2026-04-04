Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Invoke-Step {
    param(
        [string] $Label,
        [scriptblock] $Command
    )

    Write-Host "==> $Label"
    & $Command
    Write-Host "<== $Label complete"
}

Invoke-Step 'Route tests' {
    php vendor/bin/phpunit tests/Routes
}

Invoke-Step 'Critical HTTP smoke tests' {
    php vendor/bin/phpunit tests/Integration/Http/AuthenticatedPageSmokeTest.php tests/Integration/Http/PublicAdoptionJourneyHttpTest.php tests/Integration/Http/ApiDashboardHttpTest.php
}

Invoke-Step 'Full PHPUnit suite' {
    php vendor/bin/phpunit
}

Invoke-Step 'Node tooling check' {
    npm run tooling:check
}

Invoke-Step 'Frontend smoke tests' {
    node tests/Frontend/animals-inline-photo-upload.test.js
}
