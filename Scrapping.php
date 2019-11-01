<?php
class Scrap {
    public $data;

    public function getData($text,$sebelum,$sesudah) {
        $text=" ".$text;
        $ini=strpos($text, $sebelum);
        if($ini == 0) return "";
        $ini+=strlen($sebelum);
        $panjang=strpos($text,$sesudah,$ini) - $ini;
        return substr($text,$ini,$panjang);
    }

    public function setUrl($var) {
        $this->data=file_get_contents($var);
    }
}
