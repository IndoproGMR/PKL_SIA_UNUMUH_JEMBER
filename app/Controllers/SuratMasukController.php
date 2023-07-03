<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Jenissurat;
use App\Models\Suratmasuk;
use App\Models\TandaTangan;
use App\Libraries\enkripsi_library;
use CodeIgniter\Files\File;

class SuratMasukController extends BaseController
{
    // !START Untuk Mahasiswa
    // untuk melihat semua surat yang dimanta apakah sudah di ttd ngi
    public function indexStatusSurat()
    {
        PagePerm(['Mahasiswa', 'Calon Mahasiswa']);
        $model = model(Suratmasuk::class);
        $model2 = model(TandaTangan::class);
        $data['datasurat'] = $model->cekNoSurat(userInfo()['id']);

        foreach ($data['datasurat'] as $key => $value) {
            $data['surat'][$key] = $value['NoSurat'];
            $data['datasurat'][$key]['status'] = $model2->cekStatusSurat($data['surat'][$key]);
        }

        foreach ($data['datasurat'] as $key => $value) {
            if ($value['status']['belum'] == 0) {
                unset($data['datasurat'][$key]);
            }
        }

        return view('suratmasuk/status_surat', $data);
    }

    // page minta surat 2 fungsi ada dropdown ada konten box untuk mengisi data
    public function indexMintaSurat($idsurat = null)
    {
        PagePerm(['Mahasiswa', 'Calon Mahasiswa']);

        $model = model(Jenissurat::class);
        $data['jenissurat'] = $model->seeall();
        $data['minta'] = 0;

        if (!is_null($idsurat)) {
            $data['minta'] = 1;

            $model = model(Jenissurat::class);
            $data['datasurat'] = $model->seebyID($idsurat);

            // cek apakah id yang diminta ada di db ?
            if ($data['datasurat']['error'] == 'y') {
                return redirect()->to('/minta-surat');
            }

            $data['dataform'] = json_decode($data['datasurat']['form'], true);
        }
        return view('suratmasuk/mintasurat', $data);
    }

    // proses data untuk meminta
    public function addmintaSuratProses($idsurat)
    {
        PagePerm(['Mahasiswa', 'Calon Mahasiswa'], '/', false, 1);
        helper(['text']);
        $model  = model(Suratmasuk::class);
        $model2 = model(TandaTangan::class);
        $model3 = model(Jenissurat::class);

        $postdata = $this->request->getPost();

        $dataform = json_decode($model3->seebyID($idsurat)['form'], 'array');

        // $dataformarray['foto'] = '';

        if (isset($dataform['tambahan'])) {
            if (in_array('foto', $dataform['tambahan'])) {
                $validationRule = [
                    'foto' => [
                        'label' => 'Image File',
                        'rules' => [
                            'uploaded[foto]',
                            'is_image[foto]',
                            'mime_in[foto,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                            'max_size[foto,100]',
                            'max_dims[foto,1024,768]',
                        ],
                    ],
                ];

                if (!$this->validate($validationRule)) {
                    $data = ['errors' => $this->validator->getErrors()];
                    return 'error';
                }

                $img = $this->request->getFile('foto');
                if (!$img->hasMoved()) {
                    $filepath = WRITEPATH . 'uploads/' . $img->store('dataSurat/' . userInfo()['id']);
                    $data = ['uploaded_fileinfo' => new File($filepath)];
                }
                $lokasifoto = potongString($filepath, WRITEPATH);
                $postdata['foto'] = $lokasifoto;
            }
        }


        $dataformarray = json_decode(json_encode($postdata), true);
        unset($dataformarray['LaB7Thol']);
        unset($dataformarray['honeypot']);


        // !untuk menyimpan suratmasuk
        $data['NoSurat'] = random_string();
        $data['TimeStamp'] = time();
        $data['DataTambahan'] = base64_encode(json_encode($dataformarray));
        $data['JenisSurat_id'] = $idsurat;
        $data['mshw_id'] = userInfo()['id'];

        // !untuk menyimpan TTD
        $TTDdata['TTD'] = removeGroups(json_decode($model3->seebyID($data['JenisSurat_id'])['form'], true)['TTD']);
        $TTDdata['NoSurat'] = $data['NoSurat'];

        /**
         * unutk TTDdata['status','hash','randomStr','Timestamp']
         * berada di fungsi transformData(data);
         */
        $ttdArray = transformData($TTDdata);


        // set ke dalam database
        if (!$model->addSuratMasuk($data)) {
            return "error meminta surat";
        }
        if (!$model2->addTTD($ttdArray)) {
            return "error meminta ttd";
        }

        return redirect()->to('/status-surat');
    }

    // melihat riwayat surat yang sudah full ttd
    public function indexRiwayatSurat()
    {
        PagePerm(['Mahasiswa', 'Calon Mahasiswa']);

        $model = model(Suratmasuk::class);
        $model2 = model(TandaTangan::class);
        $data['datasurat'] = $model->cekNoSurat(userInfo()['id']);

        foreach ($data['datasurat'] as $key => $value) {
            $data['surat'][$key] = $value['NoSurat'];
            $data['datasurat'][$key]['status'] = $model2->cekStatusSurat($data['surat'][$key]);
        }

        foreach ($data['datasurat'] as $key => $value) {
            if ($value['status']['belum'] !== 0) {
                unset($data['datasurat'][$key]);
            }
        }

        return view('suratmasuk/riwayat_surat', $data);
    }
    // !END Untuk Mahasiswa


    // !START untuk pengajaran
    // untuk melihat macam2 jenis surat jenis surat
    public function indexJenisSurat()
    {
        PagePerm(['Dosen']);

        $jenissurat = model(Jenissurat::class);
        $data['jenissurat'] = $jenissurat->seeall(true);

        // d($data);
        return view('suratmasuk/semua_surat', $data);
    }

    // untuk menambah jenis surat ke db
    public function addJenisSuratProses()
    {
        PagePerm(['Dosen'], 'error_perm', false, 1);

        $postdata = $this->request->getPost([
            'inputisi',
            'jenisSurat',
            'diskripsi',
        ]);

        // d($postdata);
        $dataform = $this->request->getPost();

        unset($dataform['inputisi']);
        unset($dataform['jenisSurat']);
        unset($dataform['diskripsi']);
        unset($dataform['LaB7Thol']);
        unset($dataform['Za1koo5E']);

        $data['json_data'] = ubahJSONkeSimpelJSON(json_encode($dataform, true));

        $model = model(Jenissurat::class);

        if ($model->addJenisSurat($postdata['jenisSurat'], $postdata['diskripsi'], $postdata['inputisi'], $data['json_data'])) {
            return redirect()->to('/bikin-surat');
        }

        echo "tidak dapat menambahkan Surat ke db";
    }

    // untuk mengisi jenis surat
    public function addJenisSurat()
    {
        PagePerm(['Dosen']);

        $jenissurat = model(Jenissurat::class);
        $data['jenissurat'] = $jenissurat->seeall();
        $data['level'] = $jenissurat->seegrouplvl();
        $data['ttd'] = $jenissurat->seeNamaPettd();
        return view('suratmasuk/inputjenissurat', $data);
    }

    // untuk melihat detail jenis surat
    public function detailJenisSurat($idsurat)
    {
        $model = model(Jenissurat::class);

        $data['datasurat'] = $model->seebyID($idsurat);

        return view('suratmasuk/detailjenissurat', $data);
    }

    public function updateJenisSuratToggleProses()
    {
        PagePerm(['Dosen'], 'error_perm', false, 1);
        $postdata = $this->request->getPost(['id']);
        $model = model(Jenissurat::class);

        if ($model->toggleshow($postdata['id'])) {
            return redirect()->to('/semua-surat');
        }
        return "error update show";
    }

    // untuk meng update jenis surat ke db
    public function updateJenisSuratProses()
    {
        $postdata = $this->request->getPost(
            [
                'id',
                'inputisi',
                'jenisSurat',
                'diskripsi'
            ]
        );
        d($postdata);
        $model = model(Jenissurat::class);
        if ($model->updateJenisSurat($postdata['id'], $postdata['jenisSurat'], $postdata['diskripsi'], $postdata['inputisi'])) {
            return redirect()->to('/semua-surat');
        }
        return "error tidak bisa update";
    }
    // !END untuk pengajaran



    // !START untuk PenandaTangan
    public function indexStatusTTD()
    {
        PagePerm(['Dosen']);
        $model = model(TandaTangan::class);
        $data['datasurat'] = $model->cekStatusSuratTTD(userInfo());

        foreach ($data['datasurat'] as $key => $value) {
            $data['surat'][$key] = $value['NoSurat'];
            $data['datasurat'][$key]['status'] = $model->cekStatusSurat($data['surat'][$key]);
        }
        $data['perluttd'] = count($data['datasurat']);
        return view('suratmasuk/status_ttd', $data);
    }

    public function TTDProses()
    {
        PagePerm(['Dosen'], '/', false, 1);

        $postdata = $this->request->getPost([
            'id',
        ]);

        $model = model(TandaTangan::class);
        $data['TTD'] = $model->cekStatusById($postdata['id']);


        if (!$data['TTD']['Status'] == 0) {
            return "surat Sudah di TTd ngi";
        }

        $enkripsi = new enkripsi_library;

        $data['update']['Status'] = 1;
        $data['update']['hash'] = $enkripsi->enkripsiTTD($data['TTD']['NoSurat'], $data['TTD']['mshw_id']);
        $data['update']['TimeStamp'] = time();
        $data['update']['pendattg_id'] = userInfo()['id'];


        if (!$model->updateTTD($postdata['id'], $data['update'])) {
            return "TTD error";
        }
        return redirect()->to('/status-TTD');
    }

    public function indexRiwayatTTD()
    {
        PagePerm(['Dosen']);
        $model = model(TandaTangan::class);
        $data['datasurat'] = $model->cekStatusSuratTTD(userInfo(), 1);

        return view('suratmasuk/riwayat_ttd', $data);
    }
    // !END untuk PenandaTangan

    // !START untuk semua pengguna
    public function kameraQR()
    {
        $data['nocam'] = false;

        if (isset($_GET["nocam"])) {
            $nocam = $_GET["nocam"];
            if ($nocam == "true") {
                $data['nocam'] = true;
            }
        }
        return view("suratmasuk/kameraqr", $data);
        // return view("html5qrscanner", $data);
    }
    // !END untuk semua pengguna
}
