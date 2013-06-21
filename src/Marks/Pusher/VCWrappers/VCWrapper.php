<?php

namespace Marks\Pusher\VCWrappers;

use Symfony\Component\Console\Command\Command;

abstract class VCWrapper
{

    protected $command = null;
    protected $project = null;

    abstract function __construct(Command $command, array $project);
    abstract public function commit($message);
    abstract public function deploy();

}
