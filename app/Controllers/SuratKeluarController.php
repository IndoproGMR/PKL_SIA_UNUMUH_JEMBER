<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Jenissurat;
use App\Models\TandaTangan;
use App\Libraries\enkripsi_library;
use App\Models\SuratKeluraModel;

class SuratKeluarController extends BaseController
{
    // !START Untuk Mahasiswa
    // untuk melihat semua surat yang dimanta apakah sudah di ttd ngi
    public function indexStatusSurat()
    {
        PagePerm(['Mahasiswa', 'Calon Mahasiswa']);
        $model = model(SuratKeluraModel::class);
        $model2 = model(TandaTangan::class);
        $data['datasurat'] = $model->cekNoSurat(userInfo()['id'], true);

        foreach ($data['datasurat'] as $key => $value) {
            $data['surat'][$key] = $value['SuratIdentifier'];
            $data['datasurat'][$key]['status'] = $model2->cekStatusSurat($data['surat'][$key]);
        }

        foreach ($data['datasurat'] as $key => $value) {
            if ($value['status']['belum'] == '0') {
                unset($data['datasurat'][$key]);
            }
        }

        // d($data);
        return view('suratkeluar/mahasiswa/semua_Status_TTD', $data);
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
        return view('suratkeluar/mahasiswa/Proses_Minta-TTD', $data);
    }

    // proses data untuk meminta
    public function addMintaSuratProses($idsurat)
    {
        PagePerm(['Mahasiswa', 'Calon Mahasiswa'], 'error_perm', false, 1);
        helper(['text']);
        $model  = model(SuratKeluraModel::class);
        $model2 = model(TandaTangan::class);
        $model3 = model(Jenissurat::class);

        $postdata = $this->request->getPost();
        unset($postdata['LaB7Thol']);
        // return d($postdata);
        $seebyid = $model3->seebyID($idsurat);
        d($seebyid);
        // $dataform = json_decode($model3->seebyID($idsurat)['form'], 'array');
        $dataform = json_decode($seebyid['form'], 'array');
        // d($dataform);
        // d($dataform2);

        // !Validasi dan save gambar Start
        if (isset($dataform['tambahan'])) {
            if (in_array('foto', $dataform['tambahan'])) {
                $validationRule = Validasi_Foto();

                if (!$this->validate($validationRule)) {
                    $dataerror = $this->validator->getErrors();
                    return FlashMassage('Surat/Minta-TandaTangan/' . $idsurat, $dataerror, 'fail');
                    // return FlashException('Tidak Dapat Menambahkan Foto');
                }

                $img = $this->request->getFile('foto');

                if ($img->isValid() && !$img->hasMoved()) {
                    $filepath = "uploads/SuratKeluar/" . userInfo()['id'];
                    $extension = $img->getExtension();
                    $extension = empty($extension) ? '' : '.' . $extension;
                    $filename = generateIdentifier(16, 'time') . $extension;

                    // !menyimpan foto ke dalam folder public
                    try {
                        $img->move($filepath, $filename);
                    } catch (\Throwable $th) {
                        return FlashMassage('Surat/Minta-TandaTangan/' . $idsurat, [resMas('f.u.save.fl.db')], 'fail');
                        // return FlashException('Tidak Dapat Menyimpan Foto');
                    }

                    // !Copy file ke folder Arhive
                    $fileFrom = $filepath . "/" . $filename;
                    $fileTo = cekDir("../Z_Archive/" . $filepath) . "/" . $filename;

                    if (!copyFile($fileFrom, $fileTo)) {
                        return FlashMassage('Surat/Minta-TandaTangan/' . $idsurat, [resMas('f.u.save.fl.server')], 'fail');
                        // return FlashException('Tidak Dapat Menyimpan Foto ke safeplace');
                    }

                    $filepath = $filepath . '/' . $filename;
                }
                $postdata['foto'] = $filepath;
                // d($postdata);
            }
        }
        // !Validasi dan save gambar End

        // !Validasi input Start
        $dataerror = null;
        foreach ($postdata as $key => $value) {
            $validationRule = Validasi_Input($key);
            // !ganti php.ini untuk menambah upload limit

            if (!$this->validate($validationRule)) {
                $dataerror = $this->validator->getErrors();
            }
        }

        if (!$dataerror == null) {
            return FlashMassage('/minta-surat/' . $idsurat, $dataerror, 'fail');
        }
        // !Validasi input End



        $dataformarray = json_decode(json_encode($postdata), true);


        // !untuk menyimpan SuratKeluar
        $data['SuratIdentifier'] = generateIdentifier();
        $data['TimeStamp'] = getUnixTimeStamp();
        $data['DataTambahan'] = base64_encode(json_encode($dataformarray));
        $data['JenisSurat_id'] = $idsurat;
        $data['mshw_id'] = userInfo()['id'];

        // !untuk menyimpan TTD
        $TTDdata['TTD'] = removeGroups(json_decode($seebyid['form'], true)['TTD']);
        $TTDdata['SuratIdentifier'] = $data['SuratIdentifier'];

        d($TTDdata);
        d($data['JenisSurat_id']);
        d($idsurat);



        /**
         * unutk TTDdata['status','hash','IdentifierSurat','Timestamp']
         * berada di fungsi transformData(data);
         */
        $ttdArray = transformData($TTDdata);

        // d($ttdArray);
        // d($data);

        // set ke dalam database
        if (!$model->addSuratKeluar($data)) {
            return FlashMassage('Surat/Minta-TandaTangan', [resMas('f.u.minta.surat')]);
        }
        if (!$model2->addTTD($ttdArray)) {
            return FlashMassage('Surat/Minta-TandaTangan', [resMas('f.u.minta.ttd')]);
        }

        return FlashMassage('Surat/Status-TandaTangan', [resMas('s.u.minta.surat')], 'success');
    }

    // melihat riwayat surat yang sudah full ttd
    public function indexRiwayatSurat()
    {
        PagePerm(['Mahasiswa', 'Calon Mahasiswa']);

        $model = model(SuratKeluraModel::class);
        $model2 = model(TandaTangan::class);
        $data['datasurat'] = $model->cekNoSurat(userInfo()['id']);

        foreach ($data['datasurat'] as $key => $value) {
            $data['surat'][$key] = $value['SuratIdentifier'];
            $data['datasurat'][$key]['status'] = $model2->cekStatusSurat($data['surat'][$key]);
        }

        foreach ($data['datasurat'] as $key => $value) {
            if ($value['status']['belum'] !== '0') {
                unset($data['datasurat'][$key]);
            }
        }

        return view('suratkeluar/mahasiswa/Semua_Riwayat-TTD', $data);
    }
    // !END Untuk Mahasiswa


    // !START untuk pengajaran
    // untuk melihat macam2 jenis surat jenis surat
    public function indexJenisSurat()
    {
        PagePerm(['Dosen']);

        $jenissurat = model(Jenissurat::class);
        $data['jenissurat'] = $jenissurat->seeall(true);

        return view('suratkeluar/pengajaran/Semua_Master-surat', $data);
    }

    // untuk mengisi jenis Master surat
    public function addJenisSurat()
    {
        PagePerm(['Dosen']);

        $jenissurat = model(Jenissurat::class);
        $data['level'] = $jenissurat->seegrouplvl();
        $data['ttd'] = $jenissurat->seeNamaPettd();
        return view('suratKeluar/pengajaran/Input_Master-Surat', $data);
    }

    // untuk menambah jenis Master surat ke db
    public function addJenisSuratProses()
    {
        PagePerm(['Dosen'], 'error_perm', false, 1);

        $postdatasurat = $this->request->getPost(
            [
                'inputisi',
                'jenisSurat',
                'diskripsi'
            ]
        );

        $postdataform = $this->request->getPost(
            [
                'input',
                'tambahan',
                'TTD'
            ]
        );

        // !Validasi input Start
        $postdatarequired = $this->request->getPost(
            [
                'inputisi',
                'jenisSurat',
                'diskripsi',
                'TTD'
            ]
        );

        $dataerror = null;
        foreach ($postdatarequired as $key => $value) {
            $validationRule = Validasi_Input($key);
            // !ganti php.ini untuk menambah upload limit

            if (!$this->validate($validationRule)) {
                $dataerror = $this->validator->getErrors();
            }
        }

        if (!$dataerror == null) {
            return FlashMassage('/input/master-surat', $dataerror, 'warning');
        }
        // !Validasi input End

        foreach ($postdataform as $key => $value) {
            if ($value == null) {
                unset($postdataform[$key]);
            }
        }

        $data['json_data'] = json_encode($postdataform);

        $model = model(Jenissurat::class);

        if (!$model->addJenisSurat($postdatasurat['jenisSurat'], $postdatasurat['diskripsi'], $postdatasurat['inputisi'], $data['json_data'])) {
            return FlashMassage('/input/master-surat', [resMas('f.u.save.jenis.surat.db')], 'fail');
            // return FlashException('Tidak dapat Menambahkan Surat ke dalam DataBase');
        }
        return FlashMassage('/Staff/Master-Surat', [resMas('s.save.jenis.surat.db')], 'success');
    }

    // untuk melihat detail jenis surat
    public function detailJenisSurat($idsurat)
    {
        $model = model(Jenissurat::class);
        $data['datasurat'] = $model->seebyID($idsurat, 1);
        return view('suratkeluar/pengajaran/Edit_Master_Surat', $data);
    }

    // untuk meng update jenis surat ke db
    public function updateJenisSuratProses()
    {
        PagePerm(['Dosen'], 'error_perm', false, 1);
        $postdata = $this->request->getPost(
            [
                'id',
                'inputisi',
                'jenisSurat',
                'diskripsi'
            ]
        );

        // !Validasi input Start
        $dataerror = null;
        foreach ($postdata as $key => $value) {
            $validationRule = Validasi_Input($key);

            if (!$this->validate($validationRule)) {
                $dataerror = $this->validator->getErrors();
            }
        }

        if (!$dataerror == null) {
            return FlashMassage('/Staff/Master-Surat', $dataerror, 'warning');
        }
        // !Validasi input End

        $model = model(Jenissurat::class);
        if (!$model->updateJenisSurat($postdata['id'], $postdata['jenisSurat'], $postdata['diskripsi'], $postdata['inputisi'])) {
            return FlashMassage('/Staff/Master-Surat', [resMas('f.update.master.surat.db')], 'success');
        }
        return FlashMassage('/Staff/Master-Surat', [resMas('s.update.master.surat')], 'success');
    }

    // untuk menToggle visiblity master surat kepada mahasiswa
    public function updateJenisSuratToggleProses()
    {
        PagePerm(['Dosen'], 'error_perm', false, 1);
        $postdata = $this->request->getPost(['id']);
        $model = model(Jenissurat::class);

        if (!$model->toggleshow($postdata['id'])) {
            return FlashMassage('/semua_master-surat', [resMas('f.update.surat')], 'fail');
        }
        return FlashMassage('/Staff/Master-Surat', [resMas('s.update.master.surat')], 'success');
    }



    public function indexTanpaNoSurat()
    {
        PagePerm(['Dosen']);
        $model = model(SuratKeluraModel::class);
        $data['datasurat'] = $model->seeAllnoNoSurat();

        return view('suratkeluar/pengajaran/Semua_Surat-Tanpa-Nomer', $data);
        // return view('suratkeluar/pengajaran/index_SuratTanpaNo', $data);
    }

    public function updateTanpaNoSurat()
    {
        $postdata = $this->request->getPost('id');
        $model = model('SuratKeluraModel');
        $dataSurat = $model->cekSuratByNo($postdata);
        $dataSurat['id'] = $postdata;

        return view('suratKeluar/pengajaran/Edit_Surat-Tanpa-Nomer', $dataSurat);
    }

    public function updateTanpaNoSuratProses()
    {
        $postdata = $this->request->getPost(['id', 'NoSurat']);
        $model = model('SuratKeluraModel');

        // !Validasi input Start
        $dataerror = null;
        foreach ($postdata as $key => $value) {
            $validationRule = Validasi_Input($key);

            if (!$this->validate($validationRule)) {
                $dataerror = $this->validator->getErrors();
            }
        }

        if (!$dataerror == null) {
            return FlashMassage('Staff/Permintaan_TTD-Surat_Tanpa_NoSurat', $dataerror, 'warning');
        }
        // !Validasi input End


        $data = [
            'NoSurat' => $postdata['NoSurat']
        ];
        if (!$model->updateNoSurat($postdata['id'], $data)) {
            return FlashMassage('Staff/Permintaan_TTD-Surat_Tanpa_NoSurat', [resMas('f.save.surat')], 'fail');
        }
        // d($dataSurat);
        // d($postdata);

        return FlashMassage('Staff/Permintaan_TTD-Surat_Tanpa_NoSurat', [resMas('s.save.surat')], 'success');
    }

    public function deleteTanpaNoSuratProses()
    {
        $postdata = $this->request->getPost('id');
        $model = model('SuratKeluraModel');
        if (!$model->deleteSurat($postdata)) {
            return FlashMassage('Staff/Permintaan_TTD-Surat_Tanpa_NoSurat', [resMas('f.update.surat')], 'fail');
            // return FlashException('gagal menghapus Surat');
        }

        return FlashMassage('Staff/Permintaan_TTD-Surat_Tanpa_NoSurat', [resMas('S.delete.surat')], 'success');
        // return FlashSuccess('semua-surat-tanpa_NoSurat', 'berhasil menghapus Surat');
    }
    // !END untuk pengajaran



    // !START untuk PenandaTangan
    public function indexStatusTTD()
    {
        PagePerm(['Dosen']);
        $model = model(TandaTangan::class);
        $data['datasurat'] = $model->cekStatusSuratTTD(userInfo());

        // foreach ($data['datasurat'] as $key => $value) {
        //     $data['surat'][$key] = $value['SuratIdentifier'];
        //     $data['datasurat'][$key]['status'] = $model->cekStatusSurat($data['surat'][$key]);
        // }

        $data['perluttd'] = count($data['datasurat']);
        // d($data);
        return view('suratkeluar/penandatangan/Semua_Perlu_TTD', $data);
    }

    public function TTDProses()
    {
        PagePerm(['Dosen'], 'error_perm', false, 1);

        $postdata = $this->request->getPost([
            'id',
        ]);

        $model = model(TandaTangan::class);
        $data['TTD'] = $model->cekStatusById($postdata['id']);


        if (!$data['TTD']['Status'] == 0) {
            return FlashException(resMas('f.u.save.ttd.k.ttd.done.exist'));
        }

        $enkripsi = new enkripsi_library;

        $data['update']['Status']      = 1;
        $data['update']['qrcodeName']  =  "QRCode-" . generateIdentifier(16, 'time');
        $data['update']['hash']        = $enkripsi->enkripsiTTD($data['TTD']['NoSurat'], $data['TTD']['mshw_id']);
        $data['update']['TimeStamp']   = getUnixTimeStamp();
        $data['update']['pendattg_id'] = userInfo()['id'];


        if (!Render_Qr($data['update']['hash'], $data['update']['qrcodeName'])) {
            return FlashMassage('/Surat_Perlu_TandaTangan', [resMas('f.u.make.qr')], 'fail');
        }

        if (!$model->updateTTD($postdata['id'], $data['update'])) {
            return FlashMassage('/Surat_Perlu_TandaTangan', [resMas('f.u.save.ttd')], 'fail');
        }

        return FlashMassage('/Surat_Perlu_TandaTangan', [resMas('s.save.ttd')], 'success');
    }

    public function indexRiwayatTTD()
    {
        PagePerm(['Dosen']);
        $model = model(TandaTangan::class);
        $data['datasurat'] = $model->cekStatusSuratTTD(userInfo(), 1);

        return view('suratkeluar/penandatangan/Semua_Riwayat_TTD', $data);
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
        return view("suratkeluar/kameraqr", $data);
        // return view("html5qrscanner", $data);
    }
    // !END untuk semua pengguna
}
