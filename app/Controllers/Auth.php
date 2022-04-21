<?php

namespace App\Controllers;
use App\Models\Users;
use Firebase\JWT\JWT;

class Auth extends BaseController{
    
    protected $usersModel;

    public function __construct()
    {
        $this->usersModel = new Users();
    }

    public function register()
    {
        try {
            if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['telepon'])) {
                http_response_code(400);
                throw new \Exception('Silahkan masukkan username, telepon, dan password');
            }

            $password = password_hash(htmlspecialchars($_POST['password']), PASSWORD_DEFAULT);
            $username = htmlspecialchars($_POST['username']);
            $telepon = htmlspecialchars($_POST['telepon']);

            $data = [
                'username' => $username,
                'password' => $password,
                'telepon' => $telepon,
                'saldo' => 0
            ];

            if ($this->usersModel->insert($data) == NULL)
                throw new \Exception('Data akun gagal ditambahkan');

            $payload = ['username' => $username];

            $token = JWT::encode($payload, $_ENV['jwtKey'], 'HS256');
            return $this->response->setJSON([
                'message' => 'Register Berhasil',
                'username' => $_POST['username'],
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'message' => $e->getMessage()
            ]);
            exit();
        }
    }

    public function login(){
        try {
            if (!isset($_POST['username']) || !isset($_POST['password'])) {
                http_response_code(400);
                throw new \Exception('Silahkan masukkan username dan password');
            }

            $username = htmlspecialchars($_POST["username"]);
            $password = htmlspecialchars($_POST["password"]);

            $result = $this->usersModel->where('username', $username)->findAll()[0];

            if (!$result) throw new \Exception('Username dan Password Tidak Sesuai');

            if (!password_verify($password, $result['password']))
                throw new \Exception('Username dan Password Tidak Sesuai');


            $payload = ['username' => $result['username']];

            $token = JWT::encode($payload, $_ENV['jwtKey'], 'HS256');
            return $this->response->setJSON([
                'message' => 'Login Berhasil',
                'username' => $result['username'],
                'telepon' => $result['telepon'],
                'saldo' => $result['saldo'],
                'token' => $token
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'message' => $e->getMessage()
            ]);
            exit();
        }
    }
}