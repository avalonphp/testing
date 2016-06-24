<?php
/*!
 * Avalon
 * Copyright 2011-2016 Jack P.
 * https://github.com/avalonphp
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Avalon\Testing\Http;

use Avalon\Http\Request;

/**
 * Mock request for testing purposes.
 */
class MockRequest// extends Request
{
    public function __construct($path = '/', array $requestInfo = [])
    {
        $requestInfo = $requestInfo + [
            'method' => "GET",
            'post'   => [],
            'get'    => [],
            'cookie' => []
        ];

        $_SERVER['HTTP_HOST']      = "localhost";
        $_SERVER['REQUEST_METHOD'] = $requestInfo['method'];
        $_SERVER['REQUEST_URI']    = $path;
        $_SERVER['QUERY_STRING']   = '';

        $_POST = $requestInfo['post'];
        $_GET  = $requestInfo['get'];
        $_REQUEST = array_merge($_GET, $_POST);
        $_COOKIE = $requestInfo['cookie'];

        Request::reset();
        Request::init();
    }
}
