<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 19/02/2015
 * Time: 17:13
 */

namespace Devyn\Bundle\RALBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopulatorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ral:elastica:populate')
            ->setDescription('(Remise à zéro et) population des index elastica')
            ->addArgument('type', InputArgument::OPTIONAL, 'type cible')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('ral.elasticsearch_populator')->populate();
    }
} 