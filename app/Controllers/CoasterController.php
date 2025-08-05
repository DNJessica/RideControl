<?php

namespace App\Controllers;

use App\Libraries\RedisService;
use CodeIgniter\Controller;

class CoasterController extends BaseController
{
    protected $redisService;

    public function __construct()
    {
        $this->redisService = new RedisService();
    }

    // POST /api/coasters
    public function create()
    {
        $data = $this->request->getJSON(true);

        $requiredFields = ['liczba_personelu', 'liczba_klientow', 'dl_trasy', 'godziny_od', 'godziny_do'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON(['error' => "Missing field: $field"]);
            }
        }

        $coasterId = 'cid' . bin2hex(random_bytes(8));
        $this->redisService->saveCoaster($coasterId, $data);

        return $this->response
            ->setStatusCode(201)
            ->setJSON(['coaster_id' => $coasterId]);
    }

    // PUT /api/coasters/:coasterId
    public function change($coasterId = null)
    {
        $data = $this->request->getJSON(true);
        $result = $this->redisService->changeCoaster($coasterId, $data);

        if ($result) {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(['message' => 'Coaster changed']);
        }

        return $this->response
            ->setStatusCode(404)
            ->setJSON(['error' => 'Coater not found']);
    }

        // GET /api/coasters/:coasterId/check-personel
        public function checkPersonel($coasterId = null)
        {
            if ($coasterId === null) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(['error' => 'Missing coasterId']);
            }
        
            $result = $this->redisService->checkPersonelBalance($coasterId);
        
            if (isset($result['error'])) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(['error' => $result['error']]);
            }
        
            return $this->response
                ->setStatusCode(200)
                ->setJSON($result);
        }

        public function checkClients($coasterId = null)
        {
            if ($coasterId === null) {
                return $this->response
                            ->setStatusCode(400)
                            ->setJSON(['error' => 'Missing coasterId']);
            }

            $result = $this->redisService->checkClientsLoad($coasterId);

            if (isset($result['error'])) {
                return $this->response
                            ->setStatusCode(404)
                            ->setJSON(['error' => $result['error']]);
            }

            return $this->response->setJSON($result);
        }
}