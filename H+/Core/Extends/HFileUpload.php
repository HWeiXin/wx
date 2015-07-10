<?php

class HFileUpload {

    const TYPE_UPLOAD_PIC = 1; //图片
    const TYPE_UPLOAD_VIDEO = 2; //视频
    const TYPE_UPLOAD_VIDEO_PIC = 3;//视频图片

    public $dir = 'file'; //文件上传的目录
    public $max_size = 4194304; //上传最大限制 默认4M;
    public $type_arr = array( //定义允许上传的文件扩展名
        'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
        'flash' => array('swf', 'flv'),
        'video' => array('3gp','swf', 'flv', 'mp4', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
        'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
    );
    private $_file_dir; //上传文件的目录
    private $_file; //上传的文件
    private $_type; //上传文件类型
    private $_suffix; //文件后缀
    private $_size; //文件大小
    private $_res_data = array(
        'ok' => false,
        //'url' => '',文件上传成功有这个参数
        //'thumburl' => '',成功生成缩略图有这个参数
        //'error' => null,错误时候有这个参数
    );

    /**
     * 保存文件
     */
    public function save($user_id, $width, $height) {
        $fileName = uniqid() . '.' . $this->_suffix;
        $file = $this->_fileDir . $user_id . '/' . $fileName; //文件名
        $this->_mkDir($this->_fileDir . $user_id); //创建目录
        if ($this->_theFile->saveAs($file, true)) {//保存到服务器指定目录
            if ($this->_type === self::TYPE_UPLOAD_PIC) {//图片
                $this->data['url'] = Yii::app()->baseUrl . '/' . $this->dir . '/user/' . $user_id . '/' . $fileName;
                if (!$this->_thumbPic($user_id, $file, $fileName, $width, $height)) {
                    $this->data['error'] = '缩略图生成失败';
                    return false;
                }
            } else {
                $this->data['url'] = Yii::app()->baseUrl . '/' . $this->dir . '/video/' . $user_id . '/' . $fileName;
            }
            $this->data['ok'] = true;
            return true;
        }
        $this->data['error'] = $this->getError();
        return false;
    }

    /**
     * 取得上传的文件
     */
    public function getFile($fileName) {
        $this->_theFile = CUploadedFile::getInstanceByName($fileName); //读取图像上传域,并使用系统上传组件上传
        if($this->_theFile != null){
            $file_name = $this->_theFile->getName();
            $pt = strrpos($file_name, ".");
            if ($pt)
                $this->_suffix = strtolower(substr($file_name, $pt + 1, strlen($file_name) - $pt));
            $this->_size = $this->_theFile->getSize();
            if ($this->_verifySuffix() && $this->_verifySize())
                return true;
        }else {
            $this->data['error'] = '没有获取到文件';
        }
        return false;
    }

    /**
     * 验证文件类型
     */
    private function _verifySuffix() {
        if (in_array($this->_suffix, $this->typeArr['image'])) {
            $this->_type = self::TYPE_UPLOAD_PIC;
            $this->_fileDir = dirname(Yii::app()->BasePath) . '/' . $this->dir . '/user/'; //文件目录
        } elseif (in_array($this->_suffix, $this->typeArr['video'])) {
            $this->_type = self::TYPE_UPLOAD_VIDEO;
            $this->_fileDir = dirname(Yii::app()->BasePath) . '/' . $this->dir . '/video/'; //文件目录
        } else {
            $this->data['error'] = '文件类型错误';
            return false;
        }
        return true;
    }

    /**
     * 验证文件大小
     */
    private function _verifySize() {
        if ($this->_size <= $this->maxSize || $this->maxSize==null)
            return true;
        $this->data['error'] = '文件超过最大上传限制';
        return false;
    }

    /**
     * 生成缩略图
     */
    private function _thumbPic($user_id, $pic, $fileName, $width, $height) {
        $thumbPic = $this->_fileDir . $user_id . '/thumb_' . $fileName;
        if (Image::thumb2($pic, $thumbPic, $this->_suffix, $width, $height, true)) {//生成缩略图
            if ($this->_type === self::TYPE_UPLOAD_PIC) {
                $this->data['thumburl'] = Yii::app()->baseUrl . '/' . $this->dir . '/user/' . $user_id . '/thumb_' . $fileName;
            } else {
                $this->data['thumburl'] = Yii::app()->baseUrl . '/' . $this->dir . '/video/' . $user_id . '/thumb_' . $fileName;
            }
            return true;
        }
        return false;
    }

    /**
     * 创建目录
     * @param string $dir 目录字符串
     * @return bool
     */
    private function _mkDir($dir) {
        if (!is_dir($dir)) {
            if($this->makeDir(dirname($dir))) {
                return mkdir($dir);
            }
            return false;
        }
        return true;
    }

    /**
     * 获取错误信息
     * @return string
     */
    private function getError(){
        $error = $this->_theFile->getError();
        $errorData = array(
            1 => '文件大小超过服务器配置大小',
            2 => '文件大小超过HTML表单大小',
            3 => '文件只有部分被上传',
            4 => '没有文件被上传',
            5 => '',
            6 => '找不到临时文件夹',
            7 => '文件写入失败',
        );
        return $errorData[$error];
    }

} 