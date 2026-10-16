<?php
declare(strict_types=1);

const SEATING_MAX_SECTIONS = 20;
const SEATING_MAX_ROWS = 100;
const SEATING_MAX_SEATS = 1000;

function load_event_seating(int $eventId): array
{
    $sectionStatement = db()->prepare(
        'SELECT id, name, default_price, sort_order FROM event_sections WHERE event_id = :event_id '
        . 'ORDER BY sort_order, id'
    );
    $sectionStatement->execute(['event_id' => $eventId]);
    $sections = [];
    foreach ($sectionStatement->fetchAll() as $section) {
        $sectionId = (int) $section['id'];
        $sections[$sectionId] = [
            'id' => $sectionId,
            'name' => (string) $section['name'],
            'default_price' => (string) $section['default_price'],
            'rows' => [],
        ];
    }

    if ($sections === []) {
        return [];
    }

    $sectionIds = array_keys($sections);
    $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
    $rowStatement = db()->prepare(
        'SELECT id, event_id, section_id, label FROM event_rows WHERE section_id IN (' . $placeholders . ') '
        . 'ORDER BY sort_order, id'
    );
    $rowStatement->execute($sectionIds);
    $rowMap = [];
    foreach ($rowStatement->fetchAll() as $row) {
        $rowId = (int) $row['id'];
        $sectionId = (int) $row['section_id'];
        $sections[$sectionId]['rows'][] = [
            'id' => $rowId,
            'label' => (string) $row['label'],
            'seats' => [],
        ];
        $rowMap[$rowId] = [
            'section_id' => $sectionId,
            'row_index' => count($sections[$sectionId]['rows']) - 1,
        ];
    }

    if ($rowMap === []) {
        return array_values($sections);
    }

    $rowIds = array_keys($rowMap);
    $rowPlaceholders = implode(',', array_fill(0, count($rowIds), '?'));
    $seatStatement = db()->prepare(
        'SELECT s.id, s.event_id, s.row_id, s.label, s.x_position, s.y_position, s.price_override, s.status '
        . 'FROM event_seats AS s WHERE s.row_id IN (' . $rowPlaceholders . ') ORDER BY s.sort_order, s.id'
    );
    $seatStatement->execute($rowIds);
    foreach ($seatStatement->fetchAll() as $seat) {
        $rowId = (int) $seat['row_id'];
        $rowLocation = $rowMap[$rowId];
        $sections[$rowLocation['section_id']]['rows'][$rowLocation['row_index']]['seats'][] = [
            'id' => (int) $seat['id'],
            'label' => (string) $seat['label'],
            'x' => (int) $seat['x_position'],
            'y' => (int) $seat['y_position'],
            'price_override' => $seat['price_override'] === null ? null : (string) $seat['price_override'],
            'status' => (string) $seat['status'],
        ];
    }

    return array_values($sections);
}

function validate_seating_payload(array $sections): array
{
    if (count($sections) > SEATING_MAX_SECTIONS) {
        throw new InvalidArgumentException('An event can have up to ' . SEATING_MAX_SECTIONS . ' sections.');
    }

    $normalized = [];
    $totalRows = 0;
    $totalSeats = 0;
    $sectionNames = [];
    $allSeatNames = [];
    foreach ($sections as $section) {
        if (!is_array($section)) {
            throw new InvalidArgumentException('The seating layout contains invalid section data.');
        }
        $name = trim((string) ($section['name'] ?? ''));
        $defaultPrice = validate_seating_price($section['default_price'] ?? null, false);
        if ($name === '' || mb_strlen($name) > 100) {
            throw new InvalidArgumentException('Section names are required and must be 100 characters or fewer.');
        }
        $sectionKey = mb_strtolower($name);
        if (isset($sectionNames[$sectionKey])) {
            throw new InvalidArgumentException('Section names must be unique within the event.');
        }
        $sectionNames[$sectionKey] = true;
        $rows = $section['rows'] ?? [];
        if (!is_array($rows)) {
            throw new InvalidArgumentException('Each section must contain a list of rows.');
        }
        $totalRows += count($rows);
        $rowNames = [];
        $normalizedRows = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('The seating layout contains invalid row data.');
            }
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '' || mb_strlen($label) > 40) {
                throw new InvalidArgumentException('Row labels are required and must be 40 characters or fewer.');
            }
            $rowKey = mb_strtolower($label);
            if (isset($rowNames[$rowKey])) {
                throw new InvalidArgumentException('Row labels must be unique within each section.');
            }
            $rowNames[$rowKey] = true;
            $seats = $row['seats'] ?? [];
            if (!is_array($seats)) {
                throw new InvalidArgumentException('Each row must contain a list of seats.');
            }
            $totalSeats += count($seats);
            $normalizedSeats = [];
            foreach ($seats as $seat) {
                if (!is_array($seat)) {
                    throw new InvalidArgumentException('The seating layout contains invalid seat data.');
                }
                $seatLabel = trim((string) ($seat['label'] ?? ''));
                if ($seatLabel === '' || mb_strlen($seatLabel) > 40) {
                    throw new InvalidArgumentException('Seat labels are required and must be 40 characters or fewer.');
                }
                $seatKey = mb_strtolower($seatLabel);
                if (isset($allSeatNames[$seatKey])) {
                    throw new InvalidArgumentException('Seat labels must be unique within the event.');
                }
                $allSeatNames[$seatKey] = true;
                $x = filter_var($seat['x'] ?? null, FILTER_VALIDATE_INT);
                $y = filter_var($seat['y'] ?? null, FILTER_VALIDATE_INT);
                if ($x === false || $y === false || $x < 20 || $x > 980 || $y < 40 || $y > 560) {
                    throw new InvalidArgumentException('Seat positions are outside the seating map.');
                }
                $status = (string) ($seat['status'] ?? 'available');
                if (!in_array($status, ['available', 'blocked', 'locked', 'reserved', 'sold'], true)) {
                    throw new InvalidArgumentException('The layout contains an invalid seat status.');
                }
                $seatId = validate_seating_id($seat['id'] ?? null);
                if ($seatId === null && !in_array($status, ['available', 'blocked'], true)) {
                    throw new InvalidArgumentException('New seats can only be available or blocked.');
                }
                $normalizedSeats[] = [
                    'id' => $seatId,
                    'label' => $seatLabel,
                    'x' => $x,
                    'y' => $y,
                    'price_override' => validate_seating_price($seat['price_override'] ?? null, true),
                    'status' => $status,
                ];
            }
            $normalizedRows[] = [
                'id' => validate_seating_id($row['id'] ?? null),
                'label' => $label,
                'seats' => $normalizedSeats,
            ];
        }
        $normalized[] = [
            'id' => validate_seating_id($section['id'] ?? null),
            'name' => $name,
            'default_price' => $defaultPrice,
            'rows' => $normalizedRows,
        ];
    }

    if ($totalRows > SEATING_MAX_ROWS || $totalSeats > SEATING_MAX_SEATS) {
        throw new InvalidArgumentException('An event can have up to 100 rows and 1,000 seats.');
    }

    return $normalized;
}

function validate_seating_id(mixed $value): ?int
{
    if ($value === null || $value === '' || $value === 0 || $value === '0') {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('The seating layout contains an invalid record identifier.');
    }
    return $id;
}

function validate_seating_price(mixed $value, bool $nullable): ?string
{
    if ($nullable && ($value === null || $value === '')) {
        return null;
    }
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        throw new InvalidArgumentException('Prices must be valid Philippine peso amounts.');
    }
    $price = trim((string) $value);
    if (preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?$/D', $price) !== 1) {
        throw new InvalidArgumentException('Prices must be between ₱0.00 and ₱99,999,999.99 with at most 2 decimal places.');
    }
    return number_format((float) $price, 2, '.', '');
}

function save_event_seating(int $eventId, array $sections): void
{
    $sections = validate_seating_payload($sections);
    $connection = db();
    $connection->beginTransaction();
    try {
        $eventLock = $connection->prepare('SELECT id FROM events WHERE id = :id LIMIT 1 FOR UPDATE');
        $eventLock->execute(['id' => $eventId]);
        if ($eventLock->fetch() === false) {
            throw new RuntimeException('Event not found.');
        }

        $sectionRead = $connection->prepare(
            'SELECT id, name, default_price FROM event_sections WHERE event_id = :event_id FOR UPDATE'
        );
        $sectionRead->execute(['event_id' => $eventId]);
        $existingSections = [];
        foreach ($sectionRead->fetchAll() as $section) {
            $existingSections[(int) $section['id']] = $section;
        }
        $rowRead = $connection->prepare(
            'SELECT r.id, r.event_id, r.section_id, r.label FROM event_rows AS r '
            . 'INNER JOIN event_sections AS s ON s.id = r.section_id WHERE s.event_id = :event_id FOR UPDATE'
        );
        $rowRead->execute(['event_id' => $eventId]);
        $existingRows = [];
        foreach ($rowRead->fetchAll() as $row) {
            $existingRows[(int) $row['id']] = $row;
        }
        $seatRead = $connection->prepare(
            'SELECT seat.id, seat.event_id, seat.row_id, seat.label, seat.x_position, seat.y_position, '
            . 'seat.price_override, seat.status FROM event_seats AS seat '
            . 'INNER JOIN event_rows AS r ON r.id = seat.row_id '
            . 'INNER JOIN event_sections AS s ON s.id = r.section_id WHERE s.event_id = :event_id FOR UPDATE'
        );
        $seatRead->execute(['event_id' => $eventId]);
        $existingSeats = [];
        foreach ($seatRead->fetchAll() as $seat) {
            $existingSeats[(int) $seat['id']] = $seat;
        }

        $usedSections = [];
        $usedRows = [];
        $usedSeats = [];
        $sectionInsert = $connection->prepare(
            'INSERT INTO event_sections (event_id, name, default_price, sort_order) VALUES (?, ?, ?, ?)'
        );
        $sectionUpdate = $connection->prepare(
            'UPDATE event_sections SET name = ?, default_price = ?, sort_order = ? WHERE id = ? AND event_id = ?'
        );
        $rowInsert = $connection->prepare(
            'INSERT INTO event_rows (event_id, section_id, label, sort_order) VALUES (?, ?, ?, ?)'
        );
        $rowUpdate = $connection->prepare('UPDATE event_rows SET section_id = ?, label = ?, sort_order = ? WHERE id = ? AND event_id = ?');
        $seatInsert = $connection->prepare(
            'INSERT INTO event_seats (event_id, row_id, label, x_position, y_position, price_override, status, sort_order) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $seatUpdate = $connection->prepare(
            'UPDATE event_seats SET row_id = ?, label = ?, x_position = ?, y_position = ?, '
            . 'price_override = ?, status = ?, sort_order = ? WHERE id = ? AND event_id = ?'
        );

        foreach ($sections as $sectionIndex => $section) {
            if ($section['id'] === null) {
                $sectionInsert->execute([$eventId, $section['name'], $section['default_price'], $sectionIndex]);
                $sectionId = (int) $connection->lastInsertId();
            } else {
                $sectionId = $section['id'];
                if (!isset($existingSections[$sectionId]) || isset($usedSections[$sectionId])) {
                    throw new InvalidArgumentException('The seating section does not belong to this event.');
                }
                $sectionUpdate->execute([
                    $section['name'],
                    $section['default_price'],
                    $sectionIndex,
                    $sectionId,
                    $eventId,
                ]);
            }
            $usedSections[$sectionId] = true;
            if (
                isset($existingSections[$sectionId])
                && number_format((float) $existingSections[$sectionId]['default_price'], 2, '.', '') !== $section['default_price']
            ) {
                foreach ($existingSeats as $existingSeat) {
                    $existingRow = $existingRows[(int) $existingSeat['row_id']] ?? null;
                    if (
                        $existingRow !== null
                        && (int) $existingRow['section_id'] === $sectionId
                        && in_array($existingSeat['status'], ['locked', 'reserved', 'sold'], true)
                        && $existingSeat['price_override'] === null
                    ) {
                        throw new InvalidArgumentException('A section default price cannot change while locked, reserved, or sold seats use it.');
                    }
                }
            }

            foreach ($section['rows'] as $rowIndex => $row) {
                if ($row['id'] === null) {
                    $rowInsert->execute([$eventId, $sectionId, $row['label'], $rowIndex]);
                    $rowId = (int) $connection->lastInsertId();
                } else {
                    $rowId = $row['id'];
                    if (
                        !isset($existingRows[$rowId])
                        || (int) $existingRows[$rowId]['section_id'] !== $sectionId
                        || isset($usedRows[$rowId])
                    ) {
                        throw new InvalidArgumentException('The seating row does not belong to its selected section.');
                    }
                    if ($existingRows[$rowId]['label'] !== $row['label']) {
                        foreach ($existingSeats as $existingSeat) {
                            if (
                                (int) $existingSeat['row_id'] === $rowId
                                && in_array($existingSeat['status'], ['locked', 'reserved', 'sold'], true)
                            ) {
                                throw new InvalidArgumentException('A row containing locked, reserved, or sold seats cannot be renamed.');
                            }
                        }
                    }
                    $rowUpdate->execute([$sectionId, $row['label'], $rowIndex, $rowId, $eventId]);
                }
                $usedRows[$rowId] = true;

                foreach ($row['seats'] as $seatIndex => $seat) {
                    if ($seat['id'] === null) {
                        $seatInsert->execute([
                            $eventId,
                            $rowId,
                            $seat['label'],
                            $seat['x'],
                            $seat['y'],
                            $seat['price_override'],
                            $seat['status'],
                            $seatIndex,
                        ]);
                        $seatId = (int) $connection->lastInsertId();
                    } else {
                        $seatId = $seat['id'];
                        if (
                            !isset($existingSeats[$seatId])
                            || (int) $existingSeats[$seatId]['event_id'] !== $eventId
                            || isset($usedSeats[$seatId])
                        ) {
                            throw new InvalidArgumentException('The seat does not belong to this event.');
                        }
                        $existing = $existingSeats[$seatId];
                        $protected = in_array($existing['status'], ['locked', 'reserved', 'sold'], true);
                        if (!$protected && !in_array($seat['status'], ['available', 'blocked'], true)) {
                            throw new InvalidArgumentException('Only the reservation workflow can lock or sell a seat.');
                        }
                        if ($protected && (
                            (int) $existing['row_id'] !== $rowId
                            || (string) $existing['label'] !== $seat['label']
                            || (int) $existing['x_position'] !== $seat['x']
                            || (int) $existing['y_position'] !== $seat['y']
                            || ($existing['price_override'] === null ? null : number_format((float) $existing['price_override'], 2, '.', ''))
                                !== $seat['price_override']
                            || (string) $existing['status'] !== $seat['status']
                        )) {
                            throw new InvalidArgumentException('Locked, reserved, or sold seats cannot be moved, repriced, renamed, or changed.');
                        }
                        if (!$protected) {
                            $seatUpdate->execute([
                                $rowId,
                                $seat['label'],
                                $seat['x'],
                                $seat['y'],
                                $seat['price_override'],
                                $seat['status'],
                                $seatIndex,
                                $seatId,
                                $eventId,
                            ]);
                        }
                    }
                    $usedSeats[$seatId] = true;
                }
            }
        }

        foreach ($existingSeats as $seatId => $seat) {
            if (!isset($usedSeats[$seatId])) {
                if (in_array($seat['status'], ['locked', 'reserved', 'sold'], true)) {
                    throw new InvalidArgumentException('Locked, reserved, or sold seats cannot be removed.');
                }
                $deleteSeat = $connection->prepare('DELETE FROM event_seats WHERE id = ?');
                $deleteSeat->execute([$seatId]);
            }
        }
        foreach ($existingRows as $rowId => $row) {
            if (!isset($usedRows[$rowId])) {
                $deleteRow = $connection->prepare('DELETE FROM event_rows WHERE id = ?');
                $deleteRow->execute([$rowId]);
            }
        }
        foreach ($existingSections as $sectionId => $section) {
            if (!isset($usedSections[$sectionId])) {
                $deleteSection = $connection->prepare('DELETE FROM event_sections WHERE id = ?');
                $deleteSection->execute([$sectionId]);
            }
        }

        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}
