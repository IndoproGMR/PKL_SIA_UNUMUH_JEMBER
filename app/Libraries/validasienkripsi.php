<?php

namespace App\Libraries;

use App\Libraries\enkripsi;

class validasienkripsi
{
    public function validasidariurl()
    {
        if (isset($_GET["validasi"]) && isset($_GET["noSurat"])) {
            $data = $_GET["validasi"];
            $noSurat = $_GET["noSurat"];
            echo $this->validasiEnkrispsi($data, $noSurat);
        } else {
            echo "input error";
        }
    }
    public function validasiEnkrispsi(String $data, String $noSurat)
    {
        $enkripsi = new enkripsi;
        $type = $enkripsi->pecahkan($data);

        $datahash = $type[2];
        if ($type[0] == "DiTandaTanganiOleh") {
            return $this->validasiTTD($datahash, $noSurat);
        } elseif ($type[0] == "PDF") {
            return $this->validasiPDF();
        } else {
            return "error";
        }
    }

    public function validasiTTD(String $data, String $noSurat)
    {
        // TODO panggil Model yang memiliki datahash dengan noSurat yang sama
        echo "</br>";
        echo $noSurat;
        echo "</br>";
        echo $data;
        echo "</br>";
        // TODO bila data dihash tidak ada di dalam DB maka dekripsikan hash nya
        $enkripsi = new enkripsi;
        $type = $enkripsi->dekripsiTTD($data);
        echo "</br>";
        echo "NoSurat: " . $type[0]; // nosurat
        echo "</br>";
        echo "UUID: " . $type[1]; // datakosong
        echo "</br>";
        echo "Penandatangan: " . $type[2]; // penandatangan
        echo "</br>";
        echo "Mahasiswa: " . $type[3]; // mahasiswa
        echo "</br>";
        echo "timestamp: " . $type[4]; // kapan
        echo "</br>";

        // return "ada yang sama";
    }

    public function validasiPDF()
    {
    }
}
