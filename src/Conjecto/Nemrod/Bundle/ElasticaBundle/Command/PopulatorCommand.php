<?php
/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 19/02/2015
 * Time: 17:13.
 */

namespace Conjecto\Nemrod\Bundle\ElasticaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PopulatorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ral:elastica:populate')
            ->setDescription('(Remise à zéro et) population des index elastica')
            ->addArgument('type', InputArgument::OPTIONAL, 'type cible')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'reset index')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reset = false;
        if ($input->getOption('reset')) {
            $reset = true;
        }
        $type = $input->getArgument('type');

        $this->getContainer()->get('nemrod.elastica.populator')->populate($type, $reset);
    }
}
