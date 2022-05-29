<?php

namespace MultilineAmio\MultipartFormDataBuilder;

class FormDataBuilder
{

    private array $data = [];
    private array $files = [];
    private array $headers = [];
    private string $boundary;

    public function __construct()
    {
        $this->boundary = $this->generateBoundary();
    }

    public function addData(string $name, string $value): self
    {
        $this->data[] = [$name, $value];
        return $this;
    }

    /**
     * @throws FormDataBuilderException
     */
    public function addFile(string $name, string $path): self
    {
        if (!file_exists($path) || is_dir($path)) {
            throw new FormDataBuilderException('File not exists');
        }

        $this->files[] = [$name, $path];
        return $this;
    }

    /**
     * @param string $header one valid http header as a single line string
     * 
     * @throws FormDataBuilderException
     */
    public function addHeader(string $header)
    {
        if(!$header){
            throw new FormDataBuilderException('No header provided');
        }

        $this->headers[] = $header;
    }

    /**
     * @return string
     */
    public function getBoundary(): string
    {
        return $this->boundary;
    }

    public function getContentType(): string
    {
        return 'multipart/form-data; boundary=' . $this->boundary;
    }


    /**
     * @param string $url Url to send the request to
     * 
     * @return string
     * 
     * @throws FormDataBuilderException
     */
    public function send(string $url): string
    {
        $host = parse_url($url,PHP_URL_HOST);
        $path = parse_url($url,PHP_URL_PATH);
        $port = parse_url($url,PHP_URL_PORT);

        if($port != ""){
            $host .= ":" . $port;
        }

        if(!$host || !$path){
            throw new FormDataBuilderException('Invalid url: '.$url);
        }

        $data = $this->build();

        $fp = fsockopen($host, 8888);
        if ($fp) {

            $str = "POST " . $path . " HTTP/1.1\r\n";
            $str .= "Host: " . $host . "\r\n";
            foreach ($this->headers as $header) {
                $str .= $header . "\r\n";
            }
            $str .= "Content-Type: " . $this->getContentType() . "\r\n";
            $str .= "Content-Length: " . strlen($data) . "\r\n";
            $str .= "Connection: close\r\n\r\n";

            $str .= $data;

            $success = fwrite($fp, $str);
            if(!$success){
                throw new FormDataBuilderException('Fail to send the request');
            }

            $result = '';
            while (!feof($fp)) {
                $result .= fgets($fp, 128);
            }
            fclose($fp);

            $response = explode("\r\n\r\n", $result);
            return $response[1];
        }
        else{
            throw new FormDataBuilderException('Unable to open socket to host: '.$host);
        }
    }

    public function build(): string
    {
        $eol = "\r\n";

        $data = "";

        foreach ($this->data as $item) {
            $key = $item[0];
            $value = $item[1];

            $data .= '--' . $this->boundary . $eol . 'Content-Disposition: form-data; name="' . $key . '"'
                . $eol . $eol . $value . $eol;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        foreach ($this->files as $item) {
            $key = $item[0];
            $filePath = $item[1];
            $value = file_get_contents($filePath);
            $fileContentType = finfo_file($finfo, $filePath);

            if(!$fileContentType){
                throw new FormDataBuilderException('Unable to identify the content type of one of the files: '.$filePath);
            }

            $data .= '--' . $this->boundary . $eol . 'Content-Disposition: form-data; name="' . $key . '"; '
                . 'filename="'.basename($filePath).'"' . $eol
                . 'Content-Type: ' . $fileContentType . $eol
                . 'Content-Transfer-Encoding: binary' . $eol . $eol
                . $value . $eol;
        }

        $data .= '--' . $this->boundary . '--';

        return $data;
    }

    private function generateBoundary(): string
    {
        return md5(uniqid(time()));
    }

}
