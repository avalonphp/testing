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

use ReflectionClass;
use Avalon\AppKernel;
use Avalon\Http\Request;
use Avalon\Testing\Http\MockRequest;
use Avalon\Database\ConnectionManager;
use Avalon\Database\Migrator;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as CodeCoverageHtmlFacade;

/**
 * Test Suite.
 *
 * @package Avalon\Testing
 * @author  Jack P.
 * @since   1.0.0
 */
class TestSuite
{
    /**
     * Whether or not the test suite has been setup.
     *
     * @var boolean
     */
    protected $isSetup = false;

    /**
     * @var Group[]
     */
    protected $groups = [];

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
     * @var string
     */
    protected $appClass;

    /**
     * @var AppKernel
     */
    protected $app;

    /**
     * @var object
     */
    protected $appSeederClass;

    /**
     * @var array
     */
    protected $appConfig;

    /**
     * Database seeder.
     *
     * @var object
     */
    protected $seeder;

    /**
     * @var CodeCoverage
     */
    protected $codeCoverage;

    /**
     * Whether or not if code coverage is enabled.
     *
     * @var boolean
     */
    protected $codeCoverageEnabled = false;

    /**
     * Directory to output code coverage report.
     *
     * @var string
     */
    protected $coverageOutputDirectory;

    /**
     * @param string $appClass
     * @param string $seederClass
     * @param arrray $config
     */
    public function __construct($appClass, $seederClass, $config)
    {
        global $argv;

        $this->appClass = $appClass;
        $this->appSeederClass = $seederClass;
        $this->appConfig = $config;

        $codeCoverageKey = array_search('--code-coverage', $argv);

        if ($codeCoverageKey) {
            $coverageDirectoryKey = $codeCoverageKey + 1;
            $coverageOutputDirectory = isset($argv[$coverageDirectoryKey])
                                       ? $argv[$coverageDirectoryKey]
                                       : 'tmp/code-coverage-report';

            // if (file_exists($coverageOutputDirectory) || is_dir($coverageOutputDirectory)) {
            //     echo 'Code coverage output directory already exists', PHP_EOL;
            //     exit(1);
            // }

            $this->codeCoverageEnabled = true;
            $this->coverageOutputDirectory = $coverageOutputDirectory;
            $this->codeCoverage = new CodeCoverage;
        }
    }

    /**
     * @return AppKernel
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Run test suite.
     */
    public function run()
    {
        if (!$this->isSetup) {
            $this->setup();
        }

        echo 'Avalon Test Suite by Jack P.', PHP_EOL, PHP_EOL;

        foreach ($this->groups as $group) {
            $group->run();

            $this->testCount = $this->testCount + $group->getTestCount();
            $this->assertionCount = $this->assertionCount + $group->getAssertionCount();
            $this->failureCount = $this->failureCount + $group->getFailureCount();
        }

        echo PHP_EOL;

        if ($this->failureCount) {
            echo PHP_EOL;

            foreach ($this->groups as $group) {
                if ($group->getFailureCount() > 0) {
                    echo $group->getName() . PHP_EOL;

                    foreach ($group->getTests() as $test) {
                        if ($test->getFailureCount()) {
                            echo '  - ', $test->getName(), PHP_EOL;

                            foreach ($test->getErrorMessages() as $message) {
                                echo '      - ', $message, PHP_EOL;
                            }
                        }
                    }
                }
            }
        }

        printf(
            PHP_EOL . 'Ran %d tests with %d assertions and %d failures' . PHP_EOL,
            $this->testCount,
            $this->assertionCount,
            $this->failureCount
        );

        if ($this->codeCoverageEnabled) {
            echo PHP_EOL . 'Generating code coverage report..' . PHP_EOL;
            $writer = new CodeCoverageHtmlFacade;
            $writer->process($this->codeCoverage, $this->coverageOutputDirectory);
        }

        exit($this->failureCount > 0 ? 1 : 0);
    }

    /**
     * @return boolean
     */
    public function codeCoverageEnabled()
    {
        return $this->codeCoverageEnabled;
    }

    /**
     * @return CodeCoverage
     */
    public function getCodeCoverage()
    {
        return $this->codeCoverage;
    }

    /**
     * Create test group.
     *
     * @param string   $name
     * @param callable $func
     *
     * @return TestGroup
     */
    public function createGroup($name, callable $func)
    {
        $group = new TestGroup($name, $func);
        $group->setTestSuite($this);
        $this->groups[] = $group;
        return $group;
    }

    /**
     * Setup the test suite.
     */
    public function setup()
    {
        if ($this->isSetup) {
            return;
        }

        $appReflection = new ReflectionClass($this->appClass);
        $this->appPath = dirname($appReflection->getFileName());

        // Set environment and HTTP host
        $_ENV['environment'] = 'test';
        $_SERVER['HTTP_HOST'] = 'localhost';

        // Connect to the database
        $this->db = ConnectionManager::create($this->appConfig['db']['test']);

        // Drop tables
        $this->sm = ConnectionManager::getConnection()->getSchemaManager();
        foreach ($this->sm->listTables() as $table) {
            $this->db->query("DROP TABLE {$table->getName()}");
        }

        // Migrate
        $this->migrator = new Migrator;
        $this->migrator->loadMigrations("{$this->appPath}/Database/Migrations");
        $this->migrator->migrate();

        // Seed
        $seederClass = $this->appSeederClass;
        $seeder = new $seederClass;
        $seeder->seed();

        // Set app
        $appClass = $this->appClass;

        if ($this->codeCoverageEnabled) {
            $this->codeCoverage->start('TestSuite setup');
            $this->app = new $appClass;
            $this->codeCoverage->stop();
        } else {
            $this->app = new $appClass;
        }

        new MockRequest;
        Request::init();
    }
}
