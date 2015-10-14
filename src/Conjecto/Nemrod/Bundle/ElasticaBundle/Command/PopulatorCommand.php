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
            ->addOption('index', null, InputOption::VALUE_OPTIONAL, 'The index to repopulate')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'The type to repopulate')
            ->addOption('batch', null, InputOption::VALUE_OPTIONAL, 'batch size', 50)
            ->addOption('no-reset', null, InputOption::VALUE_NONE, 'populate without index reset')
            ->addOption('no-clear-cache', null, InputOption::VALUE_NONE, 'does not clear the cache')
            ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'maximum command execution time, 3600 by default', 3600)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reset = !$input->getOption('no-reset');

        $index         = $input->getOption('index');
        $type          = $input->getOption('type');
        $maxTimeExecution = (int)$input->getOption('timeout');

        /*if (null === $index && null !== $type) {
            throw new \InvalidArgumentException('Cannot specify type option without an index.');
        }*/
        if (null === $index) {
            throw new \InvalidArgumentException('You must specify an index.');
        }

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

            $args = array_merge(array('php', $kernel->getRootDir() . DIRECTORY_SEPARATOR . '../app/console', 'nemrod:elastica:populate', '--no-clear-cache'));

            if ($options['slice']) {
                $args[] = "--batch=".$options['slice'];
            }

            if ($index) {
                $args[] = '--index=' . $index;
            }

            if ($type) {
                $args[] = '--type=' . $type;
            }

            $pb = new ProcessBuilder($args);
            $process = $pb->getProcess();
            $process->setTimeout($maxTimeExecution);
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

        $this->getContainer()->get('nemrod.elastica.populator')->populate($index, $type, $reset, $options, $output);
    }
}
