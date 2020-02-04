<?php
/**
 * 票據憑證服務
 * @author Weine
 * @date 2020-02-04
 */

namespace App\Services;


class SocketCertificateService
{
    private $str = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    private $pixLen = 36;
    private $pixOne = 0;
    private $pixTwo = 0;
    private $pixThree = 0;
    private $pixFour = 0;
    private $pixFive = 0;
    private $privateKey = "MOONJOY";

    /** 产生一组socket票据 */
    public function generateCertification()
    {
        $dataSrc = $this->generateSourceData();//生成源数据
        $dataConfused = $this->confuseData($dataSrc);
        $dataMd5 = md5($dataConfused);
        $dataMd5 = strtoupper($dataMd5);
        $middleSrc = strlen($dataSrc) >> 1;
        $middleMD5 = strlen($dataMd5) >> 1;
        $result = "";
        $result .= substr($dataSrc, 0, $middleSrc);
        $result .= substr($dataMd5, 0, $middleMD5);
        $result .= substr($dataSrc, $middleSrc, strlen($dataSrc) - $middleSrc);
        $result .= substr($dataMd5, $middleMD5, strlen($dataMd5) - $middleMD5);

        return $result;
    }

    /**
     * 产生源数据
     * @return
     */
    private function generateSourceData()
    {
        $sb = "";// 创建一个StringBuilder
        $sb .= dechex(time() * 1000 + mt_rand(0, 999));// 先添加当前时间的毫秒值的16进制
        $this->pixFive++;

        if ($this->pixFive == $this->pixLen) {
            $this->pixFive = 0;
            $this->pixFive++;
            if ($this->pixFour == $this->pixLen) {
                $this->pixFour = 0;
                $this->pixThree++;
                if ($this->pixThree == $this->pixLen) {
                    $this->pixThree = 0;
                    $this->pixTwo++;
                    if ($this->pixTwo == $this->pixLen) {
                        $this->pixTwo = 0;
                        $this->pixOne++;
                        if ($this->pixOne == $this->pixLen) {
                            $this->pixOne = 0;
                        }
                    }
                }
            }
        }

        $sb .= substr($this->str, $this->pixOne, 1);
        $sb .= substr($this->str, $this->pixTwo, 1);
        $sb .= substr($this->str, $this->pixThree, 1);
        $sb .= substr($this->str, $this->pixFour, 1);
        $sb .= substr($this->str, $this->pixFive, 1);

        return strtoupper($sb);
    }

    /**
     * 混淆数据
     * @param dataSrc
     * @return
     */
    private function confuseData($dataSrc)
    {
        $keyLen = strlen($this->privateKey);
        $dataLen = strlen($dataSrc);
        $offLen = 0;
        $strBuild = "";

        if ($dataLen >= $keyLen) {
            $offLen = (int)($dataLen / $keyLen);

            for ($idx = 0; $idx < $keyLen; $idx++) {
                $strBuild .= substr($dataSrc, $idx * $offLen, ($idx + 1) * $offLen - $idx * $offLen);
                $strBuild .= substr($this->privateKey, $idx, 1);
            }

            $strBuild .= substr($dataSrc, $keyLen * $offLen);
        } else {
            $offLen = (int)($keyLen / $dataLen);

            for ($idx = 0; $idx < $dataLen; $idx++) {
                $strBuild .= substr($this->privateKey, $idx * $offLen, ($idx + 1) * $offLen - $idx * $offLen);
                $strBuild .= substr($dataSrc, $idx, 1);
            }

            $strBuild .= substr($this->privateKey, $dataLen * $offLen);
        }

        return $strBuild;
    }
}