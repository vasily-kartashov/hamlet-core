<?php

namespace Hamlet\TestRunner\SimpleTest {

    use SimpleReporter;
    use Symfony\Component\Console\Output\OutputInterface;
    use Symfony\Component\Console\Formatter\OutputFormatterStyle;

    class OutputReporterFacade extends SimpleReporter
    {

        protected $output;
        private static $BAR = "<secondary>--------------------------------------------------------------------------------</secondary>";
        private static $DOUBLE_BAR = "<info>================================================================================</info>";

        public function __construct(OutputInterface $output)
        {
            parent::__construct();
            $this->output = $output;

            $classStyle = new OutputFormatterStyle('yellow', null, ['bold']);
            $secondary = new OutputFormatterStyle('white');

            $output->getFormatter()->setStyle('class', $classStyle);
            $output->getFormatter()->setStyle('secondary', $secondary);

        }

        function paintHeader($test_name)
        {
            $this->output->writeln(OutputReporterFacade::$DOUBLE_BAR);
            $this->output->writeln("<class>${test_name}</class>");
        }

        function paintFooter($test_name)
        {
            $this->output->writeln(OutputReporterFacade::$BAR);
            if ($this->getFailCount() + $this -> getExceptionCount() == 0) {
                $this->output->writeln("[OK]");
            } else {
                $this->output->writeln("<error>[Failure]</error>");
            }
            $this->output->writeln('Test cases run: ' . $this -> getTestCaseProgress() . ' / ' . $this -> getTestCaseCount());
            $this->output->writeln('  Passes:       ' . $this -> getPassCount());
            $this->output->writeln('  Failures:     ' . $this -> getFailCount());
            $this->output->writeln('  Exceptions:   ' . $this -> getExceptionCount());
        }

        function paintFail($message)
        {
            parent::paintFail($message);
            $this->output->writeln(OutputReporterFacade::$BAR);
            $this->output->writeln("<error>[Test failed]</error>");
            $this->output->writeln($message);
        }

        function paintError($message)
        {
            parent::paintError($message);
            $this->output->writeln(OutputReporterFacade::$BAR);
            $this->output->writeln('<error>[Error]</error>');
            $this->output->writeln($message);
        }

        function paintException($exception)
        {
            parent::paintError($exception);
            $this->output->writeln(OutputReporterFacade::$BAR);
            $this->output->writeln('<error>[Exception]</error>');
            $this->output->writeln('- Code:    ' . $exception->getCode());
            $this->output->writeln('- Line:    ' . $exception->getLine());
            $this->output->writeln('- Message: ' . $exception->getMessage());
            $this->output->writeln($this->getTestList());
        }

        function paintSkip($message)
        {
            parent::paintSkip($message);
            $this->output->writeln(OutputReporterFacade::$BAR);
            $this->output->writeln('<info>[Test skipped]</info>');
            $this->output->writeln($message);
        }

        function paintFormattedMessage($message)
        {
            $this->output->writeln(OutputReporterFacade::$BAR);
            $this->output->writeln($message);
        }
    }
}