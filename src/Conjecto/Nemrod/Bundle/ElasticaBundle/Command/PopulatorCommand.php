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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressHelper;
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
            ->setDescription('(Reset and) populate elastica indexes')
            ->addArgument('type', InputArgument::OPTIONAL, 'target type')
            ->addOption('batch', null, InputOption::VALUE_OPTIONAL, 'batch size', 50)
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

        $options['slice'] = $input->getOption('batch');


        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $this->getContainer()->get('nemrod.elastica.populator')->populate($type, $reset, $options, $output);
    }
}
