<?php

namespace Marks\Pusher\VCWrappers;

use Symfony\Component\Console\Command\Command;
use Marks\Pusher\BaseCommand;

class Subversion extends VCWrapper {

    function __construct(Command $command, array $project)
    {
        $this->command = $command;
        $this->project = $project;
    }

    protected function commit($message)
    {

    }

    protected function deploy()
    {

    }

    /**
     * Synchronize the SVN project (add and remove new and deleted files).
     *
     * @return void
     */
    protected function sync()
    {
        /**
         * This is the commandset we need to run off of:
         *
         * echo " - Adding Files."
         * svn status | grep '?' | sed 's/^.* //' | xargs svn add
         *
         * echo " - Deleting Files."
         * svn status | grep '!' | sed 's/^.* //' | xargs svn delete
         */

        $this->command->log('Synchronizing SVN Files', 'white', BaseCommand::DEBUG_VERBOSE);
        $status_results = $this->command->exec('svn status ' . $this->project['directory'], true, false, true);
        print_r($status_results);
        exit;
    }

}
