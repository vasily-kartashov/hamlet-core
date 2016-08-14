<?php

namespace Hamlet\TestRunners\SimpleTest {

    use SimpleReporter;
    use Symfony\Component\Console\Output\OutputInterface;
    use Symfony\Component\Console\Formatter\OutputFormatterStyle;

    class OutputReporterFacade extends SimpleReporter {

        protected $output;

        public function __construct(OutputInterface $output) {
            parent::__construct();
            $this -> output = $output;

            $classStyle = new OutputFormatterStyle('yellow', null, ['bold']);
            $secondary = new OutputFormatterStyle('white');

            $output -> getFormatter() -> setStyle('class', $classStyle);
            $output -> getFormatter() -> setStyle('secondary', $secondary);
        }

        function paintFooter($test_name) {
            if ($this -> getFailCount() + $this -> getExceptionCount() == 0) {
                $this -> output -> write("[OK]");
            } else {
                $this -> output -> write("<error>[FAIL]</error>");
            }
            $this -> output -> write(" <class>${test_name}</class> ");
            $this -> output -> writeln('Passes: ' . $this -> getPassCount() . ', Failures: ' . $this -> getFailCount() . ', Exceptions: ' . $this -> getExceptionCount());
        }

        function paintFail($message) {
            parent::paintFail($message);
            $this -> output -> writeln($message);
        }

        function paintError($message) {
            parent::paintError($message);
            $this -> output -> writeln($message);
        }

        function paintException($exception) {
            parent::paintError($exception);
            $this -> output -> writeln('Code:    ' . $exception -> getCode());
            $this -> output -> writeln('Line:    ' . $exception -> getLine());
            $this -> output -> writeln('Message: ' . $exception -> getMessage());
            $this -> output -> writeln($this -> getTestList());
        }

        function paintSkip($message) {
            parent::paintSkip($message);
            $this -> output -> writeln($message);
        }

        function paintFormattedMessage($message) {
            $this -> output -> writeln($message);
        }
    }
}