<?php

namespace Marks\Pusher;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

use Marks\Pusher\VCWrappers\Git;
use Marks\Pusher\VCWrappers\Subversion;

class SubmitCommand extends BaseCommand
{

    /**
     * Configures the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('submit')
            ->setDescription('Commits and Deploys code changes.')
            ->addArgument('message', InputArgument::REQUIRED, 'VCS Commit Message');
    }

    /**
     * Executes the command.
     *
     * @param  InputInterface  $input  The input object.
     * @param  OutputInterface $output The output object.
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        // Make sure we have a valid project.
        $project = $this->checkDirectory();
        while ($project == false) {

            // Ask the user if they want to create a project.
            $this->log("There doesn't appear to be a project associated with this directory.", 'red');
            $create_project = $this->confirm('Would you like to create a project now?');

            // If the user chooses not to create a project, exit.
            if (!$create_project) exit;

            // Run the registration and refresh the project variable.
            $register = $this->getApplication()->find('register');
            $arguments = array(
                'command' => 'register',
            );
            $register->run(new ArrayInput($arguments), $output);
            $project = $this->checkDirectory();

        }

        // Depending on the VCS solution, commit the code.
        $wrapper = null;
        switch ($project['vcs']) {
            case 'subversion':
                $wrapper = new Subversion($this, $project);
                break;
            case 'git':
                $wrapper = new Git($this, $project);
                break;
        }

        // Get the commit message.
        $message = $input->getArgument('message');
        if (!$message) {
            // Prompt the user for the commit message.
            $message = $this->prompt('Commit message?', true);
        }

        // Call the commit function for the wrapper.
        $this->log('Committing');
        $wrapper->commit($message);

        // Now deploy to the server.
        $this->log('Deploying');

        // Prepare the list of commands.
        $commands = array();

        if ($project['remote']['do-update']) {
            $commands[] = str_replace('"', '\\"', $wrapper->deploy());
        }

        // Add extra commands to the commands array.
        if (array_key_exists('extra-commands', $project['remote']) &&
            is_array($project['remote']['extra-commands'])) {
            foreach ($project['remote']['extra-commands'] as $command) {
                $commands[] = str_replace('"', '\\"', $command);
            }
        }

        // Build out the main command.
        $host = $project['remote']['host'];
        $remote_command = 'ssh "' . $host . '" "';
        if ($project['remote']['sudo-needed']) {
            $remote_command .= 'sudo su -c \\"';
        }
        $remote_command .= implode(' && ', $commands);
        if ($project['remote']['sudo-needed']) {
            $remote_command .= '\\"';
        }
        $remote_command .= '"';

        // Run the command.
        $this->exec($remote_command, true);

        $this->log('Complete!', 'green');
    }

    /**
     * Checks to see if the current directory is a supported project.
     *
     * @return mixed Array if a match was found, false if not.
     */
    protected function checkDirectory()
    {
        $this->loadConfig();
        $directory = getcwd();
        $match = null;
        foreach ($this->config['projects'] as $project) {
            // Trim the slashes from the beginning and end to make sure we
            // match the meat of the path and not break if the trailing slashes
            // are different.
            if (trim($directory, '/') == trim($project['directory'], '/')) {
                $match = $project;
                break;
            }
        }

        if ($match !== null) {
            return $match;
        } else {
            return false;
        }
    }

}
