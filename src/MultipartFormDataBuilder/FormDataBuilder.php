<?php

namespace MultilineAmio\MultipartFormDataBuilder;

class FormDataBuilder
{

    private array $data = [];
    private array $files = [];
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
