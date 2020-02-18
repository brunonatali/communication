<?php declare(strict_types=1);

namespace BrunoNatali\Communication;

Use React\EventLoop\LoopInterface;
Use React\Filesystem\Node\FileInterface; 
Use React\Stream\ReadableStreamInterface;

class CompositeMachineToMachine implements CompositeMachineToMachineInterface
{
    Protected $messageType = self::COMMUNICATION_M2M_TYPE_UNKNOW; // Store if message is a request or a response
    Protected $connectionKeepAlive = false; // Indicate if communication connection need to be kept opened 
    Protected $maxFileSizeToTransmit = 0;
    Protected $error;
    Private $uploadedFileSize = 0;

    Private $constFunctions = array(
        self::POST_FILE_WITH_ANSWER => = function ($functions, $objects, $array, $string, &$composedArray) {
            if (isset($objects['file'])) {
                $arrayPostFile = [
                    'fileInfo' => [
                        'originalSize' => null,
                        'accessTime' => null,
                        'creationTime' => null,
                        'modifiedTime' => null
                    ],
                    'thisSize' => null
                ];

                $objects['file']->time()->then(function ($times) {
                    $arrayPostFile['fileInfo']['accessTime'] = $times['atime']->format('r');
                    $arrayPostFile['fileInfo']['creationTime'] = $times['ctime']->format('r');
                    $arrayPostFile['fileInfo']['modifiedTime'] = $times['mtime']->format('r');
                }, function ($e) {
                    throw new \Exception("Error Colecting file time, " . $e->getMessage());
                });
                $objects['file']->size()->then(function ($size) use ($arrayPostFile) {
                    $arrayPostFile['fileInfo']['originalSize'] = $size;
                }, function ($e) {
                    throw new \Exception("Error Colecting file size, " . $e->getMessage());
                });

                if ($this->maxFileSizeToTransmit && 
                    $this->maxFileSizeToTransmit < $arrayPostFile['fileInfo']['originalSize']
                ) {
                    $objects['file']->open('r')->then(function (ReadableStreamInterface $stream) use ($objects, $arrayPostFile) {
                        $fileDescriptor = $stream->getFiledescriptor();
                        $loop->addPeriodicTimer(1, function () use ($objects, $fileDescriptor, $arrayPostFile) {
                            $objects['file']->adapter->read(
                                $fileDescriptor, 
                                $this->uploadedFileSize + $this->maxFileSizeToTransmit, 
                                ($this->uploadedFileSize ? $this->uploadedFileSize : 0)
                                )->then(function ($content) use ($arrayPostFile) {
                                    $arrayPostFile['data'] = $content;
                                    $arrayPostFile['thisSize'] = strlen($content);
                                }, function ($e) {
                                    throw new \Exception("Error get partial content A, " . $e->getMessage());
                                }
                            );
                        });
                    }, function ($e) {
                        throw new \Exception("Error get partial content B, " . $e->getMessage());
                    });
                } else {
                    $objects['file']->getContents()->then(function ($content) use ($arrayPostFile) {
                        $arrayPostFile['data'] = $content;
                        $arrayPostFile['thisSize'] = strlen($content);
                    }, function ($e) {
                        throw new \Exception("Error get all content, " . $e->getMessage());
                    });
                }

                $this->messageType = self::COMMUNICATION_M2M_TYPE_ANSWER;
                $composedArray += $arrayPostFile;
            }
        },
        self::FLAG_HASH => = function ($functions, $objects, $array, $string, &$composedArray) {
            if (isset($functions[self::FLAG_DECODE])) { // Check hash
                if (isset($composedArray['hash'])) 
                    if ($composedArray['hash'] !== hash('md5', $composedArray['data'])) 
                        return false;
            } else { // Create hash
                if (isset($composedArray['data'])) 
                    $composedArray['hash'] = hash('md5', $composedArray['data']);
            }
            return true;
        },
        self::FLAG_ZIP => = function ($functions, $objects, $array, $string, &$composedArray) {
            if (isset($functions[self::FLAG_DECODE])) { // Decode
                if (isset($functions[self::FLAG_HASH])) 
                    if (isset($composedArray['data']) &&
                        $functions[self::FLAG_HASH]($functions, $objects, $array, $string, $composedArray['data'])) 
                        $composedArray['data'] = bzdecompress(base64_decode($composedArray['data']))
            } else  { // Encode
                if (isset($composedArray['data'])) 
                    $composedArray['data'] = base64_encode(bzcompress($composedArray['data'], 9));
                if (isset($functions[self::FLAG_HASH])) 
                    $functions[self::FLAG_HASH]($functions, $objects, $array, $string, $composedArray['data']);
            }
        },
        self::FLAG_DECODE => = function ($functions, $objects, $array, $string, &$composedArray) {
            if (isset($composedArray['data'])) {
                if (isset($functions[self::FLAG_HASH]) &&
                    $functions[self::FLAG_HASH]($functions, $objects, $array, $string, $composedArray['data']))

            }
        }
    );

    Public function build(...$args) {
        $composedArray = [];
        $functions = [];
        $objects = [];
        $array = [];
        $string = null;
        foreach ($args as $value) {
            if (is_int($value)) {
                if ($value >= 0xA0 && $value <= 0xAF) {
                    if ($value === self::MAX_FILE_SIZE_2_TRANSMIT_10K)
                        $this->maxFileSizeToTransmit = 10000;
                    else if ($value === self::MAX_FILE_SIZE_2_TRANSMIT_100K)    
                        $this->maxFileSizeToTransmit = 100000;
                    else if ($value === self::MAX_FILE_SIZE_2_TRANSMIT_500K)    
                        $this->maxFileSizeToTransmit = 500000;
                    else if ($value === self::MAX_FILE_SIZE_2_TRANSMIT_1M)    
                        $this->maxFileSizeToTransmit = 1000000;
                } else {
                    $functions[] = $value;
                }
            }
            else if (is_string($value))$string = $value;
            else if (is_array($value))$array = $value;
            else if (is_object($value)) {
                if ($value instanceof LoopInterface) $objects['loop'] = $value;
                else if ($value instanceof FileInterface) $objects['file'] = $value;
            }
        }

        foreach (sort($functions) as $function) {
            if (isset($constFunctions[$function])) {
                $composedResult = $constFunctions[$function](
                    $functions, 
                    $objects,
                    $array,
                    $string,
                    $composedArray
                );
                if (is_array($composedResult)) $composedArray += $composedResult;
            }
        }
    }

    Public function buildBody($data, int $type = self::REQUEST_NOTHING): int
    {
        if (count($data)) {
            if ($type === self::REQUEST_NOTHING && $this->requestType === self::REQUEST_NOTHING) {
                $this->errorCode = self::ERROR_REQUEST_NOTHING_WITH_DATA;
                return self::ERROR_REQUEST_NOTHING_WITH_DATA;
            }

            $jsonData = json_encode($data);
            if ($jsonData === false || !is_string($jsonData)) {
                $this->errorCode = self::ERROR_ARRAY_TO_JSON;
                return self::ERROR_ARRAY_TO_JSON;
            }

            $data = bzcompress($jsonData, 9);
            $data = base64_encode($data);
        } else {
            $jsonData = null;
            if ($type !== self::REQUEST_NOTHING || $this->requestType !== self::REQUEST_NOTHING) {
                $this->errorCode = self::ERROR_NOT_REQUEST_NOTHING_WITHOUT_DATA;
                return self::ERROR_NOT_REQUEST_NOTHING_WITHOUT_DATA;
            }
        }

        $this->jsonBody = json_encode([
            'type' => ($type ? $type : ($this->requestType ? $this->requestType : self::REQUEST_NOTHING)),
            'data' => $data,
            'hash' => ($jsonData ? hash('md5', $jsonData) : '')
        ]);
        if ($this->jsonBody === false || !is_string($this->jsonBody)) {
            $this->jsonBody = null;
            $this->errorCode = self::ERROR_ARRAY_TO_JSON;
            return self::ERROR_ARRAY_TO_JSON;
        }

        // Free system memmory ASAP
        unset($jsonData);

        $this->errorCode = self::ERROR_OK;
        return self::ERROR_OK;
    }

    Public function readBody(&$data, &$type = self::REQUEST_NOTHING): int
    {
        if (!is_string($data)) return self::ERROR_REQUEST_DECOMPOSE_INPUT;

        $jsonArray = json_decode($data, true);
        if($jsonArray === false || !is_array($jsonArray)) return self::ERROR_JSON_TO_ARRAY;

        if ($jsonArray['type'] !== self::REQUEST_NOTHING) {
            $dataArray = base64_decode($jsonArray['data']);
            $dataArray = bzdecompress($dataArray);

            if (hash('md5', $dataArray) != $jsonArray['hash']) return self::ERROR_REQUEST_HASH;
            $data = $dataArray;
            unset($dataArray); // Free sys memmory ASAP
        } else {
            $data = [];
        }
        $type = $jsonArray['type'];
        return self::ERROR_OK;
    }
}