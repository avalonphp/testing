<?php
/*!
 * Avalon
 * Copyright 2011-2015 Jack P.
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
use Avalon\Routing\Router;
use Avalon\Http\RedirectResponse;

/**
 * Test a response for stuff.
 */
class Response
{
    /**
     * @var \Avalon\Http\Response
     */
    protected $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * Check if the response body contains the specified string.
     *
     * @var string
     *
     * @return boolean
     */
    public function contains($contains)
    {
        return strpos($this->response->body, $contains) !== false;
    }

    /**
     * Check if the response redirection URL matches the URL of the specified route.
     *
     * @var string
     *
     * @return boolean
     */
    public function shouldRedirectTo($routeName)
    {
        $url = Request::basePath(Router::generateUrl($routeName, false));
        return ($this->response instanceof RedirectResponse && $this->response->url == $url);
    }

    /**
     * Converts response to string.
     *
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * Converts response to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->response->body;
    }
}
