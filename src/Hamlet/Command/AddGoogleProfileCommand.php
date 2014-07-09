<?php

namespace Hamlet\Command;

use Exception;
use Hamlet\GoogleDrive\GoogleDriveClientFactory;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddGoogleProfileCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('add-google-profile')
            ->setDescription('Add new Google application profile')
            ->addArgument('profile', InputArgument::REQUIRED, 'Profile name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetPath = GoogleDriveClientFactory::getProfilesFilePath();
        $targetDirectoryPath = dirname($targetPath);
        if (!file_exists($targetDirectoryPath)) {
            throw new Exception("Path does not exist '{$targetDirectoryPath}'");
        }
        if (file_exists($targetPath)) {
            $settings = json_decode(file_get_contents($targetPath));
        } else {
            $settings = new StdClass();
        }

        $factory = new GoogleDriveClientFactory();
        $output->writeln('Enter Google client ID: ');
        $clientId = trim(fgets(STDIN));
        $output->writeln('Enter Google client secret: ');
        $clientSecret = trim(fgets(STDIN));

        $client = $factory->getClient($clientId, $clientSecret);

        $url = $client->createAuthUrl();
        $output->writeln('Visit the following URL and copy the code' . PHP_EOL);
        $output->writeln($url . PHP_EOL);
        $output->writeln('Please enter the code: ');
        $authCode = trim(fgets(STDIN));

        $profile = $input->getArgument('profile');
        $settings->{$profile} = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'accessToken' => json_decode($client->authenticate($authCode)),
        ];
        file_put_contents($targetPath, json_encode($settings, JSON_PRETTY_PRINT));
        $output->writeln("Profile {$profile} added");
    }
}