<?php

class FTPUpload
{

    public static $ftp_connection;
    public static $ftp_server;
    public static $ftp_user;
    public static $ftp_password;

    /**
     * Function upload file via FTP
     * @param String $dir is directory of uploaded file 
     * @param String localfile is file upload
     * @param String $remotefile is name of uploaded file
     */
    public function upload( $dir, $local_file, $remote_file)
    {
        if (!$this->ftp_is_dir(self::$ftp_connection, $dir)) {
            $this->ftp_mkdris(self::$ftp_connection, $dir);
        }

        try {
            ftp_put(self::$ftp_connection, ( $dir . $remote_file ), $local_file, FTP_BINARY);
            ftp_close(self::$ftp_connection);

            $success = true;
            $message = 'Berhasil upload '. $remote_file ;

        }catch (Exception $e) {

            $success = false;
            $message = $e->getMessage();

		}

        return ['success' => $success, 'message' => $message];
    }

    /** 
     * Function preview file from FTP
     * @param String $path is url of file
    */
    public function preview( $path )
    {
        $parsing = parse_url( $path );
        $explode = explode(".", $parsing['path']);
        $ext = end($explode);
        if( $this->existFileFTP( $path ) )
        {
            $pathFtp = 'ftp://'.self::$ftp_user.':'.self::$ftp_password.'@'.self::$ftp_server. "/".( $path ) ;
            switch($ext)
            {
                case 'jpg':
                    $mime = 'data:image/jpeg';
                    break;
                case 'png':
                    $mime = 'data:image/png';
                    break;
                case 'pdf':
                    $mime = 'data:application/pdf';
                    break;
                default:
                    $mime = 'data:image/jpeg';
            }
            return htmlentities($mime . ";Base64,".base64_encode(file_get_contents($pathFtp)));

        }else{
            return 'could not found the file';
        }
    }

    /** 
     * Function download file from FTP
     * @param String $source is source url of file
     * @param String $file is name of downloaded file 
    */
    public function download( $source, $file )
    {
        $preview = $this->preview( $source );
        $explode = explode(",", $preview);
        $encodedData = end($explode);
        $decodedData = base64_decode($encodedData);

        // Actual download.
        header('Content-Type: application/force-download'); 
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"".$file."\"");

        echo $decodedData;

        die();
    }

    /** 
     * Function connect to FTP
     * @param String $address
     * @param String $username
     * @param String $password
    */
    static function connect($address, $username, $password)
    {
        self::config($address, $username, $password);
        try {

			$connection = ftp_connect($address);
			ftp_login($connection, $username, $password);

            self::$ftp_connection = $connection;

            return new static;
		} catch (Exception $e) {
			return $e->getMessage;
		}
    }


    /** 
     * Set variable
     * @param String $address
     * @param String $username
     * @param String $password
    */
    private static function config($address, $username, $pwd)
    {
        self::$ftp_server = $address;
        self::$ftp_user = $username;
        self::$ftp_password = $pwd;
    }

    /** 
     * Function ceck exist of file
     * @param String $dir
    */
    private function existFileFTP( $dir )
    {
        $res = ftp_size(self::$ftp_connection , $dir );            
        ftp_close(self::$ftp_connection );

        if ($res != -1) {
            return true;
        }
        
        return false;
    }

    /** 
     * Function ceck exist of directory
     * @param String $dir
     * @param $connection
    */
    private function ftp_is_dir($connection, $dir)
    {
        $pushd = ftp_pwd($connection);

        if ($pushd !== false && @ftp_chdir($connection, $dir)) {
            ftp_chdir($ftp, $pushd);
            return true;
        }

        return false;
    }

    /**
     * Function make directory
     * @param $connection
     * @param String $path
    */
    private function ftp_mkdris($connection, $path)
    {
        $str = explode("/", $path);
        $dir = "";
        foreach ($str as $part) {
            $dir .= "/". $part ;
            if (!$this->ftp_is_dir($connection, $dir)) {
                return ftp_mkdir($connection, $dir);
            }
        }
    }

}