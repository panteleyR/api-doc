<?php

declare(strict_types=1);

namespace Lilith\ApiDoc;

use Lilith\Common\CaseConverter;
use Lilith\Filesystem\Filesystem;

class Generator
{
    protected array $doc = [];
    protected string $path;
    protected Filesystem $filesystem;

    public function __construct(string $path = '/src/Dto/Api', protected string $namespace = 'App\Dto\Api')
    {
        $this->path = getcwd() . $path;
        $this->filesystem = new Filesystem();
    }

    public function generateApiDto(): void
    {
        $this->clearDir();

        foreach ($this->doc as $dto => $config) {
            $dtoClass = CaseConverter::toPascaleCase($dto) . 'Dto';
            if ($config['request']) {
                if (isset($config['request']['headers'])) {
                    $this->generate('RequestHeaders'.$dtoClass, $config['request']['headers'], $this->namespace, $this->path.'/Headers');
                }

                if (isset($config['request']['body'])) {
                    $this->generate('RequestBody'.$dtoClass, $config['request']['body'], $this->namespace, $this->path.'/Body');
                }

                if (isset($config['request'])) {
                    $requestConfig = array_filter($config['request'], fn ($item) => $item === 'headers' || $item === 'body');
                    $this->generate('Request'.$dtoClass, $requestConfig, $this->namespace, $this->path.'/Body');
                }
//                $this->test($dtoClass, $config['request'], $this->namespace, $this->path.'/Headers');
//                [$requestData, $requestHeadersData, $requestBodyData] = $this->generateDtoFiles($dtoClass, $config['request']);
//                $this->filesystem->filePutContents("$this->path/Headers/RequestHeaders$dtoClass.php", $requestHeadersData);
//                $this->filesystem->filePutContents("$this->path/Body/RequestBody$dtoClass.php", $requestBodyData);
//                $this->filesystem->filePutContents("$this->path/Request$dtoClass.php", $requestData);
            }

            if ($config['response']) {
                foreach ($config['response'] as $statusCode => $responseConfig) {
                    if (isset($responseConfig['headers'])) {
                        $this->generate($statusCode.'ResponseHeaders'.$dtoClass, $responseConfig['headers'], $this->namespace, $this->path.'/Headers');
                    }

                    if (isset($responseConfig['body'])) {
                        $this->generate($statusCode.'ResponseBody'.$dtoClass, $responseConfig['body'], $this->namespace, $this->path.'/Body');
                    }

                    if (isset($responseConfig)) {
                        $this->generate($statusCode.'Response'.$dtoClass, $responseConfig, $this->namespace, $this->path.'/Body');
                    }
//                    [$responseData, $responseHeadersData, $responseBodyData] = $this->generateDtoFiles($dtoClass, $responseConfig[$statusCode]);
//                    $this->filesystem->filePutContents("$this->path/Headers/{$statusCode}ResponseHeaders$dtoClass.php", $responseHeadersData);
//                    $this->filesystem->filePutContents("$this->path/Body/{$statusCode}ResponseBody$dtoClass.php", $responseBodyData);
//                    $this->filesystem->filePutContents("$this->path/{$statusCode}Response$dtoClass.php", $responseData);
                }
            }
        }
    }

//    @TODO реализоват require параметр хз как
    protected function generate(string $dtoClass, array $config, string $namespace, string $path): void
    {
        $properties = [];
        foreach ($config as $name => $value) {
            if (is_string($value)) {
                $properties[$name] = $value;
            } else if (is_array($value)) {
                if (isset($value['type'])) {
                    $properties[$name] = $value['type'];
                } else {
                    $this->generate($dtoClass . ucfirst($name), $value, $namespace . '\Nested', $path . '/nested');
                    $properties[$name] = $namespace . '\Nested\\' . $dtoClass . ucfirst($name);
                }
            }
        }

        $data = $this->generateDtoFile($dtoClass, $namespace, $properties);
        $this->filesystem->filePutContents($path . '/'.$dtoClass.'.php', $data);
    }

    protected function generateDtoFile(string $dtoClass, string $namespace, array $properties): string
    {
        $propStr = '';
        foreach ($properties as $name => $type) {
            $propStr .= 'protected readonly '.$type.' $'.CaseConverter::toCamelCase($name) . ';'.PHP_EOL;
        }

        $str = <<<EOD
        <?php
        
        declare(strict_types=1);

        namespace $namespace;
        
        use Lilith\Common\NestedDto;
        
        class $dtoClass extends NestedDto
        {
            $propStr
        }

        EOD;

        return $str;
    }

//    protected function generateDtoFiles(string $dtoClass, array $config): array
//    {
//        $namespace = $this->namespace;
//
//        $str = <<<EOD
//        <?php
//
//        declare(strict_types=1);
//
//        namespace $namespace;
//
//        use Lilith\Common\NestedDto;
//
//        class $dtoClass extends NestedDto
//        {
//
//        }
//
//        EOD;
//        return [];
//    }

    protected function clearDir(): void
    {
        if ($this->filesystem->isDir($this->path)) {
            $this->filesystem->rm($this->path);
        }

        $this->filesystem->mkdir($this->path);
    }

    public function setDocs(array $doc): void
    {
        $this->doc = $doc;
    }
}
