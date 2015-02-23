<?php

namespace Devyn\Bundle\RALBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ral:elastica:reset')
            ->setDescription('Remise à zéro des index elastica')
            ->addArgument('type', InputArgument::OPTIONAL, 'type cible')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $type = $input->getArgument('type');

        $this->getContainer()->get('ral.elasticsearch_resetter')->reset($type);
    }
} 