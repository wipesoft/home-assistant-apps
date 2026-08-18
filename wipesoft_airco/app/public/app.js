const config = window.APP_CONFIG;
const cards = new Map([...document.querySelectorAll('.climate-card')].map(card => [card.dataset.room, card]));
const states = new Map();
const connection = document.querySelector('#connection');
const message = document.querySelector('#message');
let messageTimer;

const modeLabels = { auto: 'Auto', cool: 'Koelen', heat: 'Verwarmen', dry: 'Drogen', fan_only: 'Ventileren', off: 'Uitgeschakeld' };
const modeIcons = { auto: '✦', cool: '❄', heat: '☀', dry: '◌', fan_only: '≋' };
const fanLabels = { auto: 'Auto', quiet: 'Stil', low: 'Laag', medium: 'Midden', high: 'Hoog' };
const verticalLabels = { up_down_auto: 'Op & neer', highest: 'Helemaal boven', middle: 'Schuin boven', normal: 'Midden', lowest: 'Helemaal onder', '3d_auto': '3D automatisch' };
const horizontalLabels = { left_right_auto: 'Links & rechts', left_left: 'Links', left_center: 'Links-midden', center_center: 'Midden', center_right: 'Rechts-midden', right_right: 'Rechts', left_right: 'Breed naar buiten', right_left: 'Naar binnen', '3d_auto': '3D automatisch' };
const verticalAngles = { highest: -18, middle: -6, normal: 8, lowest: 22, up_down_auto: 10, '3d_auto': 12 };
const horizontalOffsets = { left_left: -22, left_center: -12, center_center: 0, center_right: 12, right_right: 22, left_right: 0, right_left: 0, left_right_auto: 0, '3d_auto': 0 };

function showMessage(text = '', error = false) {
    clearTimeout(messageTimer);
    message.textContent = text;
    message.className = `toast${text ? ' visible' : ''}${error ? ' error' : ''}`;
    if (text) messageTimer = setTimeout(() => message.classList.remove('visible'), 2800);
}

function optionList(select, values, selected, labels) {
    const signature = values.join('|');
    if (select.dataset.values !== signature) {
        select.replaceChildren(...values.map(value => {
            const option = document.createElement('option'); option.value = value; option.textContent = labels[value] || value.replaceAll('_', ' '); return option;
        }));
        select.dataset.values = signature;
    }
    select.value = selected || '';
    select.disabled = values.length === 0;
}

function buttonPicker(container, values, selected, labels, icons = {}) {
    const signature = values.join('|');
    if (container.dataset.values !== signature) {
        container.replaceChildren(...values.map(value => {
            const button = document.createElement('button'); button.type = 'button'; button.dataset.value = value;
            button.innerHTML = icons[value] ? `<i>${icons[value]}</i><span>${labels[value] || value}</span>` : `<span>${labels[value] || value}</span>`; return button;
        }));
        container.dataset.values = signature;
    }
    [...container.children].forEach(button => button.classList.toggle('selected', button.dataset.value === selected));
}

function render(room, state) {
    const card = cards.get(room); if (!card) return;
    states.set(room, state); card.classList.remove('is-loading'); card.classList.toggle('is-on', state.on); card.dataset.mode = state.on ? state.state : 'off';
    card.querySelector('[data-current]').textContent = state.current_temperature == null ? '—' : `${state.current_temperature}°`;
    card.querySelector('[data-target]').textContent = state.target_temperature ?? '—';
    card.querySelector('[data-state]').textContent = state.on ? (state.action_label || modeLabels[state.state] || state.state) : 'Airco staat uit';
    card.querySelector('[data-mode-label]').textContent = modeLabels[state.state] || state.state;
    card.querySelector('[data-fan-label]').textContent = fanLabels[state.fan_mode] || state.fan_mode || '—';
    const power = card.querySelector('.power');
    power.querySelector('span').textContent = state.on ? 'Aan' : 'Uit';
    power.title = state.on ? 'Tik om uit te schakelen' : 'Tik om in te schakelen';
    buttonPicker(card.querySelector('[data-mode-picker]'), (state.hvac_modes || []).filter(mode => mode !== 'off'), state.state, modeLabels, modeIcons);
    buttonPicker(card.querySelector('[data-fan-picker]'), state.fan_modes || [], state.fan_mode, fanLabels);
    optionList(card.querySelector('[data-swing-vertical]'), state.swing_modes || [], state.swing_mode, verticalLabels);
    optionList(card.querySelector('[data-swing-horizontal]'), state.swing_horizontal_modes || [], state.swing_horizontal_mode, horizontalLabels);
    const visual = card.querySelector('.airflow-visual');
    visual.style.setProperty('--air-angle', `${verticalAngles[state.swing_mode] ?? 8}deg`); visual.style.setProperty('--air-shift', `${horizontalOffsets[state.swing_horizontal_mode] ?? 0}px`);
    const autoSwing = ['up_down_auto', '3d_auto'].includes(state.swing_mode) || ['left_right_auto', '3d_auto'].includes(state.swing_horizontal_mode);
    visual.classList.toggle('is-swinging', autoSwing);
    card.querySelector('[data-airflow-caption]').textContent = state.swing_mode === '3d_auto' || state.swing_horizontal_mode === '3d_auto' ? '3D comfort' : autoSwing ? 'Automatisch bewegen' : 'Gerichte luchtstroom';
    const min = Number(state.min_temperature ?? 16), max = Number(state.max_temperature ?? 30), target = Number(state.target_temperature ?? min);
    const progress = Math.max(0, Math.min(1, (target - min) / Math.max(1, max - min)));
    card.querySelector('[data-thermostat]').style.setProperty('--temp-progress', `${Math.round(progress * 270)}deg`);
}

async function request(body = null) {
    const response = await fetch(config.api, { method: body ? 'POST' : 'GET', headers: body ? { 'Content-Type': 'application/json' } : {}, body: body ? JSON.stringify({ ...body, csrf_token: config.csrfToken }) : null, cache: 'no-store' });
    const data = await response.json(); if (!response.ok || !data.ok) throw new Error(data.error || 'Onbekende fout'); return data;
}

async function refresh({ quiet = false } = {}) {
    try { const data = await request(); Object.entries(data.aircons).forEach(([room, state]) => render(room, state)); connection.classList.remove('offline'); connection.querySelector('b').textContent = 'Live'; if (!quiet) showMessage(); }
    catch (error) { connection.classList.add('offline'); connection.querySelector('b').textContent = 'Offline'; if (!quiet) showMessage(error.message, true); }
}

function optimisticState(state, action, extra) {
    const next = { ...state };
    switch (action) {
        case 'turn_on':
            next.on = true;
            next.state = state.state === 'off' ? 'auto' : state.state;
            next.action_label = 'Wordt ingeschakeld…';
            break;
        case 'turn_off':
            next.on = false;
            next.state = 'off';
            break;
        case 'set_temperature':
            next.target_temperature = extra.temperature;
            break;
        case 'set_mode':
            next.on = true;
            next.state = extra.mode;
            next.action_label = 'Stand wordt aangepast…';
            break;
        case 'set_fan':
            next.fan_mode = extra.fan_mode;
            break;
        case 'set_swing_vertical':
            next.swing_mode = extra.swing_mode;
            break;
        case 'set_swing_horizontal':
            next.swing_horizontal_mode = extra.swing_mode;
            break;
    }
    return next;
}

async function act(room, action, extra = {}) {
    const card = cards.get(room);
    const previous = states.get(room);
    if (!previous || card.classList.contains('busy')) return;
    const optimistic = optimisticState(previous, action, extra);
    render(room, optimistic);
    card.classList.add('busy');
    showMessage('Instelling wordt toegepast…');
    try {
        const data = await request({ room, action, ...extra });
        render(room, optimisticState(data.aircon, action, extra));
        showMessage('Comfortinstelling bijgewerkt');
        setTimeout(() => refresh({ quiet: true }), 1400);
    } catch (error) {
        render(room, previous);
        showMessage(error.message, true);
        await refresh({ quiet: true });
    } finally {
        card.classList.remove('busy');
    }
}

cards.forEach((card, room) => {
    card.querySelector('.power').addEventListener('click', () => act(room, states.get(room)?.on ? 'turn_off' : 'turn_on'));
    card.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
        const state = states.get(room), current = Number(state?.target_temperature); if (!Number.isFinite(current)) return; const target = current + Number(button.dataset.step);
        if (target >= Number(state.min_temperature ?? 16) && target <= Number(state.max_temperature ?? 30)) act(room, 'set_temperature', { temperature: target });
    }));
    card.querySelector('[data-mode-picker]').addEventListener('click', event => { const button = event.target.closest('button[data-value]'); if (button) act(room, 'set_mode', { mode: button.dataset.value }); });
    card.querySelector('[data-fan-picker]').addEventListener('click', event => { const button = event.target.closest('button[data-value]'); if (button) act(room, 'set_fan', { fan_mode: button.dataset.value }); });
    card.querySelector('[data-swing-vertical]').addEventListener('change', event => act(room, 'set_swing_vertical', { swing_mode: event.target.value }));
    card.querySelector('[data-swing-horizontal]').addEventListener('change', event => act(room, 'set_swing_horizontal', { swing_mode: event.target.value }));
});

refresh(); setInterval(() => refresh({ quiet: true }), 10000);
