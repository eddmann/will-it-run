<?php declare(strict_types=1);

use Ahc\Cron\Expression;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;

require_once __DIR__ . '/vendor/autoload.php';

function parseCrontab(string $filePath): array {
    \preg_match_all(
        '/^([0-9\,\*\/\-]+\s+[0-9\,\*\/\-]+\s+[0-9\,\*\/\-]+\s+[0-9\,\*\/\-]+\s+[0-9\,\*\/\-]+)\s+(.+)$/m',
        \file_get_contents($filePath),
        $commands
    );

    return \array_map(null, ...$commands);
}

function calculateCommandExecutions(array $crontab, \DateTimeImmutable $startTime, \DateTimeImmutable $endTime): array {
    $currentTime = \DateTime::createFromImmutable($startTime);
    $executions = [];

    while ($currentTime <= $endTime) {
        foreach ($crontab as [$command, $expression]) {
            if (Expression::isDue($expression, $currentTime)) {
                $executions[$command] ??= [];
                $executions[$command][] = \DateTimeImmutable::createFromMutable($currentTime);
            }
        }

        $currentTime->add(new \DateInterval('PT1M'));
    }

    return $executions;
}

(new SingleCommandApplication())
    ->setName('Will it run?')
    ->setVersion('0.0.1')
    ->addArgument('crontab-file-path', InputArgument::REQUIRED, 'The crontab file path')
    ->addArgument('start-time', InputArgument::REQUIRED, 'The start time')
    ->addArgument('end-time', InputArgument::OPTIONAL, 'The end time')
    ->addOption('show-timestamps', 't', InputOption::VALUE_NONE)
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        $executions = \calculateCommandExecutions(
            \parseCrontab($input->getArgument('crontab-file-path')),
            new \DateTimeImmutable($input->getArgument('start-time')),
            new \DateTimeImmutable($input->getArgument('end-time') ?: $input->getArgument('start-time'))
        );

        $showTimestamps = $input->getOption('show-timestamps');

        foreach ($executions as $command => $executed) {
            $output->writeln([
                \sprintf('<fg=green>%s</>', $command),
                \sprintf('executed <options=underscore>%s</> time%s', count($executed), \count($executed) > 1 ? 's' : ''),
            ]);

            if ($showTimestamps) {
                $output->writeln(
                    \sprintf(
                        '<fg=gray>%s</>',
                        \implode(', ', \array_map(fn(\DateTimeInterface $d) => $d->format('Y-m-d H:i'), $executed))
                    )
                );
            }

            $output->writeln('');
        }

        return 0;
    })
    ->run();
