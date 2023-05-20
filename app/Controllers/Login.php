<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\validasienkripsi;
use App\Models\Testusers;

class Login extends BaseController
{
    public function index()
    {
        d(user_id());
        d(in_group("Mahasiswa"));
        $model = model(Testusers::class);
        $data['datalogin'] = $model->seeall();
        return view("login", $data);
    }

    public function debuglogin()
    {
        $session = \Config\Services::session();
        $postdata = $this->request->getPost([
            'logindengan'
        ]);
        $model = model(Testusers::class);
        d($postdata['logindengan']);
        $id = $postdata['logindengan'];
        $data['userdata'] = [
            'id' => $id
        ];
        $session->set($data);
        d($id);
        d($data);
        $hasil = $model->seebyID($postdata['logindengan']);
        d($hasil[0]);
        d($session->get());
    }
}
