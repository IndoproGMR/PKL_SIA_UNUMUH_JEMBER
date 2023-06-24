<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\validasienkripsi;
use App\Models\AuthUserGroup;
use App\Models\Testusers;

class Login extends BaseController
{
    public function index()
    {
        $session = \Config\Services::session();
        $session->destroy();
        $data['datacoba'] = [
            '1' => [
                'login' => 'yuni',
                'password' => '*FDF3D0567'
            ],
            '2' => [
                'login' => '99111084',
                'password' => '*6947C77DB'
            ],
            '3' => [
                'login' => 'yasin',
                'password' => '*30C2AA2CF'
            ],
            '4' => [
                'login' => '0000001',
                'password' => '*638F025EF'
            ],
            '5' => [
                'login' => '0001016602',
                'password' => '*3FB29F57B'
            ],
            '6' => [
                'login' => '1210652011',
                'password' => '*3E5287812'
            ],


        ];

        // d(user_id());
        // d(in_group("Mahasiswa"));
        // $model = model(Testusers::class);
        // $data['datalogin'] = $model->seeall();
        $data['defaultdata'] = '';
        return view("login", $data);
    }

    public function debuglogin()
    {
        $session = \Config\Services::session();
        $postdata = $this->request->getPost([
            'dataLogin',
            'dataPassword'
        ]);
        $model = model(AuthUserGroup::class);

        if (!$model->proseslogin($postdata['dataLogin'], $postdata['dataPassword'])) {
            $session->destroy();
            return redirect()->to('error_perm');
        }
        $datauser = $model->cekuserinfo($postdata['dataLogin']);
        $data['userdata'] = [
            'id' => $datauser['LoginUser'],
            'NamaUser' => $datauser['NamaUser'],
            'FotoUser' => $datauser['FotoUser'],
            'namaLVL' => $datauser['namaLVL']
        ];
        $session->set($data);
        // $hasil = $model->seebyID($postdata['logindengan']);

        return redirect()->to('/');
        // d($postdata);
        // d($data);
        // d($session->get());
    }
    function logout()
    {
        $session = \Config\Services::session();
        $session->destroy();
        return redirect()->to('/');
    }
}
