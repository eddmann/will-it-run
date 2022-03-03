# Will it run? 🏃

Based on a supplied crontab, check to see what cron jobs will run at a given time/range.

## Usage

`./will-it-run [-t|--show-timestamps] [--] <crontab-file-path> <start-time> [<end-time>]`

During a recent maintenance window we were required to pause scheduled background processes handled by Cron.
This script was used to determine and plan for which commands would be missed.
