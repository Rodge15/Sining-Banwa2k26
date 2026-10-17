document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('seating-designer');
    if (!(root instanceof HTMLElement)) {
        return;
    }

    const sectionsHost = root.querySelector('[data-seating-sections]');
    const status = root.querySelector('[data-save-status]');
    const initial = root.dataset.initial;
    if (!(sectionsHost instanceof HTMLElement) || !(status instanceof HTMLElement) || !initial) {
        return;
    }

    let sections;
    try {
        sections = JSON.parse(initial);
    } catch {
        status.textContent = 'Could not load the saved seat layout.';
        return;
    }
    if (!Array.isArray(sections)) {
        status.textContent = 'The saved seat layout has an invalid format.';
        return;
    }

    const maxColumns = 40;
    const maxRows = 30;
    const protectedStatuses = ['locked', 'reserved', 'sold'];
    let selectedSeat = null;

    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
    const isProtected = (seat) => protectedStatuses.includes(seat.status);
    const rowLetter = (rowNumber) => {
        let label = '';
        let number = rowNumber;
        while (number > 0) {
            number -= 1;
            label = String.fromCharCode(65 + (number % 26)) + label;
            number = Math.floor(number / 26);
        }
        return label || 'A';
    };
    const makeInput = (labelText, value, type = 'text') => {
        const wrapper = document.createElement('label');
        wrapper.className = 'form-label small mb-0';
        wrapper.append(document.createTextNode(labelText));
        const input = document.createElement('input');
        input.className = 'form-control form-control-sm mt-1';
        input.type = type;
        input.value = value ?? '';
        if (type === 'number') {
            input.min = '0';
            input.max = '99999999.99';
            input.step = '0.01';
        }
        wrapper.append(input);
        return { wrapper, input };
    };
    const makeButton = (label, className, onClick) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = label;
        button.addEventListener('click', onClick);
        return button;
    };
    const statusMeta = {
        available: { short: 'Open', className: 'seat-box-available', payment: 'No payment yet' },
        locked: { short: 'Hold', className: 'seat-box-locked', payment: 'Temporarily held' },
        reserved: { short: 'Due', className: 'seat-box-reserved', payment: 'Reserved, payment not verified' },
        sold: { short: 'Paid', className: 'seat-box-sold', payment: 'Payment recorded' },
        blocked: { short: 'Off', className: 'seat-box-blocked', payment: 'Not for sale' },
    };

    sections.forEach((section) => {
        section.grid_columns = clamp(Number(section.grid_columns) || 12, 1, maxColumns);
        section.grid_rows = clamp(Number(section.grid_rows) || 8, 1, maxRows);
        section.rows.forEach((row) => {
            row.seats.forEach((seat) => {
                seat.column = clamp(Number(seat.column || seat.x) || 1, 1, maxColumns);
                seat.row = clamp(Number(seat.row || seat.y) || 1, 1, maxRows);
                section.grid_columns = Math.max(section.grid_columns, seat.column);
                section.grid_rows = Math.max(section.grid_rows, seat.row);
            });
        });
    });

    function allSeats(section) {
        return section.rows.flatMap((row) => row.seats);
    }

    function uniqueLabel(section, preferred) {
        const used = new Set(sections.flatMap((item) => allSeats(item).map((seat) => seat.label.toLowerCase())));
        if (!used.has(preferred.toLowerCase())) {
            return preferred.slice(0, 40);
        }
        let serial = 2;
        while (used.has(`${preferred}-${serial}`.toLowerCase())) {
            serial += 1;
        }
        return `${preferred}-${serial}`.slice(0, 40);
    }

    function uniqueRowLabel(section, preferred) {
        const used = new Set(section.rows.map((row) => row.label.toLowerCase()));
        if (!used.has(preferred.toLowerCase())) {
            return preferred.slice(0, 40);
        }
        let serial = 2;
        let candidate = `${preferred} ${serial}`;
        while (used.has(candidate.toLowerCase())) {
            serial += 1;
            candidate = `${preferred} ${serial}`;
        }
        return candidate.slice(0, 40);
    }

    function suggestedSeatLabel(rowLabel, column) {
        return /^[A-Z]{1,3}$/.test(rowLabel) ? `${rowLabel}${column}` : `${rowLabel}-${column}`;
    }

    function ensureRow(section, displayRow) {
        const existing = section.rows.find((row) => row.displayRow === displayRow || row.seats.some((seat) => seat.row === displayRow));
        if (existing) {
            existing.displayRow = displayRow;
            return existing;
        }
        const created = {
            id: null,
            label: uniqueRowLabel(section, rowLetter(displayRow)),
            displayRow,
            seats: [],
        };
        section.rows.push(created);
        return created;
    }

    function seatAt(section, column, row) {
        return allSeats(section).find((seat) => seat.column === column && seat.row === row) ?? null;
    }

    function makeSection() {
        return {
            id: null,
            name: `Section ${sections.length + 1}`,
            default_price: '0.00',
            grid_columns: 12,
            grid_rows: 8,
            rows: [],
        };
    }

    function resizeGrid(section, columns, rows) {
        const outside = allSeats(section).filter((seat) => seat.column > columns || seat.row > rows);
        if (outside.length > 0) {
            status.textContent = 'Clear the boxes outside the new size first. Locked, reserved, or sold seats cannot be removed.';
            return false;
        }
        section.grid_columns = columns;
        section.grid_rows = rows;
        status.textContent = '';
        return true;
    }

    function placeSeat(section, column, row) {
        const rowRecord = ensureRow(section, row);
        const seat = {
            id: null,
            label: uniqueLabel(section, suggestedSeatLabel(rowRecord.label, column)),
            x: column,
            y: row,
            column,
            row,
            price_override: null,
            status: 'available',
        };
        rowRecord.seats.push(seat);
        return seat;
    }

    function removeSeat(section, seat) {
        if (isProtected(seat)) {
            status.textContent = 'Locked, reserved, or sold seats cannot be removed.';
            return;
        }
        section.rows.forEach((row) => {
            const index = row.seats.indexOf(seat);
            if (index >= 0) {
                row.seats.splice(index, 1);
            }
        });
        section.rows = section.rows.filter((row) => row.seats.length > 0);
        if (selectedSeat?.seat === seat) {
            selectedSeat = null;
        }
    }

    function updateSeatDetails() {
        const detail = root.querySelector('[data-seat-details]');
        if (!(detail instanceof HTMLElement)) {
            return;
        }
        detail.replaceChildren();
        const seat = selectedSeat?.seat;
        if (!seat) {
            const hint = document.createElement('p');
            hint.className = 'text-muted small mb-0';
            hint.textContent = 'Click an empty box to place a seat, or click a seat to edit its label, price, or availability.';
            detail.append(hint);
            return;
        }

        const protectedSeat = isProtected(seat);
        const meta = statusMeta[seat.status] ?? statusMeta.blocked;
        const summary = document.createElement('p');
        summary.className = 'small mb-2';
        summary.textContent = `Payment color: ${meta.payment}.`;
        detail.append(summary);

        const label = makeInput('Seat label', seat.label);
        label.input.maxLength = 40;
        label.input.disabled = protectedSeat;
        label.input.addEventListener('input', () => {
            seat.label = label.input.value;
            const selectedLabel = root.querySelector('.seat-box-selected .seat-box-label');
            if (selectedLabel) {
                selectedLabel.textContent = seat.label;
            }
            const selectedButton = root.querySelector('.seat-box-selected');
            const meta = statusMeta[seat.status] ?? statusMeta.blocked;
            selectedButton?.setAttribute('aria-label', `${seat.label}, ${meta.payment}. Click to edit this seat.`);
        });
        detail.append(label.wrapper);

        const price = makeInput('Seat price override (leave blank to use section price)', seat.price_override ?? '', 'number');
        price.input.placeholder = 'Use section price';
        price.input.disabled = protectedSeat;
        price.input.addEventListener('input', () => {
            seat.price_override = price.input.value === '' ? null : price.input.value;
        });
        detail.append(price.wrapper);

        const statusLabel = document.createElement('label');
        statusLabel.className = 'form-label small mb-0';
        statusLabel.append(document.createTextNode('Availability'));
        const statusSelect = document.createElement('select');
        statusSelect.className = 'form-select form-select-sm mt-1';
        statusSelect.disabled = protectedSeat;
        [['available', 'Available'], ['blocked', 'Blocked']].forEach(([value, text]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = text;
            statusSelect.append(option);
        });
        if (protectedSeat) {
            const option = document.createElement('option');
            option.value = seat.status;
            option.textContent = meta.payment;
            statusSelect.append(option);
        }
        statusSelect.value = protectedSeat ? seat.status : (seat.status === 'blocked' ? 'blocked' : 'available');
        statusSelect.addEventListener('change', () => {
            seat.status = statusSelect.value;
            render({ preserveDetails: true });
        });
        statusLabel.append(statusSelect);
        detail.append(statusLabel);

        if (!protectedSeat) {
            detail.append(makeButton('Clear this box', 'btn btn-outline-danger btn-sm', () => {
                const section = sections[selectedSeat.sectionIndex];
                removeSeat(section, seat);
                render();
            }));
        } else {
            const note = document.createElement('p');
            note.className = 'text-muted small mb-0';
            note.textContent = 'This seat is locked, reserved, or sold, so its box cannot be cleared or repriced.';
            detail.append(note);
        }
    }

    function render({ preserveDetails = false } = {}) {
        const activeElement = document.activeElement;
        const focusKey = activeElement instanceof HTMLElement ? activeElement.dataset.focusKey ?? '' : '';
        sectionsHost.replaceChildren();
        sections.forEach((section, sectionIndex) => {
            const card = document.createElement('section');
            card.className = 'panel mb-4';
            const fields = document.createElement('div');
            fields.className = 'row g-3 mb-3';

            const sectionName = makeInput('Section name', section.name);
            sectionName.input.maxLength = 100;
            sectionName.input.dataset.focusKey = `section-name-${sectionIndex}`;
            sectionName.input.addEventListener('input', () => { section.name = sectionName.input.value; });
            const nameColumn = document.createElement('div');
            nameColumn.className = 'col-md-6';
            nameColumn.append(sectionName.wrapper);

            const sectionPrice = makeInput('Default price (PHP)', section.default_price, 'number');
            sectionPrice.input.dataset.focusKey = `section-price-${sectionIndex}`;
            sectionPrice.input.addEventListener('input', () => { section.default_price = sectionPrice.input.value; });
            const priceColumn = document.createElement('div');
            priceColumn.className = 'col-md-6';
            priceColumn.append(sectionPrice.wrapper);

            const columnCount = makeInput('Columns', String(section.grid_columns), 'number');
            columnCount.input.min = '1';
            columnCount.input.max = String(maxColumns);
            columnCount.input.step = '1';
            columnCount.input.dataset.focusKey = `grid-columns-${sectionIndex}`;
            columnCount.input.addEventListener('change', () => {
                const next = clamp(Number(columnCount.input.value) || section.grid_columns, 1, maxColumns);
                if (!resizeGrid(section, next, section.grid_rows)) {
                    columnCount.input.value = String(section.grid_columns);
                    return;
                }
                render();
            });
            const columnWrap = document.createElement('div');
            columnWrap.className = 'col-6 col-md-3';
            columnWrap.append(columnCount.wrapper);

            const rowCount = makeInput('Rows', String(section.grid_rows), 'number');
            rowCount.input.min = '1';
            rowCount.input.max = String(maxRows);
            rowCount.input.step = '1';
            rowCount.input.dataset.focusKey = `grid-rows-${sectionIndex}`;
            rowCount.input.addEventListener('change', () => {
                const next = clamp(Number(rowCount.input.value) || section.grid_rows, 1, maxRows);
                if (!resizeGrid(section, section.grid_columns, next)) {
                    rowCount.input.value = String(section.grid_rows);
                    return;
                }
                render();
            });
            const rowWrap = document.createElement('div');
            rowWrap.className = 'col-6 col-md-3';
            rowWrap.append(rowCount.wrapper);
            fields.append(nameColumn, priceColumn, columnWrap, rowWrap);

            const actions = document.createElement('div');
            actions.className = 'd-flex flex-wrap gap-2 mb-3';
            actions.append(
                makeButton('Fill empty boxes', 'btn btn-outline-primary btn-sm', () => {
                    for (let row = 1; row <= section.grid_rows; row += 1) {
                        for (let column = 1; column <= section.grid_columns; column += 1) {
                            if (!seatAt(section, column, row)) {
                                placeSeat(section, column, row);
                            }
                        }
                    }
                    render();
                }),
                makeButton('Remove section', 'btn btn-outline-danger btn-sm', () => {
                    if (allSeats(section).some(isProtected)) {
                        status.textContent = 'A section with locked, reserved, or sold seats cannot be removed.';
                        return;
                    }
                    sections.splice(sectionIndex, 1);
                    selectedSeat = null;
                    render();
                }),
            );

            const board = document.createElement('div');
            board.className = 'seat-box-board';
            const stage = document.createElement('div');
            stage.className = 'seat-stage-label';
            stage.textContent = 'Stage';
            const grid = document.createElement('div');
            grid.className = 'seat-box-grid';
            grid.setAttribute('role', 'group');
            grid.setAttribute('aria-label', `Seat boxes for ${section.name}`);

            for (let row = 1; row <= section.grid_rows; row += 1) {
                const rowRecord = section.rows.find((item) => item.seats.some((seat) => seat.row === row));
                const line = document.createElement('div');
                line.className = 'seat-box-row';
                line.style.setProperty('--seat-columns', String(section.grid_columns));
                const rowLabel = document.createElement('input');
                rowLabel.className = 'form-control form-control-sm seat-row-label-input';
                rowLabel.maxLength = 40;
                rowLabel.value = rowRecord?.label || rowLetter(row);
                rowLabel.setAttribute('aria-label', `Label for row ${row}`);
                rowLabel.disabled = rowRecord ? rowRecord.seats.some(isProtected) : false;
                rowLabel.addEventListener('change', () => {
                    const target = ensureRow(section, row);
                    if (target.seats.some(isProtected)) {
                        status.textContent = 'A row containing locked, reserved, or sold seats cannot be renamed.';
                        rowLabel.value = target.label;
                        return;
                    }
                    const nextLabel = rowLabel.value.trim();
                    if (nextLabel === '') {
                        rowLabel.value = target.label;
                        return;
                    }
                    target.label = uniqueRowLabel(
                        { rows: section.rows.filter((item) => item !== target) },
                        nextLabel,
                    );
                    target.seats.forEach((seat) => {
                        if (!seat.id && /^[A-Z]{1,3}\d+$/.test(seat.label)) {
                            seat.label = uniqueLabel(section, suggestedSeatLabel(target.label, seat.column));
                        }
                    });
                    render();
                });
                line.append(rowLabel);

                for (let column = 1; column <= section.grid_columns; column += 1) {
                    const seat = seatAt(section, column, row);
                    const button = document.createElement('button');
                    button.type = 'button';
                    if (!seat) {
                        button.className = 'seat-box seat-box-empty';
                        button.setAttribute('aria-label', `Empty box, row ${row}, column ${column}. Click to place a seat.`);
                        button.addEventListener('click', () => {
                            selectedSeat = { seat: placeSeat(section, column, row), sectionIndex };
                            status.textContent = '';
                            render();
                        });
                    } else {
                        const meta = statusMeta[seat.status] ?? statusMeta.blocked;
                        const selected = selectedSeat?.seat === seat;
                        button.className = ['seat-box', meta.className, selected ? 'seat-box-selected' : ''].filter(Boolean).join(' ');
                        button.setAttribute('aria-pressed', String(selected));
                        button.setAttribute('aria-label', `${seat.label}, ${meta.payment}. Click to edit this seat.`);
                        const label = document.createElement('span');
                        label.className = 'seat-box-label';
                        label.textContent = seat.label;
                        const payment = document.createElement('span');
                        payment.className = 'seat-box-meta';
                        payment.textContent = meta.short;
                        button.append(label, payment);
                        button.addEventListener('click', () => {
                            selectedSeat = { seat, sectionIndex };
                            status.textContent = '';
                            render();
                        });
                    }
                    line.append(button);
                }
                grid.append(line);
            }
            board.append(stage, grid);
            card.append(fields, actions, board);
            sectionsHost.append(card);
        });

        const editor = document.createElement('section');
        editor.className = 'panel';
        const heading = document.createElement('h2');
        heading.className = 'h5';
        heading.textContent = 'Selected box';
        const details = document.createElement('div');
        details.className = 'seat-details-grid';
        details.dataset.seatDetails = '';
        editor.append(heading, details);
        sectionsHost.append(editor);
        updateSeatDetails();
        if (!preserveDetails && focusKey !== '') {
            const nextFocus = sectionsHost.querySelector(`[data-focus-key="${focusKey}"]`);
            if (nextFocus instanceof HTMLElement) {
                nextFocus.focus();
            }
        }
    }

    function sectionsForSave() {
        return sections.map((section) => ({
            id: section.id,
            name: section.name,
            default_price: section.default_price,
            grid_columns: section.grid_columns,
            grid_rows: section.grid_rows,
            rows: section.rows
                .map((row) => ({
                    id: row.id,
                    label: row.label,
                    seats: row.seats.map((seat) => ({
                        id: seat.id,
                        label: seat.label.trim(),
                        x: isProtected(seat) ? seat.x : seat.column,
                        y: isProtected(seat) ? seat.y : seat.row,
                        price_override: seat.price_override,
                        status: seat.status,
                    })),
                }))
                .filter((row) => row.seats.length > 0),
        }));
    }

    root.querySelector('[data-add-section]')?.addEventListener('click', () => {
        sections.push(makeSection());
        render();
    });
    root.querySelector('[data-save-layout]')?.addEventListener('click', async () => {
        status.textContent = 'Saving layout...';
        try {
            const response = await fetch(root.dataset.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': root.dataset.csrf,
                },
                body: JSON.stringify({
                    event_id: Number(new URLSearchParams(window.location.search).get('event_id')),
                    sections: sectionsForSave(),
                }),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Could not save the seat layout.');
            }
            status.textContent = result.message;
            window.setTimeout(() => window.location.reload(), 400);
        } catch (error) {
            status.textContent = error instanceof Error ? error.message : 'Could not save the seat layout.';
        }
    });

    render();
});