<?php
/*
 * This file is part of the Volta package.
 *
 * (c) Rob Demmenie <rob@volta-framework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

header('Content-Type: text/plain');

use Volta\Component\Configuration\Config;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $configArrayData = [
        "part1" => [
            "part2" => [
                "part3" => "value1"
            ]
        ]
    ];
    $configObj = new Config($configArrayData);
    print_r($configObj);


    $configJsonData = [
        "part1" => [
            "part2" => [
                "part3" => "value1"
            ]
        ]
    ];
    $configObj = new Config($configArrayData);
    print_r($configObj);


} catch (\Volta\Component\Configuration\Exception $e) {
    print_r($e);
}