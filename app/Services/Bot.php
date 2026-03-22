<?php
namespace Services;

class Bot
{
    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function request($method, $data = [])
    {
        $ch = curl_init("https://api.telegram.org/bot{$this->token}/{$method}");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}
