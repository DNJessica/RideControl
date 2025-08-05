<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/Libraries/RedisService.php';

use React\EventLoop\Loop;

$loop = Loop::get();
$service = new \App\Libraries\RedisService();

$env = getenv('CI_ENVIRONMENT');
$logFile = __DIR__ . "/writable/logs/monitor_{$env}.log";

function logProblem(string $msg, string $level = 'info')
{
    global $logFile, $env;

    if ($env === 'production' && !in_array($level, ['warning', 'error'])) {
        return;
    }

    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, "$timestamp [$level] $msg\n", FILE_APPEND);
}

$loop->addPeriodicTimer(15, function () use ($service) {
    $coasterIds = $service->getRedis()->sMembers('coasters');

    foreach ($coasterIds as $coasterId) {
        $coaster = $service->getRedis()->hGetAll("coaster:$coasterId");
        if (!$coaster) continue;

        $godzinyOd = $coaster['godziny_od'];
        $godzinyDo = $coaster['godziny_do'];

        $clientsStatus = $service->checkClientsLoad($coasterId);
        $staffStatus = $service->checkPersonelBalance($coasterId);

        $issues = [];
        $logLevel = 'info';

        if ($clientsStatus['status'] !== 'OK') {
            $issues[] = $clientsStatus['status'];
            $logLevel = 'warning';
        }

        if ($staffStatus['status'] !== 'ok') {
            if ($staffStatus['status'] === 'too_few') {
                $issues[] = "brakuje {$staffStatus['missing']} pracowników";
                $logLevel = 'warning';
            } elseif ($staffStatus['status'] === 'too_many') {
                $issues[] = "za dużo pracowników ({$staffStatus['available']}/{$staffStatus['required']})";
                $logLevel = 'warning';
            }
        }

        echo "\n[Kolejka $coasterId]\n";
        echo "1. Godziny działania: $godzinyOd - $godzinyDo\n";
        echo "2. Liczba wagonów: {$staffStatus['wagons']}\n";
        echo "3. Dostępny personel: {$staffStatus['available']}/{$staffStatus['required']}\n";
        echo "4. Klienci dziennie: {$clientsStatus['capacity']}/{$clientsStatus['required_clients']}\n";

        if (empty($issues)) {
            echo "5. Status: OK\n";
            logProblem("Kolejka $coasterId - Status OK", 'info');
        } else {
            echo "5. Problem: " . implode(', ', $issues) . "\n";
            logProblem("Kolejka $coasterId - Problem: " . implode(', ', $issues), $logLevel);
        }
    }
});

$loop->run();
