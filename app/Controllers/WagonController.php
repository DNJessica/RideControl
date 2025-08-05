<?php

namespace App\Controllers;

use App\Libraries\RedisService;
use CodeIgniter\RESTful\ResourceController;

class WagonController extends BaseController
{
    protected $redisService;

    public function __construct()
    {
        $this->redisService = new RedisService();
    }

    // POST /api/coasters/:coasterId/wagons
    public function create($coasterId = null)
    {
        $data = $this->request->getJSON(true);

        if (!isset($data['ilosc_miejsc']) || !isset($data['predkosc_wagonu'])) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON(['error' => 'Missing required fields']);
        }

        $wagonId = $this->redisService->saveWagon($coasterId, $data);

        return $this->response
            ->setStatusCode(201)
            ->setJSON(['wagon_id' => $wagonId]);
    }

    // DELETE /api/coasters/:coasterId/wagons/:wagonId
    public function delete($coasterId = null, $wagonId = null)
    {
        $result = $this->redisService->deleteWagon($coasterId, $wagonId);

        if ($result) {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(['message' => 'Deleted']);
        }

        return $this->response
            ->setStatusCode(404)
            ->setJSON(['error' => 'Wagon not found']);
    }
    

}
