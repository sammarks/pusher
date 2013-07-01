<?php

namespace Marks\Pusher;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterCommand extends BaseCommand
{

    // Project structure:
    //
    // {
    //     "directory": "/Users/sammarks/Code/personal/test-project/",
    //     "vcs": "subversion", (or git)
    //     "remote": {
    //         "host": "personal",
    //         "directory": "/var/www/test-project",
    //         "sudo-needed": false,
    //         "extra-commands": [],
    //         "do-update": true
    //     }
    // }

    /**
     * Configures the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('register')
            ->setDescription('Register a new project with Pusher.')
            ->addArgument('remote-commands', InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Any extra commands to run on the server.')
            ->addOption('directory', 'd', InputOption::VALUE_REQUIRED,
                'The directory of the project.')
            ->addOption('vcs', 's', InputOption::VALUE_REQUIRED,
                'Which VCS to use (subversion or git).')
            ->addOption('remote-host', null, InputOption::VALUE_REQUIRED,
                'The SSH host for deployment.')
            ->addOption('remote-directory', null, InputOption::VALUE_REQUIRED,
                'The directory on the remote server to update.')
            ->addOption('sudo-needed', null, InputOption::VALUE_NONE,
                'Whether or not elevated permissions are needed on the server.')
            ->addOption('dont-update', null, InputOption::VALUE_NONE,
                'If set, this script doesnt do any updating on the server.');
    }

    /**
     * Executes the command.
     *
     * @param  InputInterface  $input  The input interface.
     * @param  OutputInterface $output The output interface.
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        // Setup the base project.
        $project = array();

        // Grab the project directory.
        if ($input->getOption('directory')) {
            $project['directory'] = $input->getOption('directory');
        } else {
            $project['directory'] = $this->askForDirectory();
        }
        while (!is_dir($project['directory'])) {
            $this->error('The directory: "' . $project['directory'] . '" is an invalid directory.');
            $project['directory'] = $this->askForDirectory();
        }

        // Grab the VCS system for the project.
        if ($input->getOption('vcs')) {
            $project['vcs'] = $input->getOption('vcs');
        } else {

            // Try to auto-detect the VCS.
            $svn = false;
            $git = false;
            if ($handle = opendir($project['directory'])) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry == '.svn') {
                        $svn = true;
                    }
                    if ($entry == '.git') {
                        $git = true;
                    }
                }
            }

            if ($svn === false && $git === false) {
                $this->error('A VCS could not automatically be detected.');
                $project['vcs'] = $this->prompt('Which VCS do you want to use (subversion or git)?', true);
            } else if ($svn === false && $git === true) {
                $project['vcs'] = 'git';
            } else if ($svn === true && $git === false) {
                $project['vcs'] = 'subversion';
            } else if ($svn === true && $git === true) {
                $this->error('Both systems were detected in this folder.');
                $project['vcs'] = $this->prompt('Which VCS do you want to use (subversion or git)?', true);
            }

        }

        // Validate the VCS.
        while ($project['vcs'] != 'git' && $project['vcs'] != 'subversion') {
            $this->error('The VCS "' . $project['vcs'] . '" is invalid. It must be either subversion or git.');
            $project['vcs'] = $this->prompt('Which VCS do you want to use (subversion or git)?', true);
        }

        // Get the remote host and directory.
        $project['remote'] = array();

        // Remote Host
        if ($input->getOption('remote-host')) {
            $project['remote']['host'] = $input->getOption('remote-host');
        } else {
            $project['remote']['host'] = $this->prompt('Remote Host?', true);
        }

        // Remote directory.
        if ($input->getOption('remote-directory')) {
            $project['remote']['directory'] = $input->getOption('remote-directory');
        } else {
            $project['remote']['directory'] = $this->prompt('Remote directory?', true);
        }

        // Remote sudo.
        if ($input->getOption('sudo-needed')) {
            $project['remote']['sudo-needed'] = true;
        } else {
            $project['remote']['sudo-needed'] = $this->confirm('Are elevated permissions required on the target server?', true);
        }

        // Remote extra commands.
        $project['remote']['extra-commands'] = array();
        if ($input->getArgument('remote-commands')) {
            $project['remote']['extra-commands'][] = $input->getArgument('remote-commands');
        } else {
            $this->askForRemoteCommands($project);
        }

        // Don't update?
        if ($input->getOption('dont-update')) {
            $project['remote']['do-update'] = false;
        } else {
            $project['remote']['do-update'] = $this->confirm('Should I handle the updating? If your custom commands '
                . 'handle the updating, say n for this.', true);
        }

        // Add the project to the configuration.
        $this->log('Saving');
        if (!array_key_exists('projects', $this->config) || !is_array($this->config['projects'])) {
            $this->config['projects'] = array();
        }
        $this->config['projects'][] = $project;

        // Save the configuration.
        $this->writeConfig(Helpers::getHomeDirectory() . '/.pusher');

        // Done!
        $this->log('Saved!', 'green');

    }

    protected function askForRemoteCommands(&$project)
    {
        if (count($project['remote']['extra-commands']) > 0) {
            $response = $this->confirm('Any more commands?', false);
        } else {
            $response = $this->confirm('Are there any extra remote commands you would like to run?', false);
            if ($response) {
                $this->log('Be sure to use only single quotes in commands, or things may break!', 'yellow');
            }
        }
        if ($response) {
            $command = $this->prompt('Command');
            if ($command) {
                $project['remote']['extra-commands'][] = $command;
            } else {
                return;
            }
        } else {
            return;
        }
        $this->askForRemoteCommands($project);
        return;
    }

    /**
     * Asks the user for a directory.
     *
     * @return string The directory.
     */
    protected function askForDirectory()
    {
        $directory = $this->prompt('What would you like the directory of the project to be?'
            . ' Leave blank for the current directory.', false);
        if (!$directory) {
            $directory = getcwd();
        }
        return $directory;
    }

}
