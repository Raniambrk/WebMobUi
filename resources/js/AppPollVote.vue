<script setup>
  import { ref, computed, onMounted } from 'vue';
  import { useFetchApi } from '@/composables/useFetchApi';
  import { usePolling } from '@/composables/usePolling';
  import PollResults from './components/PollResults.vue';

  // Props transmises depuis la vue Blade (token dans l'URL, état de connexion)
  const props = defineProps({
    token:           { type: String, required: true },
    loginUrl:        { type: String, default: '/auth/login' },
    isAuthenticated: { type: Boolean, default: false },
  });

  const { fetchApi } = useFetchApi();

  const poll        = ref(null);
  const results     = ref(null);
  const loading     = ref(true);
  const error       = ref('');
  const selectedIds = ref([]); // options sélectionnées par l'utilisateur
  const voteError   = ref('');
  const voteSuccess = ref(false);
  const voteLoading = ref(false);

  // Chargement du sondage via son token
  async function loadPoll() {
    try {
      poll.value = await fetchApi({ url: `/polls/${props.token}` });

      // Si l'utilisateur a déjà voté, on pré-sélectionne ses choix
      if (poll.value.user_vote_option_ids?.length) {
        selectedIds.value = [...poll.value.user_vote_option_ids];
        voteSuccess.value = true;
      }
    } catch (err) {
      error.value = err.data?.message || 'Impossible de charger le sondage.';
    } finally {
      loading.value = false;
    }
  }

  // Chargement des résultats en direct (appelé aussi par le polling)
  async function loadResults() {
    if (!poll.value?.show_results) return;
    try {
      results.value = await fetchApi({ url: `/polls/${props.token}/results` });
    } catch {
      // Silencieux : on ne bloque pas si les résultats ne sont pas accessibles
    }
  }

  // Polling automatique des résultats toutes les 5 secondes (composable du prof)
  usePolling(loadResults, 5000);

  onMounted(async () => {
    await loadPoll();
    await loadResults();
  });

  // Le sondage est expiré si la date de fin est dépassée
  const isExpired = computed(() => poll.value?.is_expired);

  // Gestion de la sélection des options
  // En mode multi-choix : toggle ; en mode choix unique : remplacement
  function toggleOption(id) {
    if (poll.value?.allow_multiple_choices) {
      if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter(x => x !== id);
      } else {
        selectedIds.value.push(id);
      }
    } else {
      selectedIds.value = [id];
    }
  }

  // Envoi du vote à l'API
  async function submitVote() {
    if (!selectedIds.value.length) {
      voteError.value = 'Veuillez sélectionner une option.';
      return;
    }

    voteError.value = '';
    voteLoading.value = true;

    try {
      await fetchApi({
        url: `/polls/${props.token}/vote`,
        data: { option_ids: selectedIds.value },
        method: 'POST',
      });
      voteSuccess.value = true;
      poll.value.user_vote_option_ids = [...selectedIds.value];
      // On recharge les résultats immédiatement après le vote
      await loadResults();
    } catch (err) {
      voteError.value = err.data?.message || 'Erreur lors du vote.';
    } finally {
      voteLoading.value = false;
    }
  }
</script>

<template>
  <div class="max-w-lg mx-auto p-4 pt-8 space-y-4">

    <div v-if="loading" class="text-center py-20 text-gray-500">Chargement…</div>

    <div v-else-if="error" class="border border-red-200 bg-red-50 rounded p-4 text-red-700 text-center">
      {{ error }}
    </div>

    <template v-else-if="poll">

      <!-- En-tête du sondage -->
      <div class="border rounded p-4">
        <div class="flex gap-2 mb-2">
          <!-- Badge d'état du sondage -->
          <span v-if="isExpired" class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">Terminé</span>
          <span v-else-if="!poll.is_draft" class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">En cours</span>
        </div>

        <h1 class="text-xl font-bold">{{ poll.title || poll.question }}</h1>
        <p v-if="poll.title" class="text-gray-600 mt-1">{{ poll.question }}</p>

        <p v-if="poll.ends_at && !isExpired" class="text-xs text-gray-400 mt-2">
          Se termine le {{ new Date(poll.ends_at).toLocaleDateString('fr-CH', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
          }) }}
        </p>

        <!-- Message clair indiquant que le sondage est terminé -->
        <p v-if="isExpired" class="text-sm text-red-500 mt-2 font-medium">
          🔒 Ce sondage est terminé. Les votes ne sont plus acceptés.
        </p>
      </div>

      <!-- Zone de vote -->
      <div class="border rounded p-4">

        <!-- Pas connecté : on invite à se connecter pour voter -->
        <div v-if="!isAuthenticated" class="text-center py-4">
          <p class="text-gray-600 mb-3">Connectez-vous pour voter.</p>
          <a :href="loginUrl" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm">
            Se connecter
          </a>
        </div>

        <!-- Sondage terminé -->
        <div v-else-if="isExpired" class="text-center py-4 text-gray-500">
          Les votes ne sont plus acceptés.
        </div>

        <!-- Formulaire de vote -->
        <div v-else>
          <p class="text-sm text-gray-500 mb-3">
            {{ poll.allow_multiple_choices ? 'Plusieurs choix possibles.' : 'Un seul choix.' }}
          </p>

          <div class="space-y-2">
            <label
              v-for="opt in poll.options" :key="opt.id"
              class="flex items-center gap-3 p-3 border rounded cursor-pointer"
              :class="selectedIds.includes(opt.id) ? 'border-blue-400 bg-blue-50' : 'border-gray-200'"
            >
              <!-- Checkbox ou radio selon le type de sondage -->
              <input
                :type="poll.allow_multiple_choices ? 'checkbox' : 'radio'"
                :checked="selectedIds.includes(opt.id)"
                @change="toggleOption(opt.id)"
                class="accent-blue-600"
              />
              <span class="text-sm">{{ opt.label }}</span>
            </label>
          </div>

          <p v-if="voteError" class="text-red-600 text-sm mt-3">{{ voteError }}</p>

          <!-- Bouton de vote (ou modifier si allow_vote_change) -->
          <button
            v-if="!voteSuccess || poll.allow_vote_change"
            @click="submitVote"
            :disabled="voteLoading || !selectedIds.length"
            class="mt-4 w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 disabled:opacity-60 text-sm"
          >
            {{ voteLoading ? 'Envoi…' : (voteSuccess ? 'Modifier mon vote' : 'Voter') }}
          </button>

          <p v-if="voteSuccess && !poll.allow_vote_change" class="text-green-600 text-sm mt-3 text-center">
            ✓ Vote enregistré.
          </p>
        </div>
      </div>

      <!-- Résultats en direct via polling -->
      <div v-if="poll.show_results && results" class="border rounded p-4">
        <h2 class="font-semibold mb-4">
          Résultats en direct
          <span class="text-xs text-gray-400 font-normal ml-2">
            ({{ results.total_votes }} vote{{ results.total_votes !== 1 ? 's' : '' }})
          </span>
        </h2>
        <PollResults :results="results" />
      </div>

      <!-- Message si les résultats ne sont pas publics -->
      <p v-else-if="!poll.show_results" class="text-center text-sm text-gray-400 py-4">
        Les résultats ne sont pas publics.
      </p>

    </template>
  </div>
</template>