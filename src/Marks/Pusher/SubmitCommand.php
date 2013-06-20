<?php

namespace Marks\Pusher;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Marks\Pusher\VCWrappers\Git;
use Marks\Pusher\VCWrappers\Subversion;

class SubmitCommand extends Command {

    /**
     * Configures the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('pusher:submit')
            ->setDescription('Commits and Deploys code changes.')
            ->addArgument('message', InputArgument::OPTIONAL, 'VCS Commit Message');
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
        $project = self::checkDirectory();
        while ($project == false) {

            // Ask the user if they want to create a project.
            $this->log("There doesn't appear to be a project associated with this directory.", 'red');
            $create_project = $this->confirm('Would you like to create a project now?');

            // If the user chooses not to create a project, exit.
            if (!$create_project) exit;

            // Run the registration and refresh the project variable.
            $register = $this->getApplication()->find('pusher:register');
            $register->run($input, $output);
            $project = self::checkDirectory();

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
        $wrapper->deploy();

        $this->log('Complete!', 'green');
    }

    /**
     * Checks to see if the current directory is a supported project.
     *
     * @return mixed Array if a match was found, false if not.
     */
    protected static function checkDirectory()
    {
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
