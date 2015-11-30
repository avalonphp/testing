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

namespace Avalon\Testing;

use Avalon\Routing\Router;
use Avalon\Testing\TestCase;
use Avalon\Testing\Http\MockRequest;
use Avalon\Testing\Http\Response;

/**
 * Test.
 *
 * @author Jack P.
 */
class Test
{
    /**
     * Test name.
     *
     * @var string
     */
    protected $name;

    /**
     * Test errors.
     *
     * @var string[]
     */
    protected $errors = [];

    /**
     * @param string   $name Test name
     * @param callable
     */
    public function __construct($name, $block)
    {
        $this->name  = $name;
        $this->block = $block;
    }

    /**
     * Execute test assertions.
     */
    public function execute()
    {
        $block = $this->block;
        $block($this);
        return count($this->errors) ? false : true;
    }

    /**
     * @return string[]
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function output()
    {
        return "{$this->name}: {$this->errors[0]}";
    }

    /**
     * @param string|object $expected
     * @param object        $class
     */
    public function assertInstanceOf($expected, $class)
    {
        if (!$class instanceof $expected) {
            $expected = $this->varToString($expected);
            $class    = $this->varToString($class);

            $this->errors[] = sprintf("expected [%s] but got [%s]", $expected, $class);
        }
    }

    /**
     * @param mixed $expected
     * @param mixed $value
     */
    public function assertEqual($expected, $value)
    {
        if ($expected != $value) {
            $this->errors[] = sprintf(
                "expected [%s] got [%s]",
                $this->varToString($expected),
                $this->varToString($value)
            );
        }
    }

    /**
     * @param bool $value
     */
    public function assertTrue($value)
    {
        if (!$value === true) {
            $this->errors[] = sprintf("expected value to be true, was [%s]", $this->varToString($value));
        }
    }

    /**
     * @param bool $value
     */
    public function assertFalse($value)
    {
        if (!$value === false) {
            $this->errors[] = sprintf("expected value to be false, was [%s]", $this->varToString($value));
        }
    }

    /**
     * @param mixed  $haystack
     * @param string $search
     */
    public function shouldContain($haystack, $search)
    {
        if (is_object($haystack)) {
            $haystack = $haystack->toString();
        }

        if (strpos($haystack, $search) === false) {
            $this->errors[] = sprintf("expected value to be contain [%s]", $this->varToString($search));
        }
    }

    /**
     * Check if the response redirection URL matches the specified URL.
     *
     * @param Response $response
     * @param string   $intendedUrl
     *
     * @return boolean
     */
    public function shouldRedirectTo($response, $intendedUrl)
    {
        $response = $response->getResponse();

        if (!($response instanceof RedirectResponse)) {
            $this->errors[] = sprintf("expected response to be a redirect");
        } elseif ($response->url !== $intendedUrl) {
            $this->errors[] = sprintf(
                "expected response to redirect to [%s] but was [%s]",
                $intendedUrl,
                $response->url
            );
        }
    }

    /**
     * Visit the route.
     *
     * @return Response
     */
    public function visit($routeName, array $requestInfo = [])
    {
        $requestInfo = $requestInfo + ['routeTokens' => []];

        $route = Router::generateUrl($routeName, $requestInfo['routeTokens']);
        $request = new MockRequest($route, $requestInfo);

        return new Response(TestSuite::app()->run($request));
    }

    /**
     * @param mixed $var
     */
    protected function varToString($var)
    {
        if (is_string($var) || is_numeric($var)) {
            return $var;
        } elseif (is_bool($var)) {
            return $var ? 'true' : 'false';
        } elseif (is_object($var)) {
            return get_class($var);
        }
    }
}
