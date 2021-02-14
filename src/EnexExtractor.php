<?php
namespace App;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

use App\Models\Note;
use App\Exceptions\{FileNotFoundException, DirectoryNotFoundException};

class EnexExtractor {
	protected string $file = '';
	protected string $dir = '';
	protected ?OutputInterface $output = null;

	public function __construct(
		string $file,
		string $dir,
		OutputInterface $output = null
	) {
		$this->output = $output;

		$this->file = $file;
		if (!\file_exists($this->file)) {
			throw new FileNotFoundException();
		}

		$this->dir = $dir;
		if (!\file_exists($this->dir)) {
			throw new DirectoryNotFoundException();
		}
	}

	protected function writeln(string $msg): void {
		if ($this->output != null) {
			$this->output->writeln($msg);
		}
	}

	public function extract(): void {
		$content = \file_get_contents($this->file);
		$crawler = new Crawler($content);
		$crawler = $crawler->filterXPath('descendant-or-self::en-export/note');

		$noteIdx = 0;
		foreach ($crawler as $domElement) {
			$noteIdx++;

			$note = new Note($domElement, $noteIdx);
			$note->dump($this->dir);
		}
	}
}
