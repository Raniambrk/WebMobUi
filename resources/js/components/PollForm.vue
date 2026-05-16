<script setup>
  import { ref, computed } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';

  // Ce composant gère à la fois la création et la modification d'un sondage
  // Si `poll` est fourni en prop, on est en mode édition ; sinon en mode création
  const props = defineProps({
    poll: { type: Object, default: null },
  });

  const emit = defineEmits(['saved', 'cancel']);
  const { createPoll, updatePoll } = usePollStore();

  const isEditing = computed(() => !!props.poll);

  // Initialisation des champs avec les valeurs existantes si on est en édition
  const title           = ref(props.poll?.title ?? '');
  const question        = ref(props.poll?.question ?? '');
  const options         = ref(
    props.poll?.options?.length
      ? props.poll.options.map(o => ({ id: o.id, label: o.label }))
      : [{ id: null, label: '' }, { id: null, label: '' }] // minimum 2 options
  );
  const isDraft         = ref(props.poll?.is_draft ?? true);
  const allowMultiple   = ref(props.poll?.allow_multiple_choices ?? false);
  const allowVoteChange = ref(props.poll?.allow_vote_change ?? false);
  const resultsPublic   = ref(props.poll?.results_public ?? false);
  const hasDuration     = ref(!!props.poll?.duration);
  const durationDays    = ref(props.poll?.duration ? Math.floor(props.poll.duration / 86400) : 1);
  const durationHours   = ref(props.poll?.duration ? Math.floor((props.poll.duration % 86400) / 3600) : 0);

  const error   = ref('');
  const loading = ref(false);

  function addOption() {
    options.value.push({ id: null, label: '' });
  }

  function removeOption(index) {
    // On garde toujours au minimum 2 options
    if (options.value.length <= 2) return;
    options.value.splice(index, 1);
  }

  async function submit() {
    error.value = '';

    if (!question.value.trim()) {
      error.value = 'La question est obligatoire.';
      return;
    }

    // On filtre les options vides avant d'envoyer
    const filledOptions = options.value.filter(o => o.label.trim());
    if (filledOptions.length < 2) {
      error.value = 'Au moins 2 options sont requises.';
      return;
    }

    loading.value = true;

    // Calcul de la durée en secondes à partir des jours et heures
    const duration = hasDuration.value
      ? (durationDays.value * 86400) + (durationHours.value * 3600)
      : null;

    const payload = {
      title:                  title.value.trim() || null,
      question:               question.value.trim(),
      options:                filledOptions,
      is_draft:               isDraft.value,
      allow_multiple_choices: allowMultiple.value,
      allow_vote_change:      allowVoteChange.value,
      results_public:         resultsPublic.value,
      duration,
      start_now:              !isDraft.value,
    };

    try {
      if (isEditing.value) {
        await updatePoll(props.poll.id, payload);
      } else {
        await createPoll(payload);
      }
      emit('saved');
    } catch (err) {
      error.value = err?.data?.message || 'Une erreur est survenue.';
    } finally {
      loading.value = false;
    }
  }
</script>

<template>
  <div class="border p-4 rounded max-w-2xl">
    <h2 class="text-lg font-bold mb-4">
      {{ isEditing ? 'Modifier le sondage' : 'Nouveau sondage' }}
    </h2>

    <form @submit.prevent="submit" class="space-y-4">

      <!-- Titre optionnel -->
      <div>
        <label class="block text-sm font-medium mb-1">Titre (optionnel)</label>
        <input v-model="title" type="text" placeholder="Ex: Sondage vacances"
          class="w-full border rounded px-3 py-2 text-sm" />
      </div>

      <!-- Question obligatoire -->
      <div>
        <label class="block text-sm font-medium mb-1">Question *</label>
        <input v-model="question" type="text" placeholder="Ex: Où partir en vacances ?"
          class="w-full border rounded px-3 py-2 text-sm" />
      </div>

      <!-- Options de réponse -->
      <div>
        <label class="block text-sm font-medium mb-2">Options *</label>
        <div class="space-y-2">
          <div v-for="(opt, i) in options" :key="i" class="flex gap-2">
            <input v-model="opt.label" type="text" :placeholder="`Option ${i + 1}`"
              class="flex-1 border rounded px-3 py-2 text-sm" />
            <button type="button" @click="removeOption(i)" :disabled="options.length <= 2"
              class="text-red-500 disabled:opacity-30 px-2">✕</button>
          </div>
        </div>
        <button type="button" @click="addOption" class="mt-2 text-sm text-blue-600 hover:underline">
          + Ajouter une option
        </button>
      </div>

      <!-- Paramètres du sondage -->
      <fieldset class="border rounded p-3 space-y-2">
        <legend class="text-sm font-medium px-1">Paramètres</legend>

        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="allowMultiple" />
          <span class="text-sm">Autoriser plusieurs choix</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="resultsPublic" />
          <span class="text-sm">Résultats publics (visibles sans compte)</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="allowVoteChange" />
          <span class="text-sm">Permettre de modifier son vote</span>
        </label>

        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="hasDuration" />
          <span class="text-sm">Définir une durée</span>
        </label>

        <!-- Sélection de la durée si activée -->
        <div v-if="hasDuration" class="flex gap-4 ml-6">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Jours</label>
            <input v-model.number="durationDays" type="number" min="0" max="365"
              class="w-20 border rounded px-2 py-1 text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Heures</label>
            <input v-model.number="durationHours" type="number" min="0" max="23"
              class="w-20 border rounded px-2 py-1 text-sm" />
          </div>
        </div>
      </fieldset>

      <!-- Mode brouillon ou lancement immédiat -->
      <fieldset class="border rounded p-3 space-y-2">
        <legend class="text-sm font-medium px-1">Publication</legend>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" :value="true" v-model="isDraft" />
          <span class="text-sm">Enregistrer en brouillon</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="radio" :value="false" v-model="isDraft" />
          <span class="text-sm">Lancer maintenant</span>
        </label>
      </fieldset>

      <!-- Affichage des erreurs de validation -->
      <p v-if="error" class="text-red-600 text-sm border border-red-200 bg-red-50 rounded px-3 py-2">
        {{ error }}
      </p>

      <!-- Boutons d'action -->
      <div class="flex gap-3">
        <button type="submit" :disabled="loading"
          class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 disabled:opacity-60 text-sm">
          {{ loading ? 'Enregistrement…' : (isEditing ? 'Mettre à jour' : 'Créer') }}
        </button>
        <button type="button" @click="emit('cancel')"
          class="border px-5 py-2 rounded hover:bg-gray-50 text-sm">
          Annuler
        </button>
      </div>

    </form>
  </div>
</template>