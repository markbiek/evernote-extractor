<?php
namespace App\Exceptions;

class ResourceMismatchException extends \Exception {
	public function __construct(
		?array $a = null,
		?array $b = null,
		string $msg
	) {
		if (empty($a) && empty($b)) {
			return;
		}

		$this->message =
			"($msg) Counts between resources didn't match. " .
			count($a) .
			' vs ' .
			count($b);
	}
}
