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

/**
 * Test Group.
 *
 * @author Jack P.
 */
class TestGroup
{
    /**
     * Group name.
     *
     * @var string
     */
    protected $name;

    /**
     * @var Test[]
     */
    protected $tests = [];

    /**
     * @var string[]
     */
    protected $messages = [];

    /**
     * @param string   $name Group name.
     * @param callable $block
     */
    public function __construct($name, $block)
    {
        $this->name  = $name;
        $this->block = $block;
    }

    /**
     * Add test.
     *
     * @param string   $name Test name.
     * @param callable $block
     */
    public function test($name, $block)
    {
        $this->tests[] = new Test($name, $block);
    }

    /**
     * Execute tests.
     *
     * @return Group
     */
    public function execute()
    {
        $block = $this->block;
        $block($this);

        foreach ($this->tests as $test) {
            if (!$test->execute()) {
                echo 'F';
                $this->messages[] = $test->output();
            } else {
                echo '.';
            }
        }

        // echo PHP_EOL;

        return $this;
    }

    /**
     * Display test messages.
     */
    public function display()
    {
        if (!count($this->messages)) {
            return;
        }

        echo PHP_EOL . $this->name . PHP_EOL;
        foreach ($this->messages as $message) {
            echo " - {$message}" . PHP_EOL;
        }
    }
}
