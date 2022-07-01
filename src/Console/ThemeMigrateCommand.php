<?php

declare(strict_types=1);

namespace RZ\Roadiz\CompatBundle\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ThemeMigrateCommand extends Command
{
    protected string $projectDir;

    /**
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this->setName('themes:migrate')
            ->setDescription('Update your site against theme import files, regenerate NSEntities, update database schema and clear caches.')
            ->addArgument(
                'classname',
                InputArgument::REQUIRED,
                'Main theme classname (Use / instead of \\ and do not forget starting slash) or path to config.yml'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Do nothing, only print information.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $question = new ConfirmationQuestion(
            '<question>Are you sure to migrate against this theme?</question> This can lead in data loss.',
            !$input->isInteractive()
        );
        if ($io->askQuestion($question) === false) {
            $io->note('Nothing was done…');
            return 0;
        }

        if ($input->getOption('dry-run')) {
            $this->runCommand(
                'themes:install',
                sprintf('--data "%s" --dry-run', $input->getArgument('classname')),
                'dev',
                $input->isInteractive(),
                $output->isQuiet(),
            );
        } else {
            $this->runCommand(
                'doctrine:migrations:migrate',
                '--allow-no-migration',
                'dev',
                false,
                $output->isQuiet()
            ) === 0 ? $io->success('doctrine:migrations:migrate') : $io->error('doctrine:migrations:migrate');

            $this->runCommand(
                'themes:install',
                sprintf('--data "%s"', $input->getArgument('classname')),
                'dev',
                $input->isInteractive(),
                $output->isQuiet()
            ) === 0 ? $io->success('themes:install') : $io->error('themes:install');

            $this->runCommand(
                'generate:nsentities',
                '',
                'dev',
                $input->isInteractive(),
                $output->isQuiet()
            ) === 0 ? $io->success('generate:nsentities') : $io->error('generate:nsentities');

            $this->runCommand(
                'doctrine:schema:update',
                '--dump-sql --force',
                'dev',
                $input->isInteractive(),
                $output->isQuiet()
            ) === 0 ? $io->success('doctrine:schema:update') : $io->error('doctrine:schema:update');

            $this->runCommand(
                'cache:clear',
                '',
                'dev',
                $input->isInteractive(),
                $output->isQuiet()
            ) === 0 ? $io->success('cache:clear --env=dev') : $io->error('cache:clear --env=dev');

            $this->runCommand(
                'cache:clear',
                '',
                'prod',
                $input->isInteractive(),
                $output->isQuiet()
            ) === 0 ? $io->success('cache:clear --env=prod') : $io->error('cache:clear --env=prod');
        }
        return 0;
    }

    protected function runCommand(
        string $command,
        string $args = '',
        string $environment = 'dev',
        bool $interactive = true,
        bool $quiet = false
    ): int {
        $args .= $interactive ? '' : ' --no-interaction ';
        $args .= $quiet ? ' --quiet ' : ' -v ';
        $args .= ' --env ' . $environment;
        $process = Process::fromShellCommandline(
            'php bin/console ' . $command  . ' ' . $args
        );
        $process->setWorkingDirectory($this->projectDir);
        $process->setTty($interactive);
        $process->run();
        return $process->wait();
    }
}
