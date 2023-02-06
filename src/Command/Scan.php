<?php

declare(strict_types = 1);

namespace Forceedge01\BDDStaticAnalyser\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Forceedge01\BDDStaticAnalyser\Processor;
use Forceedge01\BDDStaticAnalyserRules\Entities;

class Scan extends Command
{
    public function configure()
    {
        $this->setName('scan');
        $this->setDescription('Analyse BDD script files and find violations based on rules enabled in the config file');
        $this->addArgument('directory', InputArgument::REQUIRED, 'Directory to scan');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to config file', DEFAULT_CONFIG_PATH . Entities\Config::DEFAULT_NAME);
        $this->addOption('severities', 's', InputOption::VALUE_REQUIRED, 'Severities to display', '0,1,2,3,4');
        $this->addOption('rules', 'r', InputOption::VALUE_NONE, 'Display rules applied');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $path = $input->getArgument('directory');
            $severities = $input->getOption('severities');
            $configFile = $input->getOption('config');
            $displayRules = $input->getOption('rules');

            $config = new Entities\Config($configFile);

            if ($displayRules) {
                $output->writeln('Active rules: ' . print_r($config->get('rules'), true));
                return self::SUCCESS;
            }

            $displayProcessorClass = $config->get('display_processor');
            $displayProcessor = new $displayProcessorClass();
            $displayProcessor->setOutput($output);
            $dirToScan = realpath($path);
            $displayProcessor->inputSummary($path, $severities, $config->path, $dirToScan);

            if (! $path) {
                throw new Exception('A path must be provided (-d=<path>) to scan for files.');
            }

            $severities = explode(',', $severities);

            if (! $dirToScan || ! is_dir($dirToScan)) {
                throw new Exception("-d param (provided: '$path') must point to the folder where feature files are stored.");
            }

            $featureFileProcessor = new Processor\FeatureFileProcessor();
            $rulesProcessor = new Processor\RulesProcessor($config->get('rules'));
            $files = Processor\DirectoryProcessor::getAllFeatureFiles($dirToScan, $config->get('feature_file_extension'));
            $outcomeCollection = new Entities\OutcomeCollection();

            if (! $files) {
                throw new Exception("No feature files found in path '$dirToScan'");
            }

            $output->writeln(print_r($files, true), OutputInterface::VERBOSITY_DEBUG);

            foreach ($files as $file) {
                try {
                    $fileContents = $featureFileProcessor->getFileContent($file);
                } catch (Exception $e) {
                    self::error($output, sprintf('Unable to process feature file contents in file "%s".', $file), $e);
                    continue;
                }

                $output->writeln(print_r($fileContents, true), OutputInterface::VERBOSITY_DEBUG);

                try {
                    $rulesProcessor->applyRules($fileContents, $outcomeCollection);
                } catch (Exception $e) {
                    self::error($output, sprintf(''), $e);
                    continue;
                }
            }

            $output->writeln(print_r($outcomeCollection, true), OutputInterface::VERBOSITY_DEBUG);

            $displayProcessor->displayOutcomes($outcomeCollection, $severities);
            $reportProcessorClass = $config->get('report_processor');
            $reportProcessor = new $reportProcessorClass(new Processor\HtmlProcessor(), $featureFileProcessor);
            $reportPath = $config->get('html_report_path');
            $reportPath = $reportProcessor->generate($reportPath, $severities, $outcomeCollection);
            $displayProcessor->printSummary($outcomeCollection, $reportPath);

            if ($outcomeCollection->getCount() > 0) {
                return self::FAILURE;
            }
        } catch (Exception $e) {
            self::error($output, $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private static function error(OutputInterface $output, string $message, \Exception $e = null)
    {
        $output->write('<error>==> Error: ' . $message);

        if ($e !== null) {
            $output->write(' Exception: ' . $e->getMessage());
        }

        $output->writeln('</error>' . PHP_EOL);
    }
}
