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

namespace Avalon\Testing;

use Avalon\Http\RedirectResponse;
use Avalon\Routing\Router;
use Avalon\Testing\Http\MockRequest;
use Avalon\Testing\Http\Response;

/**
 * Test.
 *
 * @package Avalon\Testing
 * @author  Jack P.
 * @since   1.0.0
 */
class Test
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var callable
     */
    protected $func;

    /**
     * @var TestGroup
     */
    protected $testGroup;

    /**
     * @var array
     */
    protected $errorMessages = [];

    /**
     * @var integer
     */
    protected $failureCount = 0;

    /**
     * @var integer
     */
    protected $assertionCount = 0;

    /**
     * @param string   $name
     * @param callable $func
     */
    public function __construct($name, callable $func)
    {
        $this->name = $name;
        $this->func = $func;
    }

    /**
     * @param TestGroup $testGroup
     */
    public function setTestGroup(TestGroup $testGroup)
    {
        $this->testGroup = $testGroup;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Run the test.
     */
    public function run()
    {
        $func = $this->func;
        $func($this);

        if ($this->failureCount === 0) {
            echo '.';
        } else {
            echo 'F';
        }
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * @return integer
     */
    public function getAssertionCount()
    {
        return $this->assertionCount;
    }

    /**
     * @return integer
     */
    public function getFailureCount()
    {
        return $this->failureCount;
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

    /**
     * @param string $message
     */
    protected function addError()
    {
        $this->incrementFailureCount();
        $this->errorMessages[] = call_user_func_array('sprintf', func_get_args());
    }

    /**
     * @param integer $by
     */
    protected function incrementFailureCount($by = 1)
    {
        $this->failureCount = $this->failureCount + $by;
    }

    /**
     * @param integer $by
     */
    protected function incrementAssertionCount($by = 1)
    {
        $this->assertionCount = $this->assertionCount + $by;
    }

    // -------------------------------------------------------------------------

    /**
     * Visit the route.
     *
     * @param string $routeName
     * @param array  $requestInfo
     *
     * @return Response
     */
    public function visit($routeName, array $requestInfo = [])
    {
        $requestInfo = $requestInfo + ['routeTokens' => []];

        $route = $this->generateUrl($routeName, $requestInfo['routeTokens']);
        $request = new MockRequest($route, $requestInfo);

        return $this->testGroup->getTestSuite()->getApp()->process($request);
    }

    /**
     * @param string $route
     * @param array  $tokens
     *
     * @return string
     */
    public function generateUrl($route, array $tokens = [])
    {
        return Router::generateUrl($route, $tokens);
    }

    // -------------------------------------------------------------------------
    // Assertions

    /**
     * @param mixed $value
     */
    public function assertTrue($value)
    {
        $this->incrementAssertionCount();

        if ($value !== true) {
            $this->addError('expected true but got [%s]', $this->varToString($value));
        }
    }

    /**
     * @param mixed $value
     */
    public function assertFalse($value)
    {
        $this->incrementAssertionCount();

        if ($value !== false) {
            $this->addError('expected false but got [%s]', $this->varToString($value));
        }
    }

    /**
     * @param mixed $expected
     * @param mixed $value
     */
    public function assertEquals($expected, $value)
    {
        $this->incrementAssertionCount();

        if ($expected !== $value) {
            $this->addError(
                'expected [%s] but got [%s]',
                $this->varToString($expected),
                $this->varToString($value)
            );
        }
    }

    /**
     * @param mixed $not
     * @param mixed $value
     */
    public function assertNotEquals($not, $value)
    {
        $this->incrementAssertionCount();

        if ($not === $value) {
            $this->addError(
                'expected [%s] to not be [%s]',
                $this->varToString($value),
                $this->varToString($not)
            );
        }
    }

    /**
     * @param mixed $value
     */
    public function assertArray($value)
    {
        $this->incrementAssertionCount();

        if (!is_array($value)) {
            $this->addError('expected an array but got [%s]', $this->varToString($value));
        }
    }

    /**
     * @param mixed $search
     * @param mixed $value
     */
    public function assertContains($search, $value)
    {
        $this->incrementAssertionCount();

        if (is_string($value)) {
            if (strpos($value, $search) === false) {
                $this->addError(
                    'expected [%s] to contain [%s]',
                    $this->varToString($value),
                    $this->varToString($search)
                );
            }
        }
    }

    /**
     * @param mixed $search
     * @param mixed $value
     */
    public function assertNotContains($search, $value)
    {
        $this->incrementAssertionCount();

        if (is_string($value)) {
            if (strpos($value, $search) !== false) {
                $this->addError(
                    'expected [%s] to not contain [%s]',
                    $this->varToString($value),
                    $this->varToString($search)
                );
            }
        }
    }

    /**
     * @param mixed $expected
     * @param mixed $value
     */
    public function assertInstanceOf($expected, $value)
    {
        $this->incrementAssertionCount();

        if (!($value instanceof $expected)) {
            $this->addError(
                'expected instance of [%s] but was [%s]',
                $this->varToString($search),
                $this->varToString($value)
            );
        }
    }

    /**
     * @param mixed $class
     * @param mixed $value
     */
    public function assertNotInstanceOf($class, $value)
    {
        $this->incrementAssertionCount();

        if ($value instanceof $expected) {
            $this->addError(
                'expected [%s] to not be an instance of [%s]',
                $this->varToString($value),
                $this->varToString($class)
            );
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Check if the response is a redirect to the indented URL.
     *
     * @param string   $expected
     * @param Response $response
     */
    public function shouldRedirectTo($expected, $response)
    {
        $this->incrementAssertionCount();

        if (!($response instanceof RedirectResponse)) {
            $this->addError('expected response to be a redirect');
        } elseif ($response->url !== $intendedUrl) {
            $this->errors[] = sprintf(
                "expected response to redirect to [%s] but was [%s]",
                $intendedUrl,
                $response->url
            );

            $this->addError(
                'expected response to redirect to [%] but was [%s]',
                $this->varToString($expected),
                $this->varToString($response->url)
            );
        }
    }
}
