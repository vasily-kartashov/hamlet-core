<?php

namespace Hamlet\TestRunner\SimpleTest;

use SimpleReporter;
use Symfony\Component\Console\Output\OutputInterface;

class OutputReporterFacade extends SimpleReporter
{
    protected $output;
    private static $BAR        = "--------------------------------------------------";
    private static $DOUBLE_BAR = "==================================================";

    public function __construct(OutputInterface $output)
    {
        parent::__construct();
        $this->output = $output;
    }

    function paintHeader($test_name) {
        $this->output->writeln('- ' . $test_name);
    }

    function paintFooter($test_name) {
        $this->output->writeln(OutputReporterFacade::$DOUBLE_BAR);
        if ($this->getFailCount() + $this->getExceptionCount() == 0) {
            $this->output->writeln("[OK]");
        } else {
            $this->output->writeln("[Failure]");
        }
        $this->output->writeln('Test cases run: ' . $this->getTestCaseProgress() . ' / ' . $this->getTestCaseCount());
        $this->output->writeln('- Passes:     ' . $this->getPassCount());
        $this->output->writeln('- Failures:   ' . $this->getFailCount());
        $this->output->writeln('- Exceptions: ' . $this->getExceptionCount());
    }

    function paintFail($message) {
        parent::paintFail($message);
        $this->output->writeln(OutputReporterFacade::$BAR);
        $this->output->writeln("[Test failed]");
        $this->output->writeln($message);
    }

    function paintError($message) {
        parent::paintError($message);
        $this->output->writeln(OutputReporterFacade::$BAR);
        $this->output->writeln('[Error]');
        $this->output->writeln($message);
    }

    function paintException($exception) {
        parent::paintError($exception);
        $this->output->writeln(OutputReporterFacade::$BAR);
        $this->output->writeln('[Exception]');
        $this->output->writeln('- Code:    ' . $exception->getCode());
        $this->output->writeln('- Line:    ' . $exception->getLine());
        $this->output->writeln('- Message: ' . $exception->getMessage());
        $this->output->writeln($this->getTestList());
    }

    function paintSkip($message) {
        parent::paintSkip($message);
        $this->output->writeln(OutputReporterFacade::$BAR);
        $this->output->writeln('[Test skipped]');
        $this->output->writeln($message);
    }

    function paintFormattedMessage($message) {
        $this->output->writeln(OutputReporterFacade::$BAR);
        $this->output->writeln($message);
    }
}