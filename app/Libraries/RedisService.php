<?php

namespace App\Libraries;

class RedisService
{
    protected $redis;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);  
    }

    //dla logow
    public function getRedis(): \Redis
    {
        return $this->redis;
    }

    public function saveCoaster(string $coasterId, array $data): void
    {
        $key = "coaster:".$coasterId;

        $this->redis->hMSet($key, $data);
        $this->redis->sAdd("coasters", $coasterId);
    }

    public function changeCoaster(string $coasterId, array $data): bool
    {
        if (!$this->redis->exists("coaster:$coasterId")) {
            return false;
        } 
        $coaster = $this->redis->hGetAll("coaster:$coasterId");
        $fields = ['liczba_personelu', 'liczba_klientow', 'godziny_od', 'godziny_do'];
        foreach($fields as $field){
            if(isset($data[$field])){
                $coaster[$field]=$data[$field];
            }
        }
        return $this->redis->hMSet("coaster:$coasterId", $coaster);
    }

    //wyliczamy liczbę dostepnego personelu
    public function checkPersonelBalance(string $coasterId): array
    {
        $coaster = $this->redis->hGetAll("coaster:$coasterId");

        if (!$coaster || !isset($coaster['liczba_personelu'])) {
            return [
                'error' => 'Coaster not found or missing liczba_personelu'
            ];
        }

        $available = (int)$coaster['liczba_personelu'];
        $wagonKeys = $this->redis->sMembers("coaster:{$coasterId}:wagons");
        $numWagons = count($wagonKeys);
        $required = 1 + (2 * $numWagons);

        return [
            'wagons' => $numWagons,
            'required' => $required,
            'available' => $available,
            'missing' => max(0, $required - $available),
            'excess' => max(0, $available - $required),
            'status' => $available == $required ? 'ok' : ($available > $required ? 'too_many' : 'too_few')
        ];
    }


    public function saveWagon(string $coasterId, array $data): string
    {
        $wagonId = 'wid' . bin2hex(random_bytes(8));
        $key = "coaster:{$coasterId}:wagon:{$wagonId}";

        $this->redis->hMSet($key, $data);
        $this->redis->sAdd("coaster:{$coasterId}:wagons", $wagonId);

        $capacity = $this->passengersCapacity($data['predkosc_wagonu'], $data['ilosc_miejsc'], $coasterId);
        $this->redis->hSet($key, 'liczba_pasazerow_na_dzien', $capacity);
        return $wagonId;
    }

    //liczymy ile pasazerow ten wagon obsluzy
    public function passengersCapacity(float $speed, int $capacity, string $coasterId): int
    {
        $coaster = $this->redis->hGetAll("coaster:$coasterId");
        $cycleTime = $coaster['dl_trasy']/($speed*60)+5; //czas w minutach na cykl + 5 minut przerwy
        $czasPracy = $this->getCzasPracy($coaster);
        $liczbaCykli = floor($czasPracy/$cycleTime);
        return $capacity*$liczbaCykli;
    }

    //czas pracy w minutach
    public function getCzasPracy(array $coaster): int
    {
        $start = \DateTime::createFromFormat('H:i', $coaster['godziny_od']);
        $end = \DateTime::createFromFormat('H:i', $coaster['godziny_do']);
        $minutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
        return $minutes;
    }

    public function deleteWagon(string $coasterId, string $wagonId): bool
    {
        $this->redis->del("coaster:{$coasterId}:wagon:{$wagonId}");
        return $this->redis->sRem("coaster:{$coasterId}:wagons", $wagonId);
    }
    

    public function getWagons(string $coasterId): array
    {
        $wagonIds = $this->redis->sMembers("coaster:{$coasterId}:wagons");
        $result = [];

        foreach ($wagonIds as $id) {
            $key = "coaster:{$coasterId}:wagon:{$id}";
            $result[] = $this->redis->hGetAll($key);
        }

        return $result;
    }

    public function checkClientsLoad(string $coasterId): array
    {
        $coaster = $this->redis->hGetAll("coaster:$coasterId");
        if (!$coaster) {
            return ['error' => 'Coaster not found'];
        }

        $requiredClients = $coaster['liczba_klientow'];
        $totalClients = 0;

        $wagonIds = $this->redis->sMembers("coaster:$coasterId:wagons");
       // return $wagonIds;
        foreach ($wagonIds as $wagonId) {
            $wagon = $this->redis->hGetAll("coaster:$coasterId:wagon:$wagonId");
            if ($wagon && isset($wagon['liczba_pasazerow_na_dzien'])) {
                $totalClients += $wagon['liczba_pasazerow_na_dzien'];
            }
        }

        if ($totalClients < $requiredClients) {
            return [
                'status' => 'Problem: Brakuje wagonów',
                'required_clients' => $requiredClients,
                'capacity' => $totalClients,
                'note' => 'Add more wagons to meet demand'
            ];
        }

        if ($totalClients >= 2 * $requiredClients) {
            return [
                'status' => 'Problem: Za dużo wagonów',
                'capacity' => $totalClients,
                'required_clients' => $requiredClients,
                'note' => 'Consider removing wagons to optimize resources'
            ];
        }

        return [
            'status' => 'OK',
            'capacity' => $totalClients,
            'required_clients' => $requiredClients
        ];
    }
}
