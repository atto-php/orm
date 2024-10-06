<?php

declare(strict_types=1);

namespace Atto\Orm\Command;

use Atto\CodegenTools\ClassDefinition\PHPClassDefinitionProducer;
use Atto\CodegenTools\CodeGeneration\PHPFilesWriter;
use Atto\Orm\RepositoryProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'atto:orm:generate',
    description: 'Generates repositories based on your code',
)]
final class GenerateRepositories extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'source_directory',
                InputArgument::REQUIRED,
                'Directory containing the source objects you want to generate repositories for'
            )->addOption(
                'namespace_prefix',
                null,
                InputArgument::OPTIONAL,
                'Common namespace prefix for your code, this will be replaced by the repositories namespace prefix',
                ''
            )->addOption(
                'repositories_namespace_prefix',
                null,
                InputArgument::OPTIONAL,
                'Namespace prefix for generated repositories classes',
                'Generated\\Repository'
            )->addOption(
                'psr4_namespace_prefix',
                null,
                InputArgument::OPTIONAL,
                'PSR4 namespace prefix for the output directory',
                'Generated'
            )->addArgument(
                'output_directory',
                InputArgument::REQUIRED,
                'Directory to write generated files to'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directoryContainingFilesToGenerateFor = $input->getArgument('source_directory');
        $namespacePrefixForClasses = $input->getOption('namespace_prefix');
        $namespaceForGeneratedClasses = $input->getOption('repositories_namespace_prefix');
        $outputDirectory = $input->getArgument('output_directory');
        $outputPsr4Prefix = $input->getOption('psr4_namespace_prefix');

        (new PHPFilesWriter($outputDirectory, $outputPsr4Prefix))->writeFiles(
            new PHPClassDefinitionProducer(
                (new RepositoryProvider(
                    $directoryContainingFilesToGenerateFor,
                    $namespacePrefixForClasses,
                    $namespaceForGeneratedClasses
                ))->provideFile())
        );

        return Command::SUCCESS;
    }
}
