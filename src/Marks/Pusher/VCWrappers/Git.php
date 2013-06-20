<?php

namespace Marks\Pusher\VCWrappers;

use Symfony\Component\Console\Command\Command;

class Git extends VCWrapper {

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

    protected function sync()
    {

    }

}
