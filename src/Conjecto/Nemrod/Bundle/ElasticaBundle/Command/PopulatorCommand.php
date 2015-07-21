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

use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand;
use Symfony\Bundle\FrameworkBundle\Command\CacheWarmupCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Process\ProcessBuilder;

class PopulatorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('nemrod:elastica:populate')
            ->setDescription('(Reset and) populate elastica indexes')
            ->addArgument('type', InputArgument::OPTIONAL, 'target type')
            ->addOption('batch', null, InputOption::VALUE_OPTIONAL, 'batch size', 50)
            ->addOption('no-reset', null, InputOption::VALUE_NONE, 'populate without index reset')
            ->addOption('no-clear-cache', null, InputOption::VALUE_NONE, 'does not clear the cache')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reset = !$input->getOption('no-reset');

        $type = $input->getArgument('type');

        $options['slice'] = $input->getOption('batch');

        $clearCache = !$input->getOption('no-clear-cache');

        //cache needs to be refreshed
        if ($clearCache) {

            $kernel = $this->getContainer()->get('kernel');

            //cache:clear command is called
            $command = new CacheClearCommand();
            $command->setContainer($this->getContainer());
            $ccInput = new ArrayInput(array());
            $command->run($ccInput, new NullOutput());

            $env = $kernel->getEnvironment();
            $args = array_merge(array('php', $kernel->getRootDir() . DIRECTORY_SEPARATOR . '../app/console', 'nemrod:elastica:populate', '--no-clear-cache'));

            if ($options['slice']) {
                $args[] = "--batch=".$options['slice'];
            }

            if ($type) {
                $args[] = $type;
            }

            $pb = new ProcessBuilder($args);
            $process = $pb->getProcess();
            // run & transfer output to current output
            $process->run(function ($type, $buffer) use ($output) {
                $output->writeln($buffer);
            });

            if (!$process->isSuccessful()) {

                throw new \RuntimeException($process->getErrorOutput());
            }

            exit();

        }

        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $this->getContainer()->get('nemrod.elastica.populator')->populate($type, $reset, $options, $output);
    }
}
