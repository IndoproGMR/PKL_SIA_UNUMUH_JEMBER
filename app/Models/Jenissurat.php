<?php

namespace App\Models;

use CodeIgniter\Model;

class Jenissurat extends Model
{
    // protected $DBGroup          = 'default';
    protected $table            = 'SM_JenisSurat';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    // protected $returnType       = 'array';
    // protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'isiSurat',
        'form',
        'description',
        'show',
        'delete'
    ];


    public function countdb()
    {
        return $this->countAllResults();
    }

    public function seeall($showall = false, $onlyshow = 1)
    {
        if ($showall) {
            return $this->findAll();
        }
        return $this->where('show', $onlyshow)->findAll();
    }


    public function seebyID(int $id)
    {
        $data['error'] = 'y';

        $datasurat = $this->where('id', $id)->where('show', '1')->find();

        if (count($datasurat) > 0) {
            $data['id']          = $datasurat[0]['id'];
            $data['name']        = $datasurat[0]['name'];
            $data['description'] = $datasurat[0]['description'];
            $data['isiSurat']    = base64_decode($datasurat[0]['isiSurat']);
            $data['form']        = base64_decode($datasurat[0]['form']);
            $data['error']       = 'n';
        }
        return $data;
    }

    public function addJenisSurat(String $jenissurat, String $description, String $isiSurat, String $form)
    {
        return $this->db->table('SM_JenisSurat')->insert([
            'name'          => $jenissurat,
            'description'   => $description,
            'isiSurat'      => base64_encode($isiSurat),
            'form'          => base64_encode($form),
            'show'          => 0,
        ]);
    }

    function updateJenisSurat(int $id, String $jenissurat, String $description, String $isiSurat)
    {
        return $this->update(
            $id,
            [
                'name'        => $jenissurat,
                'description' => $description,
                'isiSurat'    => base64_encode($isiSurat),
            ]
        );
    }

    function deleteJenisSurat(int $id)
    {
        return $this->update(
            $id,
            [
                'show'   => 0,
                'delete' => time()
            ]
        );
    }

    function toggleshow($id)
    {
        $cek = $this->where('id', $id)->find();
        d($cek);
        if (!(count($cek) > 0)) {
            return false;
        }

        if ($cek[0]['show'] == 1) {
            return $this->update($id, [
                'show' => 0
            ]);
        }

        if ($cek[0]['show'] == 0) {
            return $this->update($id, [
                'show' => 1
            ]);
        }
    }

    function seegrouplvl()
    {
        $db = \Config\Database::connect("siautama", false);
        $sql = "SELECT `Nama` FROM `level` ORDER BY `LevelID` ASC;";
        return $db->query($sql)->getResultArray();
    }

    function seeNamaPettd()
    {
        $db = \Config\Database::connect("siautama", false);
        $sql = "SELECT `dosen`.`Nama` as namattd, `dosen`.`Login` as login, `level`.`Nama` as lvl 
        FROM dosen LEFT JOIN `level` ON `level`.`LevelID`=`dosen`.`LevelID` UNION SELECT `karyawan`.`Nama` as namattd, `karyawan`.`Login` as login, `level`.`Nama` as lvl FROM karyawan LEFT JOIN `level` ON `level`.`LevelID`=`karyawan`.`LevelID` ORDER by namattd;";
        return $db->query($sql)->getResultArray();


        // $sql = "SELECT `dosen`.`Nama` as namattd, `dosen`.`Login` as login, `level`.`Nama` as lvl FROM dosen LEFT JOIN `level` ON `level`.`LevelID`=`dosen`.`LevelID` UNION SELECT `karyawan`.`Nama` as namattd, `karyawan`.`Login` as login, `level`.`Nama` as lvl FROM karyawan LEFT JOIN `level` ON `level`.`LevelID`=`karyawan`.`LevelID` WHERE `level`.`Nama` = 'Dosen' or `level`.`Nama` = 'Pengajaran Fakultas' ORDER by namattd;";
    }
}

// INSERT INTO `SM_JenisSurat` (`id`, `name`, `description`, `isiSurat`, `form`) VALUES (19, 'test', 'test multi data', 'bmFtYTp7e25hbWF9fQ==', 'eyJpbnB1dCI6WyJuYW1hIl0sIlRURCI6WyJHcm91cF9Eb3NlbiIsIkdyb3VwX01haGFzaXN3YSIsIkdyb3VwX0NhbG9uIE1haGFzaXN3YSIsIlBlcm9yYW5nYW5fMDAxNDAyNzUwMSJdfQ==');



// SELECT
// `dosen`.`Nama` as namattd,
// `dosen`.`Login` as login,
// `level`.`Nama` as lvl
// FROM dosen
// LEFT JOIN `level` ON `level`.`LevelID`=`dosen`.`LevelID`
// UNION
// SELECT
// `karyawan`.`Nama` as namattd,
// `karyawan`.`Login` as login,
// `level`.`Nama` as lvl
// FROM karyawan
// LEFT JOIN `level` ON `level`.`LevelID`=`karyawan`.`LevelID`
// WHERE `level`.`Nama` = 'Dosen' 
// or `level`.`Nama` = 'Pengajaran Fakultas'
// or `level`.`Nama` = 'Kepala Akademik'
// or `level`.`Nama` = 'Fakultas'
// or `level`.`Nama` = 'Kaprodi / Kajur'
// or `level`.`Nama` = 'Rektorat'
// or `level`.`Nama` = 'Pengajaran Fakultas'
// or `level`.`Nama` = 'Presenter'
// or `level`.`Nama` = 'Staf PMB'
// ORDER by namattd