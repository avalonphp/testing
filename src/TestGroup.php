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

use Avalon\Testing\TestSuite;
use Avalon\Testing\Test;

/**
 * Test Group.
 *
 * @package Avalon\Testing
 * @author  Jack P.
 * @since   1.0.0
 */
class TestGroup
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
     * @var TestSuite
     */
    protected $testSuite;

    /**
     * @var array
     */
    protected $tests = [];

    /**
     * @var array
     */
    protected $errorMessages = [];

    /**
     * @var integer
     */
    protected $testCount = 0;

    /**
     * @var integer
     */
    protected $assertionCount = 0;

    /**
     * @var integer
     */
    protected $failureCount = 0;

    /**
     * @var string   $name
     * @var callable $func
     */
    public function __construct($name, callable $func)
    {
        $this->name = $name;
        $this->func = $func;
    }

    /**
     * @param TestSuite $testSuite
     */
    public function setTestSuite(TestSuite $testSuite)
    {
        $this->testSuite = $testSuite;
    }

    /**
     * @return TestSuite
     */
    public function getTestSuite()
    {
        return $this->testSuite;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return $this->tests;
    }

    /**
     * Run the groups tests.
     */
    public function run()
    {
        $func = $this->func;
        $func($this);

        foreach ($this->tests as $test) {
            if ($this->testSuite->codeCoverageEnabled()) {
                $this->testSuite->getCodeCoverage()->start($this->name . ' / ' . $test->getName());
                $test->run();
                $this->testSuite->getCodeCoverage()->stop();
            } else {
                $test->run();
            }

            $this->mergeErrorMessages($test->getErrorMessages());

            $this->failureCount = $this->failureCount + $test->getFailureCount();
            $this->assertionCount = $this->assertionCount + $test->getAssertionCount();
        }
    }

    /**
     * Create a new test.
     *
     * @param string   $name
     * @param callable $func
     *
     * @return Test
     */
    public function test($name, callable $func)
    {
        $this->testCount = $this->testCount + 1;

        $test = new Test($name, $func);
        $test->setTestGroup($this);
        $this->tests[] = $test;
        return $test;
    }

    /**
     * Merge error messages.
     *
     * @param array $messages
     */
    protected function mergeErrorMessages(array $messages)
    {
        $this->errorMessages = array_merge($this->errorMessages, $messages);
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
    public function getTestCount()
    {
        return $this->testCount;
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
}
