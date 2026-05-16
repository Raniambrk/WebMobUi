import { ref } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';

// État global partagé entre tous les composants qui utilisent ce store
// Le ref est déclaré en dehors de la fonction pour persister entre les appels
const polls = ref([]);

export function usePollStore() {
  const { fetchApi } = useFetchApi();

  function setPolls(data) {
    polls.value = data;
  }

  async function deletePoll(id) {
    const result = await fetchApi({ url: 'polls/' + id, method: 'DELETE' });
    if (result) {
      polls.value = polls.value.filter(p => p.id !== id);
    }
  }

  // Ajout : créer un sondage via l'API et l'ajouter en tête de liste
  async function createPoll(payload) {
    const result = await fetchApi({ url: '/polls', data: payload, method: 'POST' });
    polls.value.unshift(result);
    return result;
  }

  // Ajout : modifier un sondage existant et mettre à jour la liste locale
  async function updatePoll(id, payload) {
    const result = await fetchApi({ url: `/polls/${id}`, data: payload, method: 'PUT' });
    const idx = polls.value.findIndex(p => p.id === id);
    if (idx !== -1) polls.value[idx] = result;
    return result;
  }

  // Ajout : lancer un sondage (passer de brouillon à actif)
  async function launchPoll(id) {
    const result = await fetchApi({
      url: `/polls/${id}`,
      data: { is_draft: false, start_now: true },
      method: 'PUT',
    });
    const idx = polls.value.findIndex(p => p.id === id);
    if (idx !== -1) polls.value[idx] = result;
    return result;
  }

  return { polls, setPolls, deletePoll, createPoll, updatePoll, launchPoll };
}