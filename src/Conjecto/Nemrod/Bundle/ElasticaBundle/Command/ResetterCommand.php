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
use Symfony\Component\Console\Output\OutputInterface;

class ResetterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('nemrod:elastica:reset')
            ->setDescription('Remise à zéro des index elastica')
            ->addArgument('type', InputArgument::OPTIONAL, 'type cible')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');

        $this->getContainer()->get('nemrod.elastica.resetter')->reset($type);
    }
}
