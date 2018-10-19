<?php

namespace libs;

class Cli
{
    const MAX_ARGV = 100;
    private $conf = [
        'test' => [
            'hostname' => 'dev.linkhub.dk'
        ],
        'prod' => [
            'hostname' => 'www.linkhub.dk'
        ]
    ];

    public function __construct()
    {
        $args = $this->parseConfigs($_SERVER['argv']);
        if (count($args) < 2) {
            echo "Jasen project v. 1.0\n";
            exit(0);
        }
        switch ($args[1]) {
        }
        die();
        $dns = dns_get_record($this->conf['hostname']);
    }

    private function parseConfigs(&$message = null)
    {
        if (is_string($message)) {
            $argv = explode(' ', $message);
        } elseif (is_array($message)) {
            $argv = $message;
        } else {
            global $argv;
            if (isset($argv) && count($argv) > 1) {
                array_shift($argv);
            }
        }
        $index = 0;
        $configs = array();
        while ($index < self::MAX_ARGV && isset($argv[$index])) {
            if (preg_match('/^([^-\=]+.*)$/', $argv[$index], $matches) === 1) {
                // not have ant -= prefix
                $configs[$matches[1]] = true;
            } elseif (preg_match('/^-+(.+)$/', $argv[$index], $matches) === 1) {
                // match prefix - with next parameter
                if (preg_match('/^-+(.+)\=(.+)$/', $argv[$index], $subMatches) === 1) {
                    $configs[$subMatches[1]] = $subMatches[2];
                } elseif (isset($argv[$index + 1]) && preg_match('/^[^-\=]+$/', $argv[$index + 1]) === 1) {
                    // have sub parameter
                    $configs[$matches[1]] = $argv[$index + 1];
                    $index++;
                } else {
                    $configs[$matches[1]] = true;
                }
            }
            $index++;
        }
        return $configs;
    }
}
