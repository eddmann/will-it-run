<?php

use Ahc\Cron\Expression;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;

require_once __DIR__ . '/vendor/autoload.php';

(new SingleCommandApplication())
    ->setName('Will it run?')
    ->setVersion('0.0.1')
    ->addArgument('crontabFilePath', InputArgument::REQUIRED, 'The crontab file path')
    ->addArgument('startTime', InputArgument::REQUIRED, 'The cron start time')
    ->addArgument('endTime', InputArgument::OPTIONAL, 'The cron end time')
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        \preg_match_all(
            '/^([0-9\,\*\/\-]+\s+[0-9\,\*\/\-]+\s+[0-9\,\*\/\-]+\s+[0-9\,\*\/\-]+\s+[0-9\,\*\/\-]+)\s+(.+)$/m',
            \file_get_contents($input->getArgument('crontabFilePath')),
            $matches
        );
        $crontab = \array_map(null, ...$matches);

        $startTime = new \DateTimeImmutable($input->getArgument('startTime'));
        $endTime = new \DateTimeImmutable($input->getArgument('endTime') ?? $input->getArgument('startTime'));

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

        foreach ($executions as $command => $executed) {
            $output->writeln([
                $command,
                \implode(', ', \array_map(fn(\DateTimeInterface $d) => $d->format('Y-m-d H:i'), $executed)), ''
            ]);
        }

        return 0;
    })
    ->run();
