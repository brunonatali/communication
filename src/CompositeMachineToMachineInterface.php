<?php declare(strict_types=1);

namespace BrunoNatali\Communication;

interface CompositeMachineToMachineInterface
{
    Const REQUEST_NOTHING = 0X00;               // Used to test connection
    Const REQUEST_GENERAL = 0X01;               // General request
    Const REQUEST_INFO = 0X02;                  // Request destination information 
    Const REQUEST_FUNCTION = 0X03;              // Request functions suported by think that receive this request

    Const POST_ARRAY_NO_ANSWER = 0x20;          // Inform that data will be posted as array and no answer are expected
    Const POST_ARRAY_WITH_ANSWER = 0x21;        // Inform that data will be posted as array and wait for confirmation
    Const POST_FILE_NO_ANSWER = 0x22;           // Transfering file, but not wait for an answer (just ACK)
    Const POST_FILE_WITH_ANSWER = 0x23;         // Transfering file and wait for answer

    Const COMMUNICATION_M2M_TYPE_UNKNOW = 0x40; // No type specified
    Const COMMUNICATION_M2M_TYPE_ANSWER = 0x41; // Answer package type
    Const COMMUNICATION_M2M_TYPE_RESPONSE = 0x42; // Response package type

    Const FLAG_KEEP_ALIVE = 0x61;               // Set needs of stil connection kept alive (used to wait an answer on request received)
    Const FLAG_ACK = 0x62;                      // Set an ACK - Confirm  
    Const FLAG_NACK = 0x63;                     // Set an NACK - Inform error
    Const FLAG_HASH = 0x64;                     // Hasshing data. In case of a zip, zip data will be hashed too
    Const FLAG_ZIP = 0x65;                      // Compress data using gzip
    Const FLAG_DECODE = 0x66;                   // Set to be decoded

    Const MAX_FILE_SIZE_2_TRANSMIT_10K = 0xA0;  // Set maximum file size to be readed and transmited to 10.000 Kb
    Const MAX_FILE_SIZE_2_TRANSMIT_100K = 0xA1; // Set maximum file size to be readed and transmited to 100.000 Kb
    Const MAX_FILE_SIZE_2_TRANSMIT_500K = 0xA2; // Set maximum file size to be readed and transmited to 500.000 Kb
    Const MAX_FILE_SIZE_2_TRANSMIT_1M = 0xA3;   // Set maximum file size to be readed and transmited to 1.000.000 Kb
}