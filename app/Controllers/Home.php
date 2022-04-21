<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        return $this->response->setJSON(["message" => "Selamat Datang di PayPhone"]);
    }
}
