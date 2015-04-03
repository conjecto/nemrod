<?php
/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
            ->setName('nemrod:elastica:populate')
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
