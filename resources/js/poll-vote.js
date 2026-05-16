// Entrypoint de l'app Vue pour la page de vote
// Même structure que poll-dashboard.js fourni par le prof
import './bootstrap';
import { createApp } from 'vue';
import App from './AppPollVote.vue';

// On lit les props passées depuis la vue Blade via data-props
const el = document.getElementById('app-vote');
const props = JSON.parse(el.dataset.props ?? '{}');

createApp(App, props).mount(el);