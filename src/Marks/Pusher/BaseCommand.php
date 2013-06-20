<?php

namespace Marks\Pusher;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command {

    const DEBUG_VERBOSE = 1;
    const DEBUG_NORMAL = 0;

    public $input = null;
    public $output = null;
    public $dialog = null;
    public $config = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Load the configuration necessary.
        $this->loadConfig();

        parent::__construct();
    }

    /**
     * Grabs configuration information from specified JSON
     * files located around the system.
     *
     * @return void
     */
    protected function loadConfig()
    {
        $configLocations = array(
            LIBS_ROOT . '/config.json',
            '/etc/pusher.json',
            Helpers::getHomeDirectory() . '/.pusher',
        );
        foreach ($configLocations as $location) {
            if (file_exists($location)) {
                $this->config = array_merge($this->config, json_decode(file_get_contents($location)));
                break;
            }
        }
    }

    protected function writeConfig($file)
    {
        $json_contents = json_encode($this->config);
        file_put_contents($file, $json_contents);
    }

    /**
     * Sets up the BaseCommand class for execution. This method
     * must be called before using any other methods in this
     * class.
     *
     * @param  InputInterface  $input  The input object.
     * @param  OutputInterface $output The output object.
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->dialog = $this->getHelperSet()->get('dialog');
    }

    /**
     * Logs a message to STDOUT.
     *
     * @param  string $message The message to display.
     * @param  string $color   The color for the message to be.
     * @param  int    $level   The level for debugging. Can either be BaseCommand::DEBUG_NORMAL
     *                         or BaseCommand::DEBUG_VERBOSE to only show on normal execution
     *                         or verbose execution respectively.
     * @return void
     */
    protected function log($message, $color = 'white', $level = self::DEBUG_NORMAL)
    {
        if (!$this->input || !$this->output) {
            throw new \Exception('You must call parent::execute(...) before calling this function!');
        }

        if ($this->output->getVerbosity() != OutputInterface::VERBOSITY_QUIET && $level == self::DEBUG_NORMAL) {
            $this->output->writeln("<fg={$color}>{$message}</fg={$color}>");
            return;
        }

        if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE && $level == self::DEBUG_VERBOSE) {
            $this->output->writeln("<fg={$color}>{$message}</fg={$color}>");
            return;
        }
    }

    /**
     * Displays an error to the output.
     *
     * @param  string  $message The message to display.
     * @param  boolean $fatal   Whether or not to halt execution of the
     *                          application because of this error.
     * @return void
     */
    public function error($message, $fatal = false)
    {
        if (!$this->input || !$this->output) {
            throw new Exception('You must call parent::execute(...) before calling this function!');
        }

        if ($this->output->getVerbosity() != OutputInterface::VERBOSITY_QUIET) {
            $this->output->writeln("<fg=red>{$message}</fg=red>");
        }

        if ($fatal) {
            exit(1);
        }
    }

    /**
     * Execute a command on the system.
     *
     * @param  string  $command           The command to execute.
     * @param  boolean $exit_on_fail      Whether or not to halt the application if the command
     *                                    fails to execute properly.
     * @param  boolean $force_passthrough Forces the output of the command to be displayed directly
     *                                    to STDOUT instead of returning an array of the results.
     * @param  boolean $force_exec        Forces a command to be run through exec instead of
     *                                    passthru.
     * @return mixed                      False if there was an error, otherwise the output
     *                                    returned from the command.
     */
    public function exec($command, $exit_on_fail = true, $force_passthrough = false, $force_exec = false)
    {
        if (!$this->input || !$this->output) {
            throw new Exception('You must call parent::execute(...) before calling this function!');
        }

        $output_array = array();
        $return_var = null;

        if (($this->output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE || $force_passthrough) && $force_exec == false) {
            passthru($command, $return_var);
        } else {
            exec($command . ' 2>&1', $output_array, $return_var);
        }

        if ($return_var == 1) {
            if ($exit_on_fail) {
                $this->error('There was an error with the last command. The program will now exit.', true);
            } else {
                return false;
            }
        }

        return $output_array;
    }

    /**
     * Display a confirmation question to the user.
     * @param  string  $question     The question to ask.
     * @param  boolean $defaultValue The default value of the answer.
     * @return boolean               True if yes, false if no.
     */
    public function confirm($question, $defaultValue = true)
    {
        $yes = ($defaultValue) ? 'Y' : 'y';
        $no = ($defaultValue) ? 'n' : 'N';
        $yesno = "[{$yes}/{$no}]";
        if ($this->input->getOption('no-interaction')) {
            return $defaultValue;
        }
        return $this->dialog->askConfirmation($this->output, $question . ' ' . $yesno . ': ', $defaultValue);
    }

    /**
     * Prompts a question to the user, expecting a text answer.
     *
     * @param  string  $question     The question to ask.
     * @param  boolean $required     Whether or not an answer is required.
     * @param  string  $defaultValue The default value, should the user not
     *                               give a response.
     * @param  boolean $hidden       Whether or not the input from the user
     *                               should be hidden. For example, a password
     *                               field.
     * @return string                The response from the user.
     */
    public function prompt($question, $required = false, $defaultValue = '', $hidden = false)
    {
        if ($required && $this->input->getOption('no-interaction')) {
            $this->error('Interaction is disabled, but interaction is required.', true);
            return;
        }
        if ($hidden) {
            $response = $this->dialog->askHiddenResponse($this->output, $question . ' - ');
        } else {
            $response = $this->dialog->ask($this->output, $question . ' - ', $defaultValue);
        }
        if (!$response && $required) {
            $this->error('You must supply a value.');
            return $this->prompt($question, $required, $defaultValue);
        } else {
            return $response;
        }
    }

}
