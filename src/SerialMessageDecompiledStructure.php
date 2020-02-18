<?php declare(strict_types=1);

namespace BrunoNatali\Communication;

Class SerialMessageDecompiledStructure
{
    Public $to = null;
    Public $from = null;
    Public $counter = null;
    Public $len = null;

    Public $msg = null;

    function __construct(string $data)
    {
        $decomposed = \explode(';', $data, 5);

        if (count($decomposed) === 5) {
            $this->from = \intval($decomposed[0]);
            $this->to = \intval($decomposed[1]);
            $this->counter = \intval($decomposed[2]);
            $this->len = \intval($decomposed[3]);
            $this->msg = $this->len !== 0 ? $decomposed[4] : null; // need to check msg declared len and data current len
        } else {
            throw new \Exception("Invalid format", 1);
        }
    }
}