<?php

/*
 * This file is part of the PHPBench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Progress\Logger;

use PhpBench\Benchmark\Iteration;
use PhpBench\Benchmark\IterationCollection;
use PhpBench\Benchmark\SuiteDocument;
use PhpBench\Console\OutputAwareInterface;
use PhpBench\PhpBench;
use PhpBench\Util\TimeUnit;
use Symfony\Component\Console\Output\OutputInterface;

abstract class PhpBenchLogger extends NullLogger implements OutputAwareInterface
{
    protected $output;
    protected $timeUnit;

    public function __construct(TimeUnit $timeUnit = null)
    {
        $this->timeUnit = $timeUnit;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function startSuite(SuiteDocument $suiteDocument)
    {
        $this->output->writeln('PhpBench ' . PhpBench::VERSION . '. Running benchmarks.');

        if ($configPath = $suiteDocument->firstChild->firstChild->getAttribute('config-path')) {
            $this->output->writeln(sprintf('Using configuration file: %s', $configPath));
        }

        $this->output->writeln('');
    }

    public function endSuite(SuiteDocument $suiteDocument)
    {
        if ($suiteDocument->hasErrors()) {
            $errorStacks = $suiteDocument->getErrorStacks();
            $this->output->write(PHP_EOL);
            $this->output->writeln(sprintf('%d subjects encountered errors:', count($errorStacks)));
            $this->output->write(PHP_EOL);
            foreach ($errorStacks as $errorStack) {
                $this->output->writeln(sprintf('<error>%s</error>', $errorStack['subject']));
                $this->output->write(PHP_EOL);
                foreach ($errorStack['exceptions'] as $exception) {
                    $this->output->writeln(sprintf(
                        '    %s %s',
                        $exception['exception_class'],
                        str_replace("\n", "\n    ", $exception['message'])
                    ));
                }
            }
        }

        $this->output->writeln(sprintf(
            '%s subjects, %s iterations, %s revs, %s rejects',
            $suiteDocument->getNbSubjects(),
            $suiteDocument->getNbIterations(),
            $suiteDocument->getNbRevolutions(),
            $suiteDocument->getNbRejects()
        ));

        $this->output->writeln(sprintf(
            '(best [mean mode] worst) = %s [%s %s] %s (%s)',
            number_format($this->timeUnit->toDestUnit($suiteDocument->getMinTime()), 3),
            number_format($this->timeUnit->toDestUnit($suiteDocument->getMeanTime()), 3),
            number_format($this->timeUnit->toDestUnit($suiteDocument->getModeTime()), 3),
            number_format($this->timeUnit->toDestUnit($suiteDocument->getMaxTime()), 3),
            $this->timeUnit->getDestSuffix()
        ));

        $this->output->writeln(sprintf(
            '⅀T: %s μSD/r %s μRSD/r: %s%%',
            $this->timeUnit->format($suiteDocument->getTotalTime(), null, TimeUnit::MODE_TIME),
            $this->timeUnit->format($suiteDocument->getMeanStDev(), null, TimeUnit::MODE_TIME),
            number_format($suiteDocument->getMeanRelStDev(), 3)
        ));
    }

    public function formatIterationsFullSummary(IterationCollection $iterations)
    {
        $stats = $iterations->getStats();
        $timeUnit = $this->timeUnit->resolveDestUnit($iterations->getSubject()->getOutputTimeUnit());
        $mode = $this->timeUnit->resolveMode($iterations->getSubject()->getOutputMode());

        return sprintf(
            "[μ Mo]/r: %s %s (%s) \t[μSD μRSD]/r: %s %s%%",

            $this->timeUnit->format($stats['mean'], $timeUnit, $mode, null, false),
            $this->timeUnit->format($stats['mode'], $timeUnit, $mode, null, false),
            $this->timeUnit->getDestSuffix($timeUnit, $mode),
            $this->timeUnit->format($stats['stdev'], $timeUnit, TimeUnit::MODE_TIME),
            number_format($stats['rstdev'], 2)
        );
    }

    public function formatIterationsShortSummary(IterationCollection $iterations)
    {
        $stats = $iterations->getStats();
        $timeUnit = $this->timeUnit->resolveDestUnit($iterations->getSubject()->getOutputTimeUnit());
        $mode = $this->timeUnit->resolveMode($iterations->getSubject()->getOutputMode());

        return sprintf(
            '[μ Mo]/r: %s %s μRSD/r: %s%%',

            $this->timeUnit->format($stats['mean'], $timeUnit, $mode, null, false),
            $this->timeUnit->format($stats['mode'], $timeUnit, $mode, null, false),
            number_format($stats['rstdev'], 2)
        );
    }

    protected function formatIterationTime(Iteration $iteration)
    {
        $subject = $iteration->getSubject();
        $timeUnit = $subject->getOutputTimeUnit();
        $outputMode = $subject->getOutputMode();

        $time = 0;
        if ($iteration->hasResult()) {
            $time = $iteration->getResult()->getTime() / $iteration->getRevolutions();
        }

        return number_format(
            $this->timeUnit->toDestUnit(
                $time,
                $this->timeUnit->resolveDestUnit($timeUnit),
                $this->timeUnit->resolveMode($outputMode)
            ),
            $this->timeUnit->getPrecision()
        );
    }
}
