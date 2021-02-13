<?php
namespace App\Models;

use Symfony\Component\DomCrawler\Crawler;

class Note {
	protected \DOMElement $el;

	public string $title;
	public string $content;

	public function __construct(\DOMElement $el) {
		$this->el = $el;

		$this->initTitle();
		$this->initContent();
	}

	public function dump(string $dir): bool {
		return $this->dumpContent($dir) && $this->dumpMedia($dir);
	}

	protected function dumpContent(string $dir): bool {
		if (empty($this->content)) {
			return true;
		}

		return file_put_contents("$dir/note.html", $this->content) !== false;
	}

	protected function dumpMedia(string $dir): bool {
		$ret = true;

		return $ret;
	}

	protected function initTitle(): void {
		$titleNodes = $this->el->getElementsByTagName('title');
		$this->title = $titleNodes->item(0)->nodeValue;
	}

	protected function initContent(): void {
		$contentNodes = $this->el->getElementsByTagName('content');
		$contentXml = $contentNodes->item(0)->nodeValue;
		$contentCrawler = new Crawler($contentXml);
		$contentCrawler = $contentCrawler->filterXPath(
			'descendant-or-self::en-note',
		);
		foreach ($contentCrawler as $noteElement) {
			$noteContent = $noteElement->ownerDocument->saveHTML($noteElement);
		}

		$this->content = $this->cleanContent($noteContent);
	}

	protected function cleanContent(string $content): string {
		// Strip en-note
		$content = \preg_replace('/<en-note.*?>|<\/en-note>/', '', $content);

		// Delete en-media
		$content = \preg_replace('/<en-media.*?en-media>/', '', $content);

		// Delete <div></div>
		$content = \str_replace('<div></div>', '', $content);

		// Delete <div><br></div>
		$content = \str_replace('<div><br></div>', '', $content);

		// Delete <div> inside <li>
		$content = \str_replace('<li><div>', '<li>', $content);
		$content = \str_replace('</div></li>', '</li>', $content);

		// Strip newlines
		$content = \str_replace(['\n', '\r'], '', $content);

		return $content;
	}
}
