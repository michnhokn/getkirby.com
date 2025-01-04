<?php

use Kirby\Cms\License;
use Kirby\Content\Field;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\Str;

class SecurityPage extends DefaultPage
{
	public function incidents(): array
	{
		return array_reverse(parent::incidents()->yaml());
	}

	public function incidentsTable()
	{
		return snippet('templates/security/incidents', [
			'incidents' => $this->incidents()
		], true);
	}

	public function messages(): array
	{
		return array_reverse(parent::messages()->yaml());
	}

	public function messagesTable()
	{
		return snippet('templates/security/messages', [
			'messages' => $this->messages()
		], true);
	}

	public function php(): array
	{
		return parent::php()->yaml();
	}

	protected function replace(Field $field, array $data = []): Field
	{
		$latest = $this->kirby()->version();

		// extract the part before the first dot
		preg_match('/^(\w+)\./', $latest, $matches);

		$versions = page('releases')->children()->pluck('version');
		$releasePages = implode(' || ', array_map(fn ($version) => $version . '.0', $versions));

		$data = [
			'latest'            => $latest,
			'latestMajor'       => $matches[1],
			'noVulnerabilities' => $this->versionNoVulnerabilities(),
			'releasePages'      => $releasePages,
			...$data
		];

		$field->value = Str::template($field->value, $data);

		return $field;
	}

	public function urls(): array
	{
		return $this->replace(parent::urls())->yaml();
	}

	protected function versionNoVulnerabilities(): string
	{
		$noVulnerabilities = null;

		// latest `fixed` version in the incident list =
		// no newer known vulnerabilities
		foreach ($this->incidents() as $incident) {
			foreach (Str::split($incident['fixed']) as $fixed) {
				if (
					$noVulnerabilities === null ||
					version_compare($fixed, $noVulnerabilities, '>')
				) {
					$noVulnerabilities = $fixed;
				}
			}
		}

		return $noVulnerabilities;
	}

	public function versions(): array
	{
		$versions = parent::versions()->yaml();

		// dynamically add v4+
		foreach (License::HISTORY as $major => $initialRelease) {
			// v3 and older are configured manually in `security.txt`
			if ((int)$major < 4) {
				continue;
			}

			$nextRelease = License::HISTORY[(string)($major + 1)] ?? null;

			$versions[$major . '.*'] = [
				'shortName'          => 'v' . $major,
				'initialRelease'     => $initialRelease,
				'endOfActiveSupport' => $nextRelease,
				'endOfLife'          => date('Y-m-d', strtotime($initialRelease . ' + 3 years')),
				'latest'             => page('releases/' . $major)->latestRelease()
			];
		}

		// determine current status for each version
		foreach ($versions as $constraint => $version) {
			$versions[$constraint]['status'] = match (true) {
				time() > strtotime($version['endOfLife']) => 'end-of-life',
				$version['endOfActiveSupport'] !== null   => 'security-support',
				default                                   => 'active-support'
			};
		}

		return array_reverse($versions, true);
	}

	public function versionsForUpdateCheck(): array
	{
		$versions = [
			$this->kirby()->version() => [
				'status'      => 'latest',
				'description' => 'Latest Kirby release',
			],
			'>=' . $this->versionNoVulnerabilities() => [
				'status'      => 'no-vulnerabilities',
				'description' => 'No known vulnerabilities',
			]
		];

		foreach ($this->versions() as $constraint => $version) {
			$description = match ($version['status']) {
				'end-of-life'      => 'Not supported (end of life) since ' . date('F j, Y', strtotime($version['endOfLife'])),
				'security-support' => 'Security support until ' . date('F j, Y', strtotime($version['endOfLife'])),
				'active-support'   => 'Actively supported'
			};

			$versions[$constraint] = [
				'status'         => $version['status'],
				'description'    => $description,
				'latest'         => $version['latest'],
				'initialRelease' => $version['initialRelease']
			];
		}

		return $versions;
	}

	public function versionsTable()
	{
		return snippet('templates/security/versions', [
			'versions' => new Collection($this->versions())
		], true);
	}

	public function text(): Field
	{
		return $this->replace(parent::text(), [
			'incidents' => $this->incidentsTable(),
			'messages'  => $this->messagesTable(),
			'versions'  => $this->versionsTable()
		]);
	}
}
