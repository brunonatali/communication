<?php declare(strict_types=1);

namespace BrunoNatali\Communication;

Class SerialMessageCompiledStructure
{
    Public $to;
    Public $from;
    Public $counter;
    Public $len;

    Public $msg;

    function __construct(int $from, int $to, int $counter, string $data)
    {
        $this->len = \strlen($data);
        // from ; to ; msg counter ; lenght ; data
        $this->msg = ($this->from = $from) . 
            ';' . ($this->to = $to) . 
            ';' . ($this->counter = $counter) . 
            ';' . $this->len . 
            ';' . $data;
    }
}