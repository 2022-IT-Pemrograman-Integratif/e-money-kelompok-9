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
            if ($this->usersModel->where('id', $this->current_user)->first()['role'] != 1) {
                http_response_code(400);
                throw new \Exception('Access denied');
            }

            if (!isset($_POST['jumlah']) || !isset($_POST['telepon'])) {
                http_response_code(400);
                throw new \Exception('Silahkan masukkan jumlah dan telepon');
            }
            
            $destination = $this->usersModel->where('telepon', $_POST['telepon'])->first();
            
            if (!$destination) {
                http_response_code(400);
                throw new \Exception('Nomor telepon tidak valid');
            }

            $old_saldo = $destination['saldo'];

            $saldo = $_POST["jumlah"] + $old_saldo;
            
            $data = [
                'jenis' => 1,
                'origin_id' => $this->current_user,
                'nominal' => $_POST["jumlah"]
            ];
            
            if ($this->transactionModel->insert($data) == NULL)
                throw new \Exception('Kesalahan dalam pelaporan');
            
            $this->usersModel->set('saldo', $saldo)->where('id', $destination['id'])->update();
            
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
            if (!isset($_POST['telepon']) || !isset($_POST['jumlah']) || !isset($_POST['emoney'])) {
                http_response_code(400);
                throw new \Exception('Silahkan masukkan telepon, jumlah, dan nama e-money');
            }

            $destination = $this->usersModel->where('telepon', $_POST["telepon"])->first();
            $origin = $this->usersModel->where('id', $this->current_user)->first();

            if ($_POST["jumlah"] <= 0) {
                http_response_code(400);
                throw new \Exception('Transaksi tidak valid');
            }

            if ($origin['saldo'] < $_POST["jumlah"]) {
                http_response_code(400);
                throw new \Exception('Saldo tidak mencukupi');
            }

            if ($_POST['emoney'] != 'payphone'){

                $chTransfer = curl_init();
                $chLogin= curl_init();

                curl_setopt($chTransfer, CURLOPT_POST, 1);
                curl_setopt($chTransfer, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chLogin, CURLOPT_POST, 1);
                curl_setopt($chLogin, CURLOPT_RETURNTRANSFER, true);

                if ($_POST['emoney'] == 'galle'){
        
                    $queryLogin = [
                        'username' => 'payPhone',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://gallecoins.herokuapp.com/api/login");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, json_encode($queryLogin));
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    $chLogin_output = json_decode($chLogin_output);

                    $queryTransfer = [
                        'phone' => $_POST["telepon"],
                        'amount' => $_POST["jumlah"],
                        'description' => 'transfer from PayPhone',
                    ];
        
                    curl_setopt($chTransfer, CURLOPT_URL,"https://gallecoins.herokuapp.com/api/transfer");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, json_encode($queryTransfer));
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MTksInBob25lIjoiMDgxMjYzMjM5NTAyIiwidXNlcm5hbWUiOiJwYXlQaG9uZSIsInJvbGUiOiJ1c2VyIiwiaWF0IjoxNjUyMTcwNDE0LCJleHAiOjE2NTIyNTY4MTR9.bJPaY1HKyt-nIv-FsdZNdKEh50_2gm7gA78h05iMJ6o",
                        'Content-Type:application/json'
                        ]);

                }else if ($_POST['emoney'] == "moneyz"){
                    $queryLogin = [
                        'phone' => '081263239502',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://moneyz-kelompok6.herokuapp.com/api/login");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, json_encode($queryLogin));
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    $chLogin_output = json_decode($chLogin_output);
                    
                    $query_transfer = [
                        'nomortujuan' => $_POST["telepon"],
                        'nominal' => $_POST["jumlah"],
                    ];
        
                    curl_setopt($chTransfer, CURLOPT_URL,"https://moneyz-kelompok6.herokuapp.com/api/user/transfer");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, json_encode($query_transfer));
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer $chLogin_output->token",
                        'Content-Type:application/json'
                        ]);
                }
                
                else if ($_POST['emoney'] == "CuanIND"){
                    $queryLogin = [
                        'notelp' => '081263239502',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://e-money-kelompok5.herokuapp.com/cuanind/user/login");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, json_encode($queryLogin));
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    $chLogin_output = json_decode($chLogin_output);
                    
                    $query_transfer = [
                        'target' => $_POST["telepon"],
                        'amount' => $_POST["jumlah"],
                    ];
        
                    curl_setopt($chTransfer, CURLOPT_URL,"https://e-money-kelompok5.herokuapp.com/cuanind/transfer");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, json_encode($query_transfer));
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1hIjoicGF5UGhvbmUiLCJub3RlbHAiOiIwODEyNjMyMzk1MDIiLCJpYXQiOjE2NTIxNjk4NTAsImV4cCI6MTY1MjI1NjI1MH0.I0HVNg6ZXwlKj_PSrqen_A-IonUUXCFPObKdM9xY3ks",
                        'Content-Type:application/json'
                        ]);
                }
                
                else if ($_POST['emoney'] == "peacepay"){
                    $queryLogin = [
                        'number' => '081263239502',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://e-money-kelompok-12.herokuapp.com/api/login");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, json_encode($queryLogin));
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    $chLogin_output = json_decode($chLogin_output);
                    
                    $query_transfer = [
                        'tujuan' => $_POST["telepon"],
                        'amount' => $_POST["jumlah"],
                    ];
        
                    curl_setopt($chTransfer, CURLOPT_URL,"https://e-money-kelompok-12.herokuapp.com/api/transfer");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, json_encode($query_transfer));
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer $chLogin_output->token",
                        'Content-Type:application/json'
                        ]);
                }
                
                else if ($_POST['emoney'] == "Payfresh"){
                    $queryLogin = [
                        'email' => 'payphone@gmail.com',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://payfresh.herokuapp.com/api/login");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, json_encode($queryLogin));
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    $chLogin_output = json_decode($chLogin_output);
                    
                    $query_transfer = [
                        'phone' => $_POST["telepon"],
                        'amount' => $_POST["jumlah"],
                    ];
        
                    curl_setopt($chTransfer, CURLOPT_URL,"https://payfresh.herokuapp.com/api/user/transfer/33");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, json_encode($query_transfer));
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer $chLogin_output->token",
                        'Content-Type:application/json'
                        ]);
                }
                
                else if ($_POST['emoney'] == "KCN"){
                    $queryLogin = [
                        'email' => 'payPhone@gmail.com',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://kecana.herokuapp.com/login");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, json_encode($queryLogin));
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    
                    $query_transfer = [
                        'id' => "29",
                        'nohp' => $_POST["telepon"],
                        'nominaltransfer' => (int)$_POST["jumlah"],
                    ];
        
                    curl_setopt($chTransfer, CURLOPT_URL,"https://kecana.herokuapp.com/transfer");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, json_encode($query_transfer));
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer $chLogin_output",
                        'Content-Type:application/json'
                        ]);
                }
                
                else if ($_POST['emoney'] == "e-COIN"){
                    $queryLogin = [
                        'phone' => '081263239502',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://ecoin10.my.id/api/masuk");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, json_encode($queryLogin));
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    $chLogin_output = json_decode($chLogin_output);
                    
                    $query_transfer = [
                        'phone' => '081263239502',
                        'tfmethod' => 2,
                        'phone2' => $_POST["telepon"],
                        'amount' => $_POST["jumlah"],
                        'description' => 'transfer from PayPhone',
                    ];
        
                    curl_setopt($chTransfer, CURLOPT_URL,"https://ecoin10.my.id/api/transfer");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, json_encode($query_transfer));
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer $chLogin_output->token",
                        'Content-Type:application/json'
                        ]);
                }
                
                else if ($_POST['emoney'] == "Buski Coins"){
                    $queryLogin = [
                        'username' => 'payPhone',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://arielaliski.xyz/e-money-kelompok-2/public/buskidicoin/publics/login");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, $queryLogin);
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:multipart/form-data']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    $chLogin_output = json_decode($chLogin_output);
                    
                    $query_transfer = [
                        'nomer_hp' => '081263239502',
                        'nomer_hp_tujuan' => $_POST["telepon"],
                        'e_money_tujuan' => 'Buski Coins',
                        'amount' => $_POST["jumlah"],
                        'description' => 'transfer from PayPhone',
                    ];
                    
                    $token= $chLogin_output->message->token;
                    curl_setopt($chTransfer, CURLOPT_URL,"https://arielaliski.xyz/e-money-kelompok-2/public/buskidicoin/admin/transfer");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, $query_transfer);
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer $token",
                        'Content-Type:multipart/form-data'
                        ]);
                }
                
                else if ($_POST['emoney'] == "PadPay"){
                    $queryLogin = [
                        'email' => 'payphone@gmail.com',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://mypadpay.xyz/padpay/api/login.php");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, json_encode($queryLogin));
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    $chLogin_output = json_decode($chLogin_output);
                    
                    $query_transfer = [
                        'email' => 'payphone@gmail.com',
                        'password' => 'payPhone',
                        'jwt' => $chLogin_output->Data->jwt,
                        'tujuan' => $_POST["telepon"],
                        'jumlah' => $_POST["jumlah"],
                    ];
                    
                    $token= $chLogin_output->Data->jwt;
                    curl_setopt($chTransfer, CURLOPT_URL,"https://mypadpay.xyz/padpay/api/transaksi.php/62");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, json_encode($query_transfer));
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer $token",
                        'Content-Type:application/json'
                        ]);
                }
                else if ($_POST['emoney'] == "talangin"){
                    $queryLogin = [
                        'email' => 'payPhone@gmail.com',
                        'password' => 'payPhone',
                    ];

                    curl_setopt($chLogin, CURLOPT_URL,"https://e-money-kelomok-11.000webhostapp.com/api/login.php");
                    curl_setopt($chLogin, CURLOPT_POSTFIELDS, json_encode($queryLogin));
                    curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

                    $chLogin_output = curl_exec($chLogin);
                    curl_close ($chLogin);
                    
                    $chLogin_output = json_decode($chLogin_output);
                    
                    $query_transfer = [
                        'jwt' => $chLogin_output->jwt,
                        'pengirim'=> '081263239502',
                        'penerima' => $_POST["telepon"],
                        'jumlah' => $_POST["jumlah"],
                    ];
                    
                    $token= $chLogin_output->jwt;
                    curl_setopt($chTransfer, CURLOPT_URL,"https://e-money-kelomok-11.000webhostapp.com/api/transfer.php");
                    curl_setopt($chTransfer, CURLOPT_POSTFIELDS, json_encode($query_transfer));
                    curl_setopt($chTransfer, CURLOPT_HTTPHEADER, [
                        "Authorization:Bearer $token",
                        'Content-Type:application/json'
                        ]);
                }
                
                else{
                    throw new \Exception('E-money tujuan belum ada');
                }

                curl_setopt($chTransfer, CURLOPT_RETURNTRANSFER, true);
                $server_output = curl_exec($chTransfer);
                curl_close ($chTransfer);

                if ($server_output == "OK") {
                    echo $server_output;
                } else {
                    echo $server_output;
                    exit();
                }

            }else{
                if (!$destination) {
                    http_response_code(400);
                    throw new \Exception('Nomor telepon tidak ditemukan');
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
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'message' => $e->getMessage()
            ]);
            exit();
        }
    }

    public function invoice($id){
        try {
            
            if (!isset($_POST['telepon'])) {
                http_response_code(400);
                throw new \Exception('Silahkan masukkan telepon');
            }

            $res = $this->transactionModel->find($id);
            $destination = $this->usersModel->where('telepon', $_POST["telepon"])->first();

            if (!$destination || ($this->current_user != $destination['id'])) {
                http_response_code(400);
                throw new \Exception('Nomor telepon tidak valid');
            }

            if (!$res || ($res['origin_id'] == $destination['telepon']) || ($res['destination_id'] == $destination['telepon'])) {
                throw new \Exception('Invoice tidak dapat ditemukan');
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

            if (!isset($_POST['telepon'])) {
                http_response_code(400);
                throw new \Exception('Silahkan masukkan telepon');
            }

            $destination = $this->usersModel->where('telepon', $_POST["telepon"])->first();

            if (!$destination || ($this->current_user != $destination['id'])) {
                http_response_code(400);
                throw new \Exception('Nomor telepon tidak valid');
            }

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
