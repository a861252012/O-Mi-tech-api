<?php
include dirname(__FILE__).'/../apicommon.php';

//输出图片

$captcha = new Captcha();
$captcha->width = 90;
$captcha->height = 28;
$image = $captcha->Generate();
$_SESSION['CAPTCHA_KEY'] = strtolower($captcha->phrase);
header('Content-Type:image/png');












/**
 * Class Captcha
 * @Copyright:1Room
 * @Author:D.C
 * @Version:1.0
 */
class Captcha{
    var $image  = null;
    var $fonts  = array('CeruleanNF.otf','droidsansmono__.ttf','Route66NF.otf','TuringCarNF.otf');
    var $width  = 100;
    var $height = 30;
    var $length = 4;
    var $phrase = null;
    var $string = array('A','B','C','D','E','F','H','J','K','L','M','N','P','R','S','T','U','V','W','X','Y','Z');
    var $session = null;
    var $requset = null;

    /**
     * Random String
     * @author D.C
     * @return string
     */
    public function Phrase()
    {
        if (strlen($this->phrase) != $this->length) {
            $phrase = null;
            $rand = array_rand($this->string, $this->length);
            foreach ($rand as $index)
                $phrase .= $this->string[$index];
            $this->phrase = $phrase;
        }
        return $this->phrase;
    }

    /**
     * Generate Captcha
     * @update D.C
     * @return null
     */
    public function Generate()
    {
        if (!extension_loaded("gd")) exit("Captcha Unable Load GD Library Copyright 1Room");
        $this->phrase = $this->Phrase();

        $image = is_resource($this->image) ? $this->image : $this->GenerateImage($this->phrase);
        imagepng($image);
        imagedestroy($image);
        return $image;
    }


    /**
     * Use GD Library Create Image
     * @author D.C
     * version 1.0
     * @return resource
     */
    private function CreateImage()
    {
        if (!is_resource($this->image)) {

            $this->image = imagecreatetruecolor($this->width, $this->height);
            $color1 = imagecolorallocate($this->image, mt_rand(200, 255),mt_rand(200, 255), mt_rand(150, 255));
            $color2 = imagecolorallocate($this->image, mt_rand(200, 255),mt_rand(200, 255), mt_rand(150, 255));
            $color1 = imagecolorsforindex($this->image, $color1);
            $color2 = imagecolorsforindex($this->image, $color2);
            $steps = $this->width;

            $r1 = ($color1['red'] - $color2['red']) / $steps;
            $g1 = ($color1['green'] - $color2['green']) / $steps;
            $b1 = ($color1['blue'] - $color2['blue']) / $steps;

            $x1 = 0; $y1 =& $i; $x2 = $this->width; $y2 =& $i;

            for ($i = 0; $i <= $steps; $i++) {
                $r2 = $color1['red'] - floor($i * $r1);
                $g2 = $color1['green'] - floor($i * $g1);
                $b2 = $color1['blue'] - floor($i * $b1);
                $color = imagecolorallocate($this->image, $r2, $g2, $b2);
                imageline($this->image, $x1, $y1, $x2, $y2, $color);
            }

            for ($i = 0, $count = mt_rand(10, 20); $i < $count; $i++) {
                $color = imagecolorallocatealpha($this->image, mt_rand(20, 255), mt_rand(20, 255),
                    mt_rand(100, 255), mt_rand(80, 120));
                imageline($this->image, mt_rand(0, $this->width), 0,
                    mt_rand(0, $this->width), $this->height, $color);
            }
        }
        return $this->image;
    }

    /**
     * Merge Random String and Created Image and Set Session
     * @author D.C
     * @version 1.1
     * @param null $phrase
     * @return resource
     */
    public function GenerateImage($phrase = null)
    {
        $image = $this->CreateImage();
        $phrase == null && $phrase = $this->Phrase();
        $fontsize = min($this->width, $this->height * 2) / (strlen($phrase));
        $spacing = (integer) ($this->width * 0.9 / strlen($phrase));
        $font = __DIR__.'/Fonts/'.$this->fonts[array_rand($this->fonts)];

        for ($i = 0, $strlen = strlen($phrase); $i < $strlen; $i++) {
            $color = imagecolorallocate($image, mt_rand(0, 160), mt_rand(0, 160), mt_rand(0, 160));
            $angle = mt_rand(-30, 30);
            $size = $fontsize / 12 * mt_rand(12, 14);
            $box = imageftbbox($size, $angle, $font, $phrase[$i]);
            $x = $spacing / 4 + $i * $spacing + 2;
            $y = $this->height / 2 + ($box[2] - $box[5]) / 4;
            imagefttext($image, $size, $angle, $x, $y, $color, $font, $phrase[$i]);
        }
        $this->image = $image;
        return $this->image;
    }


    /**
     * verify Captcha and Str return true or false
     * how to use please see of the Member Controller Password Method
     * @author d.c
     * @version 1.0
     * @param null $str
     * @return bool
     */
    /*
    public static function Verify(Request $request,$str = null)
    {
        $session = new Session();
        $key = $session->get('CAPTCHA_KEY');
        $session->remove('CAPTCHA_KEY');
        if(!empty($key) && !empty($str) && strtolower($str) === strtolower($key)){
            return true;
        }else{
            return false;
        }

    }
    */
}
