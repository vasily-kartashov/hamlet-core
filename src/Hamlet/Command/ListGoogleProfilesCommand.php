<?php

namespace Hamlet\Command;

use Hamlet\GoogleDrive\GoogleDriveClientFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListGoogleProfilesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('list-google-profiles')
            ->setDescription('List Google application profiles');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetPath = GoogleDriveClientFactory::getProfilesFilePath();
        if (file_exists($targetPath)) {
            $settings = (array) json_decode(file_get_contents($targetPath));
        } else {
            $settings = [];
        }
        $output->writeln('Available profiles:');
        foreach (array_keys($settings) as $profile) {
            $output->writeln("\t" . $profile);
        }
    }
}