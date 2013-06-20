<?php

namespace Marks\Pusher\VCWrappers;

use Symfony\Component\Console\Command\Command;

abstract class VCWrapper {

    protected Command $command = null;
    protected array $project = null;

    abstract function __construct(Command $command, array $project);
    abstract protected function commit($message);
    abstract protected function deploy();

}
