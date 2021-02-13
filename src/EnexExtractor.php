<?php
namespace App;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

use App\Models\Note;

class FileNotFoundException extends \Exception {
}
class DirectoryNotFoundException extends \Exception {
}
class DirectoryNotWritableException extends \Exception {
}

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

			$noteName = 'note-' . str_pad($noteIdx, 3, '0', STR_PAD_LEFT);
			$dir = "{$this->dir}/{$noteName}";
			if (!\file_exists($dir)) {
				if (mkdir($dir) === false) {
					throw new DirectoryNotWritableException();
				}
			}

			$note = new Note($domElement);
			$note->dump($dir);
		}
	}
}
