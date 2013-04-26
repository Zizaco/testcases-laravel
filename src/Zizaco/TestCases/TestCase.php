<?php namespace Zizaco\TestCases;

use Mail;

class TestCase extends \Illuminate\Foundation\Testing\TestCase {

    /**
     * Prepare for tests
     *
     */
    public function setUp()
    {
        parent::setUp();

        $this->prepareForTests();
    }

    /**
     * Creates the application.
     *
     * @return Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $unitTesting = true;

        $testEnvironment = 'testing';

        return require realpath(__DIR__.'/../../../../../../bootstrap/start.php');
    }

    /**
     * Set the mailer to 'pretend'.
     * This will cause the tests to run quickly.
     *
     */
    private function prepareForTests()
    {
        Mail::pretend(true);
    }
}
