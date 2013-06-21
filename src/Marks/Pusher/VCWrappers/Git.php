<?php

namespace Marks\Pusher\VCWrappers;

use Symfony\Component\Console\Command\Command;
use Marks\Pusher\BaseCommand;

class Git extends VCWrapper
{

    function __construct(Command $command, array $project)
    {
        $this->command = $command;
        $this->project = $project;
    }

    public function commit($message)
    {
        $this->sync();
        $message = str_replace('"', '\\"', $message);
        $this->command->exec("cd {$this->project['directory']} && git commit -am \"{$message}\" && git push", true);
    }

    public function deploy()
    {
        $remote_directory = $this->project['remote']['directory'];
        $remote_command = "cd '{$remote_directory}' && git pull";
        return $remote_command;
    }

    protected function sync()
    {
        $this->command->log('Synchronizing Git Files', 'white', BaseCommand::DEBUG_VERBOSE);
        $this->command->exec('cd ' . $this->project['directory'] . ' && git add .', false);
    }

}
