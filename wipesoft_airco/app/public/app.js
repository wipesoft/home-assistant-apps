const config = window.APP_CONFIG;
const cards = new Map([...document.querySelectorAll('.climate-card')].map(card => [card.dataset.room, card]));
const connection = document.querySelector('#connection');
const message = document.querySelector('#message');
const modeLabels = { auto: 'Automatisch', cool: 'Koelen', heat: 'Verwarmen', dry: 'Drogen', fan_only: 'Ventileren' };

function showMessage(text = '', error = false) {
    message.textContent = text;
    message.classList.toggle('error', error);
}

function fillSelect(select, values, selected, labels = {}) {
    if ([...select.options].map(option => option.value).join('|') !== values.join('|')) {
        select.replaceChildren(...values.map(value => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = labels[value] || value;
            return option;
        }));
    }
    select.value = selected || '';
    select.disabled = values.length === 0;
}

function render(room, state) {
    const card = cards.get(room);
    if (!card) return;
    card.classList.remove('is-loading');
    card.classList.toggle('is-on', state.on);
    card.querySelector('[data-current]').textContent = state.current_temperature == null ? '—' : `${state.current_temperature} °C`;
    card.querySelector('[data-target]').textContent = state.target_temperature ?? '—';
    card.querySelector('[data-state]').textContent = state.on ? (modeLabels[state.state] || state.state) : 'Uit';
    fillSelect(card.querySelector('[data-mode]'), (state.hvac_modes || []).filter(mode => mode !== 'off'), state.state, modeLabels);
    fillSelect(card.querySelector('[data-fan]'), state.fan_modes || [], state.fan_mode);
}

async function request(body = null) {
    const response = await fetch(config.api, {
        method: body ? 'POST' : 'GET',
        headers: body ? { 'Content-Type': 'application/json' } : {},
        body: body ? JSON.stringify({ ...body, csrf_token: config.csrfToken }) : null,
        cache: 'no-store',
    });
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.error || 'Onbekende fout');
    return data;
}

async function refresh() {
    try {
        const data = await request();
        Object.entries(data.aircons).forEach(([room, state]) => render(room, state));
        connection.classList.remove('offline');
        connection.querySelector('b').textContent = 'Verbonden';
        showMessage();
    } catch (error) {
        connection.classList.add('offline');
        connection.querySelector('b').textContent = 'Niet bereikbaar';
        showMessage(error.message, true);
    }
}

async function act(room, action, extra = {}) {
    const card = cards.get(room);
    card.classList.add('busy');
    showMessage('Opdracht versturen…');
    try {
        const data = await request({ room, action, ...extra });
        render(room, data.aircon);
        showMessage('Opdracht uitgevoerd.');
    } catch (error) {
        showMessage(error.message, true);
    } finally {
        card.classList.remove('busy');
    }
}

cards.forEach((card, room) => {
    card.querySelector('.power').addEventListener('click', () => act(room, card.classList.contains('is-on') ? 'turn_off' : 'turn_on'));
    card.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
        const current = Number(card.querySelector('[data-target]').textContent);
        if (Number.isFinite(current)) act(room, 'set_temperature', { temperature: current + Number(button.dataset.step) });
    }));
    card.querySelector('[data-mode]').addEventListener('change', event => act(room, 'set_mode', { mode: event.target.value }));
    card.querySelector('[data-fan]').addEventListener('change', event => act(room, 'set_fan', { fan_mode: event.target.value }));
});

refresh();
setInterval(refresh, 10000);

