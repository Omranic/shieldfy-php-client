<?php
namespace Shieldfy\Collectors;

class CodeCollector implements Collectable
{
    protected $code = [];
    protected $filesExceptionsList = [];

    public function __construct(array $fileExceptions = [])
    {
        $this->filesExceptionsList = $fileExceptions;
    }

    public function addFileExceptions($fileName)
    {
        $this->filesExceptionsList[] = $fileName;
    }

    public function collectFromFile($filePath = '', $line = '')
    {
        if ($filePath && file_exists($filePath)) {
            $content = file($filePath);
            array_unshift($content, 'x');
            unset($content[0]);
            $start = $line - 4;
            $content = array_slice($content, $start < 0 ? 0 : $start, 7, true);
        } else {
            $content = array("Cannot open the file ($filePath) in which the vulnerability exists ");
        }

        $this->code = [
            'vulnerability' => 1,
            'file' => $filePath,
            'line' => $line,
            'content'=> $content
        ];
        return $this->code;
    }

    public function collectFromStackTrace($stackString = '')
    {
        $trace = explode("\n", $stackString);
        $trace = array_reverse($trace);
        array_shift($trace); // remove {main}
        array_pop($trace); // remove call to the internal caller
        $shortedStack = '';

        foreach ($trace as $file) {
            foreach ($this->filesExceptionsList as $fileException) {
                if (strpos($file, $fileException) !== false) {
                    continue 2;
                }
            }
            $shortedStack = $file;
        }

        //extract file & number
        if (preg_match('/#[0-9]+\s*([^\s\(]+)\s*\(([0-9]+)\)/U', $shortedStack, $matches)) {
            $this->code = $this->collectFromFile($matches[1], $matches[2]);
        }

        return $this->code;
    }

    public function collectFromText($text = '', $value)
    {
        $content = explode("\n", $text);
        $line = 0;
        $code = [];
        for ($i=0; $i < count($content); $i++) {
            if (stripos($content[$i], $value) !== false) {
                $line = $i;
                $start = $i - 4;
                $code = array_slice($content, $start < 0 ? 0 : $start, 7, true);
                break;
            }
        }

        $this->code = [
            'file' => 'none',
            'line' => $line + 1, //to fix array 0 index
            'content' => $code
        ];
        return $this->code;
    }

    public function getInfo()
    {
        return $this->code;
    }
}
