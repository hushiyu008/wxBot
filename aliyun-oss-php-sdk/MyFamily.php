<?php
/**
 * Created by PhpStorm.
 * User: hushiyu
 * Date: 2020/8/4
 * Time: 3:14 PM
 */
require_once __DIR__ . '/samples/Common.php';

use OSS\Core\OssException;


class MyFamily {

    private $bucketName;
    private $client;
    //微信群的文件目录
    private $wxFileDir = "/Users/hushiyu/Library/Containers/com.tencent.xinWeChat/Data/Library/Application Support/com.tencent.xinWeChat/2.0b4.0.9/2d2fe2eebf48c6b3d18023658dcfdfd3/Message/MessageTemp/278af5380b4db3c4ae7c84d915cb68a4/";
    private $fileImagePreDir;
    //视频文件路径前缀
    private $fileVideoPreDir;
    //当前在处理的文件名，不含路径
    private $fileName;
    //当前在处理的文件名，含路径
    private $filePath;
    //
    private $object;
    //处理完成后，文件转移到这个目录
    private $removeDir;
    private $logDir;

    public function __construct()
    {
        $this->bucketName   = Common::getBucketName();
        $this->client       = Common::getOssClient();
        $this->logDir = "/Users/hushiyu/Downloads/";
        $this->removeDir = "/Users/hushiyu/Downloads/tmp";
        $this->fileImagePreDir = $this->wxFileDir . "Image/";
        $this->fileVideoPreDir = $this->wxFileDir . "Video/";

        if (is_null($this->client)) exit(1);
    }

    public function doStart() {

        echo "start ". date("Y-m-d H:i:s"). " ";

        $time = time();

        if (!$this->getSingleImage()) {
            if (!$this->getSingleMp4()) {
                echo "暂时没有需要上传的文件";
                exit;
            }
        }

        try {
            $this->client->uploadFile($this->bucketName, $this->object, $this->filePath);
            $this->_Log($this->object. " 上传成功");
            $this->remove();
        } catch (OssException $e) {
            $this->_Log($this->object ." 上传失败:" . $e->getMessage());
        }

        $time = time() - $time;
        echo $this->object . " done use " . $time . " seconds" . PHP_EOL;
    }

    private function _Log($msg) {
        file_put_contents($this->logDir . "/" . date("Y-m-d") . ".txt" , $msg . PHP_EOL, FILE_APPEND);
    }

    /**
     * 获取应该上传的一张图片
     */
    private function getSingleImage() {
        $findFile = false;
        $handler = opendir($this->fileImagePreDir);
        while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
            if ($filename != "." && $filename != ".." && substr($filename, -4) == ".jpg" && stripos($filename, "thumb") === false) {
                $this->fileName = $filename;//118471596512411_.pic.jpg
                $findFile = true;
                break;
            }
        }

        $this->filePath = $this->fileImagePreDir . "/" . $this->fileName;
        $this->object = date("Y/m/d", (int)substr($this->fileName, (stripos($this->fileName, "_.pic") - 10), stripos($this->fileName, "_.pic"))) . "/{$this->fileName}";

        closedir($handler);
        return $findFile;
    }

    /**
     * 获取应该上传的一个视频
     */
    private function getSingleMp4() {
        $findFile = false;
        $handler = opendir($this->fileVideoPreDir);
        while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
            if ($filename != "." && $filename != ".." && substr($filename, -4) == ".mp4") {
                $this->fileName = $filename;//1544870459650632.mp4
                $findFile = true;
                break;
            }
        }

        $this->filePath = $this->fileVideoPreDir . "/" . $this->fileName;
        $this->object = date("Y/m/d", (int)substr($this->fileName, 0, 10)) . "/{$this->fileName}";

        closedir($handler);
        return $findFile;
    }

    private function remove() {
        rename($this->filePath, $this->removeDir.'/'.$this->fileName);
    }
}

$obj = new MyFamily();
for($i=0;$i<10000;$i++) {
    $obj->doStart();
    usleep(100);
}






