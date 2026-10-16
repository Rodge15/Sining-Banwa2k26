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
    let nextSeatSerial = sections.reduce((highest, section) =>
        section.rows.reduce((rowHighest, row) =>
            row.seats.reduce((seatHighest, seat) => {
                const match = /^S([0-9]+)$/i.exec(seat.label);
                return match ? Math.max(seatHighest, Number(match[1])) : seatHighest;
            }, rowHighest),
        highest),
    0) + 1;

    let selectedSeat = null;
    const svgNamespace = 'http://www.w3.org/2000/svg';
    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
    const makeInput = (labelText, value, type = 'text', step = null) => {
        const wrapper = document.createElement('label');
        wrapper.className = 'form-label small mb-0';
        wrapper.append(document.createTextNode(labelText));
        const input = document.createElement('input');
        input.className = 'form-control form-control-sm mt-1';
        input.type = type;
        input.value = value;
        if (type === 'number') {
            input.min = '0';
            input.max = '99999999.99';
            input.step = step ?? '0.01';
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

    function makeSection() {
        return { id: null, name: `Section ${sections.length + 1}`, default_price: '0.00', rows: [] };
    }

    function makeSeat(row, rowIndex, seatIndex) {
        const x = Math.round(180 + ((seatIndex + 1) * 640) / (row.seats.length + 2));
        const y = Math.min(520, 100 + rowIndex * 75);
        return { id: null, label: `S${nextSeatSerial++}`, x, y, price_override: null, status: 'available' };
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
            hint.textContent = 'Select a seat on the map to edit its label, price override, or availability.';
            detail.append(hint);
            return;
        }

        const protectedSeat = ['locked', 'reserved', 'sold'].includes(seat.status);
        const label = makeInput('Seat label', seat.label);
        label.input.maxLength = 40;
        label.input.disabled = protectedSeat;
        label.input.addEventListener('input', () => {
            seat.label = label.input.value;
            const selectedGroup = root.querySelector('.seat-map-seat-selected');
            selectedGroup?.querySelector('text')?.replaceChildren(document.createTextNode(seat.label.slice(0, 6)));
            selectedGroup?.setAttribute('aria-label', `Seat ${seat.label}, ${seat.status}`);
        });
        detail.append(label.wrapper);

        const price = makeInput('Seat price override (leave blank to use section price)', seat.price_override ?? '', 'number');
        price.input.placeholder = 'Use section price';
        price.input.disabled = protectedSeat;
        price.input.addEventListener('input', () => { seat.price_override = price.input.value === '' ? null : price.input.value; });
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
        statusSelect.value = seat.status === 'blocked' ? 'blocked' : 'available';
        statusSelect.addEventListener('change', () => {
            seat.status = statusSelect.value;
            const selectedGroup = root.querySelector('.seat-map-seat-selected');
            selectedGroup?.classList.toggle('seat-map-seat-blocked', seat.status === 'blocked');
            selectedGroup?.setAttribute('aria-label', `Seat ${seat.label}, ${seat.status}`);
        });
        statusLabel.append(statusSelect);
        detail.append(statusLabel);
        const priceText = document.createElement('p');
        priceText.className = 'text-muted small mb-0';
        priceText.textContent = protectedSeat
            ? 'This seat is locked or reserved and cannot be changed.'
            : 'The override replaces the section default price for this seat.';
        detail.append(priceText);
        if (!protectedSeat) {
            detail.append(makeButton('Remove seat', 'btn btn-outline-danger btn-sm', () => {
                const row = sections[ selectedSeat.sectionIndex ]?.rows.find((item) => item.seats.includes(seat));
                if (!row) {
                    return;
                }
                row.seats.splice(row.seats.indexOf(seat), 1);
                selectedSeat = null;
                render();
            }));
        }
    }

    function renderSectionMap(sectionIndex) {
        const mapHost = root.querySelector(`[data-section-map="${sectionIndex}"]`);
        if (!(mapHost instanceof HTMLElement)) {
            return;
        }
        const svg = document.createElementNS(svgNamespace, 'svg');
        svg.setAttribute('viewBox', '0 0 1000 600');
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', `Draggable seat map for ${sections[sectionIndex].name}`);
        const stage = document.createElementNS(svgNamespace, 'rect');
        stage.setAttribute('x', '20');
        stage.setAttribute('y', '40');
        stage.setAttribute('width', '960');
        stage.setAttribute('height', '520');
        stage.setAttribute('rx', '18');
        stage.setAttribute('class', 'seat-map-stage');
        svg.append(stage);

        const section = sections[sectionIndex];
        section.rows.forEach((row) => {
            row.seats.forEach((seat) => {
                const group = document.createElementNS(svgNamespace, 'g');
                group.setAttribute('transform', `translate(${seat.x} ${seat.y})`);
                group.setAttribute('tabindex', '0');
                group.setAttribute('role', 'button');
                group.setAttribute('aria-label', `Seat ${seat.label}, ${seat.status}`);
                group.setAttribute('class', [
                    'seat-map-seat',
                    seat.status !== 'available' ? 'seat-map-seat-blocked' : '',
                    selectedSeat?.seat === seat ? 'seat-map-seat-selected' : '',
                ].filter(Boolean).join(' '));
                const circle = document.createElementNS(svgNamespace, 'circle');
                circle.setAttribute('r', '22');
                const text = document.createElementNS(svgNamespace, 'text');
                text.setAttribute('text-anchor', 'middle');
                text.setAttribute('dy', '5');
                text.textContent = seat.label.slice(0, 6);
                group.append(circle, text);
                const choose = () => {
                    selectedSeat = { seat, sectionIndex };
                    root.querySelectorAll('.seat-map-seat-selected').forEach((item) => {
                        item.classList.remove('seat-map-seat-selected');
                    });
                    group.classList.add('seat-map-seat-selected');
                    updateSeatDetails();
                };
                group.addEventListener('click', choose);
                group.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        choose();
                    }
                });
                group.addEventListener('pointerdown', (event) => {
                    if (event.button !== 0) {
                        return;
                    }
                    choose();
                    group.setPointerCapture(event.pointerId);
                });
                group.addEventListener('pointermove', (event) => {
                    if (!group.hasPointerCapture(event.pointerId)) {
                        return;
                    }
                    const point = svg.createSVGPoint();
                    point.x = event.clientX;
                    point.y = event.clientY;
                    const local = point.matrixTransform(svg.getScreenCTM().inverse());
                    seat.x = Math.round(clamp(local.x, 20, 980));
                    seat.y = Math.round(clamp(local.y, 40, 560));
                    group.setAttribute('transform', `translate(${seat.x} ${seat.y})`);
                });
                group.addEventListener('pointerup', (event) => {
                    if (group.hasPointerCapture(event.pointerId)) {
                        group.releasePointerCapture(event.pointerId);
                    }
                });
                svg.append(group);
            });
        });
        mapHost.replaceChildren(svg);
    }

    function render() {
        sectionsHost.replaceChildren();
        sections.forEach((section, sectionIndex) => {
            const card = document.createElement('section');
            card.className = 'panel mb-4';
            const top = document.createElement('div');
            top.className = 'd-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-end mb-3';
            const fields = document.createElement('div');
            fields.className = 'row g-3 flex-grow-1';
            const sectionName = makeInput('Section name', section.name);
            sectionName.input.maxLength = 100;
            sectionName.input.addEventListener('input', () => { section.name = sectionName.input.value; });
            const nameColumn = document.createElement('div');
            nameColumn.className = 'col-sm-6';
            nameColumn.append(sectionName.wrapper);
            const sectionPrice = makeInput('Default price (PHP)', section.default_price, 'number');
            sectionPrice.input.addEventListener('input', () => { section.default_price = sectionPrice.input.value; });
            const priceColumn = document.createElement('div');
            priceColumn.className = 'col-sm-6';
            priceColumn.append(sectionPrice.wrapper);
            fields.append(nameColumn, priceColumn);
            const actions = document.createElement('div');
            actions.className = 'd-flex gap-2';
            actions.append(
                makeButton('Add row', 'btn btn-outline-primary btn-sm', () => {
                    section.rows.push({ id: null, label: `Row ${section.rows.length + 1}`, seats: [] });
                    render();
                }),
                makeButton('Remove section', 'btn btn-outline-danger btn-sm', () => {
                    const hasProtectedSeats = section.rows.some((row) =>
                        row.seats.some((seat) => ['locked', 'reserved', 'sold'].includes(seat.status)),
                    );
                    if (hasProtectedSeats) {
                        status.textContent = 'A section with locked, reserved, or sold seats cannot be removed.';
                        return;
                    }
                    sections.splice(sectionIndex, 1);
                    selectedSeat = null;
                    render();
                    updateSeatDetails();
                }),
            );
            top.append(fields, actions);
            const mapHost = document.createElement('div');
            mapHost.className = 'seat-map-wrap mb-3';
            mapHost.dataset.sectionMap = String(sectionIndex);
            card.append(top, mapHost);

            section.rows.forEach((row, rowIndex) => {
                const rowPanel = document.createElement('div');
                rowPanel.className = 'seat-row-controls border-top pt-3 mt-3';
                const rowHeader = document.createElement('div');
                rowHeader.className = 'd-flex flex-column flex-sm-row gap-2 align-items-sm-end';
                const rowLabel = makeInput('Row label', row.label);
                rowLabel.input.maxLength = 40;
                rowLabel.input.addEventListener('input', () => { row.label = rowLabel.input.value; });
                const rowLabelWrap = document.createElement('div');
                rowLabelWrap.className = 'flex-grow-1';
                rowLabelWrap.append(rowLabel.wrapper);
                const rowActions = document.createElement('div');
                rowActions.className = 'd-flex gap-2';
                rowActions.append(
                    makeButton('Add seat', 'btn btn-outline-primary btn-sm', () => {
                        const seat = makeSeat(row, rowIndex, row.seats.length);
                        row.seats.push(seat);
                        selectedSeat = { seat, sectionIndex };
                        render();
                        updateSeatDetails();
                    }),
                    makeButton('Remove row', 'btn btn-outline-danger btn-sm', () => {
                        if (row.seats.some((seat) => ['locked', 'reserved', 'sold'].includes(seat.status))) {
                            status.textContent = 'A row with locked, reserved, or sold seats cannot be removed.';
                            return;
                        }
                        row.seats.forEach((seat) => {
                            if (selectedSeat?.seat === seat) {
                                selectedSeat = null;
                            }
                        });
                        section.rows.splice(rowIndex, 1);
                        render();
                        updateSeatDetails();
                    }),
                );
                rowHeader.append(rowLabelWrap, rowActions);
                rowPanel.append(rowHeader);
                card.append(rowPanel);
            });

            sectionsHost.append(card);
            renderSectionMap(sectionIndex);
        });

        const editor = document.createElement('section');
        editor.className = 'panel';
        const heading = document.createElement('h2');
        heading.className = 'h5';
        heading.textContent = 'Selected seat';
        const details = document.createElement('div');
        details.className = 'seat-details-grid';
        details.dataset.seatDetails = '';
        editor.append(heading, details);
        sectionsHost.append(editor);
        updateSeatDetails();
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
                    sections,
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
