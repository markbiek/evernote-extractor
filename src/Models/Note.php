<?php
namespace App\Models;

use Symfony\Component\DomCrawler\Crawler;

use App\Exceptions\{
	ResourceMismatchException,
	ResourceTypeNotFoundException,
	DirectoryNotFoundException,
	DirectoryNotWritableException,
};

class Note {
	protected \DOMElement $el;
	protected ?int $idx;
	protected string $dir;

	public string $title;
	public string $content;
	public array $resources = [];
	public array $resourceTypes = [];

	public function __construct(\DOMElement $el, $idx = null) {
		$this->el = $el;
		$this->idx = $idx;

		$this->initTitle();
		$this->initContent();
		$this->initResources();

		if (count($this->resources) != count($this->resourceTypes)) {
			throw new ResourceMismatchException();
		}
	}

	public function dump(string $dir): bool {
		$dir = $this->makeOutputDir($dir);

		return $this->dumpContent($dir) && $this->dumpMedia($dir);
	}

	protected function makeOutputDir(string $baseDir): string {
		$noteName =
			'note-' .
			str_pad($this->idx, 3, '0', STR_PAD_LEFT) .
			'-' .
			$this->title;
		$dir = "{$baseDir}/{$noteName}";
		if (mkdir($dir) === false) {
			throw new DirectoryNotWritableException($dir);
		}

		return $dir;
	}

	protected function dumpContent(string $dir): bool {
		if (empty($this->content)) {
			return true;
		}

		return file_put_contents("$dir/note.html", $this->content) !== false;
	}

	protected function dumpMedia(string $dir): bool {
		$types = [
			'image/jpeg' => 'jpg',
			'image/jpg' => 'jpg',
			'image/png' => 'png',
			'application/pdf' => 'pdf',
		];
		$ret = true;

		foreach ($this->resources as $idx => $resourceData) {
			$type = $types[$this->resourceTypes[$idx]];
			if (empty($type)) {
				throw new ResourceTypeNotFoundException();
			}

			$filename = "resource-{$idx}.{$type}";
			if (
				\file_put_contents("{$dir}/{$filename}", $resourceData) ===
				false
			) {
				$ret = false;
			}
		}

		return $ret;
	}

	protected function initTitle(): void {
		$titleNodes = $this->el->getElementsByTagName('title');
		$this->title = $titleNodes->item(0)->nodeValue;

		$this->title = preg_replace('/[^ aA-zZ0-9]/', '-', $this->title);
	}

	protected function initResources(): void {
		$resourceNodes = $this->el->getElementsByTagName('resource');

		foreach ($resourceNodes as $resource) {
			$dataNode = $resource->getElementsByTagName('data')->item(0);
			$encoding = $dataNode->attributes->getNamedItem('encoding')->value;

			// Currently, we only handle base64-encoded file data
			if ($encoding != 'base64') {
				throw new \Exception("Invalid encoding $encoding");
			}

			$this->resources[] = base64_decode($dataNode->textContent);
		}
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

			$mediaNodes = $noteElement->getElementsByTagName('en-media');
			foreach ($mediaNodes as $mediaNode) {
				$type = $mediaNode->attributes->getNamedItem('type')->value;
				if (!empty($type)) {
					$this->resourceTypes[] = $type;
				}
			}
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
