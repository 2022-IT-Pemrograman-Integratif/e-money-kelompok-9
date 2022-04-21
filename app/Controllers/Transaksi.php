<?php

namespace App\Controllers;

use App\Models\Users;
use App\Models\Transactions;


class Transaksi extends BaseController{
    
    protected $current_user;
    protected $userModel;
    protected $transactionModel;

    // Jenis Transaksi
    // 1. Top Up
    // 2. Transfer
    
    public function __construct(){
        helper('JwtValidation');
        try {
            $this->current_user = tokenAuth();
        } catch (\Exception $e) {
            echo json_encode(['message' => $e->getMessage()]);
            exit();
        }
        $this->usersModel = new Users();
        $this->transactionModel = new Transactions();
    }
    
    public function cekSaldo(){
        return $this->response->setJSON(
            ['saldo' => $this->usersModel->where('id', $this->current_user)->first()['saldo']]
        );
    }

    public function topUp(){
        try {
            if (!isset($_POST['jumlah'])) {
                http_response_code(400);
                throw new \Exception('Silahkan masukkan jumlah');
            }

            $saldo = $_POST["jumlah"] + $this->usersModel->where('id', $this->current_user)->first()['saldo'];
            
            $data = [
                'jenis' => 1,
                'origin_id' => $this->current_user,
                'nominal' => $_POST["jumlah"]
            ];
            
            if ($this->transactionModel->insert($data) == NULL)
                throw new \Exception('Kesalahan dalam pelaporan');
            
            $this->usersModel->set('saldo', $saldo)->where('id', $this->current_user)->update();
            
            return $this->response->setJSON([
                'message' => 'Top Up Berhasil'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'message' => $e->getMessage()
            ]);
            exit();
        }
    }

    public function transfer(){
        try {
            if (!isset($_POST['telepon']) || !isset($_POST['jumlah'])) {
                http_response_code(400);
                throw new \Exception('Silahkan masukkan telepon dan jumlah');
            }

            $destination = $this->usersModel->where('telepon', $_POST["telepon"])->first();
            $origin = $this->usersModel->where('id', $this->current_user)->first();
            
            if (!$destination) {
                throw new \Exception('Nomor telepon tidak ditemukan');
            }

            if ($origin['saldo'] < $_POST["jumlah"]) {
                throw new \Exception('Saldo tidak mencukupi');
            }

            $data = [
                'jenis' => 2,
                'origin_id' => $this->current_user,
                'destination_id' => $destination['id'],
                'nominal' => $_POST["jumlah"]
            ];
            
            if ($this->transactionModel->insert($data) == NULL)
                throw new \Exception('Kesalahan dalam pelaporan');
            
            $this->usersModel->set('saldo', $_POST["jumlah"] + $destination['saldo'])->where('telepon', $_POST["telepon"])->update();
            $this->usersModel->set('saldo', $origin['saldo'] - $_POST["jumlah"])->where('id', $this->current_user)->update();


            return $this->response->setJSON([
                'message' => 'Transer Berhasil'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'message' => $e->getMessage()
            ]);
            exit();
        }
    }

    public function invoice($id){
        try {
            $res = $this->transactionModel->find($id);
            
            if (!$res) {
                throw new \Exception('ID invoice tidak dapat ditemukan');
            }
            
            if($res['jenis'] == 1)
                return $this->response->setJSON([
                    'jenis transaksi' => 'Top Up',
                    'oleh' => $this->usersModel->find($res['origin_id'])['username'],
                    'waktu' => $res['waktu'],
                    'nominal' => $res['nominal']
                ]);

            else if($res['jenis'] == 2)
                return $this->response->setJSON([
                    'jenis transaksi' => 'Transfer',
                    'oleh' => $this->usersModel->find($res['origin_id'])['username'],
                    'waktu' => $res['waktu'],
                    'kepada' => $this->usersModel->find($res['destination_id'])['username'],
                    'nomor telepon' => $this->usersModel->find($res['destination_id'])['telepon'],
                    'nominal' => $res['nominal']
                ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function history(){
        try {
            $result = $this->transactionModel->where("origin_id='$this->current_user' OR destination_id='$this->current_user'")->findAll();
            $temp = [];
            foreach($result as $res){
                if($res['jenis'] == 1)
                    $temp[] = ([
                        'id' => $res['id'],
                        'jenis transaksi' => 'Top Up',
                        'oleh' => $this->usersModel->find($res['origin_id'])['username'],
                        'waktu' => $res['waktu'],
                        'nominal' => $res['nominal']
                    ]);
    
                else if($res['jenis'] == 2)
                    $temp[] = ([
                        'id' => $res['id'],
                        'jenis transaksi' => 'Transfer',
                        'oleh' => $this->usersModel->find($res['origin_id'])['username'],
                        'waktu' => $res['waktu'],
                        'kepada' => $this->usersModel->find($res['destination_id'])['username'],
                        'nomor telepon' => $this->usersModel->find($res['destination_id'])['telepon'],
                        'nominal' => $res['nominal']
                    ]);
            }
            return $this->response->setJSON($temp);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'message' => $e->getMessage()
            ]);
        }
    }
}