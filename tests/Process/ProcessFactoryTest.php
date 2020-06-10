<?php

namespace Liuggio\Fastest\Process;

use PHPUnit\Framework\TestCase;

class ProcessFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function shouldCreateACommandUsingParallelTests()
    {
        $factory = new ProcessFactory(10);
        $process = $factory->createAProcess('fileA', 2, 10, true);
        $serverEnvs = EnvCommandCreator::cleanEnvVariables($_SERVER);
        unset($serverEnvs['argv']);

        $this->assertEquals("'bin".DIRECTORY_SEPARATOR."phpunit' 'fileA'", $process->getCommandLine());
        $this->assertEquals(
            $this->castValues(array_change_key_case($serverEnvs + $_ENV + [
                'ENV_TEST_CHANNEL' => 2,
                'ENV_TEST_CHANNEL_READABLE' => 'test_2',
                'ENV_TEST_CHANNELS_NUMBER' => 10,
                'ENV_TEST_ARGUMENT' => 'fileA',
                'ENV_TEST_INC_NUMBER' => 10,
                'ENV_TEST_IS_FIRST_ON_CHANNEL' => 1,
            ], CASE_UPPER)),
            $this->castValues($process->getenv())
        );
    }

    /**
     * @test
     */
    public function shouldCreateACommandUsingParallelTestsWithFilteredVariables()
    {
        $factory = new ProcessFactory(10);
        $process = $factory->createAProcess('fileA', 2, 10, true);

        $this->assertEquals("'bin".DIRECTORY_SEPARATOR."phpunit' 'fileA'", $process->getCommandLine());

        $processEnv = $process->getEnv();
        $envTestVars = $this->filterEnvTestVariables($processEnv);

        $this->assertEquals([
            'ENV_TEST_CHANNEL' => 2,
            'ENV_TEST_CHANNEL_READABLE' => 'test_2',
            'ENV_TEST_CHANNELS_NUMBER' => 10,
            'ENV_TEST_ARGUMENT' => 'fileA',
            'ENV_TEST_INC_NUMBER' => 10,
            'ENV_TEST_IS_FIRST_ON_CHANNEL' => 1,
        ], $envTestVars);
    }

    /**
     * @test
     */
    public function shouldCreateACommandUsingParallelTestsWithOptions()
    {
        $factory = new ProcessFactory(11, 'execute');
        $process = $factory->createAProcess('fileA', 2, 12, false);
        $serverEnvs = EnvCommandCreator::cleanEnvVariables($_SERVER);
        unset($serverEnvs['argv']);

        $this->assertEquals("'execute'", $process->getCommandLine());
        $this->assertEquals(
            $this->castValues(array_change_key_case($serverEnvs + $_ENV + [
                'ENV_TEST_CHANNEL' => 2,
                'ENV_TEST_CHANNEL_READABLE' => 'test_2',
                'ENV_TEST_CHANNELS_NUMBER' => 11,
                'ENV_TEST_ARGUMENT' => 'fileA',
                'ENV_TEST_INC_NUMBER' => 12,
                'ENV_TEST_IS_FIRST_ON_CHANNEL' => 0,
            ], CASE_UPPER)),
            $this->castValues($process->getenv())
        );
    }

    /**
     * @test
     */
    public function shouldReplaceThePlaceholder()
    {
        $factory = new ProcessFactory(12, 'execute {p} {} {n}');
        $process = $factory->createAProcess('fileA', 1, 13, true);
        $serverEnvs = EnvCommandCreator::cleanEnvVariables($_SERVER);
        unset($serverEnvs['argv']);

        $this->assertEquals("'execute' '1' 'fileA' '13'", $process->getCommandLine());
        $this->assertEquals(
            $this->castValues(array_change_key_case($serverEnvs + $_ENV + [
                'ENV_TEST_CHANNEL' => 1,
                'ENV_TEST_CHANNEL_READABLE' => 'test_1',
                'ENV_TEST_CHANNELS_NUMBER' => 12,
                'ENV_TEST_ARGUMENT' => 'fileA',
                'ENV_TEST_INC_NUMBER' => 13,
                'ENV_TEST_IS_FIRST_ON_CHANNEL' => 1,
            ], CASE_UPPER)),
            $this->castValues($process->getenv())
        );
    }

    /**
     * Force casting of the env variable values to validate the difference of behavior
     * between the versions Symfony Process '<3.2' and '>=3.2'.
     *
     * @param array $values
     *
     * @return array
     */
    private function castValues(array $values)
    {
        $envValues = [];

        foreach ($values as $key => $value) {
            $envValues[(binary) $key] = (binary) $value;
        }

        return $envValues;
    }

    /**
     * @param array $processEnv
     *
     * @return array
     */
    private function filterEnvTestVariables(array $processEnv)
    {
        return array_filter(
            $processEnv,
            function ($key) {
                return false !== strpos($key, 'ENV_TEST_');
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
