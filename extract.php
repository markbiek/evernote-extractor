<?php
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{
	InputInterface,
	InputOption,
	InputArgument,
};
use Symfony\Component\Console\Output\OutputInterface;

use App\EnexExtractor;

(new SingleCommandApplication())
	->setName('Evernote Extractor') // Optional
	->setVersion('0.0.1') // Optional
	->addArgument(
		'enex-file',
		InputArgument::REQUIRED,
		'The Evernote .enex file to extract.',
	)
	->addArgument(
		'output-directory',
		InputArgument::REQUIRED,
		'The directory where extracted files are placed.',
	)
	->setCode(function (InputInterface $input, OutputInterface $output) {
		try {
			$ext = new EnexExtractor(
				$input->getArgument('enex-file'),
				$input->getArgument('output-directory'),
			);

			$ext->extract();

			return Command::SUCCESS;
		} catch (\Exception $e) {
			throw $e;

			return Command::FAILURE;
		}
	})
	->run();
