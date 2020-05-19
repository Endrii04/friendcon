<?php
namespace dao;

use util\Param as Param;
use util\Sql as Sql;

class Challenges {

	public static function add($name, $startTime = null, $endTime = null) {
		$query = "INSERT INTO challenges (name, startTime, endTime) VALUES (?, ?, ?)";
		$affectedRows = Sql::executeSqlForAffectedRows($query, 'sss', $name, $startTime, $endTime);
		return $affectedRows === 1;
	}

	public static function delete($challengeIndex) {
		$query = "DELETE FROM challenges WHERE challengeIndex = ?";
		$affectedRows = Sql::executeSqlForAffectedRows($query, 'i', $challengeIndex);
		return $affectedRows === 1;
	}

	public static function exists($challengeIndex) {
		$result = Sql::executeSqlForResult("SELECT * FROM challenges WHERE challengeIndex = ?", 's', $challengeIndex);
		return $result->num_rows > 0;
	}

	public static function existsWithName($name) {
		$result = Sql::executeSqlForResult("SELECT * FROM challenges WHERE name = ?", 's', $name);
		return $result->num_rows > 0;
	}

	public static function get($challengeIndex) {
		$query = "SELECT * FROM challenges WHERE challengeIndex = ?";
		$result = Sql::executeSqlForResult($query, 's', $challengeIndex);
		if (!Sql::hasRows($result, 1)) return null;
		$row = Sql::getNextRow($result);
		return [
				'challengeIndex' => Param::asInteger($row['challengeIndex']),
				'name'           => Param::asString($row['name']),
				'startTime'      => Param::asTimestamp($row['startTime']),
				'endTime'        => Param::asTimestamp($row['endTime']),
				'published'      => Param::asBoolean($row['published'])
		];
	}

	public static function getAll($currentOnly = true) {
		$isWithinTimeConstraints = "(startTime <= NOW() OR startTime IS NULL) AND (endTime >= NOW() OR endTime IS NULL)";
		$query = "SELECT * FROM challenges" . ($currentOnly ? " WHERE published = 1 OR ($isWithinTimeConstraints)" : "");
		$result = Sql::executeSqlForResult($query);

		// Build the data array
		$challenges = [];
		while ($row = Sql::getNextRow($result)) {
			// Build and append the entry
			$challenges[] = [
					'challengeIndex' => Param::asInteger($row['challengeIndex']),
					'startTime'      => Param::asTimestamp($row['startTime']),
					'endTime'        => Param::asTimestamp($row['endTime']),
					'name'           => Param::asString($row['name']),
					'published'      => Param::asBoolean($row['published'])
			];
		}
		return $challenges;
	}

	public static function getByName($name) {
		$query = "SELECT * FROM challenges WHERE name = ?";
		$result = Sql::executeSqlForResult($query, 's', $name);
		if (!Sql::hasRows($result, 1)) return null;
		$row = Sql::getNextRow($result);
		return [
				'challengeIndex' => Param::asInteger($row['challengeIndex']),
				'name'           => Param::asString($row['name']),
				'startTime'      => Param::asTimestamp($row['startTime']),
				'endTime'        => Param::asTimestamp($row['endTime']),
				'published'      => Param::asBoolean($row['published'])
		];
	}

	public static function hasApprovedUploads($challengeIndex) {
		$query = "SELECT * FROM uploads WHERE challengeIndex = ? AND state > 0";
		$result = Sql::executeSqlForResult($query, 'i', $challengeIndex);
		return $result->num_rows > 0;
	}

	public static function isValidChallengeIndex($challengeIndex) {
		return Param::isInteger($challengeIndex) && $challengeIndex >= 1;
	}

	public static function publish($challengeIndex, $isPublished) {
		return Challenges::update($challengeIndex, null, 'IGNORE', 'IGNORE', $isPublished);
	}

	public static function update($challengeIndex, $name = null, $startTime = 'IGNORE', $endTime = 'IGNORE', $isPublished = null) {
		// Build the SQL pieces
		$changes = [];
		$types = '';
		$params = [];
		if (!is_null($name)) {
			$changes[] = "name = ?";
			$types .= 's';
			$params[] = $name;
		}
		if ($startTime !== 'IGNORE') {
			$changes[] = "startTime = ?";
			$types .= 's';
			$params[] = $startTime;
		}
		if ($endTime !== 'IGNORE') {
			$changes[] = "endTime = ?";
			$types .= 's';
			$params[] = $endTime;
		}
		if (!is_null($isPublished)) {
			$changes[] = "published = ?";
			$types .= 'i';
			$params[] = $isPublished;
		}
		$changesStr = join(", ", $changes);
		$types .= 'i';
		$params[] = $challengeIndex;

		// Make the changes
		$query = "UPDATE challenges SET $changesStr WHERE challengeIndex = ?";
		$affectedRows = Sql::executeSqlForAffectedRows($query, $types, ...$params);
		return $affectedRows === 1;
	}
}