<?php

declare(strict_types=1);

namespace Celema\Boiler;

use Celema\Boiler\Exception\RuntimeException;
use Override;

/** @api */
final class Environment implements Contract\Environment
{
	private ?Contract\Wrapper $wrapper = null;
	private ?Contract\Filters $filters = null;
	private ?Contract\Escapers $escapers = null;
	private bool $sealed = false;

	public function setWrapper(Contract\Wrapper $wrapper): void
	{
		$this->assertNotSealed();

		if ($this->filters !== null || $this->escapers !== null) {
			throw new RuntimeException('Cannot set wrapper after filters or escapers are configured');
		}

		if ($this->wrapper !== null) {
			throw new RuntimeException('Wrapper is already configured');
		}

		$this->wrapper = $wrapper;
	}

	public function setFilters(Contract\Filters $filters): void
	{
		$this->assertNotSealed();

		if ($this->wrapper !== null) {
			throw new RuntimeException('Cannot set filters after wrapper is configured');
		}

		if ($this->filters !== null) {
			throw new RuntimeException('Filters are already configured');
		}

		$this->filters = $filters;
	}

	public function setEscapers(Contract\Escapers $escapers): void
	{
		$this->assertNotSealed();

		if ($this->wrapper !== null) {
			throw new RuntimeException('Cannot set escapers after wrapper is configured');
		}

		if ($this->escapers !== null) {
			throw new RuntimeException('Escapers are already configured');
		}

		$this->escapers = $escapers;
	}

	#[Override]
	public function wrapper(): Contract\Wrapper
	{
		$this->sealed = true;

		return $this->wrapper ??= new Wrapper($this->escapers, $this->filters);
	}

	#[Override]
	public function registerFilter(string $name, Contract\Filter $filter): void
	{
		$this->assertNotSealed();

		if ($this->wrapper !== null) {
			throw new RuntimeException('Cannot register filter after wrapper is configured');
		}

		$filters = $this->filters();

		if (!$filters instanceof Contract\RegistersFilters) {
			throw new RuntimeException('Configured filters registry does not support filter registration');
		}

		$filters->register($name, $filter);
	}

	#[Override]
	public function registerEscaper(string $name, Contract\Escaper $escaper): void
	{
		$this->assertNotSealed();

		if ($this->wrapper !== null) {
			throw new RuntimeException('Cannot register escaper after wrapper is configured');
		}

		$escapers = $this->escapers();

		if (!$escapers instanceof Contract\RegistersEscapers) {
			throw new RuntimeException('Configured escapers registry does not support escaper registration');
		}

		$escapers->register($name, $escaper);
	}

	private function filters(): Contract\Filters
	{
		return $this->filters ??= new Filters();
	}

	private function escapers(): Contract\Escapers
	{
		return $this->escapers ??= new Escapers();
	}

	private function assertNotSealed(): void
	{
		if ($this->sealed) {
			throw new RuntimeException('Engine configuration is sealed');
		}
	}
}
