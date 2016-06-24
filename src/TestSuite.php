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

use ReflectionObject;
use Avalon\AppKernel;
use Avalon\Database\ConnectionManager;
use Avalon\Database\Migrator;
use Avalon\Testing\Http\MockRequest;
use Avalon\Http\Request;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as CodeCoverageHtmlFacade;

/**
 * Test Suite.
 *
 * @author Jack P.
 */
class TestSuite
{
    /**
     * Instantiated TestSuite object.
     *
     * @var TestSuite
     */
    protected static $testSuite;

    /**
     * @var AppKernel
     */
    protected static $app;

    /**
     * @var Group[]
     */
    protected static $groups = [];

    /**
     * @var string
     */
    protected $appClass;

    /**
     * @var string
     */
    protected $appPath;

    /**
     * @var array
     */
    protected $appConfig;

    /**
     * @var object
     */
    protected $db;

    /**
     * @var Migrator
     */
    protected $migrator;

    /**
     * @var object
     */
    protected $seeder;

    /**
     * Test count.
     *
     * @var integer
     */
    protected static $testCount = 0;

    /**
     * Error count.
     *
     * @var integer
     */
    protected static $errorCount = 0;

    /**
     * Whether or not code coverage is enabled.
     *
     * @var boolean
     */
    protected static $enableCodeCoverage = false;

    /**
     * The directory to output the code coverage report.
     *
     * @var string
     */
    protected static $phpCodeCoverageOutputDirectory;

    /**
     * The PHP CodeCoverage instance.
     *
     * @var CodeCoverage
     */
    protected static $phpCodeCoverageInstance;

    protected function __construct()
    {
        global $argv;

        $codeCoverageKey = array_search('--code-coverage', $argv);

        if ($codeCoverageKey) {
            $codeCoverageDirectoryKey = $codeCoverageKey + 1;
            $codeCoverageOutputDirectory = isset($argv[$codeCoverageDirectoryKey])
                                           ? $argv[$codeCoverageDirectoryKey]
                                           : 'tmp/code-coverage-report';

            if (file_exists($codeCoverageOutputDirectory) || is_dir($codeCoverageOutputDirectory)) {
                die('Code coverage output directory already exists' . PHP_EOL);
            }

            static::$phpCodeCoverageOutputDirectory = $codeCoverageOutputDirectory;
            static::enableCodeCoverage();
        }
    }

    /**
     * Configure the test suite.
     *
     * @param callable $block
     */
    public static function configure(callable $block)
    {
        $testing = new static;

        $block($testing);

        $testing->setup();
    }

    /**
     * Set application class name.
     *
     * @param string
     */
    public function setAppClass($appClass)
    {
        $this->appClass = $appClass;
    }

    /**
     * Set application path.
     *
     * @param string
     */
    public function setAppPath($path)
    {
        $this->appPath = $path;
    }

    /**
     * Set application configuration array.
     *
     * @param array
     */
    public function setAppConfig(array $config)
    {
        $this->appConfig = $config;
    }

    /**
     * Set database seeder object.
     *
     * @param object
     */
    public function setSeeder($seeder)
    {
        $this->seeder = $seeder;
    }

    /**
     * Setup the test suite.
     */
    public function setup()
    {
        // Set environment and HTTP host
        $_ENV['environment'] = 'test';
        $_SERVER['HTTP_HOST'] = 'localhost';

        // Get configuration
        if ($this->appConfig === null) {
            $this->appConfig = require "{$this->appPath}/../config/config.php";
        }

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
        $this->seeder->seed();

        // Setup mock request
        new MockRequest;

        // Set app
        $appClass = $this->appClass;
        static::setApp(new $appClass);
    }

    /**
     * Set application.
     *
     * @param AppKernel $app
     */
    public static function setApp(AppKernel $app)
    {
        static::$app = $app;
    }

    /**
     * Get the app.
     *
     * @return AppKernel
     */
    public static function app()
    {
        return static::$app;
    }

    /**
     * Create tests.
     *
     * @param callable $block
     */
    public static function tests(callable $block)
    {
        if (!static::$testSuite) {
            static::$testSuite = new static;
        }

        $block(static::$testSuite);

        return static::$testSuite;
    }

    /**
     * New test group.
     *
     * @param string   $name
     * @param callable $block
     */
    public static function group($name, callable $block)
    {
        static::$groups[] = new TestGroup($name, $block);
    }

    /**
     * Execute groups.
     */
    public static function run()
    {
        foreach (static::$groups as $group) {
            $group->execute();
            static::$testCount += $group->getTestCount();
            static::$errorCount += $group->getErrorCount();
        }

        echo PHP_EOL;

        foreach (static::$groups as $group) {
            $group->display();
        }

        echo PHP_EOL;

        printf('Completed %d tests with %d errors' . PHP_EOL, static::$testCount, static::$errorCount);

        if (static::$enableCodeCoverage) {
            echo PHP_EOL . 'Generating code coverage report..' . PHP_EOL;
            $writer = new CodeCoverageHtmlFacade;
            $writer->process(static::$phpCodeCoverageInstance, static::$phpCodeCoverageOutputDirectory);
        }

        exit(static::$errorCount ? 1 : 0);
    }

    /**
     * Enable code coverage.
     */
    public static function enableCodeCoverage()
    {
        if (class_exists('SebastianBergmann\CodeCoverage\CodeCoverage')) {
            static::$phpCodeCoverageInstance = new CodeCoverage;
            static::$enableCodeCoverage = true;
        } else {
            echo PHP_EOL . 'Unable to enable code coverage, PHPCodeCoverage not found.' . PHP_EOL;
            exit;
        }
    }

    /**
     * @return boolean
     */
    public static function isCodeCoverageEnabled()
    {
        return static::$enableCodeCoverage;
    }

    /**
     * @return CodeCoverage
     */
    public static function getCodeCoverage()
    {
        return static::$phpCodeCoverageInstance;
    }
}
