<?php

namespace Devyn\Bundle\RALBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;

class ResetterCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('ral:elastica:populate')
            ->setDescription('Remise à zéro et population des index elastica')
            ->addArgument('type',InputArgument::OPTIONAL, 'type cible')
        ;
    }

    public function execute()
    {

    }

} 