<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Users;


function tokenAuth()
{
    $headers = getallheaders();
    
    if (!isset($headers)) {
        http_response_code(400);
        throw new \Exception('Silahkan masukkan token');
    }
    
    if (!isset($headers['Authorization'])) {
        http_response_code(400);
        throw new \Exception('Silahkan masukkan token');
    }

    $token = explode(' ', $headers['Authorization'])[1];
    
    if (!$token) {
        http_response_code(400);
        throw new \Exception('Silahkan masukkan token');
    }
    
    $payload = JWT::decode($token, new Key($_ENV['jwtKey'], 'HS256'));
    
    if (!is_string($token)) {
        http_response_code(400);
        throw new \Exception('Token tidak valid');
    }
    
    if (empty($payload->username)) {
        http_response_code(401);
        throw new \Exception('Token tidak valid');
    }
    
    $usersModel = new Users();
    $result = $usersModel->where('username', $payload->username)->findAll()[0];
    if (!$result) {
        http_response_code(401);
        throw new \Exception('Token tidak valid');
    }

    return $result['id'];
}