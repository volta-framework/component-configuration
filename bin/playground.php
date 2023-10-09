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

use Volta\Component\Configuration\Config;

require __DIR__ . '/../vendor/autoload.php';

$c = [
    'volta' => [
        'component' => [
            'books' => [

                'supportedResources' => [
                    // textual files
                    'html' => 'text/html',
                    'htm' => 'text/html',
                    'txt' => 'text/plain',
                    'css' => 'text/css',
                    'js' => 'text/javascript',

                    // video's
                    'avi' => 'video/x-msvideo',
                    'mpeg' => 'video/mpeg',
                    'mp4' => 'video/mp4',
                    'mov' => 'video/quicktime',

                    // images
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    'bmp' => 'image/bmp',
                    'gif' => 'image/gif',
                    'ico' => 'image/vnd.microsoft.icon',
                    'jpeg'=> 'image/jpeg',
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                ],


                'slibrary' => [
                    'een' => __DIR__ . '/../resources/ExampleBook',
                    'twee' => __DIR__ . '/../resources/ExampleBook',
                ],

                'stylesheet' => '',
                'template' => __DIR__ . '/../templates/web-book.phtml'
            ]
        ]
    ],

];

$_config = new Config();
$_config->setRequiredOptions(['volta.component.books.library']);

//
//            echo '<pre>';
//            $c = include  __DIR__ . '/../config/config.php';
//
//            print_r($c['volta']['component']['books']['library']);
//            exit(__FILE__);

    $_config->setOptions($c);

    print_r($_config->listOptions());